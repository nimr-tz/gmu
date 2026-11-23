@extends('auth.welcome.layout')

{{-- Step 2 of the welcome: what came across from NREIMS, read only. --}}

@section('title', 'From NREIMS')

@php
    $initials = strtoupper(collect(explode(' ', preg_replace('/^(Dr\.?|Prof\.?|Mr\.?|Ms\.?|Mrs\.?)\s+/i', '', $user->name)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode(''));
@endphp

@section('content')
    <a href="{{ route('profile.complete') }}" class="inline-flex items-center gap-1 self-start text-[13px] text-muted-foreground hover:text-foreground"><x-icon name="arrow-left" class="size-3.5" /> Back</a>
    <p class="mt-4 text-[13px] text-muted-foreground">Step 2 of {{ count($steps) }}</p>
    <h1 class="mt-1 text-[32px] font-semibold leading-tight tracking-[-0.03em]">We already know a little about you.</h1>
    <p class="mt-2.5 text-[15px] text-muted-foreground">You signed in with NREIMS, so there is no need to type what you have already told us. Here is what we brought across.</p>

    <section class="mt-7 overflow-hidden rounded-2xl border border-blue-100" aria-label="From your NREIMS account">
        <div class="flex items-center gap-3.5 bg-blue-50/60 px-4 py-3.5">
            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-primary text-sm font-semibold text-white">{{ $initials }}</span>
            <p class="min-w-0 flex-1 text-[15px] font-semibold">From your NREIMS account</p>
            <span class="inline-flex h-6 shrink-0 items-center gap-1.5 whitespace-nowrap rounded-md bg-card px-2 text-xs font-medium text-blue-700 ring-1 ring-blue-100"><x-icon name="lock" class="size-3" />Read only</span>
        </div>
        <dl class="grid gap-px border-t border-blue-100 bg-border sm:grid-cols-2">
            <div class="bg-card px-4 py-3.5"><dt class="text-[12.5px] text-muted-foreground">Full name</dt><dd class="mt-0.5 text-[14.5px] font-medium">{{ $user->name }}</dd></div>
            <div class="min-w-0 bg-card px-4 py-3.5 sm:col-span-2 sm:row-start-2"><dt class="text-[12.5px] text-muted-foreground">Email</dt><dd class="mt-0.5 break-all text-[14.5px] font-medium">{{ $user->email }}</dd></div>
            <div class="bg-card px-4 py-3.5"><dt class="text-[12.5px] text-muted-foreground">Phone</dt>
                @if($user->phone)
                    <dd class="mt-0.5 font-mono text-[14.5px] font-medium">{{ $user->phone }}</dd>
                @else
                    <dd class="mt-0.5 text-[13px] text-muted-foreground">Not in NREIMS. You can add it on the next step.</dd>
                @endif
            </div>
        </dl>
    </section>

    <p class="mt-4 text-[13.5px] text-gray-700">Something not right? Update it in NREIMS and it will follow you here the next time you sign in.</p>
    <p class="mt-3 flex items-start gap-2.5 rounded-xl bg-muted px-4 py-3 text-[13px] text-muted-foreground">
        <x-icon name="shield-check" class="mt-px size-4 shrink-0 text-primary" />
        <span>Only the Grants Management Unit sees these details. We use them to confirm your eligibility and to keep in touch about your application, nothing else.</span>
    </p>

    <a href="{{ route('profile.complete.details') }}" class="mt-8 flex h-[52px] items-center justify-center gap-2 rounded-xl bg-primary text-[15px] font-semibold text-white shadow-cta transition-colors hover:bg-blue-700">
        Yes, that’s me <x-icon name="arrow-right" class="size-4" />
    </a>
@endsection
