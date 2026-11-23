<?php

namespace App\Console\Commands;

use App\Services\SeedGrant\Reminders;
use Illuminate\Console\Command;

/**
 * The seed grant's daily reminders (App\Services\SeedGrant\Reminders).
 */
class SendSeedReminders extends Command
{
    protected $signature = 'gmu:seed-reminders {--dry-run : Count what would be sent without sending}';

    protected $description = 'Remind seed grant reviewers, applicants and GMU of deadlines that are near or missed.';

    private const LABELS = [
        'review-due'   => 'review due soon',
        'review-late'  => 'review overdue (to the reviewer)',
        'round-late'   => 'reviews overdue (to GMU)',
        'response-due' => 'revision response due soon',
        'concept-due'  => 'concept note due soon',
        'proposal-due' => 'full proposal due soon',
    ];

    public function handle(Reminders $reminders): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $counts = $reminders->run($dryRun);

        foreach ($counts as $kind => $count) {
            if ($count > 0) {
                $this->line(sprintf('  %d × %s', $count, self::LABELS[$kind]));
            }
        }
        $this->info(($dryRun ? '[dry run] ' : '') . array_sum($counts) . ' seed grant reminder(s)' . ($dryRun ? ' due.' : ' sent.'));

        return self::SUCCESS;
    }
}
