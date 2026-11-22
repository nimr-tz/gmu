@extends('layouts.app')

@section('title', 'Audit register')
@section('context', 'Administration · Audit register')

@php
    $tone = fn ($action) => match ($action) { 'created' => 'ok', 'deleted' => 'danger', 'restored' => 'review', default => 'neutral' };
    $filtered = collect(request()->only('user_id', 'model_type', 'action', 'date_from', 'date_to', 'search'))->filter()->isNotEmpty();
@endphp

@section('content')
<x-page-header title="Audit register"
    :description="number_format($logs->total()) . ' recorded ' . Str::plural('change', $logs->total()) . ' · who changed what, when, and from where'" />

<form method="GET" class="mb-4 rounded-xl border border-border bg-card p-4 shadow-xs">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <x-field label="Person" name="user_id">
            <select id="user_id" name="user_id" class="select select-sm">
                <option value="">Anyone</option>
                @foreach($users as $user)<option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>@endforeach
            </select>
        </x-field>
        <x-field label="Record type" name="model_type">
            <select id="model_type" name="model_type" class="select select-sm">
                <option value="">Anything</option>
                @foreach($modelTypes as $type)<option value="{{ $type }}" @selected(request('model_type') === $type)>{{ $type }}</option>@endforeach
            </select>
        </x-field>
        <x-field label="Action" name="action">
            <select id="action" name="action" class="select select-sm">
                <option value="">Any action</option>
                @foreach($actions as $action)<option value="{{ $action }}" @selected(request('action') === $action)>{{ ucfirst($action) }}</option>@endforeach
            </select>
        </x-field>
        <x-field label="From" name="date_from"><input id="date_from" type="date" name="date_from" class="input input-sm" value="{{ request('date_from') }}"></x-field>
        <x-field label="To" name="date_to"><input id="date_to" type="date" name="date_to" class="input input-sm" value="{{ request('date_to') }}"></x-field>
        <x-field label="Search" name="search"><input id="search" type="search" name="search" class="input input-sm" value="{{ request('search') }}" placeholder="Record or description"></x-field>
    </div>
    <div class="mt-3 flex justify-end gap-2">
        @if($filtered)<x-button size="sm" variant="ghost" :href="route('admin.activity-log.index')" icon="x">Clear</x-button>@endif
        <x-button size="sm" type="submit" variant="primary" icon="filter">Apply filters</x-button>
    </div>
</form>

<x-card flush>
    <div class="overflow-x-auto">
        <table class="data-table min-w-[760px]">
            <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Record</th><th>From</th><th class="text-right"><span class="sr-only">Detail</span></th></tr></thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap text-[13px]">{{ $log->created_at?->format('j M Y H:i') }}<div class="text-xs text-muted-foreground">{{ $log->created_at?->diffForHumans() }}</div></td>
                        <td class="text-[13px]">{{ $log->user?->name ?? 'System' }}</td>
                        <td><x-badge :tone="$tone($log->action)">{{ ucfirst($log->action) }}</x-badge></td>
                        <td class="max-w-xs">
                            <span class="block truncate font-medium">{{ $log->model_label ?? class_basename($log->model_type) }}</span>
                            <span class="font-mono text-xs text-muted-foreground">{{ class_basename($log->model_type) }} #{{ $log->model_id }}</span>
                        </td>
                        <td class="font-mono text-xs text-muted-foreground">{{ $log->ip_address }}</td>
                        <td class="text-right"><x-button size="sm" :href="route('admin.activity-log.show', $log)">Detail</x-button></td>
                    </tr>
                @empty
                    <tr class="hover:bg-transparent"><td colspan="6"><x-empty icon="history" title="No entries match" compact>Every create, update and delete on a grant record is logged here with the field-level before and after.</x-empty></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@if($logs->hasPages())<div class="mt-4">{{ $logs->links() }}</div>@endif
@endsection
