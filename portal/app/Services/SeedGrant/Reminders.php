<?php

namespace App\Services\SeedGrant;

use App\Models\Seed\SeedApplication;
use App\Models\Seed\SeedReviewRound;
use App\Models\User;
use App\Support\Days;
use App\Support\SeedGrant;
use Illuminate\Support\Facades\DB;

/**
 * The seed grant's daily reminders: before a review, a revision response, a
 * concept note or a full proposal is due, and once a review is late. Each
 * reminder goes to each person once (seed_reminders), through the same
 * notification and email as every other seed grant notice.
 *
 * Windows are "due within SeedGrant::REMINDER_DAYS and not yet reminded",
 * so a morning the scheduler missed is caught up the next day. Deadlines are
 * the end of the due day in Dar es Salaam, as everywhere else.
 */
class Reminders
{
    private const STAGES = ['concept' => 'concept note', 'proposal' => 'full proposal'];

    /** @var array<string,int> */
    private array $sent = [];

    private bool $dryRun = false;

    public function __construct(private Workflow $workflow) {}

    /** @return array<string,int> reminders sent (or, on a dry run, due), by kind */
    public function run(bool $dryRun = false): array
    {
        $this->dryRun = $dryRun;
        $this->sent = ['review-due' => 0, 'review-late' => 0, 'round-late' => 0, 'response-due' => 0, 'concept-due' => 0, 'proposal-due' => 0];

        $this->reviews();
        $this->responses();
        $this->conceptNotes();
        $this->proposals();

        return $this->sent;
    }

    /** Reviewers who have not submitted in an open round, and GMU once the round is late. */
    private function reviews(): void
    {
        $rounds = SeedReviewRound::whereNull('decision')->whereNotNull('due_at')
            ->with(['application.assignments.reviewer', 'reviews'])
            ->get()
            ->filter(fn ($round) => in_array($round->application?->status, [SeedGrant::CONCEPT_REVIEW, SeedGrant::PROPOSAL_REVIEW], true));

        foreach ($rounds as $round) {
            $application = $round->application;
            $submitted = $round->reviews->whereNotNull('submitted_at')->pluck('seed_reviewer_assignment_id');
            $panel = $application->assignments->where('stage', $round->stage)->filter->isActive();
            $waiting = $panel->reject(fn ($a) => $submitted->contains($a->id))->filter(fn ($a) => $a->reviewer !== null);

            $days = Days::until($round->due_at);
            $date = $round->due_at->format('j M Y');
            $stage = self::STAGES[$round->stage];

            if ($days >= 0 && $days <= SeedGrant::REMINDER_DAYS && ! $round->created_at?->isToday()) {
                foreach ($waiting as $assignment) {
                    $this->once($assignment->reviewer, 'review-due:' . $round->id, $application,
                        'Review due ' . $date . ': ' . $application->reference,
                        $assignment->coi_status === 'pending'
                            ? 'Your review of the ' . $stage . ' ' . $application->reference . ' is due by the end of ' . $date . ' (' . Days::describe($round->due_at) . '). First declare whether you have any conflict of interest, then submit your review.'
                            : 'Your round ' . $round->number . ' review of the ' . $stage . ' ' . $application->reference . ' is due by the end of ' . $date . ' (' . Days::describe($round->due_at) . '). A saved draft does not reach GMU until you submit it.');
                }
            }

            if ($days < 0 && $waiting->isNotEmpty()) {
                foreach ($waiting as $assignment) {
                    $this->once($assignment->reviewer, 'review-late:' . $round->id, $application,
                        'Review overdue: ' . $application->reference,
                        'Your round ' . $round->number . ' review of the ' . $stage . ' ' . $application->reference . ' was due on ' . $date
                            . '. Please submit it now, or tell GMU if you cannot review it so they can assign someone else.');
                }

                $names = $waiting->map(fn ($a) => $a->reviewer->name)->values();
                foreach ($this->workflow->gmu() as $officer) {
                    $this->once($officer, 'round-late:' . $round->id, $application,
                        'Reviews overdue: ' . $application->reference,
                        'Round ' . $round->number . ' of the ' . $stage . ' was due on ' . $date . '. '
                            . $names->count() . ' of ' . $panel->count() . ' reviewers have not submitted: ' . $names->join(', ', ' and ') . '. '
                            . 'Remind them, or assign another reviewer.');
                }
            }
        }
    }

    /** Applicants asked to revise, before their response is due. */
    private function responses(): void
    {
        $rounds = SeedReviewRound::where('decision', 'revise')->whereNull('responded_at')->whereNotNull('response_due_at')
            ->with('application.pi')->get();

        foreach ($rounds as $round) {
            $application = $round->application;
            if (! $application || $application->roundAwaitingResponse()?->id !== $round->id || ! $this->dueSoon($round->response_due_at)) {
                continue;
            }
            if ($round->decided_at?->isToday()) {
                continue;  // The request itself went out today.
            }

            $date = $round->response_due_at->format('j M Y');
            $this->once($application->pi, 'response-due:' . $round->id, $application,
                'Revision due ' . $date . ': ' . $application->reference,
                'GMU needs your revised ' . self::STAGES[$round->stage] . ' and your response to the reviewers by the end of ' . $date
                    . ' (' . Days::describe($round->response_due_at) . ').');
        }
    }

    /** Concept notes still in draft as the call's deadline nears (not ones GMU returned, which have no deadline). */
    private function conceptNotes(): void
    {
        $drafts = SeedApplication::where('status', SeedGrant::DRAFT)->whereNull('screened_at')->with(['call', 'pi'])->get();

        foreach ($drafts as $application) {
            $call = $application->call;
            if (! $call?->acceptingConceptNotes() || ! $call->concept_deadline || ! $this->dueSoon($call->concept_deadline)) {
                continue;
            }

            $date = $call->concept_deadline->format('j M Y');
            $this->once($application->pi, 'concept-due:' . $application->id, $application,
                'Concept note due ' . $date . ': ' . $application->reference,
                'Your concept note for the ' . $call->title . ' is still a draft. Submit it by the end of ' . $date
                    . ' (' . Days::describe($call->concept_deadline) . '); the call stops accepting concept notes after that.');
        }
    }

    /** Shortlisted applicants who have not sent the full proposal. */
    private function proposals(): void
    {
        $shortlisted = SeedApplication::where('status', SeedGrant::SHORTLISTED)->with(['call', 'pi'])->get();

        foreach ($shortlisted as $application) {
            $deadline = $application->call?->proposal_deadline;
            if (! $deadline || ! $this->dueSoon($deadline)) {
                continue;
            }

            $date = $deadline->format('j M Y');
            $this->once($application->pi, 'proposal-due:' . $application->id, $application,
                'Full proposal due ' . $date . ': ' . $application->reference,
                'Submit your full proposal by the end of ' . $date . ' (' . Days::describe($deadline) . '). It cannot be submitted after that date.');
        }
    }

    private function dueSoon($date): bool
    {
        $days = Days::until($date);

        return $days >= 0 && $days <= SeedGrant::REMINDER_DAYS;
    }

    /** Send this reminder to this person unless they have already had it. */
    private function once(?User $user, string $key, SeedApplication $application, string $title, string $message): void
    {
        if (! $user || ! $user->is_active || DB::table('seed_reminders')->where('user_id', $user->id)->where('key', $key)->exists()) {
            return;
        }

        $this->sent[strtok($key, ':')]++;
        if ($this->dryRun) {
            return;
        }

        $this->workflow->tell($user, 'seed_reminder', $title, $message, $application);
        DB::table('seed_reminders')->insert(['user_id' => $user->id, 'key' => $key, 'sent_at' => now()]);
    }
}
