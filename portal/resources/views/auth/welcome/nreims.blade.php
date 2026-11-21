@extends('auth.welcome.layout')

@section('title', 'From NREIMS')

@section('content')
    <p class="wl-eyebrow">Step 2 of {{ count($steps) }}</p>
    <h1 class="wl-title">We already know a little about you.</h1>
    <p class="wl-lede">You signed in with NREIMS, so there is no need to type what you have already told us. Here is what we brought across.</p>

    <section class="wl-record" aria-label="From your NREIMS account">
        <div class="wl-record-head">
            <strong><i class="fas fa-lock" aria-hidden="true"></i> From your NREIMS account</strong>
            <span>Read only</span>
        </div>
        <dl>
            <div><dt>Full name</dt><dd>{{ $user->name }}</dd></div>
            <div><dt>Email</dt><dd>{{ $user->email }}</dd></div>
            <div><dt>Phone</dt>
                @if($user->phone)
                    <dd class="mono">{{ $user->phone }}</dd>
                @else
                    <dd class="missing">Not in NREIMS. You can add it on the next step.</dd>
                @endif
            </div>
        </dl>
    </section>

    <p class="wl-note">Something not right? Update it in NREIMS and it will follow you here the next time you sign in.</p>
    <p class="wl-privacy">
        <i class="fas fa-shield-alt" aria-hidden="true"></i>
        <span>Only the Grants Management Unit sees these details. We use them to confirm your eligibility and to keep in touch about your application, nothing else.</span>
    </p>

    <div class="wl-actions">
        <a href="{{ route('profile.complete') }}" class="wl-back"><i class="fas fa-arrow-left" aria-hidden="true"></i> Back</a>
        <a href="{{ route('profile.complete.details') }}" class="wl-go">Yes, that’s me <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
    </div>
@endsection
