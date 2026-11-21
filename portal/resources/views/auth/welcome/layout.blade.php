{{--
    The first-sign-in welcome: a light page of its own (no side panel yet),
    the four-step line, and one card per step.
    Expects $user, $steps (labels) and $current (0-based index).
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · NIMR Grants</title>
    <link rel="icon" type="image/png" href="{{ asset('images/nimr-logo.png') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/gmu.css') }}?v={{ filemtime(public_path('css/gmu.css')) }}">
</head>
<body class="wl">
<header class="wl-head">
    <div class="wl-head-in">
        <span class="wl-brand"><img src="{{ asset('images/nimr-logo.png') }}" alt="">NIMR Grants</span>
        <span class="wl-who">
            <span>Signed in through NREIMS as <strong>{{ $user->email }}</strong></span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="wl-signout">Sign out</button>
            </form>
        </span>
    </div>
</header>

<main class="wl-main">
    <ol class="wl-steps" aria-label="Setting up your account">
        @foreach($steps as $i => $label)
            <li class="{{ $i < $current ? 'done' : ($i === $current ? 'now' : '') }}" @if($i === $current) aria-current="step" @endif>
                <span class="bar"></span>
                <span class="lbl"><span>{{ $label }}</span></span>
            </li>
        @endforeach
    </ol>

    <section class="wl-card @yield('card-class')">
        @yield('content')
    </section>
</main>

@stack('scripts')
</body>
</html>
