@extends('layouts.app')

{{-- One recorded change, field by field, from the owner's "GMU Admin" design (ink band). --}}

@php
    $old  = $log->old_values ?? [];
    $new  = $log->new_values ?? [];
    $keys = collect(array_keys($old + $new))->sort()->values();
    $show = fn ($v) => is_scalar($v) || $v === null ? (string) ($v ?? '—') : json_encode($v);
    [$chipInk, $dot] = match ($log->action) {
        'created'  => ['text-ok-ink', 'bg-primary'],
        'deleted'  => ['text-danger-ink', 'bg-danger'],
        'restored' => ['text-review-ink', 'bg-review'],
        default    => ['text-gray-700', 'bg-gray-400'],
    };
    $kind = [
        'SeedApplication' => 'Application', 'SeedCall' => 'Call', 'SeedReview' => 'Review', 'SeedReviewRound' => 'Review round',
        'SeedReviewerAssignment' => 'Reviewer assignment', 'SeedApproval' => 'Approval', 'User' => 'Person',
    ][class_basename($log->model_type)] ?? Str::headline(class_basename($log->model_type));

    // Where the record lives now, if it still does.
    $record = class_exists($log->model_type) ? $log->model_type::find($log->model_id) : null;
    $link = match (true) {
        $record instanceof \App\Models\Seed\SeedApplication => route('seed.submissions.show', $record),
        $record instanceof \App\Models\Seed\SeedCall => route('seed.calls.show', $record),
        $record instanceof \App\Models\User => route('admin.users.show', $record),
        $record instanceof \App\Models\Seed\SeedReviewerAssignment, $record instanceof \App\Models\Seed\SeedApproval => $record->application ? route('seed.submissions.show', $record->application) : null,
        $record instanceof \App\Models\Seed\SeedReview => $record->assignment?->application ? route('seed.submissions.show', $record->assignment->application) : null,
        default => null,
    };
@endphp

@section('title', 'Audit entry')
@section('context', 'Administration · Audit register · Entry #' . $log->id)
@section('topbar-colour', 'bg-foreground')

@section('content')
<div class="-mx-4 -mt-5 flex flex-col gap-2.5 bg-foreground px-7 pb-[100px] pt-[18px] text-white lg:-mx-6 lg:-mt-6">
    <a href="{{ route('admin.activity-log.index') }}" class="inline-flex items-center gap-1.5 self-start text-[13px] font-medium text-gray-300 hover:text-white"><x-icon name="arrow-left" class="size-3.5" />Audit register</a>
    <div class="flex flex-wrap items-center gap-2 text-[13px] text-gray-300">
        <span class="inline-flex h-[22px] items-center gap-1.5 rounded-md bg-white px-2 text-xs font-semibold {{ $chipInk }}"><span class="size-1.5 rounded-full {{ $dot }}"></span>{{ ucfirst($log->action) }}</span>
        <span>{{ $log->created_at?->format('j M Y H:i') }}</span><span>·</span><span>{{ $log->user?->name ?? 'System' }}</span>
    </div>
    <h1 class="max-w-[900px] text-balance text-[28px] font-semibold leading-tight tracking-[-0.025em] text-white">{{ $log->description ?: $kind . ' #' . $log->model_id }}</h1>
</div>

<div class="-mt-[72px] flex flex-wrap items-start gap-5">
    <section class="min-w-0 flex-[1_1_560px] overflow-hidden rounded-[14px] border border-border bg-card shadow-pop">
        <header class="border-b border-border bg-muted px-[18px] py-3"><h2 class="text-sm font-semibold">What changed</h2><p class="text-[13px] text-muted-foreground">Field by field, before and after</p></header>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] border-collapse text-left text-[13.5px]">
                <thead><tr>
                    <th class="h-[38px] w-[190px] border-b border-border pl-[18px] pr-3 text-[12.5px] font-medium text-muted-foreground">Field</th>
                    <th class="border-b border-border px-3 text-[12.5px] font-medium text-muted-foreground">Before</th>
                    <th class="w-7 border-b border-border"></th>
                    <th class="border-b border-border pl-3 pr-[18px] text-[12.5px] font-medium text-muted-foreground">After</th>
                </tr></thead>
                <tbody>
                    @forelse($keys as $key)
                        @php
                            $before = $old[$key] ?? null; $after = $new[$key] ?? null;
                            $hasBefore = $before !== null && $before !== ''; $hasAfter = $after !== null && $after !== '';
                        @endphp
                        <tr class="align-top">
                            <td class="border-b border-gray-100 py-3 pl-[18px] pr-3 font-medium">{{ Str::headline($key) }}</td>
                            <td class="border-b border-gray-100 p-3"><span class="inline-block break-words rounded-[5px] px-1.5 py-0.5 font-mono text-[12.5px] {{ $hasBefore ? 'bg-danger-soft text-danger-ink' : 'text-subtle' }} {{ $hasBefore && $hasAfter ? 'line-through' : '' }}">{{ $hasBefore ? $show($before) : '—' }}</span></td>
                            <td class="border-b border-gray-100 py-3.5 text-subtle"><x-icon name="arrow-right" class="size-3.5" /></td>
                            <td class="border-b border-gray-100 py-3 pl-3 pr-[18px]"><span class="inline-block break-words rounded-[5px] px-1.5 py-0.5 font-mono text-[12.5px] {{ $hasAfter ? 'bg-blue-50 text-ok-ink' : 'text-subtle' }}">{{ $hasAfter ? $show($after) : '—' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-[13px] text-muted-foreground">No field values were recorded for this entry.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <aside class="flex min-w-[280px] flex-[0_1_360px] flex-col gap-4">
        <section class="overflow-hidden rounded-[14px] border border-border bg-card shadow-pop">
            <header class="border-b border-border bg-muted px-4 py-3"><h3 class="text-sm font-semibold">Context</h3></header>
            <dl class="px-4 pb-2 pt-1">
                @foreach([
                    ['Record', $kind . ' #' . $log->model_id, false],
                    ['Label', $log->model_label ?? '—', true],
                    ['Person', $log->user?->name ?? 'System', false],
                    ['When', $log->created_at?->format('j M Y H:i:s'), false],
                    ['IP address', $log->ip_address ?? '—', true],
                ] as [$k, $v, $mono])
                    <div class="flex justify-between gap-4 border-b border-gray-100 py-2.5 text-[13px] last:border-0"><dt class="text-muted-foreground">{{ $k }}</dt><dd class="min-w-0 truncate text-right font-medium {{ $mono ? 'font-mono' : '' }}">{{ $v }}</dd></div>
                @endforeach
            </dl>
            @if($log->user_agent)
                <div class="px-4 pb-3.5 pt-2.5"><p class="text-xs font-medium text-muted-foreground">User agent</p><p class="mt-1 break-words font-mono text-[11.5px] leading-normal text-muted-foreground">{{ $log->user_agent }}</p></div>
            @endif
        </section>
        @if($link)
            <a href="{{ $link }}" class="flex items-center justify-between gap-2.5 rounded-xl border border-border bg-card px-4 py-3 text-[13.5px] font-medium hover:bg-gray-50">Open the record<x-icon name="arrow-right" class="size-3.5" /></a>
        @endif
    </aside>
</div>
@endsection
