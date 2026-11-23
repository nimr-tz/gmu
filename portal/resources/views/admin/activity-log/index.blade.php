@extends('layouts.app')

{{-- Every recorded change, newest first and grouped by day, from the owner's "GMU Admin" design (ink band). --}}

@php
    $look = fn ($action) => match ($action) {
        'created'  => ['bg-blue-50 text-ok-ink', 'bg-primary'],
        'deleted'  => ['bg-danger-soft text-danger-ink', 'bg-danger'],
        'restored' => ['bg-review-soft text-review-ink', 'bg-review'],
        default    => ['bg-gray-100 text-gray-700', 'bg-gray-400'],
    };
    // Record types in plain words, not class names.
    $kind = fn ($type) => [
        'SeedApplication' => 'Application', 'SeedCall' => 'Call', 'SeedReview' => 'Review', 'SeedReviewRound' => 'Review round',
        'SeedReviewerAssignment' => 'Reviewer assignment', 'SeedApproval' => 'Approval', 'User' => 'Person',
    ][class_basename($type)] ?? Str::headline(class_basename($type));
    $filtered = collect(request()->only('user_id', 'model_type', 'action', 'date_from', 'date_to', 'search'))->filter()->isNotEmpty();
    $lastDay = null;
    $control = 'h-8 rounded-lg border border-input bg-card px-2 text-[13px]';
@endphp

@section('title', 'Audit register')
@section('context', 'Administration · Audit register')
@section('topbar-colour', 'bg-foreground')

@section('content')
<div class="-mx-4 -mt-5 flex flex-col gap-2 bg-foreground px-7 pb-[100px] pt-5 text-white lg:-mx-6 lg:-mt-6">
    <h1 class="text-[30px] font-semibold leading-tight tracking-[-0.025em] text-white">Audit register</h1>
    <p class="text-[15px] text-gray-300">{{ number_format($logs->total()) }} recorded {{ Str::plural('change', $logs->total()) }} · who changed what, when, and from where</p>
</div>

<div class="-mt-[72px]">
    <section class="overflow-hidden rounded-[14px] border border-border bg-card shadow-pop">
        <form method="GET" class="grid items-end gap-3 border-b border-border bg-muted p-3.5 [grid-template-columns:repeat(auto-fit,minmax(150px,1fr))]" onchange="this.requestSubmit()">
            <label class="flex flex-col gap-[5px]"><span class="text-[12.5px] font-medium">Person</span>
                <select name="user_id" class="{{ $control }}"><option value="">Anyone</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>@endforeach</select></label>
            <label class="flex flex-col gap-[5px]"><span class="text-[12.5px] font-medium">Record type</span>
                <select name="model_type" class="{{ $control }}"><option value="">Anything</option>@foreach($modelTypes as $type)<option value="{{ $type }}" @selected(request('model_type') === $type)>{{ $kind($type) }}</option>@endforeach</select></label>
            <label class="flex flex-col gap-[5px]"><span class="text-[12.5px] font-medium">Action</span>
                <select name="action" class="{{ $control }}"><option value="">Any action</option>@foreach($actions as $action)<option value="{{ $action }}" @selected(request('action') === $action)>{{ ucfirst($action) }}</option>@endforeach</select></label>
            <label class="flex flex-col gap-[5px]"><span class="text-[12.5px] font-medium">From</span><input type="date" name="date_from" class="{{ $control }}" value="{{ request('date_from') }}"></label>
            <label class="flex flex-col gap-[5px]"><span class="text-[12.5px] font-medium">To</span><input type="date" name="date_to" class="{{ $control }}" value="{{ request('date_to') }}"></label>
            <label class="flex flex-col gap-[5px]"><span class="text-[12.5px] font-medium">Search</span><input type="search" name="search" class="{{ $control }}" value="{{ request('search') }}" placeholder="Record or description"></label>
            <noscript><button type="submit" class="h-8 rounded-lg bg-foreground px-3 text-[13px] font-medium text-white">Apply filters</button></noscript>
        </form>
        <div class="flex items-center justify-between gap-3 border-b border-border px-[18px] py-[9px] text-[12.5px] text-muted-foreground">
            <span>{{ $filtered ? number_format($logs->total()) . ' ' . Str::plural('entry', $logs->total()) . ' match' : 'Latest entries first · showing ' . $logs->count() . ' of ' . number_format($logs->total()) }}</span>
            @if($filtered)<a href="{{ route('admin.activity-log.index') }}" class="inline-flex items-center gap-1 font-medium text-foreground hover:underline"><x-icon name="x" class="size-3" />Clear filters</a>@endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px] border-collapse text-left text-sm">
                <thead><tr>
                    <th class="h-[38px] border-b border-border pl-[18px] pr-3 text-[12.5px] font-medium text-muted-foreground">When</th>
                    <th class="border-b border-border px-3 text-[12.5px] font-medium text-muted-foreground">Who</th>
                    <th class="border-b border-border px-3 text-[12.5px] font-medium text-muted-foreground">Action</th>
                    <th class="border-b border-border px-3 text-[12.5px] font-medium text-muted-foreground">Record</th>
                    <th class="border-b border-border px-3 text-[12.5px] font-medium text-muted-foreground">From</th>
                    <th class="border-b border-border pl-3 pr-[18px]"><span class="sr-only">Detail</span></th>
                </tr></thead>
                <tbody>
                    @forelse($logs as $log)
                        @php $day = $log->created_at?->format('j M Y'); [$chip, $dot] = $look($log->action); @endphp
                        @if($day !== $lastDay)
                            @php $lastDay = $day; @endphp
                            <tr><td colspan="6" class="border-b border-gray-100 bg-gray-50 px-[18px] py-[7px] text-[12.5px] font-semibold text-gray-700">{{ $day }}</td></tr>
                        @endif
                        <tr class="relative hover:bg-gray-50">
                            <td class="whitespace-nowrap border-b border-gray-100 py-3 pl-[18px] pr-3 text-[13px]"><span class="font-mono">{{ $log->created_at?->format('H:i') }}</span><div class="text-xs text-muted-foreground">{{ $log->created_at?->diffForHumans() }}</div></td>
                            <td class="border-b border-gray-100 p-3 text-[13px]">{{ $log->user?->name ?? 'System' }}</td>
                            <td class="border-b border-gray-100 p-3"><span class="inline-flex h-[22px] items-center gap-1.5 whitespace-nowrap rounded-md px-2 text-xs font-medium {{ $chip }}"><span class="size-1.5 rounded-full {{ $dot }}"></span>{{ ucfirst($log->action) }}</span></td>
                            <td class="max-w-[360px] border-b border-gray-100 p-3">
                                <span class="block truncate font-medium">{{ $log->description ?: ($log->model_label ?? class_basename($log->model_type)) }}</span>
                                <span class="text-xs text-muted-foreground">{{ $kind($log->model_type) }} · <span class="font-mono">{{ $log->model_label ? $log->model_label . ' ' : '' }}#{{ $log->model_id }}</span></span>
                            </td>
                            <td class="border-b border-gray-100 p-3 font-mono text-xs text-muted-foreground">{{ $log->ip_address ?? '—' }}</td>
                            <td class="border-b border-gray-100 py-3 pl-3 pr-[18px] text-right"><a href="{{ route('admin.activity-log.show', $log) }}" class="text-[13px] font-medium text-ok-ink after:absolute after:inset-0 hover:underline">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-11 text-center"><p class="text-[14.5px] font-semibold">No entries match</p><p class="mt-1 text-[13px] text-muted-foreground">Every create, update and delete on a grant record is logged here with the field-level before and after.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @if($logs->hasPages())<div class="mt-4">{{ $logs->links() }}</div>@endif
</div>
@endsection
