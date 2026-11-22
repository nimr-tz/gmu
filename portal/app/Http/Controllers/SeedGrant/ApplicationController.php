<?php

namespace App\Http\Controllers\SeedGrant;

use App\Http\Controllers\Controller;
use App\Models\Seed\SeedApplication;
use App\Models\Seed\SeedCall;
use App\Models\Seed\SeedDocument;
use App\Services\SeedGrant\Workflow;
use App\Support\GrantFiles;
use App\Support\SeedGrant;
use App\Support\UploadRules;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The applicant's side: start, write, submit, respond to reviewers, and
 * accept the grant agreement.
 */
class ApplicationController extends Controller
{
    public function __construct(private Workflow $workflow) {}

    public function start(Request $request, ?SeedCall $call = null)
    {
        $call ??= SeedCall::current();
        abort_unless($call && $call->isVisible(), 404);

        $application = $this->workflow->start($request->user(), $call);

        return redirect()->route('seed.applications.edit', $application);
    }

    /** "My applications" in the menu: straight to the one, or a list of several. */
    public function mine(Request $request)
    {
        $applications = SeedApplication::with('call')->where('pi_id', $request->user()->id)->latest('id')->get();

        if ($applications->isEmpty()) {
            return redirect()->route('seed.calls.index');
        }
        if ($applications->count() === 1) {
            $application = $applications->first();

            return redirect()->route($application->isEditableByApplicant() ? 'seed.applications.edit' : 'seed.applications.show', $application);
        }

        return view('seed.my-applications', ['applications' => $applications]);
    }

    public function show(Request $request, SeedApplication $application)
    {
        $this->authorize('view', $application);

        if ($request->user()->id !== $application->pi_id && $request->user()->can('manage-seed-grants')) {
            return redirect()->route('seed.submissions.show', $application);
        }

        $application->load(['call', 'budgetItems', 'documents', 'rounds.reviews.assignment', 'approvals']);

        return view('seed.application-show', ['application' => $application]);
    }

    public function edit(Request $request, SeedApplication $application)
    {
        $this->authorize('update', $application);
        $application->load(['call', 'budgetItems', 'documents', 'rounds.reviews.assignment', 'pi']);

        $stage = $application->stage();

        return view('seed.application-edit', [
            'application' => $application,
            'stage'       => $stage,
            'sections'    => $application->call->form()->sections($stage),
            'form'        => $application->call->form(),
            'problems'    => $this->workflow->completenessProblems($application, $stage),
            'round'       => $application->roundAwaitingResponse(),
        ]);
    }

    public function update(Request $request, SeedApplication $application)
    {
        $this->authorize('update', $application);
        $this->save($request, $application);

        return redirect()->route('seed.applications.edit', $application)->with('success', 'Saved.');
    }

    public function submit(Request $request, SeedApplication $application)
    {
        $this->authorize('update', $application);
        $this->save($request, $application);
        $application->refresh();

        if ($application->isRevising()) {
            $request->validate(['response' => ['required', 'string', 'max:10000']],
                ['response.required' => 'Tell the reviewers what you changed before sending your response.']);
            $this->workflow->resubmit($application, $request->input('response'));
            $message = 'Your response has gone back to the reviewers.';
        } elseif ($application->stage() === 'concept') {
            $this->workflow->submitConcept($application);
            $message = 'Concept note submitted. GMU will screen it for eligibility.';
        } else {
            $this->workflow->submitProposal($application);
            $message = 'Full proposal submitted.';
        }

        return redirect()->route('seed.applications.show', $application)->with('success', $message);
    }

    private function save(Request $request, SeedApplication $application): void
    {
        $stage = $application->stage();
        $sectionRules = [];
        $form = $application->call->form();
        foreach (array_keys($form->sections($stage)) as $key) {
            $sectionRules["sections.{$key}"] = ['nullable', 'string', 'max:20000'];
        }

        $choiceRules = [];
        foreach ($form->choices() as $key => $choice) {
            $choiceRules["choices.{$key}"] = ['nullable', Rule::in(array_keys($choice['options']))];
        }

        $data = $request->validate($sectionRules + $choiceRules + [
            'title'                         => ['nullable', 'string', 'max:255'],
            'mentor_name'                   => ['nullable', 'string', 'max:255'],
            'mentor_designation'            => ['nullable', 'string', 'max:255'],
            'mentor_centre'                 => ['nullable', 'string', 'max:255'],
            'mentor_suitability'            => ['nullable', 'string', 'max:3000'],
            'co_investigators'              => ['nullable', 'string', 'max:5000'],
            'prior_grant'                   => ['nullable', Rule::in(['yes', 'no'])],
            'prior_grant_details'           => ['nullable', 'string', 'max:3000'],
            'declarations'                  => ['nullable', 'array'],
            'declarations.*'                => [Rule::in(array_keys(SeedGrant::DECLARATIONS))],
            'budget'                        => ['nullable', 'array', 'max:60'],
            'budget.*.item'                 => ['nullable', 'string', 'max:255'],
            'budget.*.category'             => ['nullable', Rule::in(array_keys(SeedGrant::BUDGET_CATEGORIES))],
            'budget.*.amount'               => ['nullable', 'string', 'max:20'],
            'budget.*.q1'                   => ['nullable', 'string', 'max:20'],
            'budget.*.q2'                   => ['nullable', 'string', 'max:20'],
            'budget.*.q3'                   => ['nullable', 'string', 'max:20'],
            'budget.*.q4'                   => ['nullable', 'string', 'max:20'],
        ]);

        $sections = array_map(fn ($v) => trim((string) $v), $data['sections'] ?? []);
        $existing = ($stage === 'proposal' ? $application->proposal : $application->concept) ?? [];

        // The concept-stage fields are fixed once the concept note is shortlisted.
        if ($stage === 'concept') {
            foreach (array_keys($form->choices()) as $key) {
                $answer = $data['choices'][$key] ?? null;
                $sections[$key . '_answer'] = $answer;
                // "No" to question 7 or 8 needs no description: drop any left over.
                if ($answer !== null && ! SeedGrant::sectionRequired($key, $answer)) {
                    $sections[$key] = '';
                }
            }
            $sections['co_investigators']    = trim((string) ($data['co_investigators'] ?? ''));
            $sections['prior_grant']         = $data['prior_grant'] ?? null;
            $sections['prior_grant_details'] = ($data['prior_grant'] ?? null) === 'yes' ? trim((string) ($data['prior_grant_details'] ?? '')) : '';
            $sections['declarations']        = array_values(array_unique($data['declarations'] ?? []));

            $application->fill([
                'title'                         => $data['title'] ?? $application->title,
                'mentor_name'                   => $data['mentor_name'] ?? null,
                'mentor_designation'            => $data['mentor_designation'] ?? null,
                'mentor_centre'                 => $data['mentor_centre'] ?? null,
                'mentor_suitability'            => $data['mentor_suitability'] ?? null,
                // Kept in step for screening and reports: true only when the applicant answered "No".
                'declared_no_prior_large_grant' => ($data['prior_grant'] ?? null) === 'no',
            ]);
        }

        $application->{$stage} = array_merge($existing, $sections);
        $application->save();

        if ($request->has('budget')) {
            $this->workflow->saveBudget($application, $stage, $data['budget'] ?? []);
        }
    }

    // ── Stage 2 documents ───────────────────────────────────────────────────

    public function storeDocument(Request $request, SeedApplication $application)
    {
        $this->authorize('update', $application);
        abort_unless($application->stage() === 'proposal', 404);

        $data = $request->validate([
            'kind' => ['required', Rule::in(array_keys($application->call->form()->documents()))],
            'file' => UploadRules::for(['pdf', 'doc', 'docx'], UploadRules::MAX_STANDARD, true),
        ]);

        $file = $request->file('file');
        // One mentor letter and one CV: a new upload replaces the old one.
        if ($data['kind'] !== 'support_letter') {
            foreach ($application->documents()->where('kind', $data['kind'])->get() as $old) {
                GrantFiles::delete($old->file_path);
                $old->delete();
            }
        }

        $application->documents()->create([
            'kind'        => $data['kind'],
            'file_path'   => GrantFiles::store($file, 'seed-grant/' . $application->reference),
            'file_name'   => $file->getClientOriginalName(),
            'file_size'   => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        return redirect()->to(route('seed.applications.edit', $application) . '#documents')->with('success', 'Document uploaded.');
    }

    public function destroyDocument(Request $request, SeedApplication $application, SeedDocument $document)
    {
        $this->authorize('update', $application);

        GrantFiles::delete($document->file_path);
        $document->delete();

        return redirect()->to(route('seed.applications.edit', $application) . '#documents')->with('success', 'Document removed.');
    }

    public function downloadDocument(Request $request, SeedApplication $application, SeedDocument $document)
    {
        $user = $request->user();
        $isReviewer = $application->assignments()->where('reviewer_id', $user->id)->where('stage', 'proposal')
            ->whereNull('removed_at')->where('coi_status', 'none')->exists();

        abort_unless($user->can('view', $application) || $isReviewer, 403);

        return GrantFiles::download($document->file_path, $document->file_name);
    }

    // ── Grant agreement ─────────────────────────────────────────────────────

    public function agreement(Request $request, SeedApplication $application)
    {
        $this->authorize('viewAgreement', $application);
        $application->load(['call', 'pi', 'budgetItems', 'approvals.decider']);

        return view('seed.agreement', ['application' => $application]);
    }

    public function acceptAgreement(Request $request, SeedApplication $application)
    {
        abort_unless($application->pi_id === $request->user()->id, 403);

        $request->validate([
            'read'      => ['accepted'],
            'ethics'    => ['accepted'],
            'reporting' => ['accepted'],
        ], [
            'read.accepted'      => 'Confirm you have read the whole agreement.',
            'ethics.accepted'    => 'Confirm no data collection will start before ethics approval.',
            'reporting.accepted' => 'Confirm you will submit quarterly progress and financial reports.',
        ]);

        $this->workflow->acceptAgreement($application);

        return redirect()->route('seed.applications.agreement', $application)->with('success', 'You have accepted the grant agreement. GMU will collect the remaining signatures.');
    }

    public function signedAgreement(Request $request, SeedApplication $application)
    {
        $this->authorize('viewAgreement', $application);
        abort_unless($application->signed_agreement_path, 404);

        return GrantFiles::download($application->signed_agreement_path, $application->signed_agreement_name);
    }
}
