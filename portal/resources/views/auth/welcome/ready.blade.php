@extends('auth.welcome.layout')

@section('title', 'You’re all set')

@section('content')
    <p class="wl-eyebrow">Step 4 of {{ count($steps) }}</p>
    <h1 class="wl-title">You’re all set, {{ \Illuminate\Support\Str::of($user->name)->before(' ') }}.</h1>
    <p class="wl-lede">Your profile is complete.</p>

    <div class="wl-summary" aria-label="Your details">
        @if($user->designation)<span>{{ $user->designation }}</span>@endif
        @if($user->research_center)<span>{{ $user->research_center }}</span>@endif
        @if($user->department)<span>{{ $user->department }}</span>@endif
        @if($user->employment_type)<span>{{ \App\Models\User::EMPLOYMENT_TYPES[$user->employment_type] ?? '' }}</span>@endif
        @if($user->first_appointment_date)<span>At NIMR since {{ $user->first_appointment_date->format('j M Y') }}</span>@endif
    </div>

    <div class="wl-actions end">
        <a href="{{ route('dashboard') }}" class="btn btn-primary">Enter NIMR Grants <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
    </div>
@endsection
