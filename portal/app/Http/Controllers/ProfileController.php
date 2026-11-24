<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Save a person's own details. The month they joined NIMR keeps the exact day
     * already on record when the month is unchanged; a new month is stored as its first day.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if (array_key_exists('joined', $data)) {
            $joined = $data['joined'];
            if ($joined === null) {
                $user->first_appointment_date = null;
            } elseif ($user->first_appointment_date?->format('Y-m') !== $joined) {
                $user->first_appointment_date = $joined . '-01';
            }
            unset($data['joined']);
        }

        $user->fill($data);
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    // Closing one's own account was removed (the owner's "Staff Pages" design): only the
    // GMU Coordinator deactivates an account, from People and roles, and the record is kept (finding C5).
}
