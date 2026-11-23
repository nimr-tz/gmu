@extends('auth.welcome.layout')

{{-- Step 2 of the welcome: a summary of what they confirmed, and the way in. --}}

@section('title', 'You’re all set')

@section('content')
    <span class="grid size-14 place-items-center rounded-2xl bg-primary text-white ring-8 ring-blue-50"><x-icon name="check" class="size-7" /></span>
    <h1 class="mt-7 text-[32px] font-semibold leading-tight tracking-[-0.03em]">You’re all set</h1>
    <p class="sr-only">Your profile is complete.</p>

    <dl class="mt-6 grid grid-cols-[repeat(auto-fit,minmax(170px,1fr))] gap-px overflow-hidden rounded-2xl border border-border bg-border">
        @foreach([
            'Designation'           => $user->designation,
            'NIMR centre'           => $user->research_center,
            'Department or section' => $user->department,
            'Employment'            => \App\Models\User::EMPLOYMENT_TYPES[$user->employment_type] ?? null,
            'At NIMR since'         => $user->first_appointment_date?->format('j M Y'),
            'Mobile phone'          => $user->phone,
        ] as $label => $value)
            <div class="bg-card px-4 py-3.5"><dt class="text-[12.5px] text-muted-foreground">{{ $label }}</dt><dd class="mt-0.5 text-[14.5px] font-medium">{{ $value ?: '—' }}</dd></div>
        @endforeach
    </dl>

    <a href="{{ route('dashboard') }}" class="mt-8 flex h-[52px] items-center justify-center gap-2 rounded-xl bg-primary text-[15px] font-semibold text-white shadow-cta transition-colors hover:bg-blue-700">
        Enter NIMR Grants <x-icon name="arrow-right" class="size-4" />
    </a>
    <a href="{{ route('profile.complete', ['change' => 1]) }}" class="mt-2.5 flex h-10 items-center justify-center text-[13.5px] font-medium text-muted-foreground hover:text-foreground">Change my details</a>
@endsection
