{{--
    "Forgot your password?" and "Check your email", from the owner's "Password Reset" design.
    Only accounts with a local password (the GMU administrator) are reset here; staff reset
    theirs in NREIMS. The answer is the same whichever email is given, so nobody can use this
    page to learn which accounts exist.
--}}
@extends('layouts.plain')

@php $sent = session('reset_sent'); @endphp

@section('title', $sent ? 'Check your email' : 'Forgot your password?')

@section('content')
@component('auth.partials.reset-card')
    @if($sent)
        <span class="grid size-12 place-items-center rounded-[14px] bg-blue-50 text-ok-ink"><x-icon name="mail" class="size-[22px]" /></span>
        <h1 class="mt-[18px] text-2xl font-semibold leading-tight tracking-[-0.02em]">Check your email</h1>
        <p class="mt-2 text-[14.5px] leading-relaxed text-gray-600">If an administrator account uses <b class="break-all font-semibold text-foreground">{{ $sent }}</b>, a reset link is on its way. It works for {{ config('auth.passwords.users.expire') }} minutes.</p>
        <form method="POST" action="{{ route('password.email') }}" class="mt-[22px] rounded-[10px] bg-muted px-3.5 py-3 text-[13px] leading-normal text-gray-600">
            @csrf
            <input type="hidden" name="email" value="{{ $sent }}">
            Nothing after a few minutes? Check spam, or <button type="submit" class="font-semibold text-ok-ink hover:underline">{{ session('reset_resent') ? 'sent again.' : 'send it again.' }}</button>
            <input type="hidden" name="resend" value="1">
        </form>
    @else
        <h1 class="text-2xl font-semibold leading-tight tracking-[-0.02em]">Forgot your password?</h1>
        <p class="mt-2 text-[14.5px] leading-normal text-gray-600">Enter your email and we’ll send you a link to set a new one.</p>
        <form method="POST" action="{{ route('password.email') }}" class="mt-6 flex flex-col gap-4" x-data="{ email: @js(old('email', '')), tried: false, get ok() { return /^\S+@\S+\.\S+$/.test(this.email.trim()) } }"
              @submit="tried = true; if (! ok) $event.preventDefault()" novalidate>
            @csrf
            <label class="flex flex-col gap-1.5"><span class="text-[13px] font-medium">Email</span>
                <input type="email" name="email" x-model="email" placeholder="name@nimr.or.tz" autocomplete="email" autofocus
                       class="input h-11 text-[15px]" :class="(tried && ! ok) && 'border-danger'" @error('email') aria-invalid="true" @enderror>
                <span class="text-[12.5px] text-danger-ink" x-show="tried && ! ok" x-cloak x-text="email.trim() ? 'Enter a valid email address.' : 'Enter your email.'"></span>
                @error('email')<span class="text-[12.5px] text-danger-ink">{{ $message }}</span>@enderror
            </label>
            <button type="submit" class="h-12 rounded-[10px] bg-blue-700 text-[15px] font-semibold text-white hover:bg-blue-800">Send reset link</button>
        </form>
    @endif
@endcomponent
@endsection
