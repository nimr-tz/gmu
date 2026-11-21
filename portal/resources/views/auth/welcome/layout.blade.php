{{--
    The first-sign-in welcome: a navy panel with the crest and the four steps,
    and one page beside it. No portal navigation yet.
    Expects $user, $steps ([title, description]) and $current (0-based index).
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

<aside class="wl-side">
    <div class="wl-brand">
        <img src="{{ asset('images/nimr-logo.png') }}" alt="National Institute for Medical Research">
        <div><strong>NIMR Grants</strong><span>Grants Management Unit</span></div>
    </div>

    <nav aria-label="Setting up your account">
        <span class="wl-kicker">Setting up your account</span>
        <ol class="wl-steps">
            @foreach($steps as $i => [$title, $about])
                <li class="{{ $i < $current ? 'done' : ($i === $current ? 'now' : '') }}" @if($i === $current) aria-current="step" @endif>
                    <span class="rail">
                        <span class="dot">@if($i < $current)<i class="fas fa-check" aria-hidden="true"></i><span class="sr-only">Done:</span>@else{{ $i + 1 }}@endif</span>
                        <span class="line"></span>
                    </span>
                    <span class="txt"><span class="t">{{ $title }}</span><span class="s">{{ $about }}</span></span>
                </li>
            @endforeach
        </ol>
    </nav>

    <div class="wl-who">
        <span class="k">Signed in through NREIMS as</span>
        <span class="v">{{ $user->email }}</span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="wl-signout">Sign out</button>
        </form>
    </div>
</aside>

<main class="wl-main">
    <div class="wl-body @yield('body-class')">
        @yield('content')
    </div>
</main>

@stack('scripts')
</body>
</html>
