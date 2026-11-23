<?php

namespace App\Http\Controllers\SeedGrant;

use App\Http\Controllers\Controller;
use App\Models\Seed\SeedApplication;
use App\Models\Seed\SeedCall;
use App\Models\Seed\SeedReviewerAssignment;
use App\Models\User;
use App\Services\SeedGrant\Workflow;
use App\Support\SeedGrant as S;

/** The seed grant dashboard each role lands on. */
class DashboardController extends Controller
{
    public function __construct(private Workflow $workflow) {}

    public function render(User $user)
    {
        $call = SeedCall::current();

        if ($user->can('manage-seed-grants')) {
            return SeedCall::exists() ? $this->gmu() : view('seed.dashboard-no-call', ['user' => $user]);
        }
        if ($call && ApprovalsController::levelFor($user)) {
            return redirect()->route('seed.approvals.index');
        }

        // An applicant's dashboard (the owner's design): all their applications, what needs
        // them first; or, before they apply, the calls open for concept notes.
        $applications = SeedApplication::with(['rounds.reviews.assignment', 'budgetItems', 'call'])
            ->where('pi_id', $user->id)->latest('id')->get();

        $reviewing = SeedReviewerAssignment::where('reviewer_id', $user->id)->whereNull('removed_at')->exists();
        if ($applications->isEmpty() && $reviewing) {
            return redirect()->route('seed.reviews.index');
        }

        $calls = SeedCall::where('status', '!=', 'draft')->orderBy('concept_deadline')->get();

        return view('seed.dashboard-applicant', [
            'user'         => $user,
            'applications' => $applications,
            'openCalls'    => $calls->filter->acceptingConceptNotes()->values(),
            'closedCalls'  => $calls->reject->acceptingConceptNotes()->sortByDesc('id')->values(),
            'reviewing'    => $reviewing,
        ]);
    }

    /**
     * GMU's dashboard across every call, kept simple: four plain figures, what
     * needs action, money by call, and which reviewers still owe reviews.
     */
    private function gmu()
    {
        $applications = SeedApplication::with(['pi', 'budgetItems', 'assignments.reviewer', 'rounds.reviews', 'call'])->get();
        $submitted = $applications->where('status', '!=', S::DRAFT);

        $needs = collect();
        $overdue = 0;
        $load = [];
        foreach ($submitted as $a) {
            $why = \App\Support\GmuNext::step($a, $this->workflow);

            $round = $a->openRound();
            if ($round) {
                foreach ($a->activeAssignments() as $as) {
                    $done = $round->reviews->where('seed_reviewer_assignment_id', $as->id)->whereNotNull('submitted_at')->isNotEmpty();
                    $load[$as->reviewer_id] ??= ['name' => $as->reviewer->name, 'done' => 0, 'total' => 0, 'late' => 0];
                    $load[$as->reviewer_id]['total']++;
                    $load[$as->reviewer_id]['done'] += $done ? 1 : 0;
                    if (! $done && $round->isOverdue()) {
                        $load[$as->reviewer_id]['late']++;
                        $overdue++;
                    }
                }
            }
            if ($why) {
                $needs->push(['application' => $a, 'action' => $why[0], 'tone' => $why[1], 'toRanking' => $a->status === S::RECOMMENDED]);
            }
        }

        // A card for every call still running: drafts, open calls, and closed calls with work in progress.
        $finished = array_merge(S::CLOSED_STATUSES, [S::AWARDED]);
        $cards = SeedCall::latest('id')->get()
            ->filter(fn (SeedCall $c) => $c->status !== 'closed'
                || $submitted->where('seed_call_id', $c->id)->whereNotIn('status', $finished)->isNotEmpty())
            ->map(function (SeedCall $c) use ($applications, $submitted, $needs) {
                $mine = $submitted->where('seed_call_id', $c->id);

                return [
                    'call'      => $c,
                    'stages'    => collect(S::TRACK)->map(fn ($step) => ['label' => $step[0], 'count' => $mine->whereIn('status', $step[2])->count()]),
                    'submitted' => $mine->count(),
                    'drafts'    => $applications->where('seed_call_id', $c->id)->where('status', S::DRAFT)->count(),
                    'needs'     => $needs->filter(fn ($n) => $n['application']->seed_call_id === $c->id)->count(),
                    'requested' => $mine->sum(fn ($a) => $a->requestedAmount()),
                    'approved'  => $mine->whereIn('status', [S::APPROVED, S::AGREEMENT_ISSUED, S::AGREEMENT_ACCEPTED, S::AWARDED])->sum('approved_amount'),
                ];
            })
            // Open calls first, then drafts, then closed calls still finishing; newest first within each.
            ->sortBy(fn ($card) => [['open' => 0, 'draft' => 1, 'closed' => 2][$card['call']->status] ?? 3, -$card['call']->id])
            ->values();

        // ── Key figures: only what GMU needs to act on and where money stands ──
        $running = $cards->pluck('call');

        $openRounds = $submitted->map->openRound()->filter();
        $pendingReviews = $openRounds->sum(fn ($r) => $r->application->activeAssignments($r->stage)->count()
            - $r->reviews->whereNotNull('submitted_at')->count());

        // The next concept-note deadline still to come; a date means the end of that day.
        $nextDeadline = $running->filter(fn (SeedCall $c) => $c->status === 'open' && $c->concept_deadline
                && ! $c->concept_deadline->copy()->endOfDay()->isPast())
            ->sortBy(fn (SeedCall $c) => $c->concept_deadline)->first();

        $kpi = [
            'needs'     => $needs->count(),
            'screen'    => $needs->where('action', 'Screen')->count(),
            'approved'  => $submitted->whereIn('status', S::APPROVED_STATUSES)->sum('approved_amount'),
            'available' => (int) $running->sum('envelope'),
            'pending'   => max(0, $pendingReviews),
            'nextDue'   => $openRounds->filter(fn ($r) => ! $this->workflow->roundComplete($r))->min('due_at'),
            'late'      => $overdue,
        ];

        return view('seed.dashboard-gmu', [
            'kpi'            => $kpi,
            'cards'          => $cards,
            'callCount'      => $cards->count(),
            'nextDeadline'   => $nextDeadline,
            // Oldest waiting first, across every call.
            'needs'          => $needs->sortBy(fn ($n) => $n['application']->updated_at)->values(),
            'submittedCount' => $submitted->count(),
            'load'           => collect($load)->sortByDesc('total')->values(),
        ]);
    }
}
