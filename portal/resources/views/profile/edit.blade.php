@extends('layouts.app')

@section('title', 'My account')
@section('context', 'Account')

@section('content')
@php $user = auth()->user(); @endphp

<x-page-header title="My account" :description="$user->email . ' · ' . ($user->role?->display_name ?? 'No role assigned')" />

@if($user->must_change_password)
    <x-alert tone="warn" icon="lock" title="Choose your own password to continue" class="mb-6">
        The password on this account was set by somebody else. Until you replace it below, the rest of the portal is held.
    </x-alert>
@endif

<div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
    <div class="space-y-6">
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf @method('PATCH')
            <x-card title="Your details" description="Name and email as they appear across the portal" icon="user">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Full name" name="name" required>
                        <input id="name" name="name" class="input" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="name">
                    </x-field>
                    <x-field label="Email" name="email" required>
                        <input id="email" type="email" name="email" class="input" value="{{ old('email', $user->email) }}" required autocomplete="email">
                    </x-field>
                </div>
                @if($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <p class="mt-4 flex flex-wrap items-center gap-2 text-[13px] text-warn-ink">Your email address is not verified.
                        <x-button size="sm" form="send-verification" type="submit">Resend verification</x-button></p>
                @endif
                <x-slot:footer class="flex items-center justify-end gap-3">
                    @if(session('status') === 'profile-updated')<span class="flex items-center gap-1.5 text-[13px] text-ok-ink"><x-icon name="check" /> Saved</span>@endif
                    <x-button type="submit" variant="primary">Save details</x-button>
                </x-slot:footer>
            </x-card>
        </form>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf @method('PUT')
            <x-card title="Password" description="Use something you do not use anywhere else" icon="lock">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field class="sm:col-span-2" label="Current password" name="current_password" required>
                        <input id="current_password" type="password" name="current_password" class="input" required autocomplete="current-password">
                    </x-field>
                    <x-field label="New password" name="password" required>
                        <input id="password" type="password" name="password" class="input" required autocomplete="new-password">
                    </x-field>
                    <x-field label="Confirm new password" name="password_confirmation" required>
                        <input id="password_confirmation" type="password" name="password_confirmation" class="input" required autocomplete="new-password">
                    </x-field>
                </div>
                <x-slot:footer class="flex items-center justify-end gap-3">
                    @if(session('status') === 'password-updated')<span class="flex items-center gap-1.5 text-[13px] text-ok-ink"><x-icon name="check" /> Password changed</span>@endif
                    <x-button type="submit" variant="primary">Change password</x-button>
                </x-slot:footer>
            </x-card>
        </form>
    </div>

    <div class="space-y-4">
        <x-card title="Your access">
            <dl class="divide-y divide-border text-[13px]">
                @foreach([
                    'Role' => $user->role?->display_name ?? 'None',
                    'Department' => $user->department ?? '—',
                    'Research centre' => $user->research_center ?? '—',
                    'Phone' => $user->phone ?? '—',
                    'Member since' => $user->created_at?->format('M Y'),
                ] as $k => $v)
                    <div class="flex justify-between gap-3 py-2 first:pt-0"><dt class="text-muted-foreground">{{ $k }}</dt><dd class="text-right font-medium">{{ $v }}</dd></div>
                @endforeach
            </dl>
            <p class="mt-3 text-xs leading-relaxed text-muted-foreground">Your role decides what you can see and do. Only the GMU Coordinator can change it.</p>
            @if($user->isResearcher() && config('gmu.external_grants'))
                <x-button class="mt-3 w-full" :href="route('researchers.show', $user)" icon="user">My researcher profile</x-button>
            @endif
        </x-card>

        <form method="POST" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Close your account? You will be signed out and will not be able to sign back in.')">
            @csrf @method('DELETE')
            <x-card title="Close my account" tone="danger">
                <p class="text-[13px] leading-relaxed text-muted-foreground">Closing signs you out and removes your access immediately. Your grant records are institutional records and are kept. Only the Coordinator can reopen a closed account.</p>
                <x-field class="mt-4" label="Confirm with your password" name="password" for="close_password">
                    <input id="close_password" type="password" name="password" class="input" required autocomplete="current-password">
                    @error('password', 'userDeletion')<span class="error-text">{{ $message }}</span>@enderror
                </x-field>
                <x-button type="submit" variant="danger" class="mt-4 w-full" icon="trash">Close my account</x-button>
            </x-card>
        </form>
    </div>
</div>

@if($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail)
    <form id="send-verification" method="POST" action="{{ route('verification.send') }}" class="hidden">@csrf</form>
@endif
@endsection
