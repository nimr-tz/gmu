@extends('layouts.app')

{{--
    GMU's dashboard across every call, from the owner's "GMU" design (ink band):
      four figures → what needs GMU now → each call's money → reviewers still owing reviews.
--}}

@php
    $m = fn ($n) => $n ? number_format($n / 1e6, 1) . 'M' : '0';
    $pct = $kpi['available'] ? round($kpi['approved'] / $kpi['available'] * 100) : null;
    $daysLeft = $nextDeadline ? (int) now()->startOfDay()->diffInDays($nextDeadline->concept_deadline->copy()->startOfDay()) : null;
    $queue = route('seed.submissions.index', ['call' => 'all', 'filter' => 'action']);

    // The next step's button, tinted by what kind of step it is.
    $step = [
        ''       => 'border-warn-line bg-warn-soft text-warn-ink hover:bg-amber-100',
        'warn'   => 'border-review-line bg-review-soft text-review-ink hover:bg-violet-100',
        'info'   => 'border-warn-line bg-warn-soft text-warn-ink hover:bg-amber-100',
        'ok'     => 'border-ok-line bg-ok-soft text-ok-ink hover:bg-blue-100',
        'danger' => 'border-danger-line bg-danger-soft text-danger-ink hover:bg-red-100',
    ];
    $tile = 'flex flex-col gap-2.5 rounded-[14px] p-5 shadow-lift';
@endphp

@section('title', 'Dashboard')
@section('context', 'Home · Dashboard')
@section('topbar-colour', 'bg-foreground')

@section('content')
<div class="-mx-4 -mt-5 flex flex-wrap items-start justify-between gap-5 bg-foreground px-7 pb-[100px] pt-5 text-white lg:-mx-6 lg:-mt-6">
    <div class="flex min-w-0 flex-col gap-2">
        <p class="text-[13px] text-gray-400">{{ now()->format('l, j F Y') }} · {{ $callCount }} {{ str('call')->plural($callCount) }} running · {{ $submittedCount }} submitted</p>
        <h1 class="text-[30px] font-semibold leading-tight tracking-[-0.025em] text-white">Grants overview</h1>
    </div>
    <div class="flex flex-wrap justify-end gap-2">
        <a href="{{ route('seed.submissions.export', ['call' => 'all']) }}" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-gray-700 px-3.5 text-[13px] font-medium text-white hover:bg-gray-800"><x-icon name="download" class="size-3.5" />Export</a>
        <a href="{{ $queue }}" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3.5 text-[13px] font-semibold text-foreground hover:bg-gray-100">Work through submissions<x-icon name="arrow-right" class="size-3.5" /></a>
    </div>
</div>

<div class="-mt-[72px] flex flex-col gap-5">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ $queue }}" class="{{ $tile }} bg-warn text-amber-950 hover:bg-amber-400">
            <span class="flex items-center justify-between gap-2"><span class="text-[13.5px] font-semibold">Needs GMU</span>@if($kpi['screen'])<span class="inline-flex h-[22px] items-center whitespace-nowrap rounded-md bg-amber-950/10 px-2 text-xs font-semibold">{{ $kpi['screen'] }} to screen</span>@endif</span>
            <span class="text-[30px] font-semibold leading-tight tracking-[-0.02em] tabular-nums">{{ $kpi['needs'] }}</span>
            <span class="text-[13px] text-amber-900">{{ $kpi['needs'] ? str('Application')->plural($kpi['needs']) . ' waiting on GMU' : 'Nothing is waiting on GMU' }}</span>
        </a>
        <div class="{{ $tile }} bg-violet-700 text-white">
            <span class="flex items-center justify-between gap-2"><span class="text-[13.5px] font-semibold text-violet-100">With reviewers</span><span class="inline-flex h-[22px] items-center whitespace-nowrap rounded-md bg-white/15 px-2 text-xs font-semibold">{{ $kpi['late'] ? $kpi['late'] . ' late' : 'None late' }}</span></span>
            <span class="text-[30px] font-semibold leading-tight tracking-[-0.02em] tabular-nums">{{ $kpi['pending'] }}</span>
            <span class="text-[13px] text-violet-200">{{ str('Review')->plural($kpi['pending']) }} still to come in{{ $kpi['nextDue'] ? ', due ' . $kpi['nextDue']->format('j M Y') : '' }}</span>
        </div>
        <div class="{{ $tile }} border border-border bg-card">
            <span class="flex items-center justify-between gap-2"><span class="text-[13.5px] font-medium">Funds approved</span>@if($pct !== null)<span class="inline-flex h-[22px] items-center rounded-md bg-blue-50 px-2 text-xs font-semibold text-ok-ink">{{ $pct }}%</span>@endif</span>
            <span class="text-[30px] font-semibold leading-tight tracking-[-0.02em] text-ok-ink tabular-nums">TZS {{ $m($kpi['approved']) }}</span>
            <x-meter :value="$pct ?? 0" label="Funds approved" />
            <span class="text-[13px] text-muted-foreground">{{ $kpi['available'] ? 'Of TZS ' . $m($kpi['available']) . ' across the calls running' : 'Set funds available on each call to track this' }}</span>
        </div>
        <a href="{{ route('seed.calls.index') }}" class="{{ $tile }} border border-border bg-card hover:border-sky-200">
            <span class="flex items-center justify-between gap-2"><span class="text-[13.5px] font-medium">Next deadline</span>@if($nextDeadline)<span class="inline-flex h-[22px] items-center whitespace-nowrap rounded-md bg-sky-100 px-2 text-xs font-semibold text-sky-700">{{ $daysLeft === 0 ? 'Today' : 'In ' . $daysLeft . ' ' . str('day')->plural($daysLeft) }}</span>@endif</span>
            <span class="text-[30px] font-semibold leading-tight tracking-[-0.02em] tabular-nums">{{ $nextDeadline ? $nextDeadline->concept_deadline->format('j M Y') : 'None ahead' }}</span>
            <span class="text-[13px] text-muted-foreground">{{ $nextDeadline ? 'Concept notes close for ' . $nextDeadline->code : 'No open call has a concept-note deadline to come' }}</span>
        </a>
    </div>

    <div class="flex flex-wrap items-start gap-5">
        {{-- What needs GMU now --}}
        <section class="min-w-0 flex-[1_1_640px] overflow-hidden rounded-[14px] border border-border">
            <header class="flex items-center justify-between gap-3 border-b border-border bg-muted px-[18px] py-3">
                <div><h2 class="text-sm font-semibold">Needs your action</h2><p class="text-[13px] text-muted-foreground">Oldest first, across all calls</p></div>
                <a href="{{ $queue }}" class="inline-flex h-8 items-center rounded-[7px] border border-input bg-card px-3 text-[13px] font-medium hover:bg-gray-100">Open the queue</a>
            </header>
            @if($needs->isEmpty())
                <div class="px-5 py-9 text-center"><p class="text-[14.5px] font-semibold">All caught up</p><p class="mt-1 text-[13px] text-muted-foreground">Nothing is waiting on GMU in any call. New concept notes and finished review rounds appear here.</p></div>
            @else
                <div class="overflow-x-auto bg-card">
                    <table class="w-full min-w-[680px] border-collapse text-left text-sm">
                        <thead><tr>
                            <th class="h-[38px] border-b border-border pl-[18px] pr-3 text-[12.5px] font-medium text-muted-foreground">Application</th>
                            <th class="border-b border-border px-3 text-[12.5px] font-medium text-muted-foreground">Status</th>
                            <th class="border-b border-border px-3 text-[12.5px] font-medium text-muted-foreground">Waiting since</th>
                            <th class="border-b border-border pl-3 pr-[18px] text-right text-[12.5px] font-medium text-muted-foreground">Next step</th>
                        </tr></thead>
                        <tbody>
                            @foreach($needs->take(10) as $n)
                                @php $a = $n['application']; @endphp
                                <tr>
                                    <td class="min-w-[260px] border-b border-gray-100 py-[13px] pl-[18px] pr-3">
                                        <a href="{{ route('seed.submissions.show', $a) }}" class="font-medium leading-snug hover:text-ok-ink">{{ $a->title }}</a>
                                        <div class="mt-1 flex flex-wrap items-center gap-2 text-[12.5px] text-muted-foreground">
                                            @include('seed.partials.call-tag', ['call' => $a->call, 'short' => true])
                                            <span class="font-mono text-xs">{{ $a->reference }}</span>
                                            <span>{{ $a->pi->name }}</span>
                                        </div>
                                    </td>
                                    <td class="border-b border-gray-100 px-3 py-[13px]">@include('seed.partials.status', ['application' => $a])</td>
                                    <td class="whitespace-nowrap border-b border-gray-100 px-3 py-[13px] text-[13px]">{{ $a->updated_at->format('j M Y') }}<div class="text-xs text-muted-foreground">{{ $a->updated_at->diffForHumans() }}</div></td>
                                    <td class="border-b border-gray-100 py-[13px] pl-3 pr-[18px] text-right">
                                        <a href="{{ $n['toRanking'] ? route('seed.ranking', ['call' => $a->seed_call_id]) : route('seed.submissions.show', $a) }}"
                                           class="inline-flex h-7 items-center whitespace-nowrap rounded-[7px] border px-2.5 text-[12.5px] font-medium transition-colors {{ $step[$n['tone']] ?? $step['ok'] }}">{{ $n['action'] }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($needs->count() > 10)
                    <footer class="border-t border-border bg-gray-50 px-[18px] py-3 text-[13px]"><a href="{{ $queue }}" class="font-medium text-ok-ink hover:underline">{{ $needs->count() - 10 }} more in the queue</a></footer>
                @endif
            @endif
        </section>

        <div class="flex min-w-[300px] flex-[0_1_360px] flex-col gap-4">
            {{-- Each call's money --}}
            <section class="overflow-hidden rounded-[14px] border border-border">
                <header class="flex items-center justify-between gap-3 border-b border-border bg-muted px-4 py-3">
                    <div><h3 class="text-sm font-semibold">Calls</h3><p class="text-[13px] text-muted-foreground">Money approved against funds available</p></div>
                    <a href="{{ route('seed.calls.index') }}" class="text-[13px] font-medium text-ok-ink hover:underline">All calls</a>
                </header>
                <ul class="divide-y divide-gray-100 bg-card">
                    @forelse($cards as $card)
                        @php $c = $card['call']; $share = $c->envelope ? min(100, $card['approved'] / $c->envelope * 100) : 0; @endphp
                        <li class="{{ $c->tintClass() }} flex flex-col gap-2 px-4 py-3.5">
                            <div class="flex items-center gap-2">
                                @include('seed.partials.call-tag', ['call' => $c, 'short' => true])
                                <a href="{{ route('seed.submissions.index', ['call' => $c->id]) }}" class="min-w-0 flex-1 truncate text-[13px] font-medium hover:text-ok-ink">{{ $c->title }}</a>
                                <span class="text-xs font-medium {{ $c->status === 'open' ? 'text-ok-ink' : 'text-muted-foreground' }}">{{ \App\Models\Seed\SeedCall::STATUSES[$c->status] }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-tint ring-1 ring-inset ring-tint-line"><div class="h-full rounded-full bg-tint-solid" style="width: {{ $share }}%"></div></div>
                            <div class="flex justify-between gap-3 text-[12.5px] text-muted-foreground">
                                <span><b class="font-semibold text-foreground">TZS {{ $m($card['approved']) }}</b> of {{ $c->envelope ? $m($c->envelope) : 'not set' }}</span>
                                <span>{{ $card['submitted'] }} {{ str('application')->plural($card['submitted']) }}</span>
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-[13px] text-muted-foreground">No calls running. Open a call and it appears here.</li>
                    @endforelse
                </ul>
            </section>

            {{-- Who still owes reviews --}}
            <section class="overflow-hidden rounded-[14px] border border-border">
                <header class="border-b border-border bg-muted px-4 py-3"><h3 class="text-sm font-semibold">Reviewers</h3><p class="text-[13px] text-muted-foreground">Reviews done, in open rounds</p></header>
                <ul class="bg-card px-4 py-1">
                    @forelse($load as $r)
                        <li class="flex items-center gap-2.5 border-b border-gray-100 py-[9px] last:border-0">
                            <span class="min-w-0 flex-1 truncate text-[13.5px]">{{ $r['name'] }}</span>
                            <span class="flex gap-[3px]" aria-hidden="true">
                                @for($i = 0; $i < min($r['total'], 8); $i++)
                                    <span class="size-2 rounded-sm {{ $i < $r['done'] ? 'bg-primary' : ($i < $r['done'] + $r['late'] ? 'bg-danger' : 'bg-gray-200') }}"></span>
                                @endfor
                            </span>
                            <span class="w-[78px] whitespace-nowrap text-right text-xs font-medium {{ $r['late'] ? 'text-danger-ink' : ($r['done'] === $r['total'] ? 'text-ok-ink' : 'text-muted-foreground') }}">{{ $r['done'] }} of {{ $r['total'] }} done</span>
                        </li>
                    @empty
                        <li class="py-6 text-center text-[13px] text-muted-foreground">No open review rounds. Reviewers appear here once GMU assigns them.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
</div>
@endsection
