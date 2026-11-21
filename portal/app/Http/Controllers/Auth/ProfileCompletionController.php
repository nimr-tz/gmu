<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The welcome a member of staff walks through after their first NREIMS
 * sign-in, in four steps:
 *
 *   1. welcome   what NIMR Grants is
 *   2. nreims    what came across from NREIMS (name, email, phone)
 *   3. details   their post at NIMR: the only step that saves
 *   4. ready     a summary, and the way in
 *
 * Steps 1 to 3 are for someone who has not finished yet; step 4 is shown once
 * they have. Each step is its own page, so Back and a refresh both work.
 */
class ProfileCompletionController extends Controller
{
    public const STEPS = ['Welcome', 'From NREIMS', 'About you', 'All set'];

    public function welcome(Request $request): View|RedirectResponse
    {
        return $this->step($request, 0, 'auth.welcome.welcome', [
            'greeting' => $this->greeting(),
        ]);
    }

    public function nreims(Request $request): View|RedirectResponse
    {
        return $this->step($request, 1, 'auth.welcome.nreims');
    }

    public function edit(Request $request): View|RedirectResponse
    {
        return $this->step($request, 2, 'auth.welcome.details', [
            'designations' => config('gmu.designations'),
            'centres'      => config('gmu.centres'),
        ]);
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

        return view('auth.welcome.ready', ['user' => $user, 'steps' => self::STEPS, 'current' => 3]);
    }

    /** Steps 1 to 3: only for an NREIMS account that has not finished the welcome. */
    private function step(Request $request, int $index, string $view, array $data = []): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isNreimsLinked() || ! $user->needsProfileCompletion()) {
            return redirect()->route('dashboard');
        }

        return view($view, $data + ['user' => $user, 'steps' => self::STEPS, 'current' => $index]);
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
