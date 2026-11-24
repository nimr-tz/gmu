<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * "Forgot your password?" for accounts with a local password: the GMU administrator, and any
 * fallback account the Coordinator gave one. Staff sign in with NREIMS and reset there.
 */
class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a reset link to a local-password account. The answer is always "Check your email",
     * whatever the address, so the page never tells anyone which accounts exist.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']], [
            'email.required' => 'Enter your email.',
            'email.email'    => 'Enter a valid email address.',
        ]);
        $email = mb_strtolower(trim($data['email']));

        $user = User::where('email', $email)->first();
        if ($user && $user->is_active && ! $user->nreims_id) {
            Password::sendResetLink(['email' => $user->email]);
        }

        return redirect()->route('password.request')
            ->with('reset_sent', $email)
            ->with('reset_resent', $request->boolean('resend'));
    }
}
