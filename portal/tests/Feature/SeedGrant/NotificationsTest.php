<?php

namespace Tests\Feature\SeedGrant;

use App\Mail\SeedGrantNotice;
use App\Models\GmuNotification;
use App\Models\Seed\SeedApplication;
use App\Models\Seed\SeedCall;
use App\Models\Seed\SeedReviewRound;
use App\Models\User;
use App\Support\SeedGrant as S;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\BuildsGrantFixtures;
use Tests\TestCase;

/**
 * Who hears what, and when: the call opening, the daily reminders, and the
 * notifications page itself.
 */
class NotificationsTest extends TestCase
{
    use RefreshDatabase, BuildsGrantFixtures;

    private SeedCall $call;
    private User $gmu;
    private User $reviewerA;
    private User $reviewerB;

    protected function setUp(): void
    {
        parent::setUp();
        config(['gmu.external_grants' => false]);
        Mail::fake();

        $this->travelTo($this->eat('2026-11-25 09:00'));
        $this->call = SeedCall::create([
            'title' => 'Early-Career Researchers’ Implementation Science Seed Grant', 'code' => '2627', 'fiscal_year' => '2026/27',
            'min_amount' => 2_000_000, 'max_amount' => 10_000_000, 'duration_months' => 12, 'equipment_cap_percent' => 20,
            'status' => 'open', 'concept_deadline' => '2026-12-20', 'proposal_deadline' => '2027-02-20',
        ]);
        $this->gmu = $this->user('coordinator');
        $this->reviewerA = $this->user('research_scientist', ['name' => 'Peter Mboya']);
        $this->reviewerB = $this->user('research_scientist', ['name' => 'Sara Kimaro']);
    }

    private function eat(string $at): Carbon
    {
        return Carbon::parse($at, 'Africa/Dar_es_Salaam');
    }

    private function application(string $status, array $attributes = []): SeedApplication
    {
        $pi = $this->user('pi', ['first_appointment_date' => now()->subYears(2), 'employment_type' => 'permanent']);
        $application = new SeedApplication();
        $application->forceFill($attributes + [
            'seed_call_id' => $this->call->id, 'pi_id' => $pi->id,
            'reference' => sprintf('SG-2627-%03d', SeedApplication::count() + 1), 'title' => 'Follow-up calls for HIV care', 'status' => $status,
        ])->save();

        return $application;
    }

    /** A concept note in review with reviewers A and B, round 1 due on $due. A has submitted. */
    private function inReview(string $due): array
    {
        $application = $this->application(S::CONCEPT_REVIEW);
        $a = $application->assignments()->create(['stage' => 'concept', 'reviewer_id' => $this->reviewerA->id, 'label' => 'A', 'coi_status' => 'none']);
        $application->assignments()->create(['stage' => 'concept', 'reviewer_id' => $this->reviewerB->id, 'label' => 'B', 'coi_status' => 'none']);
        $round = $application->rounds()->create(['stage' => 'concept', 'number' => 1, 'due_at' => $due]);
        $round->reviews()->create(['seed_reviewer_assignment_id' => $a->id, 'recommendation' => 'accept', 'comments' => 'Clear.', 'submitted_at' => now()]);

        return [$application, $round];
    }

    private function reminders(User $user)
    {
        return $user->notifications()->where('type', 'seed_reminder')->get();
    }

    private function remind(): void
    {
        $this->artisan('gmu:seed-reminders')->assertSuccessful();
    }

    // ── Reminders ───────────────────────────────────────────────────────────

    public function test_a_reviewer_who_has_not_submitted_is_reminded_once_before_the_review_is_due(): void
    {
        [$application] = $this->inReview('2026-12-03');

        // Four days out: too early.
        $this->travelTo($this->eat('2026-11-29 07:00'));
        $this->remind();
        $this->assertCount(0, $this->reminders($this->reviewerB));

        // Three days out: reviewer B is reminded; A has already submitted.
        $this->travelTo($this->eat('2026-11-30 07:00'));
        $this->remind();
        $this->remind();
        $this->assertCount(1, $this->reminders($this->reviewerB));
        $this->assertCount(0, $this->reminders($this->reviewerA));
        $this->assertSame('Review due 3 Dec 2026: ' . $application->reference, $this->reminders($this->reviewerB)->first()->title);
        $this->assertStringContainsString('by the end of 3 Dec 2026 (in 3 days)', $this->reminders($this->reviewerB)->first()->message);
        Mail::assertSent(SeedGrantNotice::class, fn ($mail) => $mail->hasTo($this->reviewerB->email) && str_contains($mail->actionUrl, '/seed-grant/reviews'));
        Mail::assertNotSent(SeedGrantNotice::class, fn ($mail) => $mail->hasTo($this->reviewerA->email));
    }

    public function test_a_late_review_is_flagged_to_the_reviewer_and_to_gmu_once_the_due_day_has_ended(): void
    {
        [$application] = $this->inReview('2026-12-03');

        // On the due day itself, nothing is late yet.
        $this->travelTo($this->eat('2026-12-03 23:30'));
        $this->remind();
        $this->assertSame(0, GmuNotification::where('title', 'like', '%overdue%')->count());

        $this->travelTo($this->eat('2026-12-04 07:00'));
        $this->remind();
        $this->remind();
        $this->assertSame(1, $this->reviewerB->notifications()->where('title', 'Review overdue: ' . $application->reference)->count());
        $gmu = $this->gmu->notifications()->where('title', 'Reviews overdue: ' . $application->reference)->get();
        $this->assertCount(1, $gmu);
        $this->assertStringContainsString('1 of 2 reviewers have not submitted: Sara Kimaro.', $gmu->first()->message);
        $this->assertSame(0, $this->reviewerA->notifications()->where('title', 'like', '%overdue%')->count());
    }

    public function test_a_decided_round_sends_no_reminders(): void
    {
        [, $round] = $this->inReview('2026-12-03');
        $round->update(['decision' => 'advance', 'decided_at' => now()]);

        $this->travelTo($this->eat('2026-12-05 07:00'));
        $this->remind();

        $this->assertSame(0, GmuNotification::where('type', 'seed_reminder')->count());
    }

    public function test_applicants_are_reminded_before_their_revision_concept_note_and_full_proposal_are_due(): void
    {
        $this->call->update(['concept_deadline' => '2026-12-03', 'proposal_deadline' => '2026-12-03']);

        $draft = $this->application(S::DRAFT);
        $returned = $this->application(S::DRAFT, ['screened_at' => now()]);  // GMU returned it: no deadline applies
        $shortlisted = $this->application(S::SHORTLISTED);
        $revising = $this->application(S::CONCEPT_REVISION);
        $revising->rounds()->create(['stage' => 'concept', 'number' => 1, 'due_at' => '2026-11-20',
            'decision' => 'revise', 'decision_note' => 'Name a framework.', 'response_due_at' => '2026-12-02', 'decided_at' => now()]);

        $this->travelTo($this->eat('2026-12-01 07:00'));
        $this->remind();

        $this->assertSame(['Concept note due 3 Dec 2026: ' . $draft->reference], $this->reminders($draft->pi)->pluck('title')->all());
        $this->assertCount(0, $this->reminders($returned->pi));
        $this->assertSame(['Full proposal due 3 Dec 2026: ' . $shortlisted->reference], $this->reminders($shortlisted->pi)->pluck('title')->all());
        $this->assertSame(['Revision due 2 Dec 2026: ' . $revising->reference], $this->reminders($revising->pi)->pluck('title')->all());
        $this->assertStringContainsString('(tomorrow)', $this->reminders($revising->pi)->first()->message);
    }

    public function test_a_dry_run_sends_nothing(): void
    {
        $this->inReview('2026-12-03');
        $this->travelTo($this->eat('2026-12-04 07:00'));

        $this->artisan('gmu:seed-reminders --dry-run')->expectsOutputToContain('[dry run] 2 seed grant reminder(s) due.')->assertSuccessful();

        $this->assertSame(0, GmuNotification::count());
        Mail::assertNothingSent();
    }

    // ── A call opening ──────────────────────────────────────────────────────

    public function test_staff_who_could_apply_are_told_once_when_a_call_opens(): void
    {
        $call = SeedCall::create([
            'title' => 'Second Seed Grant', 'code' => '2728', 'fiscal_year' => '2027/28', 'min_amount' => 2_000_000, 'max_amount' => 10_000_000,
            'duration_months' => 12, 'equipment_cap_percent' => 20, 'status' => 'draft', 'concept_deadline' => '2027-01-31',
        ]);
        $eligible = $this->user('pi', ['first_appointment_date' => now()->subYears(2)]);
        $tooSenior = $this->user('pi', ['first_appointment_date' => now()->subYears(12)]);
        $drcp = $this->user('drcp');
        $settings = fn (string $status) => [
            'title' => $call->title, 'code' => $call->code, 'fiscal_year' => $call->fiscal_year, 'min_amount' => 2_000_000,
            'max_amount' => 10_000_000, 'duration_months' => 12, 'equipment_cap_percent' => 20, 'concept_deadline' => '2027-01-31', 'status' => $status,
        ];

        $this->actingAs($this->gmu)->put(route('seed.calls.update', $call), $settings('open'))
            ->assertSessionHasNoErrors()->assertSessionHas('success', 'The call is open. 3 staff who could apply were notified in the portal and by email.');

        $told = GmuNotification::where('type', 'seed_call')->get();
        $this->assertEqualsCanonicalizing([$eligible->id, $this->reviewerA->id, $this->reviewerB->id], $told->pluck('user_id')->all());
        $notice = $told->firstWhere('user_id', $eligible->id);
        $this->assertSame('New call open: Second Seed Grant', $notice->title);
        $this->assertStringContainsString('Concept notes are due by the end of 31 Jan 2027.', $notice->message);
        $this->assertSame(route('seed.calls.show', $call), $notice->actionUrl());
        Mail::assertSent(SeedGrantNotice::class, fn ($mail) => $mail->hasTo($eligible->email) && $mail->actionUrl === route('seed.calls.show', $call));
        Mail::assertNotSent(SeedGrantNotice::class, fn ($mail) => $mail->hasTo($tooSenior->email) || $mail->hasTo($drcp->email) || $mail->hasTo($this->gmu->email));

        // Closing and reopening does not announce it again.
        $this->actingAs($this->gmu)->put(route('seed.calls.update', $call), $settings('closed'))->assertSessionHasNoErrors();
        $this->actingAs($this->gmu)->put(route('seed.calls.update', $call), $settings('open'))
            ->assertSessionHasNoErrors()->assertSessionHas('success', 'Call settings saved.');
        $this->assertSame(3, GmuNotification::where('type', 'seed_call')->count());
    }

    // ── The notifications page ──────────────────────────────────────────────

    public function test_opening_a_notification_marks_it_read_and_goes_to_the_page(): void
    {
        [$application] = $this->inReview('2026-12-03');
        $this->travelTo($this->eat('2026-12-04 07:00'));
        $this->remind();
        $notice = $this->reviewerB->notifications()->firstOrFail();

        $this->actingAs($this->reviewerA)->get(route('notifications.open', $notice))->assertForbidden();

        $this->actingAs($this->reviewerB)->get(route('notifications.index'))->assertOk()
            ->assertSee('1 unread')->assertSee(route('notifications.open', $notice), false)->assertSee('Mark all read');
        $this->actingAs($this->reviewerB)->get(route('notifications.open', $notice))->assertRedirect(route('seed.reviews.index'));
        $this->assertNotNull($notice->fresh()->read_at);
        $this->actingAs($this->reviewerB)->get(route('notifications.index'))->assertOk()
            ->assertSee('Everything here has been read')->assertDontSee('Mark all read');
    }

    public function test_mark_all_read_clears_every_unread_notification_of_that_person_only(): void
    {
        $this->inReview('2026-12-03');
        $this->travelTo($this->eat('2026-12-04 07:00'));
        $this->remind();
        $this->assertSame(1, $this->gmu->unreadNotificationsCount());
        $this->assertSame(1, $this->reviewerB->unreadNotificationsCount());

        $this->actingAs($this->gmu)->patch(route('notifications.read-all'))->assertSessionHas('success', '1 notification marked as read.');

        $this->assertSame(0, $this->gmu->unreadNotificationsCount());
        $this->assertSame(1, $this->reviewerB->unreadNotificationsCount());
    }

    // ── The scheduler ───────────────────────────────────────────────────────

    public function test_external_grant_jobs_run_only_when_external_grants_are_switched_on(): void
    {
        $due = fn () => collect(app(Schedule::class)->events())
            ->filter(fn ($event) => $event->filtersPass($this->app))
            ->map(fn ($event) => preg_replace('/^.*artisan["\']?\s+/', '', (string) $event->command))
            ->values()->all();

        $this->assertContains('gmu:seed-reminders', $due());
        $this->assertContains('gmu:health', $due());
        $this->assertNotContains('gmu:send-weekly-digest', $due());
        $this->assertNotContains('gmu:send-deadline-reminders', $due());
        $this->assertNotContains('gmu:mark-overdue', $due());

        config(['gmu.external_grants' => true]);
        $this->assertContains('gmu:send-weekly-digest', $due());
    }
}
