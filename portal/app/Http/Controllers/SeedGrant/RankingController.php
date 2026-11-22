<?php

namespace App\Http\Controllers\SeedGrant;

use App\Http\Controllers\Controller;
use App\Models\Seed\SeedApplication;
use App\Models\Seed\SeedCall;
use App\Services\SeedGrant\Workflow;
use App\Support\SeedGrant;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Full proposals ranked by the reviewers' mean score, ties broken on science,
 * then feasibility, then relevance, with the funding threshold drawn across.
 */
class RankingController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage-seed-grants') || $request->user()->can('approve-seed-grants'), 403);

        $manage = $request->user()->can('manage-seed-grants');
        $call = \App\Support\CallPicker::pick($request, [SeedGrant::RECOMMENDED, SeedGrant::PROPOSAL_REVIEW], $manage);
        abort_unless($call, 404);

        $ranked = self::rank(SeedApplication::with(['pi', 'budgetItems', 'rounds.reviews'])
            ->where('seed_call_id', $call->id)
            ->whereNotIn('status', SeedGrant::CONCEPT_STATUSES)
            ->get());

        $recommended = $ranked->where('application.status', '!=', SeedGrant::NOT_RECOMMENDED)
            ->filter(fn ($row) => $row['passes']);

        return view('seed.ranking', [
            'call'        => $call,
            'ranked'      => $ranked,
            'recommendedTotal' => $recommended->sum(fn ($r) => $r['application']->budgetTotal('proposal')),
            'recommendedCount' => $recommended->count(),
            'awaitingCount'    => $ranked->where('application.status', SeedGrant::RECOMMENDED)->count(),
            'calls'            => \App\Support\CallPicker::options($manage),
            'nextAfterGmu'     => $call->form()->nextApprovalLevel('gmu'),
        ]);
    }

    /** GMU signs off every recommended proposal ticked, sending them to the DRCP. */
    public function send(Request $request, Workflow $workflow)
    {
        abort_unless($request->user()->can('manage-seed-grants'), 403);

        $data = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer']], ['ids.required' => 'Tick at least one recommended proposal.']);

        $sent = 0;
        $next = null;
        SeedApplication::with('call')->whereIn('id', $data['ids'])->where('status', SeedGrant::RECOMMENDED)->get()
            ->each(function (SeedApplication $application) use ($workflow, $request, &$sent, &$next) {
                $workflow->approve($application, $request->user(), 'gmu', 'approved', null);
                $next = $application->call->form()->nextApprovalLevel('gmu');
                $sent++;
            });

        $to = match ($next) { 'drcp' => 'sent to the DRCP', 'dg' => 'sent to the Director General', default => 'approved' };

        return back()->with('success', $sent . ' ' . str('proposal')->plural($sent) . ' ' . $to . '.');
    }

    /** @return Collection<int, array{application:SeedApplication, means:?array, passes:bool}> */
    public static function rank(Collection $applications): Collection
    {
        return $applications
            ->map(fn (SeedApplication $a) => ['application' => $a, 'means' => $m = $a->meanScores(),
                'passes' => $m !== null && $a->call->form()->meetsThreshold($m)])
            ->sort(function ($x, $y) {
                if (($x['means'] === null) !== ($y['means'] === null)) {
                    return $x['means'] === null ? 1 : -1;
                }
                foreach (array_merge(['total'], $x['application']->call->form()->tieBreak()) as $key) {
                    $cmp = ($y['means'][$key] ?? 0) <=> ($x['means'][$key] ?? 0);
                    if ($cmp !== 0) {
                        return $cmp;
                    }
                }

                return 0;
            })
            ->values();
    }
}
