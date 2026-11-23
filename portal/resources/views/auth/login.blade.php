{{--
    Sign in, from the owner's "Sign In" design: the NIMR seal on brand blue and one
    button, "Sign in with NREIMS". Staff sign in only that way, so nothing else sits
    beside the button. Accounts without NREIMS (the GMU administrator) use the small
    "Admin login" link in the top-right corner, which shows the email-and-password
    form here. Without NREIMS configured, the form shows at once.
--}}
@php
    $sso       = app(\App\Services\Auth\NreimsOAuth::class)->enabled();
    $showLocal = ! $sso || request()->boolean('admin') || $errors->any() || old('email');
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in · NIMR Grants</title>
    <link rel="icon" type="image/png" href="{{ asset('images/nimr-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-primary text-white">
<div class="flex min-h-full flex-col bg-primary bg-brand-glow">
    @if($sso && ! $showLocal)
        <div class="flex justify-end px-6 pt-5">
            <a href="{{ route('login', ['admin' => 1]) }}" class="inline-flex h-8 items-center gap-1.5 rounded-md px-3 text-[13px] text-blue-100 hover:bg-white/10 hover:text-white"><x-icon name="lock" class="size-3.5" />Admin login</a>
        </div>
    @endif
    <main class="flex flex-1 items-center justify-center px-6 py-14">
        <div class="flex w-full max-w-[420px] flex-col items-center text-center">
            <span class="grid size-[120px] place-items-center rounded-full bg-white shadow-brand ring-[10px] ring-white/10">
                <img src="{{ asset('images/nimr-logo.png') }}" alt="National Institute for Medical Research" class="size-[108px] rounded-full object-contain">
            </span>
            <h1 class="mt-9 text-balance text-[34px] font-semibold leading-[1.12] tracking-[-0.03em] text-white">National Institute for Medical Research</h1>
            <div class="mt-3.5 flex items-center gap-3 text-[13px] font-medium uppercase tracking-[0.12em] text-blue-100">
                <span class="h-px w-7 bg-white/40"></span>Grants Management Unit<span class="h-px w-7 bg-white/40"></span>
            </div>

            @if(session('status'))
                <p class="mt-8 w-full rounded-xl bg-white/10 px-4 py-3 text-[13.5px] text-white ring-1 ring-white/20">{{ session('status') }}</p>
            @endif
            @if(session('sso_error') || $errors->any())
                <p class="mt-8 w-full rounded-xl bg-white px-4 py-3 text-[13.5px] font-medium text-danger-ink" role="alert">{{ session('sso_error') ?: $errors->first() }}</p>
            @endif

            @if($showLocal)
                <form method="POST" action="{{ route('login') }}" class="mt-10 flex w-full flex-col gap-3 text-left">
                    @csrf
                    <label for="email" class="sr-only">Email</label>
                    <input id="email" type="email" name="email" class="input h-12 text-[15px]" value="{{ old('email') }}" placeholder="Email"
                           required autofocus autocomplete="username">
                    <label for="password" class="sr-only">Password</label>
                    <input id="password" type="password" name="password" class="input h-12 text-[15px]" placeholder="Password"
                           required autocomplete="current-password">
                    <input type="hidden" name="remember" value="0">
                    <button type="submit" class="mt-1 flex h-14 w-full items-center justify-center rounded-xl bg-white text-base font-semibold text-blue-700 shadow-brand transition-colors hover:bg-blue-50 hover:text-blue-800">Sign in</button>
                </form>
                <div class="mt-5 flex flex-wrap justify-center gap-5 text-[13.5px]">
                    @if($sso)
                        <a href="{{ route('login') }}" class="text-white underline-offset-4 hover:underline">Back to NREIMS sign-in</a>
                    @endif
                    @if(Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-white underline-offset-4 hover:underline">Forgotten password?</a>
                    @endif
                </div>
            @else
                <a href="{{ route('nreims.redirect') }}" class="mt-12 flex h-14 w-full items-center justify-center gap-2.5 rounded-xl bg-white text-base font-semibold text-blue-700 shadow-brand transition-colors hover:bg-blue-50 hover:text-blue-800">
                    <x-icon name="lock" class="size-[18px]" />Sign in with NREIMS
                </a>
            @endif
        </div>
    </main>
    <footer class="flex flex-wrap justify-center gap-x-6 gap-y-1.5 border-t border-white/15 px-6 py-5 text-[12.5px] text-blue-200">
        <span>© {{ now()->year }} National Institute for Medical Research, Tanzania</span>
    </footer>
</div>
</body>
</html>
