@extends('layouts.app')

{{--
    Awaiting decision, from the owner's "Approvals" design: GMU sign-off, DRCP clearance
    and the Director General use it alike. An indigo band with the call and how many
    wait; what waits and what is approved; the queue in rank order with each one's
    sign-off chain; and, beside it, what is elsewhere in approval, what was not
    recommended, and what happens when the DG approves.
--}}

@php
    use App\Support\SeedGrant as S;
    $form   = $call->form();
    $chain  = $form->approvalLevels();
    $names  = ['gmu' => 'GMU', 'drcp' => 'DRCP', 'dg' => 'DG'];
    $who    = ['gmu' => 'GMU sign-off', 'drcp' => 'DRCP clearance', 'dg' => 'your decision'][$level];
    $m      = fn ($n) => 'TZS ' . number_format($n / 1e6, 1) . 'M';
    $queueTotal    = $queue->sum(fn ($r) => $r['application']->budgetTotal('proposal'));
    $approvedTotal = $decided->sum('approved_amount');
    $share  = $call->envelope ? (int) min(100, round($approvedTotal / max(1, $call->envelope) * 100)) : null;
    $maxTotal = max(1, $form->maxTotal());
    $passPct  = $form->passMark() / $maxTotal * 100;
    $order  = ['gmu', 'drcp', 'dg'];
    $count  = $queue->count();
@endphp

@section('title', 'Approvals')
@section('context', 'Grants · Approvals')
@section('topbar-colour', 'bg-indigo-700')
@section('own-flash', '1')

@section('content')
{{-- The band --}}
<div class="-mx-4 -mt-5 flex flex-wrap items-end justify-between gap-5 bg-indigo-700 px-7 pb-[100px] pt-5 text-white lg:-mx-6 lg:-mt-6">
    <div class="flex max-w-[820px] flex-col gap-2">
        <div class="flex flex-wrap items-center gap-2.5">
            <span class="rounded-md bg-white/15 px-1.5 py-0.5 font-mono text-xs font-medium">{{ $call->code }}</span>
            <span class="text-[13px] text-indigo-100">{{ $call->title }} · FY {{ $call->fiscal_year }}</span>
        </div>
        <h1 class="text-balance text-[30px] font-semibold leading-tight tracking-[-0.025em] text-white">
            {{ $count === 0 ? 'Nothing is waiting for ' . $who : ($count === 1 ? 'One project is' : $count . ' projects are') . ' waiting for ' . $who }}
        </h1>
        <p class="text-[15px] text-indigo-100">Each was scored by the review panel and recommended. Approval runs {{ collect($chain)->map(fn ($l) => ['gmu' => 'GMU', 'drcp' => 'the DRCP', 'dg' => 'the Director General'][$l])->implode(', then ') }}.</p>
    </div>
    @if($calls->count() > 1)
        <div class="[&_label]:text-indigo-100">@include('seed.partials.call-picker')</div>
    @endif
</div>

<div class="-mt-[72px] flex flex-col gap-5">
    {{-- Two figures --}}
    <div class="grid gap-4 md:grid-cols-2">
        <div class="flex flex-col gap-2.5 rounded-[14px] bg-warn p-5 text-amber-950 shadow-lift">
            <div class="flex items-center justify-between gap-2"><span class="text-[13.5px] font-semibold">Waiting for you</span><span class="inline-flex h-[22px] shrink-0 items-center whitespace-nowrap rounded-md bg-amber-950/10 px-2 text-xs font-semibold">{{ $count }} {{ str('project')->plural($count) }}</span></div>
            <span class="text-[30px] font-semibold leading-tight tracking-[-0.02em] tabular-nums">{{ $m($queueTotal) }}</span>
            <span class="text-[13px] text-amber-900">{{ $count ? 'To decide on now, in rank order' : 'Nothing to decide right now' }}</span>
        </div>
        <div class="flex flex-col gap-2.5 rounded-[14px] border border-border bg-card p-5 shadow-lift">
            <div class="flex items-center justify-between gap-2"><span class="text-[13.5px] font-medium">Approved so far</span>@if($share !== null)<span class="inline-flex h-[22px] shrink-0 items-center whitespace-nowrap rounded-md bg-indigo-50 px-2 text-xs font-semibold text-indigo-800">{{ $share }}%</span>@endif</div>
            <span class="text-[30px] font-semibold leading-tight tracking-[-0.02em] tabular-nums text-indigo-800">{{ $m($approvedTotal) }}</span>
            @if($share !== null)
                <div class="h-2 overflow-hidden rounded-full bg-indigo-50" role="progressbar" aria-valuenow="{{ $share }}" aria-valuemin="0" aria-valuemax="100" aria-label="Share of funds approved"><div class="h-full rounded-full bg-indigo-700" style="width: {{ $share }}%"></div></div>
            @endif
            <span class="text-[13px] text-muted-foreground">{{ $call->envelope ? 'Of ' . $m($call->envelope) . ' available' : 'Funds available not set for this call' }} · {{ $decided->count() }} approved</span>
        </div>
    </div>

    {{-- What just happened --}}
    @if(session('success'))
        <div role="status" x-data="{ shown: true }" x-show="shown" class="flex items-center gap-3 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3.5 text-indigo-800">
            <span class="grid size-[22px] shrink-0 place-items-center rounded-full bg-indigo-700 text-white"><x-icon name="check" class="size-3" /></span>
            <span class="flex-1 text-[13.5px] font-medium">{{ session('success') }}</span>
            <button type="button" @click="shown = false" class="text-[13px] font-medium text-indigo-800 hover:underline">Dismiss</button>
        </div>
    @endif

    <div class="flex flex-wrap items-start gap-5">
        {{-- The queue --}}
        <section class="min-w-0 flex-[1_1_640px] overflow-hidden rounded-[14px] border border-border">
            <header class="border-b border-border bg-muted px-[18px] py-3"><h2 class="text-sm font-semibold">Waiting for your decision</h2><p class="text-[13px] text-muted-foreground">Approval runs {{ collect($chain)->map(fn ($l) => $names[$l])->implode(' → ') }} · in rank order</p></header>
            @if($queue->isEmpty())
                <div class="flex flex-col items-center gap-1.5 bg-card px-5 py-10 text-center">
                    <span class="grid size-10 place-items-center rounded-xl bg-indigo-50 text-indigo-700"><x-icon name="check-circle" class="size-5" /></span>
                    <p class="text-[14.5px] font-semibold">Nothing waiting</p>
                    <p class="text-[13px] text-muted-foreground">Recommended proposals appear here when it is your turn to decide.</p>
                </div>
            @else
                <ul class="bg-card">
                    @foreach($queue as $i => $row)
                        @php $a = $row['application']; $total = $row['means']['total'] ?? null; @endphp
                        <li class="flex flex-wrap items-center gap-4 border-b border-gray-100 px-[18px] py-4 last:border-b-0">
                            <span class="grid size-8 shrink-0 place-items-center rounded-[9px] bg-indigo-50 text-sm font-semibold tabular-nums text-indigo-800">{{ $i + 1 }}</span>
                            <div class="flex min-w-0 flex-[1_1_300px] flex-col gap-1">
                                <a href="{{ route('seed.approvals.show', $a) }}" class="text-[14.5px] font-semibold leading-snug text-foreground hover:text-indigo-700">{{ $a->title }}</a>
                                <p class="text-[13px] text-muted-foreground">{{ $a->pi->name }}{{ $a->pi->research_center ? ' · ' . $a->pi->research_center : '' }} · <span class="font-mono text-xs">{{ $a->reference }}</span></p>
                                <ol class="mt-1.5 flex items-center gap-1" aria-label="Sign-off">
                                    @foreach($chain as $l)
                                        @php $state = $l === $level ? 'now' : (array_search($l, $order) < array_search($level, $order) ? 'done' : 'todo'); @endphp
                                        <li class="flex items-center gap-1">
                                            <span class="inline-flex h-[22px] items-center gap-1 rounded-md px-[7px] text-[11.5px] font-semibold {{ ['now' => 'bg-warn-soft text-warn-ink ring-1 ring-inset ring-warn-line', 'done' => 'bg-indigo-50 text-indigo-800', 'todo' => 'text-subtle'][$state] }}">@if($state === 'done')<x-icon name="check" class="size-3" />@endif{{ $names[$l] }}</span>
                                            @unless($loop->last)<x-icon name="chevron-right" class="size-3 text-subtle" />@endunless
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                            <div class="flex w-[150px] flex-col gap-1.5">
                                <div class="flex justify-between text-xs text-muted-foreground"><span>Score</span><b class="text-[15px] font-semibold tabular-nums text-foreground">{{ $total ?? '—' }}</b></div>
                                <div class="relative h-1.5 rounded-full bg-gray-100" role="img" aria-label="Score {{ $total }} of {{ S::trimNumber($form->maxTotal()) }}; pass mark {{ S::trimNumber($form->passMark()) }}">
                                    <div class="absolute inset-y-0 left-0 rounded-full bg-indigo-700" style="width: {{ min(100, ($total ?? 0) / $maxTotal * 100) }}%"></div>
                                    <span class="absolute -bottom-1 -top-1 w-0.5 rounded-full bg-foreground" style="left: {{ $passPct }}%"></span>
                                </div>
                            </div>
                            <div class="w-[120px] text-right"><p class="text-xs text-muted-foreground">Amount, TZS</p><p class="font-mono text-sm font-medium">{{ number_format($a->budgetTotal('proposal')) }}</p></div>
                            <a href="{{ route('seed.approvals.show', $a) }}" class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-[7px] bg-foreground px-3.5 text-[13px] font-medium text-white hover:bg-gray-800">Decide</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Beside it --}}
        <div class="flex min-w-[280px] flex-[0_1_340px] flex-col gap-4">
            @if($inProgress->isNotEmpty())
                <section class="overflow-hidden rounded-[14px] border border-border">
                    <header class="border-b border-border bg-muted px-4 py-3"><h3 class="text-sm font-semibold">Elsewhere in approval</h3></header>
                    <ul class="bg-card px-4 py-1.5">
                        @foreach($inProgress as $a)
                            <li class="flex items-center justify-between gap-3 border-b border-gray-100 py-2.5 last:border-b-0"><a href="{{ route('seed.approvals.show', $a) }}" class="font-mono text-[12.5px] text-foreground hover:text-indigo-700">{{ $a->reference }}</a>@include('seed.partials.status', ['application' => $a])</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section class="overflow-hidden rounded-[14px] border border-border">
                <header class="border-b border-border bg-muted px-4 py-3"><h3 class="text-sm font-semibold">Not recommended</h3><p class="text-[13px] text-muted-foreground">For information; no decision needed</p></header>
                <ul class="bg-card px-4 py-1.5">
                    @forelse($notRecommended as $row)
                        <li class="flex justify-between gap-3 border-b border-gray-100 py-2.5 text-[13px] last:border-b-0">
                            <span class="min-w-0 truncate"><span class="font-mono text-xs">{{ $row['application']->reference }}</span> · {{ $row['application']->title }}</span>
                            <span class="font-mono text-danger-ink">{{ $row['means']['total'] ?? '—' }}</span>
                        </li>
                    @empty
                        <li class="py-2.5 text-[13px] text-muted-foreground">None in this call.</li>
                    @endforelse
                </ul>
            </section>

            <section class="overflow-hidden rounded-[14px] border border-border">
                <header class="border-b border-border bg-muted px-4 py-3"><h3 class="text-sm font-semibold">When the DG approves</h3></header>
                <ol class="flex flex-col gap-3 bg-card px-4 py-3.5 text-[13px] leading-snug text-gray-700">
                    @foreach([['The PI is told in the portal.', 'bg-sky-100 text-sky-700'], ['GMU issues the grant agreement, drafted from the approved proposal.', 'bg-violet-100 text-violet-700'], ['The PI accepts it online; GMU uploads the signed copy.', 'bg-indigo-100 text-indigo-800']] as [$step, $chip])
                        <li class="flex gap-3"><span class="grid size-6 shrink-0 place-items-center rounded-full text-xs font-semibold {{ $chip }}">{{ $loop->iteration }}</span>{{ $step }}</li>
                    @endforeach
                </ol>
            </section>
        </div>
    </div>
</div>
@endsection
