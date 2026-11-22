<?php

namespace App\Http\Controllers\SeedGrant;

use App\Http\Controllers\Controller;
use App\Models\Seed\SeedApplication;
use App\Models\Seed\SeedCall;
use App\Services\SeedGrant\Workflow;
use App\Support\CallForm;
use App\Support\SeedGrant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Calls: GMU creates as many as NIMR runs, each with its own switched-on
 * features and settings (App\Support\CallForm). Staff see the calls that are
 * open or closed; drafts stay with GMU.
 */
class CallController extends Controller
{
    // ── Lists and pages ─────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $manage = $request->user()->can('manage-seed-grants');

        $all = SeedCall::withCount('applications')
            ->when(! $manage, fn ($q) => $q->where('status', '!=', 'draft'))
            ->orderByRaw("case status when 'open' then 0 when 'draft' then 1 else 2 end")
            ->latest('id')
            ->get();

        $show = in_array($request->query('show'), ['open', 'draft', 'closed'], true) ? $request->query('show') : 'all';
        $calls = $show === 'all' ? $all : $all->where('status', $show)->values();
        $counts = $all->countBy('status');

        $mine = SeedApplication::where('pi_id', $request->user()->id)->pluck('id', 'seed_call_id');

        // For GMU: where each call's applications are, and the money approved.
        $stats = [];
        if ($manage) {
            $submitted = SeedApplication::whereIn('seed_call_id', $calls->pluck('id'))
                ->where('status', '!=', \App\Support\SeedGrant::DRAFT)
                ->get(['id', 'seed_call_id', 'status', 'approved_amount'])
                ->groupBy('seed_call_id');
            foreach ($calls as $call) {
                $mineOf = $submitted->get($call->id, collect());
                $stats[$call->id] = [
                    'submitted' => $mineOf->count(),
                    'stages'    => collect(\App\Support\SeedGrant::STAGE_GROUPS)
                        ->map(fn ($g) => $mineOf->whereIn('status', $g[1])->count())->all(),
                    'approved'  => $mineOf->whereIn('status', \App\Support\SeedGrant::APPROVED_STATUSES)->sum('approved_amount'),
                ];
            }
        }

        return view('seed.calls-index', compact('calls', 'manage', 'mine', 'show', 'counts', 'stats') + ['total' => $all->count()]);
    }

    /** The old single "The call" address: the default call's page, or setup for GMU when there is none. */
    public function current(Request $request)
    {
        $call = SeedCall::current();
        if (! $call) {
            abort_unless($request->user()->can('manage-seed-grants'), 404);

            return redirect()->route('seed.calls.create');
        }

        return redirect()->route('seed.calls.show', $call);
    }

    public function show(Request $request, SeedCall $call, Workflow $workflow)
    {
        abort_if(! $call->isVisible() && $request->user()->cannot('manage-seed-grants'), 404);

        $user = $request->user();

        return view('seed.call', [
            'call'        => $call,
            'application' => SeedApplication::where('seed_call_id', $call->id)->where('pi_id', $user->id)->first(),
            'problems'    => $workflow->eligibilityProblems($user, $call),
            'summary'     => $user->can('manage-seed-grants') ? $this->summary($call) : null,
        ]);
    }

    /** GMU's figures for one call: where applications are, and the money. */
    private function summary(SeedCall $call): array
    {
        $applications = SeedApplication::with('budgetItems')->where('seed_call_id', $call->id)->get();
        $submitted = $applications->where('status', '!=', SeedGrant::DRAFT);

        return [
            'stages'    => collect(SeedGrant::TRACK)->map(fn ($step) => ['label' => $step[0], 'count' => $submitted->whereIn('status', $step[2])->count()]),
            'drafts'    => $applications->where('status', SeedGrant::DRAFT)->count(),
            'submitted' => $submitted->count(),
            'closed'    => $submitted->whereIn('status', SeedGrant::CLOSED_STATUSES)->count(),
            'requested' => $submitted->whereNotIn('status', SeedGrant::CLOSED_STATUSES)->sum(fn ($a) => $a->requestedAmount()),
            'approved'  => $submitted->whereIn('status', SeedGrant::APPROVED_STATUSES)->sum('approved_amount'),
        ];
    }

    // ── Creating and changing a call ────────────────────────────────────────

    public function create(Request $request)
    {
        $this->authorize('manage-seed-grants');

        // A new call starts from the last one's settings, so a new round is mostly dates.
        $previous = SeedCall::latest('id')->first();
        $call = new SeedCall([
            'title'                 => $previous?->title,
            'summary'               => $previous?->summary,
            'min_amount'            => $previous?->min_amount ?? 2_000_000,
            'max_amount'            => $previous?->max_amount ?? 10_000_000,
            'duration_months'       => $previous?->duration_months ?? 12,
            'equipment_cap_percent' => $previous?->equipment_cap_percent ?? 20,
            'status'                => 'draft',
            'config'                => $previous ? $previous->form()->config() : CallForm::defaults(),
        ]);

        return view('seed.call-edit', ['call' => $call, 'form' => $call->form(), 'copiedFrom' => $previous]);
    }

    /** The old "Call settings" address. */
    public function editCurrent()
    {
        $this->authorize('manage-seed-grants');
        $call = SeedCall::current();

        return redirect()->route($call ? 'seed.calls.edit' : 'seed.calls.create', $call ? [$call] : []);
    }

    public function edit(SeedCall $call)
    {
        $this->authorize('manage-seed-grants');

        return view('seed.call-edit', ['call' => $call, 'form' => $call->form(), 'copiedFrom' => null]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-seed-grants');

        $call = new SeedCall();
        $this->save($request, $call);

        return redirect()->route('seed.calls.show', $call)->with('success', 'Call created. It stays hidden from staff until you set it to Open.');
    }

    public function update(Request $request, SeedCall $call)
    {
        $this->authorize('manage-seed-grants');

        $this->save($request, $call);

        return redirect()->route('seed.calls.show', $call)->with('success', 'Call settings saved.');
    }

    private function save(Request $request, SeedCall $call): void
    {
        $locked = $call->settingsLocked();

        $data = $request->validate([
            'title'                 => ['required', 'string', 'max:255'],
            'code'                  => ['required', 'string', 'max:12', 'alpha_num', Rule::unique('seed_calls', 'code')->ignore($call->id)],
            'fiscal_year'           => ['required', 'string', 'max:12'],
            'summary'               => ['nullable', 'string', 'max:2000'],
            'min_amount'            => ['required', 'integer', 'min:0'],
            'max_amount'            => ['required', 'integer', 'gte:min_amount'],
            'envelope'              => ['nullable', 'integer', 'min:0'],
            'duration_months'       => ['required', 'integer', 'between:1,36'],
            'equipment_cap_percent' => ['required', 'integer', 'between:0,100'],
            'concept_deadline'      => ['nullable', 'date'],
            'proposal_deadline'     => ['nullable', 'date', 'after_or_equal:concept_deadline'],
            'status'                => ['required', Rule::in(array_keys(SeedCall::STATUSES))],
            'features'              => ['nullable', 'array'],
        ]);

        $current = $call->exists ? $call->form()->config() : CallForm::defaults();
        // No feature section sent (an older form, or a script): keep the settings as they are.
        $config = $request->has('features') ? $this->configFrom((array) $data['features'], $current, $locked) : $current;

        unset($data['features']);
        if ($locked) {
            // Once open, what applicants were promised stays put: code, amounts, length and the equipment cap.
            $data = array_intersect_key($data, array_flip(['title', 'summary', 'envelope', 'concept_deadline', 'proposal_deadline', 'status']));
        }

        $call->fill($data);
        $call->config = $config;
        $call->save();
    }

    /**
     * Turn the switches and numbers from the settings form into a call's
     * config. Once a call has opened, only word limits may change, and only
     * upwards: nobody's submitted or drafted text becomes invalid.
     */
    private function configFrom(array $in, array $current, bool $locked): array
    {
        $on = fn ($v) => in_array($v, ['1', 1, true, 'on'], true);
        $int = fn ($v, $min = 0, $max = 100000) => is_numeric($v) ? max($min, min($max, (int) $v)) : null;
        $num = fn ($v, $min = 0, $max = 1000) => is_numeric($v) ? max($min, min($max, round((float) $v * 2) / 2)) : null;
        $raise = fn ($old, $new) => $locked ? max((int) $old, (int) ($new ?? $old)) : (int) ($new ?? $old);

        $c = $current;
        $errors = [];

        // Questions: on/off, wording and word limit for each stage.
        foreach (['concept', 'proposal'] as $stage) {
            foreach ($current['questions'][$stage] as $key => $q) {
                $f = $in['questions'][$stage][$key] ?? [];
                $c['questions'][$stage][$key]['limit'] = $raise($q['limit'], $int($f['limit'] ?? null, 10, 5000));
                if (! $locked) {
                    $c['questions'][$stage][$key]['on'] = $on($f['on'] ?? null);
                    $c['questions'][$stage][$key]['heading'] = trim((string) ($f['heading'] ?? '')) ?: $q['heading'];
                    $c['questions'][$stage][$key]['question'] = trim((string) ($f['question'] ?? $q['question']));
                }
            }
            if (! array_filter(array_column($c['questions'][$stage], 'on'))) {
                $errors["features.questions.{$stage}"] = 'Keep at least one question in the ' . ($stage === 'concept' ? 'concept note' : 'full proposal') . '.';
            }
        }

        // The call page's words describe the call; they stay editable after it opens.
        if (isset($in['page'])) {
            $c['page']['about'] = trim((string) ($in['page']['about'] ?? ''));
            $c['page']['who'] = trim((string) ($in['page']['who'] ?? ''));
            $c['page']['priorities_intro'] = trim((string) ($in['page']['priorities_intro'] ?? ''));
            $c['page']['priorities'] = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($in['page']['priorities'] ?? '')))));
        }

        $c['title_words'] = $raise($current['title_words'], $int($in['title_words'] ?? null, 5, 200));
        foreach (['mentor', 'co_investigators'] as $feature) {
            $c[$feature]['words'] = $raise($current[$feature]['words'], $int($in[$feature]['words'] ?? null, 10, 2000));
        }
        $c['prior_grant']['words'] = $raise($current['prior_grant']['words'], $int($in['prior_grant']['words'] ?? null, 10, 2000));

        if (! $locked) {
            foreach (['mentor', 'co_investigators', 'prior_grant', 'declarations', 'service_limit', 'equipment_cap'] as $feature) {
                $c[$feature]['on'] = $on($in[$feature]['on'] ?? null);
            }
            $c['prior_grant']['usd'] = $int($in['prior_grant']['usd'] ?? null, 0, 100_000_000) ?? $current['prior_grant']['usd'];
            $c['service_limit']['years'] = $int($in['service_limit']['years'] ?? null, 1, 60) ?? $current['service_limit']['years'];

            foreach (array_keys($current['documents']) as $kind) {
                $state = $in['documents'][$kind] ?? 'off';
                $c['documents'][$kind] = array_key_exists($state, CallForm::DOCUMENT_STATES) ? $state : 'off';
            }

            foreach ($current['criteria'] as $key => $crit) {
                $f = $in['criteria'][$key] ?? [];
                $c['criteria'][$key]['on'] = $on($f['on'] ?? null);
                $c['criteria'][$key]['max'] = $num($f['max'] ?? null, 1, 100) ?? $crit['max'];
                $min = $f['min'] ?? null;
                $c['criteria'][$key]['min'] = ($min === null || $min === '') ? null : $num($min, 0, 100);
                if ($c['criteria'][$key]['on'] && $c['criteria'][$key]['min'] !== null && $c['criteria'][$key]['min'] > $c['criteria'][$key]['max']) {
                    $errors["features.criteria.{$key}.min"] = SeedGrant::CRITERIA[$key][0] . ': the minimum cannot be above the maximum.';
                }
            }
            $c['pass_mark'] = $num($in['pass_mark'] ?? null, 0, 1000) ?? $current['pass_mark'];
            $maxTotal = array_sum(array_map(fn ($k) => $c['criteria'][$k]['on'] ? $c['criteria'][$k]['max'] : 0, array_keys($c['criteria'])));
            if ($maxTotal > 0 && $c['pass_mark'] > $maxTotal) {
                $errors['features.pass_mark'] = 'The pass mark cannot be above the total of the criteria (' . SeedGrant::trimNumber($maxTotal) . ').';
            }

            foreach (['drcp', 'dg'] as $level) {
                $c['approval'][$level] = $on($in['approval'][$level] ?? null);
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $c;
    }
}
