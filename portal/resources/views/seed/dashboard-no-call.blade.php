@extends('layouts.app')

{{-- GMU's dashboard before any call exists, from the owner's "GMU" design: the one thing to do is set one up. --}}

@php
    $first = \Illuminate\Support\Str::of(preg_replace('/^(Dr|Prof|Mr|Ms|Mrs|Miss)\.?\s+/i', '', $user->name))->before(' ');
    $greeting = now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening');
@endphp

@section('title', 'Dashboard')
@section('context', 'Home · Dashboard')
@section('topbar-colour', 'bg-foreground')

@section('content')
<div class="-mx-4 -mt-5 flex flex-col gap-2 bg-foreground px-7 pb-[110px] pt-5 text-white lg:-mx-6 lg:-mt-6">
    <p class="text-[13px] text-gray-400">{{ now()->format('l, j F Y') }}</p>
    <h1 class="text-[30px] font-semibold leading-tight tracking-[-0.025em] text-white">Good {{ $greeting }}, {{ $first }}.</h1>
    <p class="text-[15px] text-gray-300">No call is set up yet.</p>
</div>

<div class="-mt-[72px] flex flex-col gap-4">
    <section class="flex flex-wrap items-center gap-8 rounded-2xl border border-border bg-card px-10 py-11 shadow-pop">
        <span class="grid size-16 shrink-0 place-items-center rounded-2xl bg-foreground text-white"><x-icon name="megaphone" class="size-7" /></span>
        <div class="flex flex-[1_1_420px] flex-col gap-2">
            <h2 class="text-[22px] font-semibold tracking-[-0.015em]">Set up the call to begin</h2>
            <p class="max-w-[640px] text-pretty text-[15px] leading-relaxed text-gray-700">Enter the call’s title, award range, deadlines and status. Researchers see it once you set the status to Open.</p>
        </div>
        <a href="{{ route('seed.calls.create') }}" class="inline-flex h-11 shrink-0 items-center gap-2 rounded-[9px] bg-foreground px-[18px] text-sm font-medium text-white hover:bg-gray-800"><x-icon name="plus" class="size-4" />Set up the call</a>
    </section>
    @can('admin-panel')
        <div class="flex flex-wrap items-center gap-2.5 rounded-xl border border-border bg-muted px-[18px] py-3.5 text-[13.5px] text-gray-700">
            <x-icon name="users" class="size-4 text-muted-foreground" />
            <span class="min-w-[240px] flex-1">Give the DRCP and the Director General their roles before the first proposals reach approval.</span>
            <a href="{{ route('admin.users.index') }}" class="font-medium text-ok-ink hover:underline">People and roles</a>
        </div>
    @endcan
</div>
@endsection
