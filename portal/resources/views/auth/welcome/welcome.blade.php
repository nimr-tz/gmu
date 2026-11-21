@extends('auth.welcome.layout')

@section('title', 'Welcome')
@section('body-class', 'wl-hero')

@section('content')
    <p class="wl-eyebrow">National Institute for Medical Research</p>
    <h1 class="wl-title">
        {{ $greeting }}, {{ \Illuminate\Support\Str::of($user->name)->before(' ') }}.
        <span class="soft">Welcome to NIMR Grants.</span>
    </h1>
    <span class="wl-rule" aria-hidden="true"></span>
    <p class="wl-lede">The Grants Management Unit’s home for research funding at NIMR.</p>

    <ul class="wl-points">
        <li>
            <span class="ic"><i class="fas fa-microscope" aria-hidden="true"></i></span>
            <div><strong>NIMR’s own grants</strong>
                <p>Funding the Institute opens to its scientists, from seed grants onwards.</p></div>
        </li>
        <li>
            <span class="ic"><i class="fas fa-globe" aria-hidden="true"></i></span>
            <div><strong>Awards from funders and partners</strong>
                <p>Grants from donors and research partners around the world, managed with the GMU.</p></div>
        </li>
        <li>
            <span class="ic"><i class="fas fa-tasks" aria-hidden="true"></i></span>
            <div><strong>Every step in one place</strong>
                <p>From the first call to the final report: applications, review, approval and reporting.</p></div>
        </li>
    </ul>

    <div class="wl-actions start">
        <a href="{{ route('profile.complete.nreims') }}" class="wl-go">Get started <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        <span class="wl-hint">Takes about two minutes</span>
    </div>
@endsection
