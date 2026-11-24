{{--
    One error page, from the owner's "Error" design. Each code's view (404, 403, 419, 500)
    includes this with $code, $icon, $title, $text and $retry (true: "Try again" goes back).
--}}
@extends('layouts.plain')

@section('title', $title)

@section('header')
    <header class="flex items-center gap-2.5 px-7 py-5">
        <span class="grid size-9 place-items-center rounded-full bg-white"><img src="{{ asset('images/nimr-logo.png') }}" alt="" class="size-8 rounded-full object-contain"></span>
        <span class="leading-tight"><span class="block text-sm font-semibold">NIMR Grants</span><span class="block whitespace-nowrap text-xs text-blue-100">National Institute for Medical Research</span></span>
    </header>
@endsection

@section('content')
    <div class="flex w-full max-w-[480px] flex-col items-center text-center">
        <span class="grid size-[88px] place-items-center rounded-3xl bg-white text-blue-700 shadow-brand ring-[10px] ring-white/10"><x-icon :name="$icon" class="size-9" /></span>
        <p class="mt-8 flex items-center gap-3 font-mono text-[13px] font-medium tracking-[0.12em] text-blue-100"><span class="h-px w-7 bg-white/40"></span>ERROR {{ $code }}<span class="h-px w-7 bg-white/40"></span></p>
        <h1 class="mt-3.5 text-balance text-[34px] font-semibold leading-[1.12] tracking-[-0.03em] text-white">{{ $title }}</h1>
        <p class="mt-3.5 max-w-[420px] text-pretty text-base leading-relaxed text-blue-100">{{ $text }}</p>
        <div class="mt-10 flex w-full flex-col gap-3">
            @if($retry ?? false)
                <a href="{{ url()->current() }}" onclick="if (history.length > 1) { event.preventDefault(); history.back(); }"
                   class="flex h-14 w-full items-center justify-center gap-2.5 rounded-xl bg-white text-base font-semibold text-blue-700 shadow-brand hover:bg-blue-50 hover:text-blue-800"><x-icon name="rotate-ccw" class="size-[18px]" />Try again</a>
                <a href="{{ route('dashboard') }}" class="self-center text-sm font-medium text-white hover:underline">Go to your dashboard</a>
            @else
                <a href="{{ route('dashboard') }}" class="flex h-14 w-full items-center justify-center gap-2.5 rounded-xl bg-white text-base font-semibold text-blue-700 shadow-brand hover:bg-blue-50 hover:text-blue-800"><x-icon name="home" class="size-[18px]" />Go to your dashboard</a>
            @endif
        </div>
    </div>
@endsection

@if($code === '500')
    @section('footer')
        <span>If it keeps happening, tell GMU what you were doing and the time: {{ config('gmu.contact_email') }}</span>
    @endsection
@endif
