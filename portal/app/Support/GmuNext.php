<?php

namespace App\Support;

use App\Models\Seed\SeedApplication;
use App\Services\SeedGrant\Workflow;
use App\Support\SeedGrant as S;

/**
 * What GMU must do next on an application, if anything: the same words on the
 * dashboard's "Needs your action", the submissions list and the record.
 *
 * Returns [label, tone, toRanking] or null. Tones: '' screening and reviewers (amber),
 * 'warn' a round to decide (violet), 'ok' sign-off and agreements (blue), 'danger' late or blocked.
 */
class GmuNext
{
    public static function step(SeedApplication $a, Workflow $workflow): ?array
    {
        $step = match ($a->status) {
            S::SUBMITTED          => ['Screen', ''],
            S::SCREENED, S::PROPOSAL_SUBMITTED => [$a->activeAssignments()->count() < Workflow::MIN_REVIEWERS ? 'Assign reviewers' : 'Send to reviewers', ''],
            S::APPROVED           => ['Issue agreement', 'ok'],
            S::AGREEMENT_ACCEPTED => ['Upload signed copy', 'ok'],
            S::RECOMMENDED        => ['Sign off in ranking', 'ok', true],
            default               => null,
        };

        if ($round = $a->openRound()) {
            if ($workflow->roundComplete($round)) {
                $step = ['Decide round ' . $round->number, 'warn'];
            } elseif ($round->isOverdue()) {
                $step ??= ['Review overdue', 'danger'];
            }
        }
        if ($a->stageAssignments()->contains(fn ($as) => $as->coi_status === 'conflict' && $as->removed_at === null)
            && $a->activeAssignments()->count() < Workflow::MIN_REVIEWERS) {
            $step = ['Reassign: conflict', 'danger'];
        }

        return $step ? [$step[0], $step[1], $step[2] ?? false] : null;
    }
}
