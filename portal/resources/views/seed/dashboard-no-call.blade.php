@extends('layouts.gmu')

{{-- GMU's dashboard before any seed grant call exists: the one thing to do is set it up. --}}

@section('title', 'Dashboard')

@section('content')
<x-ui.page-head :title="'Good ' . (now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening')) . ', ' . \Illuminate\Support\Str::of($user->name)->before(' ') . '.'"
                lede="No seed grant call is set up yet." />

<div class="card">
    <x-ui.empty icon="fa-bullhorn" title="Set up the call to begin">
        Enter the call’s title, award range, deadlines and status. Researchers see it once you set the status to Open.
        <x-slot:action>
            <a href="{{ route('seed.call.edit') }}" class="btn btn-primary">Set up the call</a>
        </x-slot:action>
    </x-ui.empty>
</div>
@endsection
