@extends('layouts.app')

{{-- Everyone with an account and their role, from the owner's "GMU Admin" design (ink band). --}}

@php
    use App\Support\RoleLook as R;
    $inactive = request('status') === 'inactive';
    $role = request('role');
    $tab = fn ($status) => route('admin.users.index', array_filter(['status' => $status, 'search' => request('search')]));
@endphp

@section('title', 'People and roles')
@section('context', 'Administration · People and roles')
@section('topbar-colour', 'bg-foreground')

@section('content')
<div class="-mx-4 -mt-5 flex flex-wrap items-start justify-between gap-5 bg-foreground px-7 pb-[100px] pt-5 text-white lg:-mx-6 lg:-mt-6">
    <div class="flex min-w-0 flex-col gap-2">
        <h1 class="text-[30px] font-semibold leading-tight tracking-[-0.025em] text-white">People and roles</h1>
        <p class="text-[15px] text-gray-300">{{ $total }} {{ Str::plural('account', $total) }} · {{ $sso ? 'people appear here when they first sign in with NREIMS, or when you add them' : 'accounts are created here, never by self-registration' }}</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-lg bg-white px-3.5 text-[13px] font-semibold text-foreground hover:bg-gray-100"><x-icon name="user-plus" class="size-3.5" />{{ $sso ? 'Add a person' : 'Create an account' }}</a>
</div>

<div class="-mt-[72px] flex flex-col gap-[18px]">
    <div class="grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(170px,1fr))]">
        @foreach($tiles as $t)
            @php
                $on = $role === $t['key'];
                $warn = $t['key'] === 'none' && $t['n'] > 0;
            @endphp
            <a href="{{ $on ? route('admin.users.index') : route('admin.users.index', ['role' => $t['key']]) }}" @if($on) aria-current="page" @endif
               class="flex flex-col gap-1 rounded-[14px] border px-4 py-3.5 shadow-lift {{ $on ? 'border-foreground bg-foreground text-white' : ($warn ? 'border-danger-line bg-danger-soft text-danger-ink' : 'border-border bg-card hover:border-gray-300') }}">
                <span class="flex items-center gap-2 text-[13px] font-medium"><span class="size-2 rounded-sm {{ $t['key'] === 'none' ? 'bg-danger' : R::dot($t['key']) }} {{ $on && $t['key'] === 'coordinator' ? 'bg-white' : '' }}"></span>{{ $t['label'] }}</span>
                <span class="text-2xl font-semibold tracking-[-0.02em] tabular-nums">{{ $t['n'] }}</span>
            </a>
        @endforeach
    </div>

    <section class="overflow-hidden rounded-[14px] border border-border">
        <div class="flex flex-wrap items-center gap-2.5 border-b border-border bg-muted px-3 py-2.5">
            <nav class="flex gap-0.5 rounded-[9px] bg-gray-200/70 p-[3px]" aria-label="Status">
                @foreach(['Everyone' => null, 'Deactivated' => 'inactive'] as $label => $status)
                    @php $sel = $inactive === ($status === 'inactive'); @endphp
                    <a href="{{ $tab($status) }}" @if($sel) aria-current="page" @endif class="inline-flex h-7 items-center rounded-[7px] px-2.5 text-[13px] font-medium {{ $sel ? 'bg-card text-foreground shadow-sm' : 'text-gray-600 hover:text-foreground' }}">{{ $label }}</a>
                @endforeach
            </nav>
            <form method="GET" class="flex flex-1 flex-wrap items-center justify-end gap-2" role="search">
                @if($inactive)<input type="hidden" name="status" value="inactive">@endif
                @unless($inactive)
                    <label for="role" class="sr-only">Role</label>
                    <select id="role" name="role" class="select h-8 w-auto text-[13px]" onchange="this.form.submit()">
                        <option value="">All roles</option>
                        @foreach($roles as $r)<option value="{{ $r->name }}" @selected($role === $r->name)>{{ $r->display_name }}</option>@endforeach
                        <option value="none" @selected($role === 'none')>No role</option>
                    </select>
                @endunless
                <div class="relative w-[260px] max-w-full">
                    <label for="search" class="sr-only">Search</label>
                    <x-icon name="search" class="pointer-events-none absolute left-2.5 top-1/2 size-3.5 -translate-y-1/2 text-subtle" />
                    <input id="search" type="search" name="search" class="input h-8 bg-card pl-8 text-[13px]" placeholder="Name, email or department" value="{{ request('search') }}">
                </div>
            </form>
        </div>
        <div class="overflow-x-auto bg-card">
            <table class="w-full min-w-[820px] border-collapse text-left text-sm">
                <thead><tr>
                    <th class="h-[38px] border-b border-border pl-[18px] pr-3 text-[12.5px] font-medium text-muted-foreground">Person</th>
                    <th class="border-b border-border px-3 text-[12.5px] font-medium text-muted-foreground">Role</th>
                    <th class="border-b border-border px-3 text-[12.5px] font-medium text-muted-foreground">Department</th>
                    <th class="border-b border-border px-3 text-[12.5px] font-medium text-muted-foreground">Status</th>
                    <th class="border-b border-border px-3 text-[12.5px] font-medium text-muted-foreground">Sign-in</th>
                    <th class="border-b border-border pl-3 pr-[18px]"><span class="sr-only">Edit</span></th>
                </tr></thead>
                <tbody>
                    @forelse($users as $user)
                        <tr class="{{ $user->is_active ? '' : 'opacity-70' }}">
                            <td class="border-b border-gray-100 py-3 pl-[18px] pr-3">
                                <div class="flex items-center gap-3">
                                    <span class="grid size-[34px] shrink-0 place-items-center rounded-full text-xs font-semibold {{ R::avatar($user->id) }}">{{ R::initials($user->name) }}</span>
                                    <span class="min-w-0 leading-tight"><a href="{{ route('admin.users.show', $user) }}" class="font-medium hover:text-ok-ink">{{ $user->name }}</a><span class="block truncate text-xs text-muted-foreground">{{ $user->email }}</span></span>
                                </div>
                            </td>
                            <td class="border-b border-gray-100 p-3"><span class="inline-flex h-[22px] items-center whitespace-nowrap rounded-md px-2 text-xs font-medium {{ R::chip($user->role?->name) }}">{{ R::label($user->role?->name, $user->role?->display_name) }}</span></td>
                            <td class="border-b border-gray-100 p-3 text-[13px] text-gray-600">{{ $user->department ?: '—' }}</td>
                            <td class="border-b border-gray-100 p-3"><span class="inline-flex h-[22px] items-center gap-1.5 whitespace-nowrap rounded-md px-2 text-xs font-medium {{ $user->is_active ? 'bg-blue-50 text-ok-ink' : 'bg-gray-100 text-gray-700' }}"><span class="size-1.5 rounded-full {{ $user->is_active ? 'bg-primary' : 'bg-gray-400' }}"></span>{{ $user->is_active ? 'Active' : 'Deactivated' }}</span></td>
                            <td class="whitespace-nowrap border-b border-gray-100 p-3 text-[13px]">
                                @if($user->nreims_id)
                                    NREIMS<div class="text-xs text-muted-foreground">{{ $user->last_login_at ? 'Last in ' . $user->last_login_at->diffForHumans() : 'Linked' }}</div>
                                @elseif($user->must_change_password)
                                    Local password<div class="text-xs text-warn-ink">Must change at next sign-in</div>
                                @elseif(! $user->last_login_at)
                                    <span class="text-muted-foreground">Not signed in yet</span>@if($sso)<div class="text-xs text-muted-foreground">Links at first NREIMS sign-in</div>@endif
                                @else
                                    Local password<div class="text-xs text-muted-foreground">Last in {{ $user->last_login_at->diffForHumans() }}</div>
                                @endif
                            </td>
                            <td class="border-b border-gray-100 py-3 pl-3 pr-[18px] text-right"><a href="{{ route('admin.users.edit', $user) }}" aria-label="Edit {{ $user->name }}" class="inline-flex h-[30px] items-center rounded-[7px] border border-input bg-card px-[11px] text-[12.5px] font-medium hover:bg-gray-100">Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-11 text-center"><p class="text-[14.5px] font-semibold">No accounts match</p><p class="mt-1 text-[13px] text-muted-foreground">Try another filter or search.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @if($users->hasPages())<div>{{ $users->links() }}</div>@endif
</div>
@endsection
