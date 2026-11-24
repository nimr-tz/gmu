<?php

namespace App\Notifications;

use App\Mail\SeedGrantNotice;
use Illuminate\Auth\Notifications\ResetPassword;

/**
 * The password-reset email for local-password accounts (the GMU administrator), in the
 * owner's "Email" design instead of Laravel's default: ink band, "Action needed", one
 * button to set a new password, and what to do if it wasn't them.
 */
class AdminPasswordReset extends ResetPassword
{
    public function toMail($notifiable): SeedGrantNotice
    {
        $minutes = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire');

        return (new SeedGrantNotice(
            recipientName: $notifiable->name,
            headline: 'Set a new password',
            body: 'Someone asked to reset the password on your NIMR Grants administrator account. Use the button to set a new one. The link works once.',
            actionUrl: route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]),
            reference: 'Administrator',
            button: 'Set a new password',
            style: SeedGrantNotice::KINDS['gmu'],
            facts: [['Account', $notifiable->getEmailForPasswordReset()], ['Link works for', $minutes . ' minutes']],
            why: 'If you did not ask for this, ignore this email; your password stays as it is.',
            signIn: 'from Admin login',
            inPortal: false,
        ))->subject('Set a new password · NIMR Grants')->to($notifiable->getEmailForPasswordReset());
    }
}
