<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A person's own details (the owner's "Staff Pages" design): shown on their applications and
 * agreement, and used to check eligibility. The email comes from NREIMS for linked accounts.
 */
class ProfileUpdateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'                   => ['required', 'string', 'max:255'],
            // NREIMS holds the address of a linked account; only a local account changes it here.
            'email'                  => [$this->user()->nreims_id ? 'prohibited' : 'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class)->ignore($this->user()->id)],
            'designation'            => ['nullable', 'string', Rule::in(config('gmu.designations'))],
            'research_center'        => ['nullable', 'string', Rule::in(config('gmu.centres'))],
            'department'             => ['nullable', 'string', 'max:255'],
            'phone'                  => ['nullable', 'string', 'max:20'],
            'employment_type'        => ['nullable', Rule::in(array_keys(User::EMPLOYMENT_TYPES))],
            'joined'                 => ['nullable', 'date_format:Y-m', 'before_or_equal:' . now()->format('Y-m'), 'after:1960-01'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'Enter your full name.',
            'joined.before_or_equal' => 'The month you joined cannot be in the future.',
            'email.prohibited'       => 'Your email comes from NREIMS. Change it there.',
        ];
    }
}
