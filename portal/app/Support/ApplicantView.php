<?php

namespace App\Support;

use App\Models\Seed\SeedApplication;
use App\Support\SeedGrant as S;
use Carbon\CarbonInterface;

/**
 * What an application means to its applicant, in plain words, for the applicant's
 * dashboard (the owner's design): whether it needs them, what they must do by when,
 * where it is on the eight steps, and which date to show beside it.
 *
 * Dates are whole days in East Africa Time: a date is due by the end of that day,
 * and is late only once that day has passed.
 */
class ApplicantView
{
    /** The applicant has something to do on it. */
    public static function needsYou(SeedApplication $a): bool
    {
        return $a->isRevising() || in_array($a->status, [S::DRAFT, S::SHORTLISTED, S::AGREEMENT_ISSUED], true);
    }

    /**
     * What the applicant must do: [value, date|null, note, short, kind].
     * value: the tile's words ("Revision due 2 Dec"); short: for a sentence ("a revision for …").
     */
    public static function task(SeedApplication $a): ?array
    {
        $call = $a->call;
        $round = $a->latestRound();

        return match (true) {
            $a->isRevising() => ['Revision due', $round?->response_due_at, 'Respond to the reviewers on ' . $a->reference, 'a revision for the ' . $call->title, 'revise'],
            $a->status === S::DRAFT => ['Concept note due', $call->concept_deadline, 'Submit ' . $a->reference . ' before the end of that day', 'a concept note for the ' . $call->title, 'draft'],
            $a->status === S::SHORTLISTED => ['Full proposal due', $call->proposal_deadline, 'With the mentor letter and your CV', 'a full proposal for the ' . $call->title, 'proposal'],
            $a->status === S::AGREEMENT_ISSUED => ['Accept your agreement', null, 'Read it and accept it online', 'your grant agreement for the ' . $call->title, 'agreement'],
            default => null,
        };
    }

    /** Whole days from today to the date: negative once it has passed. */
    public static function daysTo(?CarbonInterface $date): ?int
    {
        return $date ? (int) now()->startOfDay()->diffInDays($date->copy()->startOfDay(), false) : null;
    }

    /** "In 10 days", "Today" or "Late"; never "Today" for a date already passed. */
    public static function when(?CarbonInterface $date): ?string
    {
        $d = self::daysTo($date);

        return $d === null ? null : ($d < 0 ? 'Late' : ($d === 0 ? 'Today' : 'In ' . $d . ' ' . ($d === 1 ? 'day' : 'days')));
    }

    /** Where the application sits, for the colours: needs | review | approval | awarded | closed. */
    public static function bucket(SeedApplication $a): string
    {
        return match (true) {
            self::needsYou($a) => 'needs',
            in_array($a->status, S::CLOSED_STATUSES, true) => 'closed',
            $a->status === S::AWARDED => 'awarded',
            in_array($a->status, [S::RECOMMENDED, S::WITH_DRCP, S::WITH_DG, S::APPROVED, S::AGREEMENT_ACCEPTED], true) => 'approval',
            default => 'review',
        };
    }

    /** The date beside the money on a card: [label, date|null, tone ink class]. */
    public static function cardDate(SeedApplication $a): array
    {
        $round = $a->latestRound();

        return match (true) {
            $a->isRevising() => ['Response due', $round?->response_due_at, 'text-warn-ink'],
            $a->status === S::DRAFT => ['Concept notes close', $a->call->concept_deadline, 'text-warn-ink'],
            $a->status === S::SHORTLISTED => ['Full proposal due', $a->call->proposal_deadline, 'text-warn-ink'],
            in_array($a->status, [S::CONCEPT_REVIEW, S::PROPOSAL_REVIEW], true) && $round?->due_at => ['Reviews due', $round->due_at, 'text-sky-700'],
            $a->status === S::AGREEMENT_ISSUED => ['Agreement issued', $a->agreement_issued_at, 'text-foreground'],
            in_array($a->status, [S::AGREEMENT_ACCEPTED, S::AWARDED], true) => ['Agreement', $a->agreement_accepted_at, 'text-foreground'],
            default => ['Submitted', $a->concept_submitted_at, 'text-foreground'],
        };
    }
}
