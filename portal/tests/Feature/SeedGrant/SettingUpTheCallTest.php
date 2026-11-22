<?php

namespace Tests\Feature\SeedGrant;

use App\Models\Seed\SeedCall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsGrantFixtures;
use Tests\TestCase;

/**
 * Before the first call exists (a fresh production install), GMU must still
 * have a way to create it, and nobody else should see a broken page.
 */
class SettingUpTheCallTest extends TestCase
{
    use RefreshDatabase, BuildsGrantFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        config(['gmu.external_grants' => false]);
    }

    public function test_gmu_is_led_to_set_up_the_first_call(): void
    {
        $gmu = $this->user('coordinator');
        $this->assertNull(SeedCall::current());

        $this->actingAs($gmu)->get(route('dashboard'))->assertOk()
            ->assertSee('No seed grant call is set up yet.')->assertSee(route('seed.calls.create'), false);
        $this->actingAs($gmu)->get(route('seed.call'))->assertRedirect(route('seed.calls.create'));
        $this->actingAs($gmu)->get(route('seed.calls.create'))->assertOk()
            ->assertSee('New call')->assertSee('Create the call');

        $this->actingAs($gmu)->post(route('seed.calls.store'), [
            'title' => 'Early-Career Researchers’ Implementation Science Seed Grant', 'code' => '2627', 'fiscal_year' => '2026/27',
            'min_amount' => 2_000_000, 'max_amount' => 10_000_000, 'duration_months' => 12, 'equipment_cap_percent' => 20,
            'status' => 'draft',
        ])->assertSessionHasNoErrors();

        $call = SeedCall::current();
        $this->assertSame('2627', $call?->code);
        $this->actingAs($gmu)->get(route('seed.calls.edit', $call))->assertOk()->assertSee('Call settings');
    }

    public function test_others_see_no_call_page_and_cannot_create_one(): void
    {
        $researcher = $this->user('pi');

        $this->actingAs($researcher)->get(route('seed.call'))->assertNotFound();
        $this->actingAs($researcher)->get(route('seed.calls.create'))->assertForbidden();
        $this->actingAs($researcher)->get(route('dashboard'))->assertOk()->assertDontSee('Set up the call');
    }
}
