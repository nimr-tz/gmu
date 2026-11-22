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
@section('context', 'Grants · Approvals · ' . $call->code)

@section('content')
<x-page-header :eyebrow="$call->title . ' · FY ' . $call->fiscal_year"
    :title="$queue->count() === 0 ? 'Nothing is waiting for ' . $who : ($queue->count() === 1 ? 'One project is waiting for ' . $who : $queue->count() . ' projects are waiting for ' . $who)"
    :description="'Each was scored by the review panel and recommended. Approval runs ' . collect($call->form()->approvalLevels())->map(fn ($l) => ['gmu' => 'GMU', 'drcp' => 'the DRCP', 'dg' => 'the Director General'][$l])->implode(', then ') . '.'">
    <x-slot:actions>@include('seed.partials.call-picker')</x-slot:actions>
</x-page-header>

@php $share = $call->envelope ? min(100, $approvedTotal / max(1, $call->envelope) * 100) : null; @endphp
<div class="mb-5 grid gap-3 md:grid-cols-2">
    <x-kpi tone="amber" title="Waiting for you" :value="'TZS ' . number_format($queueTotal / 1e6, 1) . 'M'"
        :badge="$queue->count() . ' ' . str('project')->plural($queue->count())"
        :note="$queue->isEmpty() ? 'Nothing to decide right now' : 'To decide on now, in rank order'" />
    <x-kpi tone="blue" title="Approved so far" :value="'TZS ' . number_format($approvedTotal / 1e6, 1) . 'M'"
        :badge="$share !== null ? round($share) . '%' : null" :meter="$share"
        :note="($call->envelope ? 'Of TZS ' . number_format($call->envelope / 1e6, 1) . 'M available' : 'Funds available not set for this call') . ' · ' . $decided->count() . ' approved'" />
</div>

<div class="grid items-start gap-4 2xl:grid-cols-[minmax(0,1fr)_24rem]">
    <x-card title="Waiting for your decision" :description="'Approval runs ' . implode(' → ', $chain)" flush>
        <div class="overflow-x-auto">
            <table class="data-table min-w-[760px]">
                <thead><tr><th class="w-16">Rank</th><th>Project</th><th>Score</th><th class="text-right">Amount, TZS</th><th>Sign-off</th><th class="text-right"><span class="sr-only">Open</span></th></tr></thead>
                <tbody>
                    @forelse($queue as $i => $row)
                        @php $a = $row['application']; @endphp
                        <tr>
                            <td><span class="grid size-7 place-items-center rounded-lg bg-ok-soft text-[13px] font-semibold tabular-nums text-ok-ink">{{ $i + 1 }}</span></td>
                            <td class="max-w-sm">
                                <a href="{{ route('seed.approvals.show', $a) }}" class="font-medium hover:text-primary">{{ $a->title }}</a>
                                <div class="mt-0.5 text-[13px] text-muted-foreground">{{ $a->pi->name }}{{ $a->pi->research_center ? ' · ' . $a->pi->research_center : '' }} · <span class="font-mono text-xs">{{ $a->reference }}</span></div>
                            </td>
                            <td class="text-[15px] font-semibold tabular-nums">{{ $row['means']['total'] ?? '—' }}</td>
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
                            <td class="text-right"><x-button size="sm" variant="primary" :href="route('seed.approvals.show', $a)">Decide</x-button></td>
                        </tr>
                    @empty
                        <tr class="hover:bg-transparent"><td colspan="6"><x-empty icon="check-circle" title="Nothing waiting" compact>Recommended proposals appear here when it is your turn to decide.</x-empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="grid items-start gap-4 md:grid-cols-2 2xl:grid-cols-1">
        @if($inProgress->isNotEmpty())
            <x-card title="Elsewhere in approval">
                <ul class="space-y-2.5">
                    @foreach($inProgress as $a)
                        <li class="flex items-center justify-between gap-3"><span class="font-mono text-xs">{{ $a->reference }}</span>@include('seed.partials.status', ['application' => $a])</li>
                    @endforeach
                </ul>
            </x-card>
        @endif
        <x-card title="Not recommended" description="For information; no decision needed">
            <ul class="space-y-2.5 text-[13px]">
                @forelse($notRecommended as $row)
                    <li class="flex justify-between gap-3"><span class="min-w-0 truncate"><span class="font-mono text-xs">{{ $row['application']->reference }}</span> · {{ $row['application']->title }}</span><span class="font-mono tabular-nums">{{ $row['means']['total'] ?? '—' }}</span></li>
                @empty
                    <li class="text-muted-foreground">None. Full proposals the panel does not recommend appear here, so you can see them before you sign.</li>
                @endforelse
            </ul>
        </x-card>
        <x-card title="When the DG approves">
            <ol class="space-y-2.5 text-[13px] text-gray-700">
                @foreach([['The PI is told in the portal.', 'bg-sky-100 text-sky-700'], ['GMU issues the grant agreement, drafted from the approved proposal.', 'bg-violet-100 text-violet-700'], ['The PI accepts it online; GMU uploads the signed copy.', 'bg-blue-100 text-blue-700']] as [$step, $dot])
                    <li class="flex gap-3"><span class="grid size-6 shrink-0 place-items-center rounded-full text-xs font-semibold {{ $dot }}">{{ $loop->iteration }}</span>{{ $step }}</li>
                @endforeach
            </ol>
        </x-card>
    </div>
</div>
@endsection
