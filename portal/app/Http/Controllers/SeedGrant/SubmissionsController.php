<?php

namespace App\Http\Controllers\SeedGrant;

use App\Http\Controllers\Controller;
use App\Models\Seed\SeedApplication;
use App\Models\Seed\SeedCall;
use App\Models\Seed\SeedReviewerAssignment;
use App\Models\User;
use App\Services\SeedGrant\Workflow;
use App\Support\GrantFiles;
use App\Support\SeedGrant;
use App\Support\UploadRules;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;

/** GMU's side: screening, reviewers, round decisions and the agreement. */
class SubmissionsController extends Controller implements HasMiddleware
{
    /** Filters on the submissions list: label => statuses. */
    public const FILTERS = [
        'action'     => ['Needs GMU', [SeedGrant::SUBMITTED, SeedGrant::SCREENED, SeedGrant::PROPOSAL_SUBMITTED, SeedGrant::APPROVED, SeedGrant::AGREEMENT_ACCEPTED]],
        'review'     => ['With reviewers', [SeedGrant::CONCEPT_REVIEW, SeedGrant::PROPOSAL_REVIEW]],
        'applicants' => ['With applicants', [SeedGrant::DRAFT, SeedGrant::CONCEPT_REVISION, SeedGrant::SHORTLISTED, SeedGrant::PROPOSAL_REVISION, SeedGrant::AGREEMENT_ISSUED]],
        'approval'   => ['In approval', [SeedGrant::RECOMMENDED, SeedGrant::WITH_DRCP, SeedGrant::WITH_DG]],
        'awarded'    => ['Awarded', [SeedGrant::AWARDED]],
        'closed'     => ['Closed', SeedGrant::CLOSED_STATUSES],
    ];

    public function __construct(private Workflow $workflow) {}

    public static function middleware(): array
    {
        return [new Middleware('can:manage-seed-grants')];
    }

    public function index(Request $request)
    {
        $call = \App\Support\CallPicker::pick($request, self::FILTERS['action'][1]);
        if (! $call) {
            return redirect()->route('seed.calls.index');
        }

        $filter = $request->query('filter');
        $search = trim((string) $request->query('q', ''));

        $query = SeedApplication::with(['pi', 'budgetItems', 'rounds.reviews', 'assignments'])
            ->where('seed_call_id', $call->id);

        if ($filter && isset(self::FILTERS[$filter])) {
            $query->whereIn('status', self::FILTERS[$filter][1]);
        } else {
            // Drafts are the applicant's business until submitted.
            $query->where('status', '!=', SeedGrant::DRAFT);
        }
        if ($search !== '') {
            $query->where(fn ($q) => $q->where('reference', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%")
                ->orWhereHas('pi', fn ($p) => $p->where('name', 'like', "%{$search}%")));
        }

        $counts = SeedApplication::where('seed_call_id', $call->id)->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return view('seed.submissions-index', [
            'call'         => $call,
            'applications' => $query->latest('updated_at')->paginate(25)->withQueryString(),
            'filter'       => $filter,
            'search'       => $search,
            'counts'       => $counts,
            'calls'        => \App\Support\CallPicker::options(),
        ]);
    }

    public function show(Request $request, SeedApplication $application)
    {
        $application->load(['call', 'pi', 'budgetItems', 'documents', 'screener', 'approvals.decider',
            'assignments.reviewer', 'rounds.reviews.assignment.reviewer', 'rounds.decider']);

        $stage = $application->stage();
        $taken = $application->stageAssignments($stage)->whereNull('removed_at')->pluck('reviewer_id')
            ->merge($this->workflow->excludedReviewerIds($application));

        return view('seed.submissions-show', [
            'application' => $application,
            'stage'       => $stage,
            'tab'         => in_array($request->query('tab'), ['concept', 'proposal', 'history'], true) ? $request->query('tab') : 'reviews',
            'candidates'  => User::where('is_active', true)->whereNotIn('id', $taken)->orderBy('name')->get(['id', 'name', 'research_center']),
            'eligibility' => $this->workflow->eligibilityProblems($application->pi),
            'complete'    => $this->workflow->completenessProblems($application, 'concept'),
            'roundDone'   => ($r = $application->openRound()) ? $this->workflow->roundComplete($r) : false,
        ]);
    }

    public function screen(Request $request, SeedApplication $application)
    {
        $data = $request->validate([
            'checks'   => ['array'],
            'checks.*' => ['boolean'],
            'result'   => ['required', Rule::in(['eligible', 'return', 'ineligible'])],
            'note'     => ['nullable', 'string', 'max:2000'],
        ]);

        $this->workflow->screen($application, $request->user(), $data['checks'] ?? [], $data['result'], $data['note'] ?? null);

        return back()->with('success', match ($data['result']) {
            'eligible' => 'Marked eligible. Assign reviewers next.',
            'return'   => 'Returned to the applicant for correction. They have been told what to fix.',
            default    => 'Marked not eligible. The applicant has been told why.',
        });
    }

    public function assign(Request $request, SeedApplication $application)
    {
        $data = $request->validate(['reviewer_id' => ['required', 'exists:users,id']]);

        $assignment = $this->workflow->assignReviewer($application, User::findOrFail($data['reviewer_id']), $request->user());

        return back()->with('success', $assignment->reviewer->name . ' added as Reviewer ' . $assignment->label . '. They must declare any conflict before reviewing.');
    }

    public function unassign(Request $request, SeedApplication $application, SeedReviewerAssignment $assignment)
    {
        $this->workflow->removeReviewer($assignment);

        return back()->with('success', 'Reviewer removed.');
    }

    public function startReview(Request $request, SeedApplication $application)
    {
        $data = $request->validate(['due_at' => ['nullable', 'date']]);

        $round = $this->workflow->startReview($application, $data['due_at'] ?? null);

        return back()->with('success', 'Sent to reviewers. Reviews are due ' . $round->due_at->format('j M Y') . '.');
    }

    public function decide(Request $request, SeedApplication $application)
    {
        $data = $request->validate([
            'decision'        => ['required', 'string'],
            'decision_note'   => ['nullable', 'string', 'max:5000'],
            'response_due_at' => ['nullable', 'date', 'after:today'],
        ]);

        $this->workflow->decide($application, $request->user(), $data['decision'], $data['decision_note'] ?? null, $data['response_due_at'] ?? null);

        return back()->with('success', 'Decision recorded: ' . $application->fresh()->statusLabel() . '. The applicant has been told.');
    }

    public function issueAgreement(Request $request, SeedApplication $application)
    {
        $this->workflow->issueAgreement($application);

        return redirect()->route('seed.applications.agreement', $application)->with('success', 'Agreement issued. The PI has been asked to accept it.');
    }

    public function uploadSignedAgreement(Request $request, SeedApplication $application)
    {
        $request->validate(['file' => UploadRules::for(['pdf'], UploadRules::MAX_STANDARD, true)]);
        $file = $request->file('file');

        $path = GrantFiles::replace($file, 'seed-grant/' . $application->reference, $application->signed_agreement_path);
        $this->workflow->recordSignedAgreement($application, $path, $file->getClientOriginalName());

        return back()->with('success', 'Signed agreement uploaded. The grant is awarded.');
    }
}
