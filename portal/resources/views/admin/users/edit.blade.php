@extends('layouts.app')

@section('title', 'Edit account')
@section('context', 'Administration · People and roles')

@section('content')
<x-page-header title="Edit account" :back="route('admin.users.show', $user)" :back-label="$user->name" :description="$user->name . ' · ' . $user->email" />

<div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
    <form method="POST" action="{{ route('admin.users.update', $user) }}" id="user-form" class="space-y-6">
        @csrf @method('PUT')
        <x-card title="The person" icon="user">
            @include('admin.users.partials.fields')
        </x-card>

        <x-card title="Reset the password" icon="lock" description="Setting a password here holds the account at the profile page until the person chooses their own.">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="New password" name="password" help="Leave blank to keep the current one.">
                    <input id="password" type="password" name="password" class="input" minlength="8" autocomplete="new-password">
                </x-field>
                <x-field label="Confirm" name="password_confirmation">
                    <input id="password_confirmation" type="password" name="password_confirmation" class="input" minlength="8" autocomplete="new-password">
                </x-field>
            </div>
        </x-card>
    </form>

    <div class="space-y-4">
        <x-card title="Access" :tone="$user->is_active ? null : 'danger'">
            <label class="flex items-center gap-3 text-sm">
                <input type="hidden" name="is_active" value="0" form="user-form">
                <input type="checkbox" class="switch" name="is_active" value="1" form="user-form" @checked(old('is_active', $user->is_active))> Account is active
            </label>
            <p class="mt-3 text-xs text-muted-foreground">Deactivating takes effect immediately: the person cannot sign in, and any open session ends on their next click. Their records stay exactly as they are.</p>
        </x-card>

        <div class="flex gap-2">
            <x-button :href="route('admin.users.index')">Cancel</x-button>
            <x-button type="submit" form="user-form" variant="primary" class="flex-1">Save changes</x-button>
        </div>

        @if($user->id !== auth()->id())
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Deactivate this account?')">
                @csrf @method('DELETE')
                <x-card title="Deactivate this account" tone="danger">
                    <p class="text-xs text-muted-foreground">Accounts are never deleted: grant records have to outlive the people who created them. This deactivates instead.</p>
                    <x-button type="submit" variant="danger" class="mt-3 w-full">Deactivate account</x-button>
                </x-card>
            </form>
        @endif
    </div>
</div>
@endsection
