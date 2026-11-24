{{--
    The brand-blue page with no side panel, for pages seen outside the portal's frame:
    the error pages and the administrator's password reset (the owner's "Error" and
    "Password Reset" designs). Pages fill `content`, and may set `title` and `footer`.
--}}
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
<body class="h-full bg-primary text-white">
<div class="flex min-h-full flex-col bg-primary bg-brand-glow">
    @yield('header')
    <main class="flex flex-1 items-center justify-center px-6 pb-[72px] pt-12">
        @yield('content')
    </main>
    <footer class="flex flex-wrap justify-center gap-x-6 gap-y-1.5 border-t border-white/15 px-6 py-5 text-[12.5px] text-blue-200">
        @hasSection('footer')
            @yield('footer')
        @else
            <span>© {{ now()->year }} National Institute for Medical Research, Tanzania</span>
        @endif
    </footer>
</div>
</body>
</html>
