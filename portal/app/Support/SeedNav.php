<?php

namespace App\Support;

use App\Http\Controllers\SeedGrant\ApprovalsController;
use App\Models\Seed\SeedApplication;
use App\Models\Seed\SeedCall;
use App\Models\Seed\SeedReviewerAssignment;
use App\Models\User;

/**
 * The seed grant entries in the side panel, by role.
 * Each item: [label, url, icon, active route pattern, count or null, count is an alert].
 */
final class SeedNav
{
    public static function groups(User $user): array
    {
        $call = SeedCall::current();
        if (! $call) {
            // GMU still needs a way in before the first call exists.
            return $user->can('manage-seed-grants')
                ? ['Seed grant' => [['Set up the call', route('seed.call.edit'), 'fa-bullhorn', 'seed.call*', null, false]]]
                : [];
        }

        if ($user->can('manage-seed-grants')) {
            $needs = SeedApplication::where('seed_call_id', $call->id)
                ->whereIn('status', [SeedGrant::SUBMITTED, SeedGrant::SCREENED, SeedGrant::PROPOSAL_SUBMITTED, SeedGrant::APPROVED, SeedGrant::AGREEMENT_ACCEPTED])
                ->count();
            $recommended = SeedApplication::where('seed_call_id', $call->id)->where('status', SeedGrant::RECOMMENDED)->count();

            return ['Seed grant' => [
                ['Submissions', route('seed.submissions.index'), 'fa-file-alt', 'seed.submissions.*', $needs ?: null, false],
                ['Ranking', route('seed.ranking'), 'fa-list-ol', 'seed.ranking', null, false],
                ['Approvals', route('seed.approvals.index'), 'fa-shield-alt', 'seed.approvals.*', $recommended ?: null, false],
                ['The call', route('seed.call'), 'fa-bullhorn', 'seed.call*', null, false],
            ]] + self::reviewing($user);
        }

        if ($level = ApprovalsController::levelFor($user)) {
            $waiting = SeedApplication::where('seed_call_id', $call->id)->where('status', ApprovalsController::QUEUE_STATUS[$level])->count();

            return ['Decisions' => [
                ['Awaiting decision', route('seed.approvals.index'), 'fa-shield-alt', 'seed.approvals.*', $waiting ?: null, false],
                ['Full ranking', route('seed.ranking'), 'fa-list-ol', 'seed.ranking', null, false],
                ['The call', route('seed.call'), 'fa-bullhorn', 'seed.call', null, false],
            ]] + self::reviewing($user);
        }

        $application = SeedApplication::where('seed_call_id', $call->id)->where('pi_id', $user->id)->first();
        $items = [];
        if ($call->isVisible()) {
            $items[] = ['The call', route('seed.call'), 'fa-bullhorn', 'seed.call', null, false];
        }
        if ($application) {
            $items[] = ['My application', route('seed.mine'), 'fa-file-alt', 'seed.applications.show|seed.applications.edit',
                $application->isRevising() ? 'Revise' : null, false];
            if ($application->agreement_issued_at) {
                $items[] = ['Grant agreement', route('seed.applications.agreement', $application), 'fa-file-signature', 'seed.applications.agreement',
                    $application->status === SeedGrant::AGREEMENT_ISSUED ? 1 : null, false];
            }
        }

        return ($items ? ['My grant' => $items] : []) + self::reviewing($user);
    }

    private static function reviewing(User $user): array
    {
        $assignments = SeedReviewerAssignment::with('application.rounds.reviews')
            ->where('reviewer_id', $user->id)->whereNull('removed_at')->get();

        if ($assignments->isEmpty()) {
            return [];
        }

        $controller = app(\App\Http\Controllers\SeedGrant\ReviewsController::class);
        $todo = $assignments->filter(fn ($a) => $controller->needsAction($a))->count();

        return ['Reviewing' => [
            ['My reviews', route('seed.reviews.index'), 'fa-comments', 'seed.reviews.*', $todo ?: null, false],
        ]];
    }
}
