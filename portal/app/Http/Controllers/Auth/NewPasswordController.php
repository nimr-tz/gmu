<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * Setting a new local password from a reset link (the owner's "Password Reset" design):
 * the form, or "This link has expired" when the link is used, old or wrong, and afterwards
 * "Password changed" with every other session signed out.
 */
class NewPasswordController extends Controller
{
    public function create(Request $request): View
    {
        $email = (string) $request->query('email', '');
        $user = User::where('email', $email)->first();
        $valid = $user && ! $user->nreims_id && Password::broker()->tokenExists($user, (string) $request->route('token'));

        return view('auth.reset-password', [
            'state' => $valid ? 'set' : 'expired',
            'token' => $request->route('token'),
            'email' => $email,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'password.confirmed' => 'The two passwords don’t match.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                    'must_change_password' => false,
                ])->save();

                // "Any other signed-in sessions have been signed out."
                if (config('session.driver') === 'database') {
                    DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
                }

                event(new PasswordReset($user));
            }
        );

        // A used, old or wrong link shows "This link has expired" on the link's own page.
        return $status === Password::PASSWORD_RESET
            ? redirect()->route('password.changed')
            : redirect()->route('password.reset', ['token' => $request->input('token'), 'email' => $request->input('email')]);
    }

    public function changed(): View
    {
        return view('auth.reset-password', ['state' => 'done', 'token' => null, 'email' => null]);
    }
}
