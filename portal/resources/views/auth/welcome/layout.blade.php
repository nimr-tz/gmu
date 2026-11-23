{{--
    The first-sign-in welcome, from the owner's "Onboarding" design: a blue panel with
    the crest, the greeting and the two steps, and a white page beside it.
    Expects $user, $steps (titles), $current (0-based index) and $greeting.
--}}
@php
    $first = \Illuminate\Support\Str::of($user->name)->replaceMatches('/^(Dr\.?|Prof\.?|Mr\.?|Ms\.?|Mrs\.?)\s+/i', '')->before(' ');
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · NIMR Grants</title>
    <link rel="icon" type="image/png" href="{{ asset('images/nimr-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-primary">
<div class="flex min-h-screen flex-wrap bg-primary">
    <aside class="flex min-w-[300px] flex-[0_1_420px] flex-col justify-between gap-10 p-10 text-white">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/nimr-logo.png') }}" alt="" class="size-11 rounded-full bg-white object-contain p-0.5">
            <div class="leading-snug"><p class="text-[14.5px] font-semibold">National Institute for Medical Research</p><p class="text-[12.5px] text-blue-100">Grants Management Unit</p></div>
        </div>

        <div class="flex flex-col gap-7">
            <h2 class="text-[40px] font-semibold leading-[1.05] tracking-[-0.035em] text-white">{{ $greeting }}, {{ $first }}.</h2>
            <ol class="flex flex-col gap-2.5" aria-label="Setting up your account">
                @foreach($steps as $i => $title)
                    <li class="flex items-center gap-3 text-[15px] {{ $i === $current ? 'font-semibold text-white' : ($i < $current ? 'font-medium text-white' : 'font-medium text-blue-200') }}" @if($i === $current) aria-current="step" @endif>
                        @if($i < $current)
                            <span class="grid size-[26px] place-items-center rounded-full bg-white text-primary"><x-icon name="check" class="size-[13px]" /><span class="sr-only">Done:</span></span>
                        @elseif($i === $current)
                            <span class="grid size-[26px] place-items-center rounded-full bg-white font-mono text-xs font-semibold text-primary ring-[5px] ring-white/20">{{ $i + 1 }}</span>
                        @else
                            <span class="grid size-[26px] place-items-center rounded-full border-[1.5px] border-white/40 font-mono text-xs">{{ $i + 1 }}</span>
                        @endif
                        <span>{{ $title }}</span>
                    </li>
                @endforeach
            </ol>
        </div>

        <div class="flex items-center justify-between gap-3 text-[13px] text-blue-100">
            <span class="min-w-0 truncate">{{ $user->email }}</span>
            <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                @csrf
                <button type="submit" class="font-medium text-white underline">Sign out</button>
            </form>
        </div>
    </aside>

    <main class="flex min-h-screen min-w-0 flex-[1_1_480px] items-center justify-center bg-card px-6 py-14 text-foreground sm:px-12 sm:rounded-l-[28px]">
        <div class="flex w-full max-w-[560px] flex-col">
            @yield('content')
        </div>
    </main>
</div>
@stack('scripts')
</body>
</html>
