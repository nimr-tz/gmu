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
    $initials = strtoupper(collect(explode(' ', preg_replace('/^(Dr\.?|Prof\.?|Mr\.?|Ms\.?|Mrs\.?)\s+/i', '', $user->name)))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode(''));
    $external = config('gmu.external_grants');

    // Where you are, from the page's context: "Submissions · SG-2627-004" → Submissions › SG-2627-004.
    $crumbs = array_values(array_filter(explode(' · ', trim($__env->yieldContent('context', 'Overview')))));

    // A page may colour the top bar with a 'topbar-colour' section: 'bg-primary' for brand blue,
    // or the call's tint class plus 'bg-tint-ink' for the call's own colour. Otherwise it stays white.
    $bar = trim($__env->yieldContent('topbar-colour'));

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

    $link = fn (bool $active) => 'group flex h-8 items-center gap-2.5 rounded-md px-2 text-[13.5px] transition-colors '
        . ($active ? 'bg-card font-medium text-foreground shadow-xs ring-1 ring-border' : 'text-gray-700 hover:bg-gray-200/60 hover:text-foreground');
    $icon = fn (bool $active) => 'size-4 ' . ($active ? 'text-foreground' : 'text-subtle group-hover:text-gray-600');
@endphp
<body class="h-full bg-background" x-data="{ nav: false }" @keydown.escape.window="nav = false">

{{-- Side panel: the only navigation. Fixed on desktop, slides in on small screens. --}}
<div x-cloak x-show="nav" x-transition.opacity class="fixed inset-0 z-40 bg-foreground/30 lg:hidden" @click="nav = false"></div>
<aside id="sidebar" aria-label="Side panel"
       class="fixed inset-y-0 left-0 z-50 flex w-60 -translate-x-full flex-col bg-background transition-transform duration-200 lg:translate-x-0 print:hidden max-lg:shadow-pop"
       :class="nav && 'translate-x-0'">
    <div class="flex h-14 shrink-0 items-center justify-between gap-2 px-4">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-2.5">
            <img src="{{ asset('images/nimr-logo.png') }}" alt="" class="size-9 shrink-0 rounded-full bg-white object-contain">
            <span class="min-w-0 leading-tight">
                <span class="block text-sm font-semibold">NIMR Grants</span>
                <span class="block truncate text-xs text-muted-foreground">Grants Management Unit</span>
            </span>
        </a>
        <button type="button" class="grid size-7 place-items-center rounded-md text-subtle hover:bg-gray-200/60 hover:text-foreground lg:hidden" @click="nav = false" aria-label="Close menu">
            <x-icon name="x" />
        </button>
    </div>

    <nav class="flex-1 space-y-5 overflow-y-auto px-3 pb-4 pt-2" aria-label="Main">
        <div>
            <p class="mb-1 px-2 text-[13px] text-muted-foreground">Home</p>
            @php $active = request()->routeIs('dashboard'); @endphp
            <a href="{{ route('dashboard') }}" class="{{ $link($active) }}" @if($active) aria-current="page" @endif>
                <x-icon name="layout-dashboard" class="{{ $icon($active) }}" /> Dashboard
            </a>
        </div>

        @foreach($seedGroups as $heading => $items)
            <div>
                <p class="mb-1 px-2 text-[13px] text-muted-foreground">{{ ucfirst(strtolower($heading)) }}</p>
                <div class="space-y-px">
                    @foreach($items as [$label, $url, $iconName, $pattern, $count, $alert])
                        @php $active = request()->routeIs(...explode('|', $pattern)); @endphp
                        <a href="{{ $url }}" class="{{ $link($active) }}" @if($active) aria-current="page" @endif>
                            <x-icon :name="$iconName" class="{{ $icon($active) }}" />
                            <span class="min-w-0 flex-1 truncate">{{ $label }}</span>
                            @if($count !== null)
                                <span class="grid h-5 min-w-5 place-items-center rounded-md px-1.5 text-[11.5px] font-semibold tabular-nums {{ $alert ? 'bg-danger-soft text-danger-ink' : 'bg-primary-soft text-ok-ink' }}">{{ $count }}</span>
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
                <p class="mb-1 px-2 text-[13px] text-muted-foreground">{{ $heading }}</p>
                <div class="space-y-px">
                    @foreach($shown as [$label, $route, $iconName, $pattern])
                        @php $active = request()->routeIs($pattern); @endphp
                        <a href="{{ route($route) }}" class="{{ $link($active) }}" @if($active) aria-current="page" @endif>
                            <x-icon :name="$iconName" class="{{ $icon($active) }}" /> {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    {{-- The account, at the foot of the panel --}}
    <div class="shrink-0 p-2">
        <x-dropdown direction="up" align="left" width="w-56">
            <x-slot:trigger>
                <button type="button" class="flex w-full items-center gap-2.5 rounded-lg p-2 text-left hover:bg-gray-200/60" aria-label="Account menu">
                    <span class="relative grid size-8 shrink-0 place-items-center rounded-full bg-review-soft text-xs font-semibold text-review-ink">{{ $initials }}</span>
                    <span class="min-w-0 flex-1 leading-tight">
                        <span class="block truncate text-[13px] font-semibold">{{ $user->name }}</span>
                        <span class="block truncate text-xs text-muted-foreground">{{ $user->role?->name === 'pi' ? 'Researcher' : ($user->role?->display_name ?? 'No role assigned') }}</span>
                    </span>
                    <x-icon name="chevrons-up-down" class="size-3.5 text-subtle" />
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
    </div>
</aside>

{{-- The page: a white panel set in the frame, using the full width beside the side panel --}}
<div class="flex min-h-full lg:pl-60 print:pl-0">
    <div class="flex min-w-0 flex-1 flex-col bg-card lg:my-2 lg:mr-2 lg:rounded-xl lg:border lg:border-border lg:shadow-xs print:m-0 print:border-0 print:shadow-none">
        {{-- Top bar: where you are, and notifications. Never navigation. --}}
        <header class="sticky top-0 z-30 flex h-12 shrink-0 items-center gap-2 px-3 lg:rounded-t-xl lg:px-5 print:hidden {{ $bar ? $bar . ' text-white' : 'border-b border-border bg-card/90 backdrop-blur' }}">
            <button type="button" class="grid size-8 place-items-center rounded-md lg:hidden {{ $bar ? 'text-white/80 hover:bg-white/10 hover:text-white' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}"
                    @click="nav = true" aria-label="Open menu" aria-controls="sidebar" :aria-expanded="nav">
                <x-icon name="menu" class="size-[18px]" />
            </button>
            <nav aria-label="Breadcrumb" class="min-w-0 flex-1">
                <ol class="flex min-w-0 items-center gap-1.5 text-[13px]">
                    @foreach($crumbs as $crumb)
                        <li class="flex min-w-0 items-center gap-1.5 {{ $loop->last ? ($bar ? 'font-medium text-white' : 'font-medium text-foreground') : ($bar ? 'text-white/70 max-sm:hidden' : 'text-muted-foreground max-sm:hidden') }}">
                            @unless($loop->first)<x-icon name="chevron-right" class="size-3.5 {{ $bar ? 'text-white/50' : 'text-subtle' }}" />@endunless
                            <span class="truncate">{{ $crumb }}</span>
                        </li>
                    @endforeach
                </ol>
            </nav>
            @yield('topbar')
            <a href="{{ route('notifications.index') }}" class="relative grid size-8 place-items-center rounded-md {{ $bar ? 'text-white/85 hover:bg-white/10 hover:text-white' : 'text-muted-foreground hover:bg-muted hover:text-foreground' }}"
               aria-label="Notifications{{ $unread ? ", {$unread} unread" : '' }}">
                <x-icon name="bell" class="size-[18px]" />
                @if($unread > 0)<span class="absolute right-1.5 top-1.5 size-2 rounded-full {{ $bar ? 'bg-white ring-2 ring-primary' : 'bg-primary ring-2 ring-card' }}"></span>@endif
            </a>
        </header>

        <main class="w-full flex-1 px-4 py-5 lg:px-6 lg:py-6 print:p-0">
            @php $showErrors = $errors->any() && ! $errors->has('__hidden'); @endphp
            @if(session('success') || session('error') || session('status') || $showErrors)
                <div class="mb-5 space-y-3">
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
</div>

@stack('scripts')
</body>
</html>
