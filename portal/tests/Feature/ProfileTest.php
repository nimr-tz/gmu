<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_staff_details_are_saved_and_the_joining_month_keeps_its_day(): void
    {
        $user = User::factory()->create(['first_appointment_date' => '2022-03-17']);

        $this->actingAs($user)->patch('/profile', [
            'name' => 'Dr Test User', 'email' => $user->email,
            'designation' => 'Research Scientist I', 'research_center' => 'Amani Centre',
            'employment_type' => 'permanent', 'joined' => '2022-03',
        ])->assertSessionHasNoErrors()->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame('Amani Centre', $user->research_center);
        $this->assertSame('2022-03-17', $user->first_appointment_date->toDateString(), 'Same month: the exact day is kept.');

        $this->actingAs($user)->patch('/profile', ['name' => 'Dr Test User', 'email' => $user->email, 'joined' => '2021-07'])->assertSessionHasNoErrors();
        $this->assertSame('2021-07-01', $user->fresh()->first_appointment_date->toDateString());
    }

    public function test_an_nreims_account_cannot_change_its_email_here_and_cannot_close_itself(): void
    {
        $user = User::factory()->create(['nreims_id' => 'NR-1', 'profile_completed_at' => now()]);

        $this->actingAs($user)->patch('/profile', ['name' => 'X', 'email' => 'other@example.com'])->assertSessionHasErrors('email');
        $this->actingAs($user)->patch('/profile', ['name' => 'X'])->assertSessionHasNoErrors();
        $this->assertSame('X', $user->fresh()->name);

        $this->actingAs($user)->delete('/profile')->assertStatus(405);
        $this->assertTrue($user->fresh()->is_active);
    }
}
