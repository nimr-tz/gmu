@extends('layouts.app')

@section('title', $user->name)
@section('context', 'Administration · People and roles')

@section('content')
@php
    $initials = strtoupper(collect(explode(' ', $user->name))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode(''));
    $abilities = match ($user->role?->name) {
        'coordinator'        => ['Runs every call: screening, reviewers, decisions and agreements', 'GMU sign-off on recommended proposals', 'Adds people and sets their roles', 'Reads the audit register'],
        'planning_officer'   => ['Runs every call: screening, reviewers, decisions and agreements', 'GMU sign-off on recommended proposals'],
        'drcp'               => ['Clears recommended proposals for the Director General'],
        'dg'                 => ['Final approval of recommended proposals, with or without conditions'],
        null                 => ['Nothing yet: an account without a role sees no grant data'],
        default              => ['Applies to open calls as lead applicant', 'Reviews concept notes and proposals when GMU assigns them'],
    };
@endphp

<x-page-header :title="$user->name" :back="route('admin.users.index')" back-label="People and roles" :description="$user->email">
    <x-slot:meta>
        <div class="mt-3 flex flex-wrap gap-2">
            <x-badge :tone="$user->role?->name === 'coordinator' ? 'review' : 'outline'">{{ $user->role?->display_name ?? 'No role' }}</x-badge>
            <x-badge :tone="$user->is_active ? 'ok' : 'danger'" dot>{{ $user->is_active ? 'Active' : 'Deactivated' }}</x-badge>
        </div>
    </x-slot:meta>
    <x-slot:actions>
        @if(config('gmu.external_grants'))<x-button :href="route('researchers.show', $user)">Researcher profile</x-button>@endif
        <x-button variant="primary" :href="route('admin.users.edit', $user)" icon="pencil">Edit account</x-button>
    </x-slot:actions>
</x-page-header>

<div class="grid items-start gap-6 lg:grid-cols-2">
    <x-card title="Account details" icon="user">
        <dl class="divide-y divide-border text-[13px]">
            @foreach([
                'Email' => $user->email,
                'Phone' => $user->phone ?? '—',
                'Department' => $user->department ?? '—',
                'Research centre' => $user->research_center ?? '—',
                'Sign-in' => $user->nreims_id ? 'NREIMS' : 'Local password',
                'Created' => $user->created_at?->format('j M Y'),
                'Last signed in' => $user->last_login_at?->format('j M Y H:i') ?? 'Not yet',
            ] as $k => $v)
                <div class="flex justify-between gap-4 py-2.5 first:pt-0"><dt class="text-muted-foreground">{{ $k }}</dt><dd class="text-right font-medium">{{ $v }}</dd></div>
            @endforeach
            @unless($user->nreims_id)
                <div class="flex justify-between gap-4 py-2.5"><dt class="text-muted-foreground">Password</dt>
                    <dd class="text-right font-medium {{ $user->must_change_password ? 'text-warn-ink' : '' }}">{{ $user->must_change_password ? 'Must be changed at next sign-in' : 'Set by the user' }}</dd></div>
            @endunless
        </dl>
    </x-card>

    <x-card title="What this role can do" icon="shield-check">
        <ul class="space-y-2.5 text-sm">
            @foreach($abilities as $ability)
                <li class="flex gap-2.5"><span class="mt-0.5 grid size-4 shrink-0 place-items-center rounded-full bg-primary-soft text-primary"><x-icon name="check" class="size-2.5" /></span>{{ $ability }}</li>
            @endforeach
        </ul>
        <p class="mt-4 border-t border-border pt-3 text-xs text-muted-foreground">Anyone GMU assigns can review, whatever their role. Reviewers are anonymous to applicants.</p>
    </x-card>
</div>
@endsection
