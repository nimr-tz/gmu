<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · NIMR Grants</title>
    <link rel="icon" type="image/png" href="{{ asset('images/nimr-logo.png') }}">

    {{-- Everything is served from this application: the portal has to render
         correctly on a network with no route to the internet. --}}
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/gmu.css') }}?v={{ filemtime(public_path('css/gmu.css')) }}">
    @stack('head')
</head>
<body>
@php
    $user     = auth()->user();
    $unread   = $user->unreadNotificationsCount();
    $initials = collect(explode(' ', $user->name))->take(2)->map(fn($p) => mb_substr($p, 0, 1))->implode('');
    $external = config('gmu.external_grants');

    // Seed grant items come first, by role (App\Support\SeedNav).
    $seedGroups = \App\Support\SeedNav::groups($user);

    // The rest of the side panel, as groups of [label, route, icon, active-pattern, visible].
    $groups = [
        'Insights' => [
            ['Analytics', 'analytics', 'fa-chart-line', 'analytics', $external && Gate::allows('view-analytics')],
        ],
        'Grant opportunities' => [
            ['Funding calls', 'calls.index', 'fa-bullhorn', 'calls.*', $external],
            ['Donors', 'donors.index', 'fa-handshake', 'donors.*', $external && $user->hasAnyRole(['coordinator', 'planning_officer'])],
        ],
        'Proposals' => [
            ['Proposals', 'proposals.index', 'fa-file-alt', 'proposals.*', $external],
            ['Proposal library', 'library.index', 'fa-book-open', 'library.*', $external],
            [$user->isGmuStaff() ? 'Researchers' : 'My research profile', 'researchers.index', 'fa-user-graduate', 'researchers.*', $external],
        ],
        'Awards and compliance' => [
            ['Active awards', 'awards.index', 'fa-trophy', 'awards.*', $external],
            ['Reports', 'reports.index', 'fa-file-invoice', 'reports.*', $external],
        ],
        'Library' => [
            ['Documents', 'documents.index', 'fa-folder-open', 'documents.*', $external],
            ['Donor policies', 'donor-policies.index', 'fa-book', 'donor-policies.*', $external],
            ['AI hub', 'ai.hub', 'fa-robot', 'ai.*', $external && Gate::allows('view-analytics')],
        ],
        'Administration' => [
            ['People and roles', 'admin.users.index', 'fa-users-cog', 'admin.users.*', $user->isCoordinator()],
            ['Audit register', 'admin.activity-log.index', 'fa-history', 'admin.activity-log.*', $user->isCoordinator()],
        ],
    ];
@endphp

<div class="shell">

    <aside class="sidebar" id="sidebar" aria-label="Sidebar">
        <a href="{{ route('dashboard') }}" class="sidebar-head">
            <img src="{{ asset('images/nimr-logo.png') }}" alt="" class="sidebar-logo">
            <span>
                <span class="sidebar-name">NIMR Grants</span>
                <span class="sidebar-sub">Grants Management Unit</span>
            </span>
        </a>

        <nav class="sidebar-nav" aria-label="Main">
            <div class="nav-group">Overview</div>
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                <i class="fas fa-th-large" aria-hidden="true"></i> Dashboard
            </a>

            @foreach($seedGroups as $heading => $items)
                <div class="nav-group">{{ $heading }}</div>
                @foreach($items as [$label, $url, $icon, $pattern, $count, $alert])
                    @php $active = request()->routeIs(...explode('|', $pattern)); @endphp
                    <a href="{{ $url }}" class="nav-link {{ $active ? 'is-active' : '' }}" @if($active) aria-current="page" @endif>
                        <x-icon :name="$icon" /> {{ $label }}
                        @if($count !== null)<span class="nav-count {{ $alert ? 'is-alert' : '' }}">{{ $count }}</span>@endif
                    </a>
                @endforeach
            @endforeach

            @foreach($groups as $heading => $items)
                @php $shown = array_filter($items, fn($i) => $i[4]); @endphp
                @continue(empty($shown))
                <div class="nav-group">{{ $heading }}</div>
                @foreach($shown as [$label, $route, $icon, $pattern])
                    @php $active = request()->routeIs($pattern); @endphp
                    <a href="{{ route($route) }}" class="nav-link {{ $active ? 'is-active' : '' }}" @if($active) aria-current="page" @endif>
                        <i class="fas {{ $icon }}" aria-hidden="true"></i> {{ $label }}
                    </a>
                @endforeach
            @endforeach
        </nav>

        @hasSection('sidebar-foot')
            <div class="sidebar-foot">@yield('sidebar-foot')</div>
        @endif
    </aside>
    <div class="nav-backdrop" data-nav-close></div>

    <div class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="icon-btn menu-btn" aria-label="Open menu" aria-controls="sidebar" data-nav-toggle>
                    <i class="fas fa-bars" aria-hidden="true"></i>
                </button>
                @if($external)
                    <form class="topbar-search" method="GET" action="{{ route('awards.index') }}" role="search">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <input type="search" name="search" placeholder="Search awards" value="{{ request('search') }}" aria-label="Search awards">
                    </form>
                @else
                    <span class="topbar-context">@yield('context', 'National Institute for Medical Research')</span>
                @endif
            </div>

            <div class="topbar-right">
                <a href="{{ route('notifications.index') }}" class="icon-btn" aria-label="Notifications{{ $unread ? ", {$unread} unread" : '' }}">
                    <i class="far fa-bell" aria-hidden="true"></i>
                    @if($unread > 0)<span class="icon-count">{{ $unread > 9 ? '9+' : $unread }}</span>@endif
                </a>

                <span class="topbar-divider" aria-hidden="true"></span>

                <details class="user-menu">
                    <summary aria-label="Account menu">
                        <span class="avatar">{{ strtoupper($initials) }}</span>
                        <span class="user-text">
                            <span class="user-name" style="display:block;">{{ $user->name }}</span>
                            <span class="user-role" style="display:block;">{{ $user->role?->display_name ?? 'No role assigned' }}</span>
                        </span>
                        <i class="fas fa-chevron-down" aria-hidden="true" style="font-size:11px; color:var(--muted-2);"></i>
                    </summary>
                    <div class="user-pop">
                        <a href="{{ route('profile.edit') }}"><i class="far fa-user" aria-hidden="true"></i> My account</a>
                        <a href="{{ route('notifications.index') }}"><i class="far fa-bell" aria-hidden="true"></i> Notifications</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"><i class="fas fa-sign-out-alt" aria-hidden="true"></i> Sign out</button>
                        </form>
                    </div>
                </details>
            </div>
        </header>

        <main class="page">
            @if(session('success'))
                <div class="alert alert-success"><i class="fas fa-check-circle"></i><span>{{ session('success') }}</span></div>
            @endif
            @if(session('error'))
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><span>{{ session('error') }}</span></div>
            @endif
            @if(session('status'))
                <div class="alert alert-info"><i class="fas fa-info-circle"></i><span>{{ session('status') }}</span></div>
            @endif
            @if($errors->any() && ! $errors->has('__hidden'))
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>{{ $errors->count() }} {{ Str::plural('problem', $errors->count()) }} with what was submitted</strong>
                        <ul style="margin:6px 0 0; padding-left:18px;">
                            @foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script>
    // Small screens: the side panel slides in over the page.
    (function () {
        var body = document.body;
        document.querySelectorAll('[data-nav-toggle]').forEach(function (b) {
            b.addEventListener('click', function () { body.classList.toggle('nav-open'); });
        });
        document.querySelectorAll('[data-nav-close]').forEach(function (b) {
            b.addEventListener('click', function () { body.classList.remove('nav-open'); });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { body.classList.remove('nav-open'); }
        });
    })();
</script>
@stack('scripts')
</body>
</html>
