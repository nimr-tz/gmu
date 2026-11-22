@extends('layouts.app')

@section('title', 'People and roles')
@section('context', 'Administration · People and roles')

@section('content')

<x-page-header title="People and roles"
    :description="$users->total() . ' ' . Str::plural('account', $users->total()) . ($sso
        ? ' · people appear here when they first sign in with NREIMS, or when you add them'
        : ' · accounts are created here, never by self-registration')">
    <x-slot:actions>
        <x-button variant="primary" :href="route('admin.users.create')" icon="user-plus">{{ $sso ? 'Add a person' : 'Create an account' }}</x-button>
    </x-slot:actions>
</x-page-header>

@php
    $inactive = request('status') === 'inactive';
    $statusItems = [
        route('admin.users.index', array_filter(['role' => request('role'), 'search' => request('search')])) => 'Everyone',
        route('admin.users.index', array_filter(['status' => 'inactive', 'search' => request('search')])) => 'Deactivated',
    ];
    $avatars = ['bg-blue-50 text-blue-700', 'bg-violet-50 text-violet-700', 'bg-sky-50 text-sky-700', 'bg-pink-50 text-pink-700', 'bg-amber-50 text-amber-800'];
@endphp

<section class="overflow-hidden rounded-xl border border-border bg-muted">
    <div class="flex flex-wrap items-center gap-2 px-3 py-2.5">
        <x-segmented :items="$statusItems" :active="array_keys($statusItems)[$inactive ? 1 : 0]" label="Status" />
        <form method="GET" class="flex flex-1 flex-wrap items-center justify-end gap-2" role="search">
            @if($inactive)<input type="hidden" name="status" value="inactive">@endif
            @unless($inactive)
                <label for="role" class="sr-only">Role</label>
                <select id="role" name="role" class="select select-sm !w-auto" onchange="this.form.submit()">
                    <option value="">All roles</option>
                    @foreach($roles as $role)<option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ $role->display_name }}</option>@endforeach
                </select>
            @endunless
            <div class="relative w-full sm:w-72">
                <label for="search" class="sr-only">Search</label>
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-subtle" />
                <input id="search" type="search" name="search" class="input input-sm pl-9" placeholder="Name, email or department" value="{{ request('search') }}">
            </div>
        </form>
    </div>
    <div class="overflow-x-auto border-t border-border bg-card">
        <table class="data-table min-w-[760px]">
            <thead><tr><th>Person</th><th>Role</th><th>Department</th><th>Status</th><th>Sign-in</th><th class="text-right"><span class="sr-only">Edit</span></th></tr></thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="grid size-8 shrink-0 place-items-center rounded-full text-xs font-semibold {{ $avatars[$user->id % 5] }}">{{ strtoupper(collect(explode(' ', preg_replace('/^(Dr\.?|Prof\.?|Mr\.?|Ms\.?|Mrs\.?)\s+/i', '', $user->name)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('')) }}</span>
                                <div class="min-w-0"><a href="{{ route('admin.users.show', $user) }}" class="font-medium hover:text-primary">{{ $user->name }}</a>
                                    <div class="truncate text-xs text-muted-foreground">{{ $user->email }}</div></div>
                            </div>
                        </td>
                        <td>
                            @if($user->role)<x-badge :tone="$user->role->name === 'coordinator' ? 'review' : 'outline'">{{ $user->role->display_name }}</x-badge>
                            @else<x-badge tone="danger">No role</x-badge>@endif
                        </td>
                        <td class="text-[13px] text-muted-foreground">{{ $user->department ?? '—' }}</td>
                        <td><x-badge :tone="$user->is_active ? 'ok' : 'neutral'" dot>{{ $user->is_active ? 'Active' : 'Deactivated' }}</x-badge></td>
                        <td class="text-[13px]">
                            @if($user->nreims_id)
                                <span>NREIMS</span><div class="text-xs text-muted-foreground">{{ $user->last_login_at ? 'Last in ' . $user->last_login_at->diffForHumans() : 'Linked' }}</div>
                            @elseif($user->must_change_password)
                                <span>Local password</span><div class="text-xs text-warn-ink">Must change at next sign-in</div>
                            @elseif(! $user->last_login_at)
                                <span class="whitespace-nowrap text-muted-foreground">Not signed in yet</span>
                                @if($sso)<div class="whitespace-nowrap text-xs text-muted-foreground">Links at first NREIMS sign-in</div>@endif
                            @else
                                <span>Local password</span><div class="text-xs text-muted-foreground">Last in {{ $user->last_login_at->diffForHumans() }}</div>
                            @endif
                        </td>
                        <td class="text-right"><x-button size="sm" :href="route('admin.users.edit', $user)" aria-label="Edit {{ $user->name }}">Edit</x-button></td>
                    </tr>
                @empty
                    <tr class="hover:bg-transparent"><td colspan="6"><x-empty icon="users" title="No accounts match" compact>Try another filter or search.</x-empty></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@if($users->hasPages())<div class="mt-4">{{ $users->links() }}</div>@endif
@endsection
