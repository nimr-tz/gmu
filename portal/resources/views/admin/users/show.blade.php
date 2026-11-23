@extends('layouts.app')

{{-- One person's account and what their role can do, from the owner's "GMU Admin" design. --}}

@php
    use App\Support\RoleLook as R;
    $roleName = $user->role?->name;
    $sso = app(\App\Services\Auth\NreimsOAuth::class)->enabled();
@endphp

@section('title', $user->name)
@section('context', 'Administration · People and roles · ' . $user->name)
@section('topbar-colour', 'bg-foreground')

@section('content')
<div class="-mx-4 -mt-5 flex flex-col gap-3.5 bg-foreground px-7 pb-[100px] pt-[18px] text-white lg:-mx-6 lg:-mt-6">
    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1.5 self-start text-[13px] font-medium text-gray-300 hover:text-white"><x-icon name="arrow-left" class="size-3.5" />People and roles</a>
    <div class="flex flex-wrap items-start justify-between gap-5">
        <div class="flex items-center gap-4">
            <span class="grid size-[60px] shrink-0 place-items-center rounded-full text-xl font-semibold ring-[3px] ring-gray-800 {{ R::avatar($user->id) }}">{{ R::initials($user->name) }}</span>
            <div class="flex flex-col gap-1.5">
                <h1 class="text-[28px] font-semibold leading-tight tracking-[-0.025em] text-white">{{ $user->name }}</h1>
                <div class="flex flex-wrap items-center gap-2 text-[13.5px] text-gray-300">
                    <span>{{ $user->email }}</span>
                    <span class="inline-flex h-[22px] items-center rounded-md bg-white px-2 text-xs font-semibold {{ $roleName ? (R::isApprover($roleName) ? 'text-indigo-700' : 'text-foreground') : 'text-danger-ink' }}">{{ R::label($roleName, $user->role?->display_name) }}</span>
                    <span class="inline-flex h-[22px] items-center gap-1.5 rounded-md bg-white/10 px-2 text-xs font-medium"><span class="size-1.5 rounded-full {{ $user->is_active ? 'bg-primary' : 'bg-gray-400' }}"></span>{{ $user->is_active ? 'Active' : 'Deactivated' }}</span>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(config('gmu.external_grants'))<a href="{{ route('researchers.show', $user) }}" class="inline-flex h-9 items-center rounded-lg border border-gray-700 px-3.5 text-[13px] font-medium text-white hover:bg-gray-800">Researcher profile</a>@endif
            <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3.5 text-[13px] font-semibold text-foreground hover:bg-gray-100"><x-icon name="pencil" class="size-3.5" />Edit account</a>
        </div>
    </div>
</div>

<div class="-mt-[72px] grid items-start gap-5 [grid-template-columns:repeat(auto-fit,minmax(min(100%,340px),1fr))]">
    <section class="overflow-hidden rounded-[14px] border border-border bg-card shadow-pop">
        <header class="border-b border-border bg-muted px-[18px] py-3"><h2 class="text-sm font-semibold">Account details</h2></header>
        <dl class="px-[18px] pb-2 pt-1">
            @foreach([
                'Email' => $user->email,
                'Phone' => $user->phone ?: '—',
                'Department' => $user->department ?: '—',
                'Research centre' => $user->research_center ?: '—',
                'Sign-in' => $user->nreims_id ? 'NREIMS' : ($sso && ! $user->last_login_at ? 'NREIMS, not linked yet' : 'Local password' . ($user->must_change_password ? ', must change at next sign-in' : '')),
                'Created' => $user->created_at?->format('j M Y'),
                'Last signed in' => $user->last_login_at?->format('j M Y H:i') ?? 'Not yet',
            ] as $k => $v)
                <div class="flex justify-between gap-4 border-b border-gray-100 py-[11px] text-[13.5px] last:border-0"><dt class="text-muted-foreground">{{ $k }}</dt><dd class="text-right font-medium">{{ $v }}</dd></div>
            @endforeach
        </dl>
    </section>
    @include('admin.users.partials.abilities', ['roleName' => $roleName, 'roleLabel' => $user->role?->display_name])
</div>
@endsection
