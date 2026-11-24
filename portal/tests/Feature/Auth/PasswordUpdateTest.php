<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'New-password-2026',
                'password_confirmation' => 'New-password-2026',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue(Hash::check('New-password-2026', $user->refresh()->password));
    }

    public function test_a_weak_password_is_refused(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/profile')->put('/password', [
            'current_password' => 'password', 'password' => 'short1', 'password_confirmation' => 'short1',
        ])->assertSessionHasErrorsIn('updatePassword', 'password');

        // 12 characters and both cases, but no number or symbol.
        $this->actingAs($user)->from('/profile')->put('/password', [
            'current_password' => 'password', 'password' => 'LongButPlainPassword', 'password_confirmation' => 'LongButPlainPassword',
        ])->assertSessionHasErrorsIn('updatePassword', 'password');
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'New-password-2026',
                'password_confirmation' => 'New-password-2026',
            ]);

        $response
            ->assertSessionHasErrorsIn('updatePassword', 'current_password')
            ->assertRedirect('/profile');
    }
}
