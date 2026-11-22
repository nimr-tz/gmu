@extends('layouts.app')

@section('title', 'Audit entry')
@section('context', 'Administration · Audit register')

@section('content')
@php
    $old  = $log->old_values ?? [];
    $new  = $log->new_values ?? [];
    $keys = collect(array_keys($old + $new))->sort()->values();
    $show = fn ($v) => is_scalar($v) || $v === null ? ($v ?? '—') : json_encode($v);
@endphp

<x-page-header :title="$log->description" :back="route('admin.activity-log.index')" back-label="Audit register">
    <x-slot:meta>
        <div class="mt-3 flex flex-wrap items-center gap-2.5 text-[13px] text-muted-foreground">
            <x-badge :tone="match ($log->action) { 'created' => 'ok', 'deleted' => 'danger', 'restored' => 'review', default => 'neutral' }">{{ ucfirst($log->action) }}</x-badge>
            <span>{{ $log->created_at?->format('j M Y H:i') }}</span><span>·</span><span>{{ $log->user?->name ?? 'System' }}</span>
        </div>
    </x-slot:meta>
</x-page-header>

<div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
    <x-card title="What changed" description="Field by field, before and after" flush>
        <div class="overflow-x-auto">
            <table class="data-table min-w-[560px]">
                <thead><tr><th class="w-48">Field</th><th>Before</th><th>After</th></tr></thead>
                <tbody>
                    @forelse($keys as $key)
                        @php $before = $old[$key] ?? null; $after = $new[$key] ?? null; @endphp
                        <tr class="align-top">
                            <td class="font-medium">{{ Str::headline($key) }}</td>
                            <td class="break-words text-[13px] text-muted-foreground {{ array_key_exists($key, $new) && $before !== null ? 'line-through decoration-gray-400' : '' }}">{{ $show($before) }}</td>
                            <td class="break-words text-[13px]">{{ $show($after) }}</td>
                        </tr>
                    @empty
                        <tr class="hover:bg-transparent"><td colspan="3"><x-empty icon="file" compact>No field values were recorded for this entry.</x-empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-card title="Context">
        <dl class="divide-y divide-border text-[13px]">
            @foreach([
                'Record' => class_basename($log->model_type) . ' #' . $log->model_id,
                'Label' => $log->model_label ?? '—',
                'Person' => $log->user?->name ?? 'System',
                'When' => $log->created_at?->format('j M Y H:i:s'),
            ] as $k => $v)
                <div class="flex justify-between gap-4 py-2 first:pt-0"><dt class="text-muted-foreground">{{ $k }}</dt><dd class="min-w-0 truncate text-right font-medium">{{ $v }}</dd></div>
            @endforeach
            <div class="flex justify-between gap-4 py-2"><dt class="text-muted-foreground">IP address</dt><dd class="font-mono">{{ $log->ip_address ?? '—' }}</dd></div>
        </dl>
        @if($log->user_agent)
            <p class="mt-4 border-t border-border pt-3 text-xs font-medium uppercase tracking-wider text-muted-foreground">User agent</p>
            <p class="mt-1 break-words text-xs text-muted-foreground">{{ $log->user_agent }}</p>
        @endif
    </x-card>
</div>
@endsection
