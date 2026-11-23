<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The welcome a member of staff walks through after their first NREIMS sign-in,
 * in two steps (the owner's "Onboarding" design):
 *
 *   1. details   confirm their post at NIMR: the only step that saves
 *   2. ready     a summary, and the way in
 *
 * Step 1 is for someone who has not finished yet, or who chose "Change my
 * details" on step 2. The old welcome and NREIMS step addresses lead to step 1.
 */
class ProfileCompletionController extends Controller
{
    /** The step list in the blue panel. */
    public const STEPS = ['Confirm your details', 'Enter the portal'];

    /** Step 1: their details, with what came from NREIMS shown above the form. */
    public function welcome(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isNreimsLinked() || (! $user->needsProfileCompletion() && ! $request->boolean('change'))) {
            return redirect()->route('dashboard');
        }

        return view('auth.welcome.details', [
            'user'         => $user,
            'steps'        => self::STEPS,
            'current'      => 0,
            'greeting'     => $this->greeting(),
            'designations' => config('gmu.designations'),
            'centres'      => config('gmu.centres'),
        ]);
    }

    /** The earlier "From NREIMS" and "About you" addresses: both are step 1 now. */
    public function nreims(Request $request): RedirectResponse
    {
        return redirect()->route('profile.complete', $request->only('change'));
    }

    public function edit(Request $request): RedirectResponse
    {
        return redirect()->route('profile.complete', $request->only('change'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isNreimsLinked(), 404);

        $data = $request->validate([
            'designation'            => ['required', 'string', Rule::in(config('gmu.designations'))],
            'research_center'        => ['required', 'string', Rule::in(config('gmu.centres'))],
            'department'             => ['required', 'string', 'max:255'],
            'phone'                  => ['required', 'string', 'max:30', 'regex:/^\+?[0-9 ()-]{9,}$/'],
            'employment_type'        => ['required', Rule::in(array_keys(User::EMPLOYMENT_TYPES))],
            'first_appointment_date' => ['required', 'date', 'before_or_equal:today', 'after:1960-01-01'],
        ], [
            'department.required' => 'Enter your section.',
            'research_center.required' => 'Choose your centre.',
            'phone.regex' => 'Enter a phone number with digits only, for example +255 712 345 678.',
            'first_appointment_date.before_or_equal' => 'The date of first appointment cannot be in the future.',
        ]);

        $user->fill($data);
        $user->profile_completed_at ??= now();
        $user->save();

        return redirect()->route('profile.complete.ready');
    }

    public function ready(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->needsProfileCompletion()) {
            return redirect()->route('profile.complete');
        }

        return view('auth.welcome.ready', ['user' => $user, 'steps' => self::STEPS, 'current' => 1, 'greeting' => $this->greeting()]);
    }

    /** Good morning, afternoon or evening, in East Africa Time. */
    private function greeting(): string
    {
        $hour = (int) now()->format('G');

        return match (true) {
            $hour < 12 => 'Good morning',
            $hour < 17 => 'Good afternoon',
            default    => 'Good evening',
        };
    }
}
