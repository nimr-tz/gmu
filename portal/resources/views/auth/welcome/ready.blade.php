@extends('auth.welcome.layout')

@section('title', 'You’re all set')

@section('content')
    <span class="wl-done" aria-hidden="true"><i class="fas fa-check"></i></span>
    <p class="wl-eyebrow">Step 4 of {{ count($steps) }}</p>
    <h1 class="wl-title">You’re all set, {{ \Illuminate\Support\Str::of($user->name)->before(' ') }}.</h1>
    <p class="wl-lede">Your profile is complete.</p>

    <dl class="wl-summary" aria-label="Your details">
        <div><dt>Designation</dt><dd>{{ $user->designation ?: '—' }}</dd></div>
        <div><dt>NIMR centre</dt><dd>{{ $user->research_center ?: '—' }}</dd></div>
        <div><dt>Department or section</dt><dd>{{ $user->department ?: '—' }}</dd></div>
        <div><dt>Employment</dt><dd>{{ \App\Models\User::EMPLOYMENT_TYPES[$user->employment_type] ?? '—' }}</dd></div>
        <div><dt>At NIMR since</dt><dd>{{ $user->first_appointment_date?->format('j M Y') ?? '—' }}</dd></div>
        <div><dt>Mobile phone</dt><dd class="mono">{{ $user->phone ?: '—' }}</dd></div>
    </dl>

    <div class="wl-actions end">
        <a href="{{ route('dashboard') }}" class="wl-go">Enter NIMR Grants <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
    </div>
@endsection
