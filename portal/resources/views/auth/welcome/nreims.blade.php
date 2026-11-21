@extends('auth.welcome.layout')

@section('title', 'From NREIMS')

@section('content')
    <p class="wl-eyebrow">Step 2 of {{ count($steps) }}</p>
    <h1 class="wl-title">We already know a little about you.</h1>
    <p class="wl-lede">
        You signed in with NREIMS, so there is no need to type what you have already told us.
        Here is what we brought across.
    </p>

    <ul class="wl-facts">
        <li>
            <span class="ic"><i class="fas fa-user" aria-hidden="true"></i></span>
            <span><span class="k">Full name</span><span class="v">{{ $user->name }}</span></span>
            <span class="src"><i class="fas fa-lock" aria-hidden="true"></i> NREIMS</span>
        </li>
        <li>
            <span class="ic"><i class="fas fa-envelope" aria-hidden="true"></i></span>
            <span><span class="k">Email</span><span class="v">{{ $user->email }}</span></span>
            <span class="src"><i class="fas fa-lock" aria-hidden="true"></i> NREIMS</span>
        </li>
        <li>
            <span class="ic"><i class="fas fa-phone" aria-hidden="true"></i></span>
            <span><span class="k">Phone</span>
                @if($user->phone)
                    <span class="v mono">{{ $user->phone }}</span>
                @else
                    <span class="v missing">Not in NREIMS. You can add it on the next step.</span>
                @endif
            </span>
            @if($user->phone)<span class="src"><i class="fas fa-lock" aria-hidden="true"></i> NREIMS</span>@endif
        </li>
    </ul>

    <p class="wl-note">Something not right? Update it in NREIMS and it will follow you here the next time you sign in.</p>
    <p class="wl-privacy">
        <i class="fas fa-shield-alt" aria-hidden="true"></i>
        <span>Only the Grants Management Unit sees these details. We use them to confirm your eligibility and to keep in touch about your application, nothing else.</span>
    </p>

    <div class="wl-actions">
        <a href="{{ route('profile.complete') }}" class="wl-back"><i class="fas fa-arrow-left" aria-hidden="true"></i> Back</a>
        <a href="{{ route('profile.complete.details') }}" class="btn btn-primary">
            Yes, that’s me <i class="fas fa-arrow-right" aria-hidden="true"></i>
        </a>
    </div>
@endsection
