<?php

namespace Tests\Feature\Security;

use App\Models\ProposalReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsGrantFixtures;
use Tests\TestCase;

/**
 * Audit findings H1, H2, H3 and H4 — account state, separation of duties, and
 * the confidentiality of conflict-of-interest disclosures.
 */
class AccountAndReviewIntegrityTest extends TestCase
{
    use RefreshDatabase, BuildsGrantFixtures;

    // ── H1: deactivation ────────────────────────────────────────────────────

    public function test_a_deactivated_account_cannot_sign_in(): void
    {
        $user = $this->user('pi', ['is_active' => false]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_deactivation_ends_an_active_session(): void
    {
        $user = $this->user('pi');

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $user->update(['is_active' => false]);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    // ── H4: passwords set by somebody else ──────────────────────────────────

    public function test_a_user_given_a_password_must_replace_it_before_working(): void
    {
        $user = $this->user('coordinator', ['must_change_password' => true]);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('profile.edit'));

        $this->actingAs($user)->put(route('password.update'), [
            'current_password'      => 'password',
            'password'              => 'my-own-Password!9',
            'password_confirmation' => 'my-own-Password!9',
        ])->assertSessionHasNoErrors();

        $this->assertFalse($user->fresh()->must_change_password);
        $this->actingAs($user->fresh())->get(route('dashboard'))->assertOk();
    }

    public function test_an_account_created_by_the_coordinator_starts_held(): void
    {
        $coordinator = $this->user('coordinator');

        $this->actingAs($coordinator)->post(route('admin.users.store'), [
            'name'                  => 'New Officer',
            'email'                 => 'officer@nimr.or.tz',
            'password'              => 'temporary-Pass1',
            'password_confirmation' => 'temporary-Pass1',
            'is_active'             => true,
        ])->assertRedirect();

        $this->assertTrue(User::where('email', 'officer@nimr.or.tz')->value('must_change_password'));
    }

    // ── H2: nobody reviews their own proposal ───────────────────────────────

    public function test_a_reviewer_cannot_review_their_own_proposal(): void
    {
        $scientist = $this->user('research_scientist');
        $proposal  = $this->proposal($scientist, null, ['status' => 'internal_review']);

        $this->actingAs($scientist)
            ->post(route('proposals.review', $proposal), [
                'status'   => 'approved',
                'comments' => 'This proposal is excellent and I approve it fully.',
                'score'    => 10,
            ])
            ->assertForbidden();

        $this->assertNull(
            ProposalReview::where('proposal_id', $proposal->id)->where('status', 'approved')->first()
        );
    }

    public function test_a_reviewer_can_review_somebody_elses_proposal(): void
    {
        $scientist = $this->user('research_scientist');
        $proposal  = $this->proposal($this->user('pi'), null, ['status' => 'internal_review']);

        $this->actingAs($scientist)
            ->post(route('proposals.review', $proposal), [
                'status'   => 'approved',
                'comments' => 'Methodology is sound and the team has the right expertise.',
                'score'    => 8,
            ])
            ->assertRedirect();

        $review = ProposalReview::where('proposal_id', $proposal->id)->where('review_type', 'technical')->first();
        $this->assertSame('approved', $review->status);
        $this->assertSame($scientist->id, $review->reviewer_id);
    }

    public function test_a_role_without_a_review_track_cannot_review(): void
    {
        $secretary = $this->user('secretary');
        $proposal  = $this->proposal($this->user('pi'), null, ['status' => 'internal_review']);

        $this->actingAs($secretary)
            ->post(route('proposals.review', $proposal), [
                'status'   => 'approved',
                'comments' => 'Looks good to me, approving on behalf of the office.',
            ])
            ->assertForbidden();
    }

    // ── H3: FCOI confidentiality ────────────────────────────────────────────

    public function test_conflict_disclosures_are_hidden_from_other_staff(): void
    {
        $researcher = $this->user('pi');
        $researcher->fcoiDisclosures()->create([
            'disclosure_year'      => (int) now()->year,
            'has_conflict'         => true,
            'conflict_description' => 'Shareholding in a diagnostics supplier',
            'status'               => 'submitted',
        ]);

        // The planning officer may see the profile, but not the disclosure.
        $this->actingAs($this->user('planning_officer'))
            ->get(route('researchers.show', $researcher))
            ->assertOk()
            ->assertDontSee('Shareholding in a diagnostics supplier');

        // The person themselves, and the Coordinator who reviews it, may.
        $this->actingAs($researcher)
            ->get(route('researchers.show', $researcher))
            ->assertOk()
            ->assertSee('Shareholding in a diagnostics supplier');

        $this->actingAs($this->user('coordinator'))
            ->get(route('researchers.show', $researcher))
            ->assertOk()
            ->assertSee('Shareholding in a diagnostics supplier');
    }

    public function test_only_the_coordinator_clears_a_disclosure(): void
    {
        $researcher = $this->user('pi');
        $disclosure = $researcher->fcoiDisclosures()->create([
            'disclosure_year' => (int) now()->year,
            'has_conflict'    => true,
            'status'          => 'submitted',
        ]);

        $this->actingAs($this->user('planning_officer'))
            ->patch(route('fcoi.review', $disclosure), ['status' => 'cleared'])
            ->assertForbidden();

        $this->actingAs($this->user('coordinator'))
            ->patch(route('fcoi.review', $disclosure), ['status' => 'cleared'])
            ->assertRedirect();

        $this->assertSame('cleared', $disclosure->fresh()->status);
    }

    // ── C5: the record outlives the person ──────────────────────────────────

    public function test_deactivating_an_account_keeps_the_grant_record(): void
    {
        $pi       = $this->user('pi');
        $proposal = $this->proposal($pi);
        $award    = $this->award($proposal);

        // Only the Coordinator deactivates an account now; nobody closes their own.
        $this->actingAs($this->user('coordinator'))->delete(route('admin.users.destroy', $pi))->assertRedirect();

        $this->assertNotNull(User::find($pi->id));
        $this->assertFalse(User::find($pi->id)->is_active);
        $this->assertNotNull($proposal->fresh());
        $this->assertNotNull($award->fresh());
    }
}
