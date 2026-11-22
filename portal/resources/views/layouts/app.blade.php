<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · NIMR Grants</title>
    <link rel="icon" type="image/png" href="{{ asset('images/nimr-logo.png') }}">
    {{-- Built by Vite and served from this application: no CDN, so the portal
         renders on a network with no route to the internet. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
@php
    $user     = auth()->user();
    $unread   = $user->unreadNotificationsCount();
    $initials = strtoupper(collect(explode(' ', $user->name))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode(''));
    $external = config('gmu.external_grants');

    // Seed grant entries by role, with "needs action" counts (App\Support\SeedNav).
    $seedGroups = \App\Support\SeedNav::groups($user);

    // The rest of the panel: [label, route, icon, active pattern, visible].
    $groups = [
        'External grants' => [
            ['Analytics', 'analytics', 'chart', 'analytics', $external && Gate::allows('view-analytics')],
            ['Funding calls', 'calls.index', 'megaphone', 'calls.*', $external],
            ['Donors', 'donors.index', 'building', 'donors.*', $external && $user->hasAnyRole(['coordinator', 'planning_officer'])],
            ['Proposals', 'proposals.index', 'file-text', 'proposals.*', $external],
            ['Active awards', 'awards.index', 'award', 'awards.*', $external],
            ['Reports', 'reports.index', 'file', 'reports.*', $external],
            ['Documents', 'documents.index', 'layers', 'documents.*', $external],
        ],
        'Administration' => [
            ['People and roles', 'admin.users.index', 'users', 'admin.users.*', $user->isCoordinator()],
            ['Audit register', 'admin.activity-log.index', 'history', 'admin.activity-log.*', $user->isCoordinator()],
        ],
    ];

    $navLink = fn (bool $active) => 'group flex h-9 items-center gap-3 rounded-md px-3 text-[13.5px] font-medium transition-colors '
        . ($active ? 'bg-sidebar-chip text-white' : 'text-sidebar-foreground hover:bg-sidebar-hover hover:text-white');
@endphp
<body class="h-full" x-data="{ nav: false }" @keydown.escape.window="nav = false">

{{-- Side panel: the only navigation. Fixed on desktop, slides in on small screens. --}}
<div x-cloak x-show="nav" x-transition.opacity class="fixed inset-0 z-40 bg-blue-950/50 lg:hidden" @click="nav = false"></div>
<aside id="sidebar" aria-label="Side panel"
       class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-sidebar transition-transform duration-200 lg:translate-x-0 print:hidden"
       :class="nav && 'translate-x-0'">
    <div class="flex h-16 shrink-0 items-center justify-between gap-3 px-4">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3 rounded-md">
            <img src="{{ asset('images/nimr-logo.png') }}" alt="" class="size-9 shrink-0 rounded-full bg-white p-0.5">
            <span class="min-w-0 leading-tight">
                <span class="block text-[15px] font-semibold text-white">NIMR Grants</span>
                <span class="block truncate text-xs text-sidebar-muted">Grants Management Unit</span>
            </span>
        </a>
        <button type="button" class="grid size-8 place-items-center rounded-md text-sidebar-muted hover:bg-sidebar-hover hover:text-white lg:hidden" @click="nav = false" aria-label="Close menu">
            <x-icon name="x" />
        </button>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4" aria-label="Main">
        <div class="space-y-0.5">
            @php $active = request()->routeIs('dashboard'); @endphp
            <a href="{{ route('dashboard') }}" class="{{ $navLink($active) }}" @if($active) aria-current="page" @endif>
                <x-icon name="layout-dashboard" class="size-4 {{ $active ? 'text-white' : 'text-sidebar-muted group-hover:text-white' }}" /> Dashboard
            </a>
        </div>

        @foreach($seedGroups as $heading => $items)
            <div>
                <p class="mb-1.5 px-3 text-[11px] font-medium uppercase tracking-wider text-sidebar-muted">{{ $heading }}</p>
                <div class="space-y-0.5">
                    @foreach($items as [$label, $url, $icon, $pattern, $count, $alert])
                        @php $active = request()->routeIs(...explode('|', $pattern)); @endphp
                        <a href="{{ $url }}" class="{{ $navLink($active) }}" @if($active) aria-current="page" @endif>
                            <x-icon :name="$icon" class="size-4 {{ $active ? 'text-white' : 'text-sidebar-muted group-hover:text-white' }}" />
                            <span class="min-w-0 flex-1 truncate">{{ $label }}</span>
                            @if($count !== null)
                                <span class="grid h-5 min-w-5 place-items-center rounded-full px-1.5 font-mono text-[11px] font-medium {{ $alert ? 'bg-danger text-white' : 'bg-primary text-white' }}">{{ $count }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach

        @foreach($groups as $heading => $items)
            @php $shown = array_filter($items, fn ($i) => $i[4]); @endphp
            @continue(empty($shown))
            <div>
                <p class="mb-1.5 px-3 text-[11px] font-medium uppercase tracking-wider text-sidebar-muted">{{ $heading }}</p>
                <div class="space-y-0.5">
                    @foreach($shown as [$label, $route, $icon, $pattern])
                        @php $active = request()->routeIs($pattern); @endphp
                        <a href="{{ route($route) }}" class="{{ $navLink($active) }}" @if($active) aria-current="page" @endif>
                            <x-icon :name="$icon" class="size-4 {{ $active ? 'text-white' : 'text-sidebar-muted group-hover:text-white' }}" /> {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <div class="shrink-0 border-t border-white/10 p-3">
        <div class="flex items-center gap-3 rounded-md px-2 py-1.5">
            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-sidebar-chip text-xs font-semibold text-white">{{ $initials }}</span>
            <span class="min-w-0 flex-1 leading-tight">
                <span class="block truncate text-[13px] font-medium text-white">{{ $user->name }}</span>
                <span class="block truncate text-xs text-sidebar-muted">{{ $user->role?->display_name ?? 'No role assigned' }}</span>
            </span>
        </div>
    </div>
</aside>

<div class="flex min-h-full flex-col lg:pl-64 print:pl-0">
    {{-- Top bar: context, notifications and the account menu. Never navigation. --}}
    <header class="sticky top-0 z-30 print:hidden flex h-16 shrink-0 items-center gap-3 border-b border-border bg-card/85 px-4 backdrop-blur supports-[backdrop-filter]:bg-card/75 sm:px-6 lg:px-8">
        <button type="button" class="-ml-1 grid size-9 place-items-center rounded-md text-muted-foreground hover:bg-muted hover:text-foreground lg:hidden"
                @click="nav = true" aria-label="Open menu" aria-controls="sidebar" :aria-expanded="nav">
            <x-icon name="menu" class="size-5" />
        </button>
        <p class="min-w-0 flex-1 truncate text-[13px] text-muted-foreground">@yield('context', 'National Institute for Medical Research')</p>

        <a href="{{ route('notifications.index') }}" class="relative grid size-9 place-items-center rounded-md text-muted-foreground hover:bg-muted hover:text-foreground"
           aria-label="Notifications{{ $unread ? ", {$unread} unread" : '' }}">
            <x-icon name="bell" class="size-[18px]" />
            @if($unread > 0)
                <span class="absolute right-1 top-1 grid h-4 min-w-4 place-items-center rounded-full bg-primary px-1 font-mono text-[10px] font-medium text-white ring-2 ring-card">{{ $unread > 9 ? '9+' : $unread }}</span>
            @endif
        </a>
        <span class="h-6 w-px bg-border" aria-hidden="true"></span>

        <x-dropdown width="w-60">
            <x-slot:trigger>
                <button type="button" class="flex items-center gap-2.5 rounded-md py-1 pl-1 pr-2 hover:bg-muted" aria-label="Account menu">
                    <span class="grid size-8 place-items-center rounded-full bg-primary-soft text-xs font-semibold text-blue-800">{{ $initials }}</span>
                    <span class="hidden text-left leading-tight sm:block">
                        <span class="block text-[13px] font-medium">{{ $user->name }}</span>
                        <span class="block text-xs text-muted-foreground">{{ $user->role?->display_name ?? 'No role assigned' }}</span>
                    </span>
                    <x-icon name="chevron-down" class="size-3.5 text-subtle" />
                </button>
            </x-slot:trigger>
            <div class="border-b border-border px-2.5 pb-2 pt-1.5">
                <p class="truncate text-[13px] font-medium">{{ $user->name }}</p>
                <p class="truncate text-xs text-muted-foreground">{{ $user->email }}</p>
            </div>
            <div class="py-1">
                <x-dropdown-item :href="route('profile.edit')" icon="user">My account</x-dropdown-item>
                <x-dropdown-item :href="route('notifications.index')" icon="bell">Notifications</x-dropdown-item>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="border-t border-border pt-1">
                @csrf
                <x-dropdown-item icon="log-out">Sign out</x-dropdown-item>
            </form>
        </x-dropdown>
    </header>

    <main class="mx-auto w-full max-w-page flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8 print:p-0">
        @php $showErrors = $errors->any() && ! $errors->has('__hidden'); @endphp
        @if(session('success') || session('error') || session('status') || $showErrors)
            <div class="mb-6 space-y-3">
                @if(session('success'))<x-alert tone="ok">{{ session('success') }}</x-alert>@endif
                @if(session('error'))<x-alert tone="danger">{{ session('error') }}</x-alert>@endif
                @if(session('status'))<x-alert tone="info">{{ session('status') }}</x-alert>@endif
                @if($showErrors)
                    <x-alert tone="danger" :title="$errors->count() . ' ' . Str::plural('problem', $errors->count()) . ' with what was submitted'">
                        <ul class="list-disc space-y-0.5 pl-4">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
                    </x-alert>
                @endif
            </div>
        @endif

        @yield('content')
    </main>
</div>

@stack('scripts')
</body>
</html>
