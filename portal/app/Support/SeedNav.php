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
        // Counts run across every call: GMU, the approvers and applicants may have work in several.
        if ($user->can('manage-seed-grants')) {
            $needs = SeedApplication::whereIn('status', [SeedGrant::SUBMITTED, SeedGrant::SCREENED, SeedGrant::PROPOSAL_SUBMITTED, SeedGrant::APPROVED, SeedGrant::AGREEMENT_ACCEPTED])->count();
            $recommended = SeedApplication::where('status', SeedGrant::RECOMMENDED)->count();

            $items = [['Calls', route('seed.calls.index'), 'megaphone', 'seed.calls.*|seed.call*', null, false]];
            if (SeedCall::exists()) {
                $items[] = ['Submissions', route('seed.submissions.index'), 'file-text', 'seed.submissions.*', $needs ?: null, false];
                $items[] = ['Ranking', route('seed.ranking'), 'list-ordered', 'seed.ranking', null, false];
                $items[] = ['Approvals', route('seed.approvals.index'), 'shield-check', 'seed.approvals.*', $recommended ?: null, false];
            }

            return ['Grants' => $items] + self::reviewing($user);
        }

        if ($level = ApprovalsController::levelFor($user)) {
            $waiting = SeedApplication::where('status', ApprovalsController::QUEUE_STATUS[$level])->count();

            return ['Decisions' => [
                ['Awaiting decision', route('seed.approvals.index'), 'shield-check', 'seed.approvals.*', $waiting ?: null, false],
                ['Full ranking', route('seed.ranking'), 'list-ordered', 'seed.ranking', null, false],
                ['Calls', route('seed.calls.index'), 'megaphone', 'seed.calls.*|seed.call', null, false],
            ]] + self::reviewing($user);
        }

        $applications = SeedApplication::where('pi_id', $user->id)->latest('id')->get();
        $items = [];
        if (SeedCall::where('status', '!=', 'draft')->exists()) {
            $items[] = ['Calls', route('seed.calls.index'), 'megaphone', 'seed.calls.*|seed.call', null, false];
        }
        if ($applications->isNotEmpty()) {
            $items[] = [$applications->count() === 1 ? 'My application' : 'My applications', route('seed.mine'), 'file-text',
                'seed.mine|seed.applications.show|seed.applications.edit', $applications->contains->isRevising() ? 'Revise' : null, false];
            foreach ($applications->whereNotNull('agreement_issued_at') as $application) {
                $items[] = ['Grant agreement · ' . $application->reference, route('seed.applications.agreement', $application), 'pen-line',
                    'seed.applications.agreement', $application->status === SeedGrant::AGREEMENT_ISSUED ? 1 : null, false];
            }
        }

        return ($items ? ['My grants' => $items] : []) + self::reviewing($user);
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
            ['My reviews', route('seed.reviews.index'), 'message-square', 'seed.reviews.*', $todo ?: null, false],
        ]];
    }
}
