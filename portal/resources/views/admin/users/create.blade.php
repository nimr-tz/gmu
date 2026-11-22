@extends('layouts.app')

@section('title', $sso ? 'Add a person' : 'Create an account')
@section('context', 'Administration · People and roles')

@section('content')
<x-page-header :title="$sso ? 'Add a person' : 'Create an account'" :back="route('admin.users.index')" back-label="People and roles"
    :description="$sso
        ? 'Add someone before they have signed in, for example to make them a reviewer. Use the email they have in NREIMS: their account links the first time they sign in with it.'
        : 'The person is held at their profile page until they replace the password you set here.'" />

<form method="POST" action="{{ route('admin.users.store') }}" class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
    @csrf
    <x-card title="The person" icon="user">
        @include('admin.users.partials.fields', ['user' => null])
    </x-card>

    <div class="space-y-4">
        @if($sso)
            <x-card title="Signing in">
                <p class="text-[13px] text-muted-foreground">They sign in with NREIMS. No password is needed here.</p>
                <details class="group mt-3" @if($errors->has('password')) open @endif>
                    <summary class="flex cursor-pointer list-none items-center gap-1.5 text-[13px] font-medium [&::-webkit-details-marker]:hidden">
                        <x-icon name="chevron-right" class="size-3.5 text-subtle transition-transform group-open:rotate-90" /> Give a local password instead
                    </summary>
                    <p class="mt-2 text-xs text-muted-foreground">Only for a fallback administrator who must get in when NREIMS is down.</p>
                    <div class="mt-3 space-y-4">
                        <x-field label="Password" name="password" help="At least 8 characters.">
                            <input id="password" type="password" name="password" class="input" minlength="8" autocomplete="new-password">
                        </x-field>
                        <x-field label="Confirm password" name="password_confirmation">
                            <input id="password_confirmation" type="password" name="password_confirmation" class="input" minlength="8" autocomplete="new-password">
                        </x-field>
                    </div>
                </details>
            </x-card>
        @else
            <x-card title="First password">
                <div class="space-y-4">
                    <x-field label="Password" name="password" required help="At least 8 characters.">
                        <input id="password" type="password" name="password" class="input" required minlength="8" autocomplete="new-password">
                    </x-field>
                    <x-field label="Confirm password" name="password_confirmation" required>
                        <input id="password_confirmation" type="password" name="password_confirmation" class="input" required minlength="8" autocomplete="new-password">
                    </x-field>
                </div>
                <p class="mt-3 text-xs text-muted-foreground">Hand this over in person or by a channel you trust, not by email alongside the address. The account cannot be used for anything else until they set their own.</p>
            </x-card>
        @endif

        <x-card title="Access">
            <label class="flex items-center gap-3 text-sm">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" class="switch" name="is_active" value="1" @checked(old('is_active', true))> Account is active
            </label>
        </x-card>

        <div class="flex gap-2">
            <x-button :href="route('admin.users.index')">Cancel</x-button>
            <x-button type="submit" variant="primary" class="flex-1" icon="user-plus">{{ $sso ? 'Add person' : 'Create account' }}</x-button>
        </div>
    </div>
</form>
@endsection
