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

        // An applicant's dashboard follows their own latest application, in whichever call it is.
        $application = SeedApplication::with(['rounds.reviews.assignment', 'budgetItems', 'call'])
            ->where('pi_id', $user->id)->latest('id')->first();
        if ($application) {
            $call = $application->call;
        }

        $reviewing = SeedReviewerAssignment::where('reviewer_id', $user->id)->whereNull('removed_at')->exists();
        if (! $application && $reviewing) {
            return redirect()->route('seed.reviews.index');
        }

        return view('seed.dashboard-applicant', [
            'user'        => $user,
            'call'        => $call,
            'application' => $application,
            'problems'    => $this->workflow->eligibilityProblems($user),
            'reviewing'   => $reviewing,
        ]);
    }

    /**
     * GMU's dashboard across every call: what needs action anywhere first,
     * then a card per call still running, then reviewer load and centres.
     */
    private function gmu()
    {
        $applications = SeedApplication::with(['pi', 'budgetItems', 'assignments.reviewer', 'rounds.reviews', 'call'])->get();
        $submitted = $applications->where('status', '!=', S::DRAFT);

        $needs = collect();
        $overdue = 0;
        $load = [];
        foreach ($submitted as $a) {
            $next = $a->call->form()->nextApprovalLevel('gmu');
            $why = match ($a->status) {
                S::SUBMITTED          => ['Screen', ''],
                S::SCREENED, S::PROPOSAL_SUBMITTED => [$a->activeAssignments()->count() < Workflow::MIN_REVIEWERS ? 'Assign reviewers' : 'Send to reviewers', 'info'],
                S::APPROVED           => ['Issue agreement', 'ok'],
                S::AGREEMENT_ACCEPTED => ['Upload signed copy', 'ok'],
                S::RECOMMENDED        => [['drcp' => 'Send to DRCP', 'dg' => 'Send to the DG'][$next] ?? 'Approve', 'ok'],
                default               => null,
            };

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
                if ($this->workflow->roundComplete($round)) {
                    $why = ['Decide', 'warn'];
                } elseif ($round->isOverdue()) {
                    $why ??= ['Review overdue', 'danger'];
                }
            }
            if ($a->stageAssignments()->contains(fn ($as) => $as->coi_status === 'conflict' && $as->removed_at === null)
                && $a->activeAssignments()->count() < Workflow::MIN_REVIEWERS) {
                $why = ['Reassign: conflict', 'danger'];
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

        // ── Key figures ────────────────────────────────────────────────────
        $running = $cards->pluck('call');
        $approvedStatuses = [S::APPROVED, S::AGREEMENT_ISSUED, S::AGREEMENT_ACCEPTED, S::AWARDED];

        $openRounds = $submitted->map->openRound()->filter();
        $pendingReviews = $openRounds->sum(fn ($r) => $r->application->activeAssignments($r->stage)->count()
            - $r->reviews->whereNotNull('submitted_at')->count());
        $submittedReviews = $submitted->flatMap(fn ($a) => $a->rounds)->flatMap(fn ($r) => $r->reviews->whereNotNull('submitted_at')->map(fn ($rv) => [$r, $rv]));
        $turnaround = $submittedReviews->isEmpty() ? null
            : round($submittedReviews->avg(fn ($pair) => $pair[0]->created_at->diffInHours($pair[1]->submitted_at) / 24), 1);

        // Days from concept note to GMU's first decision on it.
        $decisionDays = $submitted->map(function ($a) {
            $first = $a->rounds->where('stage', 'concept')->whereNotNull('decided_at')->sortBy('decided_at')->first();

            return $first && $a->concept_submitted_at ? $a->concept_submitted_at->diffInHours($first->decided_at) / 24 : null;
        })->filter(fn ($d) => $d !== null)->sort()->values();
        $median = $decisionDays->isEmpty() ? null : round($decisionDays->get(intdiv($decisionDays->count() - 1, 2)), 1);

        $kpi = [
            'needs'        => $needs->count(),
            'needsOld'     => $needs->filter(fn ($n) => $n['application']->updated_at->lt(now()->subDays(7)))->count(),
            'screen'       => $needs->where('action', 'Screen')->count(),
            'decide'       => $needs->where('action', 'Decide')->count(),
            'approved'     => $submitted->whereIn('status', $approvedStatuses)->sum('approved_amount'),
            'available'    => (int) $running->sum('envelope'),
            'inPlay'       => $submitted->whereNotIn('status', array_merge(S::CLOSED_STATUSES, $approvedStatuses))->sum(fn ($a) => $a->requestedAmount()),
            'awarded'      => $submitted->where('status', S::AWARDED)->count(),
            'pending'      => max(0, $pendingReviews),
            'late'         => $overdue,
            'turnaround'   => $turnaround,
            'reviewsDone'  => $submittedReviews->count(),
            'decisionDays' => $median,
            'decided'      => $decisionDays->count(),
        ];

        // ── Pipeline by call: applications in each part of the process ──────
        $groups = [
            'concept'  => ['Concept note', [S::SUBMITTED, S::SCREENED, S::CONCEPT_REVIEW, S::CONCEPT_REVISION]],
            'proposal' => ['Full proposal', [S::SHORTLISTED, S::PROPOSAL_SUBMITTED, S::PROPOSAL_REVIEW, S::PROPOSAL_REVISION]],
            'approval' => ['Approval', [S::RECOMMENDED, S::WITH_DRCP, S::WITH_DG, S::APPROVED]],
            'award'    => ['Agreement',[S::AGREEMENT_ISSUED, S::AGREEMENT_ACCEPTED, S::AWARDED]],
            'closed'   => ['Closed', S::CLOSED_STATUSES],
        ];
        $pipeline = $running->map(fn (SeedCall $c) => [
            'call'     => $c,
            'segments' => collect($groups)->map(fn ($g) => $submitted->where('seed_call_id', $c->id)->whereIn('status', $g[1])->count())->all(),
        ])->map(fn ($row) => $row + ['total' => array_sum($row['segments'])])->values();
        $band = collect($groups)->map(fn ($g, $key) => ['label' => $g[0], 'count' => $submitted->whereIn('status', $g[1])->count()]);

        return view('seed.dashboard-gmu', [
            'kpi'            => $kpi,
            'pipeline'       => $pipeline,
            'groups'         => collect($groups)->map(fn ($g) => $g[0]),
            'band'           => $band,
            'cards'          => $cards,
            'callCount'      => $cards->count(),
            // Oldest waiting first, across every call.
            'needs'          => $needs->sortBy(fn ($n) => $n['application']->updated_at)->values(),
            'submittedCount' => $submitted->count(),
            'overdue'        => $overdue,
            'withApplicants' => $submitted->whereIn('status', [S::CONCEPT_REVISION, S::PROPOSAL_REVISION, S::SHORTLISTED, S::AGREEMENT_ISSUED])->count(),
            'ineligible'     => $submitted->where('status', S::INELIGIBLE)->count(),
            'load'           => collect($load)->sortByDesc('total')->values(),
            'byCentre'       => $submitted->groupBy(fn ($a) => $a->pi->research_center ?: 'Not recorded')->map->count()->sortDesc(),
        ]);
    }
}
