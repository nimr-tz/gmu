<?php

namespace App\Support;

use App\Http\Controllers\SeedGrant\ApprovalsController;
use App\Models\Seed\SeedApplication;
use App\Models\Seed\SeedCall;
use App\Models\Seed\SeedReviewerAssignment;
use App\Models\User;

/**
 * The seed grant entries in the side panel, by role.
 * Each item: [label, url, icon, active route pattern, count or null, count style:
 * false (blue), true (red, an alert) or 'warn' (amber, waiting on this person)].
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

            // The owner's Approvals design: Approvals (with what waits, in amber) and Ranking.
            return ['Grants' => [
                ['Approvals', route('seed.approvals.index'), 'shield-check', 'seed.approvals.*', $waiting ?: null, 'warn'],
                ['Ranking', route('seed.ranking'), 'list-ordered', 'seed.ranking', null, false],
            ]] + self::reviewing($user);
        }

        // Staff (the owner's "Staff Pages" design): their calls, then their account. Their
        // applications and agreements are on the dashboard, which opens each one.
        $items = [];
        if (SeedCall::where('status', '!=', 'draft')->exists()) {
            $items[] = ['Calls', route('seed.calls.index'), 'megaphone', 'seed.calls.*|seed.call', null, false];
        }
        $unread = $user->unreadNotificationsCount();

        return ($items ? ['My grants' => $items] : []) + self::reviewing($user) + ['Account' => [
            ['Notifications', route('notifications.index'), 'bell', 'notifications.*', $unread ?: null, false],
            ['Profile', route('profile.edit'), 'user', 'profile.*', null, false],
        ]];
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

        return ['Review panel' => [
            ['My reviews', route('seed.reviews.index'), 'clipboard-check', 'seed.reviews.*', $todo ?: null, 'warn'],
        ]];
    }
}
