{{--
    "Set a new password", "This link has expired" and "Password changed", from the owner's
    "Password Reset" design. Expects $state ('set', 'expired' or 'done'); when setting,
    $token and $email from the link. The rule is Password::defaults(): at least 12
    characters, upper and lower case, and a number or a symbol.
--}}
@extends('layouts.plain')

@section('title', ['set' => 'Set a new password', 'expired' => 'This link has expired', 'done' => 'Password changed'][$state])

@section('content')
@component('auth.partials.reset-card', ['back' => $state !== 'done'])
    @if($state === 'expired')
        <span class="grid size-12 place-items-center rounded-[14px] bg-warn-soft text-warn-ink"><x-icon name="clock" class="size-[22px]" /></span>
        <h1 class="mt-[18px] text-2xl font-semibold leading-tight tracking-[-0.02em]">This link has expired</h1>
        <p class="mt-2 text-[14.5px] leading-relaxed text-gray-600">Reset links work once, for {{ config('auth.passwords.users.expire') }} minutes. Ask for a new one.</p>
        <a href="{{ route('password.request') }}" class="mt-[22px] flex h-12 w-full items-center justify-center rounded-[10px] bg-blue-700 text-[15px] font-semibold text-white hover:bg-blue-800">Send a new link</a>

    @elseif($state === 'done')
        <span class="grid size-12 place-items-center rounded-[14px] bg-primary text-white"><x-icon name="check" class="size-[22px]" /></span>
        <h1 class="mt-[18px] text-2xl font-semibold leading-tight tracking-[-0.02em]">Password changed</h1>
        <p class="mt-2 text-[14.5px] leading-relaxed text-gray-600">Sign in with your new password. Any other signed-in sessions have been signed out.</p>
        <a href="{{ route('login', ['admin' => 1]) }}" class="mt-[22px] flex h-12 w-full items-center justify-center rounded-[10px] bg-blue-700 text-[15px] font-semibold text-white hover:bg-blue-800">Sign in</a>

    @else
        <h1 class="text-2xl font-semibold leading-tight tracking-[-0.02em]">Set a new password</h1>
        <p class="mt-2 text-[14.5px] leading-normal text-gray-600">Use one you don’t use anywhere else.</p>
        <form method="POST" action="{{ route('password.store') }}" class="mt-6 flex flex-col gap-4" novalidate
              x-data="{ pw: '', pw2: '', show: false, tried: @js($errors->has('password')),
                        get rules() { return [this.pw.length >= 12, /[a-z]/.test(this.pw) && /[A-Z]/.test(this.pw), /[0-9\W_]/.test(this.pw)] },
                        get met() { return this.rules.filter(Boolean).length },
                        get score() { return ! this.pw ? 0 : this.met + (this.met === 3 && this.pw.length >= 16 ? 1 : 0) },
                        get mismatch() { return this.pw2 && this.pw2 !== this.pw } }"
              @submit="tried = true; if (met < 3 || ! pw2 || mismatch) $event.preventDefault()">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">
            <label class="flex flex-col gap-1.5"><span class="text-[13px] font-medium">Email</span>
                <input type="email" class="input h-11 text-[15px]" value="{{ $email }}" disabled>
            </label>
            <label class="flex flex-col gap-1.5"><span class="text-[13px] font-medium">New password</span>
                <span class="relative block">
                    <input :type="show ? 'text' : 'password'" name="password" x-model="pw" autocomplete="new-password" autofocus
                           class="input h-11 pr-16 text-[15px]" :class="(tried && met < 3) && 'border-danger'">
                    <button type="button" @click="show = ! show" class="absolute right-1.5 top-1.5 h-8 rounded-[7px] px-2.5 text-[12.5px] font-medium text-gray-700 hover:bg-gray-100" x-text="show ? 'Hide' : 'Show'">Show</button>
                </span>
            </label>
            <div class="flex flex-col gap-2" aria-live="polite">
                <div class="flex gap-1" aria-hidden="true">
                    @for($i = 0; $i < 4; $i++)
                        <span class="h-1 flex-1 rounded-full" :class="{{ $i }} < score ? ['', 'bg-danger', 'bg-warn', 'bg-primary', 'bg-blue-700'][score] : 'bg-gray-200'"></span>
                    @endfor
                </div>
                <ul class="flex flex-col gap-[5px]">
                    @foreach(['At least 12 characters', 'Upper and lower case letters', 'A number or a symbol'] as $i => $rule)
                        <li class="flex items-center gap-2 text-[13px]" :class="rules[{{ $i }}] ? 'text-foreground' : (tried ? 'text-danger-ink' : 'text-muted-foreground')">
                            <span class="grid size-4 shrink-0 place-items-center rounded-full text-white" :class="rules[{{ $i }}] ? 'bg-primary' : (tried ? 'bg-danger' : 'bg-gray-300')"><x-icon name="check" class="size-[9px]" /></span>{{ $rule }}
                        </li>
                    @endforeach
                </ul>
                @error('password')<span class="text-[12.5px] text-danger-ink">{{ $message }}</span>@enderror
                @error('email')<span class="text-[12.5px] text-danger-ink">{{ $message }}</span>@enderror
            </div>
            <label class="flex flex-col gap-1.5"><span class="text-[13px] font-medium">Confirm new password</span>
                <input :type="show ? 'text' : 'password'" name="password_confirmation" x-model="pw2" autocomplete="new-password"
                       class="input h-11 text-[15px]" :class="((tried && ! pw2) || (mismatch && pw2.length >= pw.length)) && 'border-danger'">
                <span class="text-[12.5px] text-danger-ink" x-show="(tried && ! pw2) || (mismatch && (tried || pw2.length >= pw.length))" x-cloak
                      x-text="! pw2 ? 'Type the password again.' : 'The two passwords don’t match.'"></span>
            </label>
            <button type="submit" class="h-12 rounded-[10px] bg-blue-700 text-[15px] font-semibold text-white hover:bg-blue-800">Set password</button>
        </form>
    @endif
@endcomponent
@endsection
