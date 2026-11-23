<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\BuildsGrantFixtures;
use Tests\TestCase;

/**
 * Single sign-on through NREIMS (Laravel Passport, authorization-code grant),
 * and the one-time profile that follows a first sign-in.
 */
class NreimsLoginTest extends TestCase
{
    use RefreshDatabase, BuildsGrantFixtures;

    private const BASE = 'https://nreims.example.test';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.nreims' => [
            'base_url'      => self::BASE,
            'client_id'     => '7',
            'client_secret' => 'secret',
            'redirect'      => 'http://localhost/auth/nreims/callback',
            'default_role'  => 'pi',
        ]]);
        Role::firstOrCreate(['name' => 'pi'], ['display_name' => 'Principal Investigator']);
    }

    /** Fake NREIMS answering with the given user, and return the callback URL with a valid state. */
    private function nreimsReturns(array $user, int $tokenStatus = 200): string
    {
        Http::fake([
            self::BASE . '/oauth/token' => Http::response(
                $tokenStatus === 200 ? ['access_token' => 'tok-123', 'token_type' => 'Bearer'] : ['error' => 'invalid_grant'],
                $tokenStatus
            ),
            self::BASE . '/api/user' => Http::response($user),
        ]);

        $this->withSession(['nreims_oauth_state' => 'state-abc']);

        return route('nreims.callback', ['code' => 'code-xyz', 'state' => 'state-abc']);
    }

    private function neema(array $overrides = []): array
    {
        return $overrides + [
            'id' => 4812, 'first_name' => 'Neema', 'middle_name' => 'A.', 'last_name' => 'Mushi',
            'email' => 'Neema.Mushi@nimr.or.tz', 'phone' => '+255712000111',
            'email_verified_at' => '2026-01-10T08:00:00.000000Z',
        ];
    }

    public function test_the_sign_in_page_offers_nreims_once_it_is_configured(): void
    {
        $this->get(route('login'))->assertSee('Sign in with NREIMS')->assertDontSee('name="password"', false);
        $this->get(route('login', ['admin' => 1]))->assertSee('name="password"', false);

        config(['services.nreims.client_id' => null]);
        $this->get(route('login'))->assertDontSee('Sign in with NREIMS')->assertSee('name="password"', false);
    }

    public function test_redirect_sends_the_browser_to_nreims_with_a_state(): void
    {
        $response = $this->get(route('nreims.redirect'));

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith(self::BASE . '/oauth/authorize?', $location);
        $this->assertStringContainsString('client_id=7', $location);
        $this->assertStringContainsString('response_type=code', $location);

        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame(session('nreims_oauth_state'), $query['state']);
    }

    public function test_a_first_sign_in_creates_the_account_and_asks_for_the_profile(): void
    {
        $url = $this->nreimsReturns($this->neema());

        $this->get($url)->assertRedirect(route('profile.complete'));

        $user = User::where('nreims_id', 4812)->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('Neema A. Mushi', $user->name);
        $this->assertSame('neema.mushi@nimr.or.tz', $user->email);
        $this->assertSame('pi', $user->role->name);
        $this->assertTrue($user->needsProfileCompletion());

        // Held in the welcome until it is done: step 1 greets them by name and asks for their details.
        $this->get(route('dashboard'))->assertRedirect(route('profile.complete'));
        $this->get(route('profile.complete'))->assertOk()
            ->assertSee('Neema.')->assertSee('Welcome to NIMR Grants.')->assertSee('Get started');
    }

    public function test_the_welcome_walks_through_four_steps(): void
    {
        $this->get($this->nreimsReturns($this->neema()));

        // Step 2 shows what came from NREIMS; step 3 holds the form.
        $this->get(route('profile.complete.nreims'))->assertOk()
            ->assertSee('We already know a little about you.')->assertSee('neema.mushi@nimr.or.tz')
            ->assertSee('+255712000111')->assertSee('Yes, that’s me', false);
        $this->get(route('profile.complete.details'))->assertOk()
            ->assertSee('Confirm your details')->assertSee('From NREIMS')->assertSee('name="first_appointment_date"', false);

        // Step 4 is only for someone who has finished.
        $this->get(route('profile.complete.ready'))->assertRedirect(route('profile.complete'));
        $this->post(route('profile.complete.store'), $this->postDetails())->assertRedirect(route('profile.complete.ready'));
        $this->get(route('profile.complete.ready'))->assertOk()
            ->assertSee('You’re all set', false)->assertSee('Mwanza Centre')
            ->assertSee('Enter NIMR Grants')->assertSee('Change my details');

        // Once done, the first three steps send them on to the dashboard, unless they are changing their details.
        foreach (['profile.complete', 'profile.complete.nreims', 'profile.complete.details'] as $step) {
            $this->get(route($step))->assertRedirect(route('dashboard'));
        }
        $this->get(route('profile.complete.details', ['change' => 1]))->assertOk()->assertSee('Confirm your details');
    }

    private function postDetails(): array
    {
        return [
            'designation'            => 'Research Scientist II',
            'research_center'        => 'Mwanza Centre',
            'department'             => 'Health Systems',
            'phone'                  => '+255 712 000 111',
            'employment_type'        => 'contract',
            'first_appointment_date' => now()->subYears(3)->toDateString(),
        ];
    }

    public function test_completing_the_profile_releases_the_user(): void
    {
        $this->get($this->nreimsReturns($this->neema()));

        $this->post(route('profile.complete.store'), [])->assertSessionHasErrors([
            'designation', 'research_center', 'department', 'phone', 'employment_type', 'first_appointment_date',
        ]);

        $this->post(route('profile.complete.store'), [
            'designation'            => 'Research Scientist II',
            'research_center'        => 'Mwanza Centre',
            'department'             => 'Health Systems',
            'phone'                  => '+255 712 000 111',
            'employment_type'        => 'contract',
            'first_appointment_date' => now()->subYears(3)->toDateString(),
        ])->assertRedirect(route('profile.complete.ready'));

        $user = User::where('nreims_id', 4812)->first();
        $this->assertFalse($user->needsProfileCompletion());
        $this->assertTrue($user->withinSeedGrantServiceLimit());
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_a_returning_user_is_matched_on_the_nreims_id_and_kept_in_step(): void
    {
        $user = $this->user('pi', ['email' => 'old@nimr.or.tz', 'name' => 'Old Name', 'phone' => '+255700000000']);
        $user->forceFill(['nreims_id' => 4812, 'profile_completed_at' => now()])->save();

        $this->get($this->nreimsReturns($this->neema()))->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('Neema A. Mushi', $user->name);
        $this->assertSame('neema.mushi@nimr.or.tz', $user->email);
        $this->assertSame('+255700000000', $user->phone, 'A phone set in the portal is kept.');
        $this->assertNotNull($user->last_login_at);
    }

    public function test_an_unverified_nreims_email_is_refused(): void
    {
        $added = $this->user('research_scientist', ['email' => 'neema.mushi@nimr.or.tz']);

        $this->get($this->nreimsReturns($this->neema(['email_verified_at' => null])))
            ->assertRedirect(route('login'))
            ->assertSessionHas('sso_error');

        $this->assertGuest();
        $this->assertNull($added->fresh()->nreims_id, 'An unverified email must not link an account.');
        $this->assertSame(1, User::where('email', 'neema.mushi@nimr.or.tz')->count(), 'No second account is made.');
    }

    public function test_a_person_gmu_added_is_linked_on_their_first_verified_sign_in(): void
    {
        $added = $this->user('research_scientist', [
            'email' => 'neema.mushi@nimr.or.tz', 'name' => 'Neema Mushi', 'must_change_password' => true,
        ]);

        $this->get($this->nreimsReturns($this->neema()))->assertRedirect(route('dashboard'));

        $added->refresh();
        $this->assertAuthenticatedAs($added);
        $this->assertSame(4812, $added->nreims_id);
        $this->assertSame('research_scientist', $added->role->name, 'The role GMU gave is kept.');
        $this->assertFalse($added->must_change_password, 'They sign in with NREIMS now.');
        $this->assertFalse($added->needsProfileCompletion(), 'Staff and reviewers GMU added skip the applicant form.');
        $this->assertSame(1, User::where('email', 'neema.mushi@nimr.or.tz')->count());

        // Next time they are matched on the NREIMS id.
        auth()->logout();
        $this->get($this->nreimsReturns($this->neema()))->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($added->fresh());
    }

    public function test_an_applicant_gmu_added_still_completes_the_profile(): void
    {
        $this->user('pi', ['email' => 'neema.mushi@nimr.or.tz']);

        $this->get($this->nreimsReturns($this->neema()))->assertRedirect(route('profile.complete'));
    }

    public function test_an_email_held_by_another_nreims_user_is_refused(): void
    {
        $other = $this->user('pi', ['email' => 'neema.mushi@nimr.or.tz']);
        $other->forceFill(['nreims_id' => 9999])->save();

        $this->get($this->nreimsReturns($this->neema()))->assertSessionHas('sso_error');

        $this->assertGuest();
        $this->assertSame(9999, $other->fresh()->nreims_id);
    }

    public function test_a_deactivated_account_gmu_added_cannot_be_linked(): void
    {
        $added = $this->user('research_scientist', ['email' => 'neema.mushi@nimr.or.tz', 'is_active' => false]);

        $this->get($this->nreimsReturns($this->neema()))->assertSessionHas('sso_error');

        $this->assertGuest();
        $this->assertNull($added->fresh()->nreims_id);
    }

    public function test_gmu_adds_a_person_without_a_password_when_nreims_is_on(): void
    {
        $admin = $this->user('coordinator');
        $role  = Role::firstOrCreate(['name' => 'research_scientist'], ['display_name' => 'Research Scientist']);

        $this->actingAs($admin)->get(route('admin.users.create'))->assertOk()->assertSee('Add a person');

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Dr Peter Mboya', 'email' => 'P.Mboya@nimr.or.tz', 'role_id' => $role->id, 'is_active' => 1,
        ])->assertRedirect(route('admin.users.index'))->assertSessionHasNoErrors();

        $person = User::where('email', 'p.mboya@nimr.or.tz')->firstOrFail();
        $this->assertNull($person->nreims_id);
        $this->assertFalse($person->must_change_password);

        $this->actingAs($admin)->get(route('admin.users.index'))->assertSee('Not signed in yet');
    }

    public function test_a_password_is_still_required_when_nreims_is_off(): void
    {
        config(['services.nreims.client_id' => null]);
        $admin = $this->user('coordinator');
        $role  = Role::firstOrCreate(['name' => 'research_scientist'], ['display_name' => 'Research Scientist']);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Dr Peter Mboya', 'email' => 'p.mboya@nimr.or.tz', 'role_id' => $role->id,
        ])->assertSessionHasErrors('password');
    }

    public function test_a_deactivated_account_cannot_sign_in(): void
    {
        $user = $this->user('pi', ['is_active' => false]);
        $user->forceFill(['nreims_id' => 4812])->save();

        $this->get($this->nreimsReturns($this->neema()))->assertSessionHas('sso_error');
        $this->assertGuest();
    }

    public function test_a_forged_or_missing_state_is_refused(): void
    {
        Http::fake();
        $this->withSession(['nreims_oauth_state' => 'state-abc'])
            ->get(route('nreims.callback', ['code' => 'c', 'state' => 'not-the-state']))
            ->assertSessionHas('sso_error');

        $this->assertGuest();
        Http::assertNothingSent();
    }

    public function test_nreims_failing_to_issue_a_token_is_handled(): void
    {
        $this->get($this->nreimsReturns($this->neema(), 400))
            ->assertRedirect(route('login'))
            ->assertSessionHas('sso_error');

        $this->assertGuest();
    }

    public function test_local_accounts_are_not_sent_to_the_profile_form(): void
    {
        $admin = $this->user('coordinator');

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('profile.complete'))->assertRedirect(route('dashboard'));
    }

    public function test_sso_routes_are_off_until_configured(): void
    {
        config(['services.nreims.client_id' => null]);

        $this->get(route('nreims.redirect'))->assertNotFound();
    }
}
