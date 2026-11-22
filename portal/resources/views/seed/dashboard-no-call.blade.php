@extends('layouts.app')

{{-- GMU's dashboard before any call exists: the one thing to do is set one up. --}}

@section('title', 'Dashboard')

@section('content')
<x-page-header :title="'Good ' . (now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening')) . ', ' . \Illuminate\Support\Str::of($user->name)->before(' ') . '.'"
               description="No call is set up yet." />

<x-card>
    <x-empty icon="megaphone" title="Set up the call to begin">
        Enter the call’s title, award range, deadlines and status. Researchers see it once you set the status to Open.
        <x-slot:action><x-button variant="primary" :href="route('seed.calls.create')" icon="plus">Set up the call</x-button></x-slot:action>
    </x-empty>
</x-card>
@endsection
