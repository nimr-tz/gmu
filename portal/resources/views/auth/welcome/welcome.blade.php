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
        The Grants Management Unit’s home for research funding at NIMR: the Institute’s own grants for its scientists,
        and awards from funders and partners around the world. From the first call to the final report, every step of a
        grant is managed here.
    </p>

    <div class="wl-actions end">
        <a href="{{ route('profile.complete.nreims') }}" class="btn btn-primary">
            Get started <i class="fas fa-arrow-right" aria-hidden="true"></i>
        </a>
    </div>
@endsection
