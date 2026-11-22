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
            return $call ? $this->gmu($call) : view('seed.dashboard-no-call', ['user' => $user]);
        }
        if ($call && ApprovalsController::levelFor($user)) {
            return redirect()->route('seed.approvals.index');
        }

        $application = $call ? SeedApplication::with(['rounds.reviews.assignment', 'budgetItems', 'call'])
            ->where('seed_call_id', $call->id)->where('pi_id', $user->id)->first() : null;

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

    private function gmu(SeedCall $call)
    {
        $applications = SeedApplication::with(['pi', 'budgetItems', 'assignments.reviewer', 'rounds.reviews'])
            ->where('seed_call_id', $call->id)->get();
        $submitted = $applications->where('status', '!=', S::DRAFT);

        // Counts for each step of the track, drafts aside.
        $pipeline = collect(S::TRACK)->map(fn ($step) => [
            'label' => $step[0],
            'count' => $submitted->whereIn('status', $step[2])->count(),
        ]);

        $needs = collect();
        $overdue = 0;
        $load = [];
        foreach ($submitted as $a) {
            $why = match ($a->status) {
                S::SUBMITTED          => ['Screen', ''],
                S::SCREENED, S::PROPOSAL_SUBMITTED => [$a->activeAssignments()->count() < Workflow::MIN_REVIEWERS ? 'Assign reviewers' : 'Send to reviewers', 'info'],
                S::APPROVED           => ['Issue agreement', 'ok'],
                S::AGREEMENT_ACCEPTED => ['Upload signed copy', 'ok'],
                S::RECOMMENDED        => ['Send to DRCP', 'ok'],
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
                $needs->push(['application' => $a, 'action' => $why[0], 'tone' => $why[1]]);
            }
        }

        return view('seed.dashboard-gmu', [
            'call'       => $call,
            'pipeline'   => $pipeline,
            'needs'      => $needs->sortBy(fn ($n) => $n['application']->updated_at)->values(),
            'requested'  => $submitted->sum(fn ($a) => $a->requestedAmount()),
            'submittedCount' => $submitted->count(),
            'overdue'    => $overdue,
            'withApplicants' => $submitted->whereIn('status', [S::CONCEPT_REVISION, S::PROPOSAL_REVISION, S::SHORTLISTED, S::AGREEMENT_ISSUED])->count(),
            'ineligible' => $submitted->where('status', S::INELIGIBLE)->count(),
            'drafts'     => $applications->where('status', S::DRAFT)->count(),
            'load'       => collect($load)->sortByDesc('total')->values(),
            'byCentre'   => $submitted->groupBy(fn ($a) => $a->pi->research_center ?: 'Not recorded')->map->count()->sortDesc(),
        ]);
    }
}
