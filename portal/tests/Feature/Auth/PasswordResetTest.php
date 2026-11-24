<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\AdminPasswordReset as ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_the_reset_email_uses_the_email_design(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $html = $notification->toMail($user)->render();
            $this->assertStringContainsString('Set a new password', $html);
            $this->assertStringContainsString('Action needed', $html);
            $this->assertStringContainsString(route('password.reset', ['token' => $notification->token, 'email' => $user->email]), html_entity_decode($html));

            return true;
        });
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->get('/reset-password/'.$notification->token.'?email='.urlencode($user->email))->assertOk()->assertSee('Set a new password');
            $this->get('/reset-password/wrong-token?email='.urlencode($user->email))->assertOk()->assertSee('This link has expired');

            return true;
        });
    }

    public function test_an_nreims_account_gets_no_reset_link_but_the_same_answer(): void
    {
        Notification::fake();

        $user = User::factory()->create(['nreims_id' => 'NR-9']);

        $this->post('/forgot-password', ['email' => $user->email])->assertRedirect(route('password.request'));
        $this->get('/forgot-password')->assertSee('Check your email');
        $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertRedirect(route('password.request'));

        Notification::assertNothingSent();
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create(['must_change_password' => true]);

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'New-password-2026',
                'password_confirmation' => 'New-password-2026',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('password.changed'));
            $this->get(route('password.changed'))->assertOk()->assertSee('Password changed');

            $this->assertFalse($user->fresh()->must_change_password);

            return true;
        });
    }
}
