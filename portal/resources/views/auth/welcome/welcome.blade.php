@extends('auth.welcome.layout')

@section('title', 'Welcome')
@section('card-class', 'wl-hero')

@section('content')
    <img src="{{ asset('images/nimr-logo.png') }}" alt="National Institute for Medical Research" class="wl-crest">

    <p class="wl-eyebrow">National Institute for Medical Research</p>
    <h1 class="wl-title">
        {{ $greeting }}, {{ \Illuminate\Support\Str::of($user->name)->before(' ') }}.
        <span class="soft">Welcome to NIMR Grants.</span>
    </h1>
    <p class="wl-lede">
        Every grant NIMR opens to its researchers will be here, ready for you to apply.
    </p>

    <div class="wl-actions end">
        <a href="{{ route('profile.complete.nreims') }}" class="btn btn-primary">
            Get started <i class="fas fa-arrow-right" aria-hidden="true"></i>
        </a>
    </div>
@endsection
