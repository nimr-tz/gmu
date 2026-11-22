@extends('layouts.app')

@php
    use App\Support\SeedGrant as S;
    $who = ['gmu' => 'GMU sign-off', 'drcp' => 'DRCP clearance', 'dg' => 'your decision'][$level];
    $queueTotal = $queue->sum(fn ($r) => $r['application']->budgetTotal('proposal'));
    $approvedTotal = $decided->sum('approved_amount');
    $order = ['gmu', 'drcp', 'dg'];
    $chain = array_intersect_key(['gmu' => 'GMU', 'drcp' => 'DRCP', 'dg' => 'DG'], array_flip($call->form()->approvalLevels()));
@endphp

@section('title', 'Approvals')
@section('context', 'Approvals · ' . $call->title)

@section('content')
<x-page-header :eyebrow="$call->title . ' · FY ' . $call->fiscal_year"
    :title="$queue->count() === 0 ? 'Nothing is waiting for ' . $who : ($queue->count() === 1 ? 'One project is waiting for ' . $who : $queue->count() . ' projects are waiting for ' . $who)"
    :description="'Each was scored by the review panel and recommended. Approval runs ' . collect($call->form()->approvalLevels())->map(fn ($l) => ['gmu' => 'GMU', 'drcp' => 'the DRCP', 'dg' => 'the Director General'][$l])->implode(', then ') . '.'">
    <x-slot:actions>@include('seed.partials.call-picker')</x-slot:actions>
</x-page-header>

<section class="mb-6 flex flex-wrap items-center gap-x-12 gap-y-6 rounded-xl bg-sidebar p-6 text-sidebar-foreground shadow-sm">
    <div class="min-w-0 flex-[1_1_20rem]">
        <div class="flex flex-wrap justify-between gap-2 text-[13px]">
            <span class="text-sidebar-muted">Approved so far, against the funds available</span>
            <span class="font-mono">{{ number_format($approvedTotal / 1e6, 1) }}M of {{ $call->envelope ? number_format($call->envelope / 1e6, 1) . 'M' : 'not set' }}</span>
        </div>
        <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-sidebar-chip">
            <div class="h-full rounded-full bg-blue-400" style="width: {{ $call->envelope ? min(100, $approvedTotal / max(1, $call->envelope) * 100) : 0 }}%"></div>
        </div>
    </div>
    <dl class="flex gap-10">
        <div><dt class="text-[13px] text-sidebar-muted">Waiting for you</dt><dd class="mt-1 font-mono text-2xl text-white">TZS {{ number_format($queueTotal / 1e6, 1) }}M</dd></div>
        <div><dt class="text-[13px] text-sidebar-muted">Approved</dt><dd class="mt-1 font-mono text-2xl text-white">{{ $decided->count() }}</dd></div>
    </dl>
</section>

<div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
    <x-card flush>
        <div class="overflow-x-auto">
            <table class="data-table min-w-[760px]">
                <thead><tr><th class="w-16">Rank</th><th>Project</th><th>Score</th><th class="text-right">Amount (TZS)</th><th>Sign-off</th><th class="text-right"><span class="sr-only">Open</span></th></tr></thead>
                <tbody>
                    @forelse($queue as $i => $row)
                        @php $a = $row['application']; @endphp
                        <tr>
                            <td class="font-mono text-lg tabular-nums text-muted-foreground">{{ $i + 1 }}</td>
                            <td class="max-w-sm">
                                <a href="{{ route('seed.approvals.show', $a) }}" class="font-medium hover:text-primary">{{ $a->title }}</a>
                                <div class="mt-0.5 text-[13px] text-muted-foreground">{{ $a->pi->name }}{{ $a->pi->research_center ? ' · ' . $a->pi->research_center : '' }} · <span class="font-mono text-xs">{{ $a->reference }}</span></div>
                            </td>
                            <td><x-badge tone="ok" class="h-7 px-2.5 font-mono text-sm">{{ $row['means']['total'] ?? '—' }}</x-badge></td>
                            <td class="num">{{ number_format($a->budgetTotal('proposal')) }}</td>
                            <td>
                                <ol class="flex items-center gap-1 text-xs font-medium">
                                    @foreach($chain as $l => $label)
                                        @php $state = $l === $level ? 'now' : (array_search($l, $order) < array_search($level, $order) ? 'done' : 'todo'); @endphp
                                        <li class="flex items-center gap-1">
                                            <span class="inline-flex h-6 items-center gap-1 rounded-md px-1.5 {{ ['now' => 'bg-warn-soft text-warn-ink ring-1 ring-inset ring-warn-line', 'done' => 'bg-ok-soft text-ok-ink', 'todo' => 'text-subtle'][$state] }}">
                                                @if($state === 'done')<x-icon name="check" class="size-3" />@endif{{ $label }}
                                            </span>
                                            @unless($loop->last)<x-icon name="chevron-right" class="size-3 text-subtle" />@endunless
                                        </li>
                                    @endforeach
                                </ol>
                            </td>
                            <td class="text-right"><x-button size="sm" :variant="$i === 0 ? 'primary' : 'secondary'" :href="route('seed.approvals.show', $a)">Decide</x-button></td>
                        </tr>
                    @empty
                        <tr class="hover:bg-transparent"><td colspan="6"><x-empty icon="check-circle" title="Nothing waiting" compact>Recommended proposals appear here when it is your turn to decide.</x-empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="space-y-4">
        @if($inProgress->isNotEmpty())
            <x-card title="Elsewhere in approval">
                <ul class="space-y-2.5">
                    @foreach($inProgress as $a)
                        <li class="flex items-center justify-between gap-3"><span class="font-mono text-xs">{{ $a->reference }}</span>@include('seed.partials.status', ['application' => $a])</li>
                    @endforeach
                </ul>
            </x-card>
        @endif
        <x-card title="Not recommended" description="Full proposals the panel did not recommend. For information; no decision needed.">
            <ul class="space-y-2.5 text-[13px]">
                @forelse($notRecommended as $row)
                    <li class="flex justify-between gap-3"><span class="min-w-0 truncate"><span class="font-mono text-xs">{{ $row['application']->reference }}</span> · {{ $row['application']->title }}</span><span class="font-mono tabular-nums">{{ $row['means']['total'] ?? '—' }}</span></li>
                @empty
                    <li class="text-muted-foreground">None.</li>
                @endforelse
            </ul>
        </x-card>
        <x-card title="When the DG approves">
            <ol class="space-y-2.5 text-[13px] text-gray-700">
                @foreach(['The PI is told in the portal.', 'GMU issues the grant agreement, drafted from the approved proposal.', 'The PI accepts it online; GMU uploads the signed copy.'] as $step)
                    <li class="flex gap-3"><span class="grid size-5 shrink-0 place-items-center rounded-full bg-muted font-mono text-[11px]">{{ $loop->iteration }}</span>{{ $step }}</li>
                @endforeach
            </ol>
        </x-card>
    </div>
</div>
@endsection
