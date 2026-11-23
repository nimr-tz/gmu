@extends('auth.welcome.layout')

{{-- Step 1 of the welcome: what NIMR Grants is. --}}

@section('title', 'Welcome')

@section('content')
    <p class="text-[13px] font-medium uppercase tracking-[0.12em] text-primary">National Institute for Medical Research</p>
    <h1 class="mt-3 text-[32px] font-semibold leading-tight tracking-[-0.03em]">Welcome to NIMR Grants.</h1>
    <p class="mt-2.5 text-[15px] text-muted-foreground">The Grants Management Unit’s home for research funding at NIMR.</p>

    <ul class="mt-8 flex flex-col gap-3">
        @foreach([
            ['award', 'NIMR’s own grants', 'Funding the Institute opens to its scientists, from seed grants onwards.', 'bg-blue-50 text-blue-700'],
            ['building', 'Awards from funders and partners', 'Grants from donors and research partners around the world, managed with the GMU.', 'bg-sky-50 text-sky-700'],
            ['clipboard-check', 'Every step in one place', 'From the first call to the final report: applications, review, approval and reporting.', 'bg-violet-50 text-violet-700'],
        ] as [$icon, $head, $body, $chip])
            <li class="flex items-start gap-3.5 rounded-2xl border border-border px-4 py-3.5">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl {{ $chip }}"><x-icon :name="$icon" class="size-5" /></span>
                <div class="leading-snug"><p class="text-[15px] font-semibold">{{ $head }}</p><p class="mt-0.5 text-[13.5px] text-muted-foreground">{{ $body }}</p></div>
            </li>
        @endforeach
    </ul>

    <a href="{{ route('profile.complete.nreims') }}" class="mt-8 flex h-[52px] items-center justify-center gap-2 rounded-xl bg-primary text-[15px] font-semibold text-white shadow-cta transition-colors hover:bg-blue-700">
        Get started <x-icon name="arrow-right" class="size-4" />
    </a>
    <p class="mt-3 text-center text-[12.5px] text-muted-foreground">Takes about two minutes</p>
@endsection
