@extends('auth.welcome.layout')

@section('title', 'You’re ready')

@section('content')
    <p class="wl-eyebrow">Step 4 of {{ count($steps) }}</p>
    <h1 class="wl-title">You’re all set, {{ \Illuminate\Support\Str::of($user->name)->before(' ') }}.</h1>
    <p class="wl-lede">Your profile is complete. Here is the road ahead, and we will walk it with you.</p>

    <div class="wl-summary" aria-label="Your details">
        @if($user->designation)<span>{{ $user->designation }}</span>@endif
        @if($user->research_center)<span>{{ $user->research_center }}</span>@endif
        @if($user->employment_type)<span>{{ \App\Models\User::EMPLOYMENT_TYPES[$user->employment_type] ?? '' }}</span>@endif
        @if($user->first_appointment_date)<span>At NIMR since {{ $user->first_appointment_date->format('j M Y') }}</span>@endif
    </div>

    <h2 class="wl-road-title">How a seed grant application moves</h2>
    <ol class="wl-road">
        @foreach(\App\Support\SeedGrant::TRACK as [$title, $sub])
            <li><span class="t">{{ $title }}</span><span class="s">{{ $sub }}</span></li>
        @endforeach
    </ol>

    <div class="wl-actions">
        @if($canStart)
            <a href="{{ route('seed.call') }}" class="wl-back">Read the call first</a>
            <form method="POST" action="{{ route('seed.apply') }}">
                @csrf
                <button type="submit" class="btn btn-primary">Start your concept note <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
            </form>
        @elseif($hasApplication)
            <span></span>
            <a href="{{ route('seed.mine') }}" class="btn btn-primary">Go to your application <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        @else
            @if($call)<a href="{{ route('seed.call') }}" class="wl-back">Read the call</a>@else<span></span>@endif
            <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to your dashboard <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        @endif
    </div>

    <p class="wl-contact">Questions at any point? The Grants Management Unit is at <a href="mailto:grantsunit@nimr.or.tz">grantsunit@nimr.or.tz</a>.</p>
@endsection
