@extends('auth.welcome.layout')

{{-- Step 1 of the welcome: confirm their post at NIMR. Asked once; GMU uses it to check eligibility. --}}

@section('title', 'Confirm your details')

@php
    $initials = strtoupper(collect(explode(' ', preg_replace('/^(Dr\.?|Prof\.?|Mr\.?|Ms\.?|Mrs\.?)\s+/i', '', $user->name)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode(''));
    $field = 'h-[42px] rounded-[9px] text-sm';
    $missing = $errors->count();
@endphp

@section('content')
    @unless(request()->boolean('change'))
        <a href="{{ route('profile.complete.nreims') }}" class="inline-flex items-center gap-1 self-start text-[13px] text-muted-foreground hover:text-foreground"><x-icon name="arrow-left" class="size-3.5" /> Back</a>
        <p class="mt-4 text-[13px] text-muted-foreground">Step 3 of {{ count($steps) }}</p>
    @endunless
    <h1 class="mt-1 text-[32px] font-semibold leading-tight tracking-[-0.03em]">Confirm your details</h1>
    <p class="mt-2.5 text-[15px] text-muted-foreground">To make NIMR Grants work well for you, we need a few details about your post at NIMR. You give them once, and we won’t ask again. GMU also uses them to check eligibility.</p>

    @if($missing)
        <div role="alert" class="mt-5 rounded-xl border border-danger-line bg-danger-soft px-3.5 py-3 text-[13.5px] font-medium text-danger-ink">
            {{ $missing === 1 ? 'One field still needs an answer.' : $missing . ' fields still need an answer.' }}
            <ul class="mt-1 list-disc pl-4 font-normal">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- What came from NREIMS --}}
    <div class="mt-7 flex items-center gap-3.5 rounded-2xl border border-blue-100 bg-blue-50/60 px-4 py-3.5">
        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-primary text-sm font-semibold text-white">{{ $initials }}</span>
        <div class="min-w-0 flex-1 leading-snug">
            <p class="text-[15px] font-semibold">{{ $user->name }}</p>
            <p class="truncate text-[13px] text-muted-foreground">{{ $user->email }}</p>
        </div>
        <span class="inline-flex h-6 shrink-0 items-center gap-1.5 whitespace-nowrap rounded-md bg-card px-2 text-xs font-medium text-blue-700 ring-1 ring-blue-100"><x-icon name="lock" class="size-3" />From NREIMS</span>
    </div>

    <form method="POST" action="{{ route('profile.complete.store') }}" class="mt-5" novalidate>
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="flex flex-col gap-1.5"><span class="text-[13px] font-medium">Designation</span>
                <select name="designation" class="select {{ $field }}" @error('designation') aria-invalid="true" @enderror>
                    <option value="">Choose…</option>
                    @foreach($designations as $d)<option value="{{ $d }}" @selected(old('designation', $user->designation) === $d)>{{ $d }}</option>@endforeach
                </select>
            </label>
            <label class="flex flex-col gap-1.5"><span class="text-[13px] font-medium">NIMR centre</span>
                <select name="research_center" class="select {{ $field }}" @error('research_center') aria-invalid="true" @enderror>
                    <option value="">Choose…</option>
                    @foreach($centres as $c)<option value="{{ $c }}" @selected(old('research_center', $user->research_center) === $c)>{{ $c }}</option>@endforeach
                </select>
            </label>
            <label class="flex flex-col gap-1.5"><span class="text-[13px] font-medium">Department or section</span>
                <input name="department" maxlength="255" value="{{ old('department', $user->department) }}" class="input {{ $field }}" @error('department') aria-invalid="true" @enderror>
            </label>
            <label class="flex flex-col gap-1.5"><span class="text-[13px] font-medium">Mobile phone</span>
                <input name="phone" inputmode="tel" placeholder="+255 712 345 678" value="{{ old('phone', $user->phone) }}" class="input {{ $field }} font-mono" @error('phone') aria-invalid="true" @enderror>
                <span class="text-xs text-muted-foreground">For reminders about deadlines and review requests.</span>
            </label>
            <fieldset class="flex flex-col gap-1.5">
                <legend class="mb-1.5 text-[13px] font-medium">Employment</legend>
                <div class="grid h-[42px] grid-cols-2 rounded-[9px] border bg-gray-100 p-[3px] {{ $errors->has('employment_type') ? 'border-danger' : 'border-gray-100' }}">
                    @foreach(\App\Models\User::EMPLOYMENT_TYPES as $value => $label)
                        <label class="grid cursor-pointer place-items-center rounded-[7px] text-[13.5px] font-medium text-muted-foreground transition-colors has-[:checked]:bg-card has-[:checked]:text-foreground has-[:checked]:shadow-sm has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring">
                            <input type="radio" name="employment_type" value="{{ $value }}" class="sr-only" @checked(old('employment_type', $user->employment_type) === $value)>{{ $label }}
                        </label>
                    @endforeach
                </div>
            </fieldset>
            <label class="flex flex-col gap-1.5"><span class="text-[13px] font-medium">First appointed at NIMR</span>
                <input type="date" name="first_appointment_date" max="{{ now()->toDateString() }}" value="{{ old('first_appointment_date', $user->first_appointment_date?->toDateString()) }}" class="input {{ $field }}" @error('first_appointment_date') aria-invalid="true" @enderror>
            </label>
        </div>

        <button type="submit" class="mt-8 flex h-[52px] w-full items-center justify-center gap-2 rounded-xl bg-primary text-[15px] font-semibold text-white shadow-cta transition-colors hover:bg-blue-700">
            Save and continue <x-icon name="arrow-right" class="size-4" />
        </button>
        <p class="mt-3 text-center text-[12.5px] text-muted-foreground">Name or email wrong? Change it in NREIMS.</p>
    </form>
@endsection
