@extends('layouts.app')

@section('title', 'People and roles')
@section('context', 'Administration · People and roles')

@section('content')
@php
    $tab = fn (bool $on) => 'inline-flex h-8 items-center whitespace-nowrap rounded-md px-3 text-[13px] font-medium transition-colors '
        . ($on ? 'bg-card text-foreground shadow-xs ring-1 ring-border' : 'text-muted-foreground hover:text-foreground');
@endphp

<x-page-header title="People and roles"
    :description="$users->total() . ' ' . Str::plural('account', $users->total()) . ($sso
        ? ' · people appear here when they first sign in with NREIMS, or when you add them'
        : ' · accounts are created here, never by self-registration')">
    <x-slot:actions>
        <x-button variant="primary" :href="route('admin.users.create')" icon="user-plus">{{ $sso ? 'Add a person' : 'Create an account' }}</x-button>
    </x-slot:actions>
</x-page-header>

<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <nav class="flex max-w-full gap-1 overflow-x-auto rounded-lg bg-muted p-1" aria-label="Filter">
        <a href="{{ route('admin.users.index') }}" class="{{ $tab(! request('role') && ! request('status')) }}">All</a>
        @foreach($roles as $role)
            <a href="{{ route('admin.users.index', ['role' => $role->name]) }}" class="{{ $tab(request('role') === $role->name) }}">{{ $role->display_name }}</a>
        @endforeach
        <a href="{{ route('admin.users.index', ['status' => 'inactive']) }}" class="{{ $tab(request('status') === 'inactive') }}">Deactivated</a>
    </nav>
    <form method="GET" class="relative w-full sm:w-72" role="search">
        @if(request('role'))<input type="hidden" name="role" value="{{ request('role') }}">@endif
        <label for="search" class="sr-only">Search</label>
        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-subtle" />
        <input id="search" type="search" name="search" class="input pl-9" placeholder="Name, email or department" value="{{ request('search') }}">
    </form>
</div>

<x-card flush>
    <div class="overflow-x-auto">
        <table class="data-table min-w-[760px]">
            <thead><tr><th>Person</th><th>Role</th><th>Department</th><th>Status</th><th>Sign-in</th><th class="text-right"><span class="sr-only">Edit</span></th></tr></thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="grid size-8 shrink-0 place-items-center rounded-full bg-muted text-xs font-semibold text-gray-700">{{ strtoupper(collect(explode(' ', $user->name))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('')) }}</span>
                                <div class="min-w-0"><a href="{{ route('admin.users.show', $user) }}" class="font-medium hover:text-primary">{{ $user->name }}</a>
                                    <div class="truncate text-xs text-muted-foreground">{{ $user->email }}</div></div>
                            </div>
                        </td>
                        <td>
                            @if($user->role)<x-badge :tone="$user->role->name === 'coordinator' ? 'review' : 'outline'">{{ $user->role->display_name }}</x-badge>
                            @else<x-badge tone="danger">No role</x-badge>@endif
                        </td>
                        <td class="text-[13px] text-muted-foreground">{{ $user->department ?? '—' }}</td>
                        <td><x-badge :tone="$user->is_active ? 'ok' : 'danger'" dot>{{ $user->is_active ? 'Active' : 'Deactivated' }}</x-badge></td>
                        <td class="text-[13px]">
                            @if($user->nreims_id)
                                <span>NREIMS</span><div class="text-xs text-muted-foreground">{{ $user->last_login_at ? 'Last in ' . $user->last_login_at->diffForHumans() : 'Linked' }}</div>
                            @elseif($user->must_change_password)
                                <span>Local password</span><div class="text-xs text-warn-ink">Must change at next sign-in</div>
                            @elseif(! $user->last_login_at)
                                <span class="whitespace-nowrap text-warn-ink">Not signed in yet</span>
                                @if($sso)<div class="whitespace-nowrap text-xs text-muted-foreground">Links at first NREIMS sign-in</div>@endif
                            @else
                                <span>Local password</span><div class="text-xs text-muted-foreground">Last in {{ $user->last_login_at->diffForHumans() }}</div>
                            @endif
                        </td>
                        <td class="text-right"><x-button size="icon-sm" variant="ghost" :href="route('admin.users.edit', $user)" aria-label="Edit {{ $user->name }}"><x-icon name="pencil" /></x-button></td>
                    </tr>
                @empty
                    <tr class="hover:bg-transparent"><td colspan="6"><x-empty icon="users" title="No accounts match" compact>Try another filter or search.</x-empty></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@if($users->hasPages())<div class="mt-4">{{ $users->links() }}</div>@endif
@endsection
