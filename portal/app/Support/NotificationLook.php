<?php

namespace App\Support;

use App\Models\GmuNotification;
use App\Models\Seed\SeedApplication;
use App\Models\Seed\SeedCall;
use App\Models\User;
use App\Support\SeedGrant as S;

/**
 * How a notification shows on the Notifications page and in its email, from the owner's
 * "Staff Pages" and "Email" designs: an icon and tone by kind, the reference it is about,
 * and what its button says. One place, so the page and the email agree.
 */
class NotificationLook
{
    /** Icon, and the icon tile's tone classes. */
    public static function icon(string $type): array
    {
        return match ($type) {
            'seed_agreement'                       => ['pen-line', 'bg-warn-soft text-warn-ink'],
            'seed_reminder', 'seed_conflict', 'seed_returned' => ['clock', 'bg-warn-soft text-warn-ink'],
            'seed_call', 'new_call'                => ['megaphone', 'bg-blue-50 text-ok-ink'],
            'seed_approved', 'seed_approval'       => ['check-circle', 'bg-indigo-50 text-indigo-700'],
            'seed_decision', 'seed_screened', 'seed_review', 'seed_reviews_in' => ['clipboard-check', 'bg-review-soft text-review-ink'],
            'seed_submitted', 'seed_resubmitted'   => ['send', 'bg-gray-100 text-gray-700'],
            default                                => ['bell', 'bg-gray-100 text-gray-700'],
        };
    }

    /** The application or call it is about, for its reference chip. */
    public static function subject(GmuNotification $n): SeedApplication|SeedCall|null
    {
        $related = $n->related_type ? $n->related : null;

        return $related instanceof SeedApplication || $related instanceof SeedCall ? $related : null;
    }

    /** What the button says: the page it opens, in the reader's words. */
    public static function action(GmuNotification $n, User $user): string
    {
        $subject = self::subject($n);

        return match (true) {
            $subject instanceof SeedCall => $subject->acceptingConceptNotes() ? 'Read and apply' : 'Read the call',
            ! $subject instanceof SeedApplication => 'Open',
            $user->id === $subject->pi_id => match (true) {
                $n->type === 'seed_agreement' && $subject->status === S::AGREEMENT_ISSUED => 'Read the agreement',
                $n->type === 'seed_decision' && $subject->latestRound()?->decided_at !== null => 'Read their comments',
                $subject->isEditableByApplicant() => 'Continue the application',
                default => 'Open application',
            },
            $user->can('manage-seed-grants')  => 'Open submission',
            $user->can('approve-seed-grants') => 'Open decision',
            default                           => 'Open my reviews',
        };
    }

    /** Why this person got it, for the email's footer. */
    public static function why(string $type, User $user, ?SeedApplication $application): string
    {
        return match (true) {
            $application && $user->id === $application->pi_id => 'You are receiving this as the PI on ' . $application->reference . '.',
            $user->can('manage-seed-grants')                 => 'You are receiving this as GMU staff running the call.',
            $user->hasRole('drcp')                           => 'You are receiving this as the DRCP.',
            $user->hasRole('dg')                             => 'You are receiving this as the Director General.',
            $type === 'seed_review'                          => 'You are receiving this because GMU assigned you as a reviewer.',
            $type === 'seed_call'                            => 'You are receiving this as NIMR staff.',
            default                                          => 'You are receiving this because you take part in this grant.',
        };
    }
}
