@extends('layouts.app')

{{--
    GMU's dashboard across every call, kept simple:
      four plain figures → what needs GMU now → each call's money → reviewers still owing reviews.
--}}

@php
    $m = fn ($n) => $n ? number_format($n / 1e6, 1) . 'M' : '0';
    $pct = $kpi['available'] ? round($kpi['approved'] / $kpi['available'] * 100) : null;
    $daysLeft = $nextDeadline ? (int) now()->startOfDay()->diffInDays($nextDeadline->concept_deadline->copy()->startOfDay()) : null;

    // The next step's button, tinted by what kind of step it is.
    $step = [
        ''        => 'border-warn-line bg-warn-soft text-warn-ink hover:bg-amber-100',
        'warn'    => 'border-warn-line bg-warn-soft text-warn-ink hover:bg-amber-100',
        'info'    => 'border-review-line bg-review-soft text-review-ink hover:bg-violet-100',
        'ok'      => 'border-ok-line bg-ok-soft text-ok-ink hover:bg-blue-100',
        'danger'  => 'border-danger-line bg-danger-soft text-danger-ink hover:bg-red-100',
    ];
@endphp

@section('title', 'Dashboard')
@section('context', 'Home · Dashboard')

@section('content')
<x-page-header title="Grants overview"
    :description="now()->format('l, j F Y') . ' · ' . $callCount . ' ' . str('call')->plural($callCount) . ' running · ' . $submittedCount . ' submitted'">
    <x-slot:actions>
        <x-button :href="route('seed.submissions.export', ['call' => 'all'])" icon="download">Export</x-button>
        <x-button variant="primary" :href="route('seed.submissions.index', ['call' => 'all', 'filter' => 'action'])" icon-right="arrow-right">Work through submissions</x-button>
    </x-slot:actions>
</x-page-header>

{{-- Four plain answers --}}
<div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <x-kpi tone="amber" title="Needs GMU" :value="$kpi['needs']"
        :badge="$kpi['screen'] ? $kpi['screen'] . ' to screen' : null"
        :note="$kpi['needs'] ? str('Application')->plural($kpi['needs']) . ' waiting on GMU' : 'Nothing is waiting on GMU'"
        :href="route('seed.submissions.index', ['call' => 'all', 'filter' => 'action'])" />

    <x-kpi tone="violet" title="With reviewers" :value="$kpi['pending']"
        :badge="$kpi['late'] ? null : 'None late'"
        :note="$kpi['late'] ? $kpi['late'] . ' ' . str('review')->plural($kpi['late']) . ' late' : str('Review')->plural($kpi['pending']) . ' still to come in'" />

    <x-kpi tone="blue" title="Funds approved" :value="'TZS ' . $m($kpi['approved'])"
        :badge="$pct !== null ? $pct . '%' : null" :meter="$pct"
        :note="$kpi['available'] ? 'Of TZS ' . $m($kpi['available']) . ' across the calls running' : 'Set funds available on each call to track this'" />

    @if($nextDeadline)
        <x-kpi tone="sky" title="Next deadline" :value="$nextDeadline->concept_deadline->format('j M Y')"
            :badge="$daysLeft === 0 ? 'Today' : 'In ' . $daysLeft . ' ' . str('day')->plural($daysLeft)"
            :note="'Concept notes close for ' . $nextDeadline->code"
            :href="route('seed.calls.index')" />
    @else
        <x-kpi tone="sky" title="Next deadline" value="None ahead" note="No open call has a concept-note deadline to come" :href="route('seed.calls.index')" />
    @endif
</div>

<div class="mt-5 grid items-start gap-4 xl:grid-cols-[minmax(0,1fr)_22rem]">
    {{-- What needs GMU now --}}
    <x-card title="Needs your action" description="Oldest first, across all calls" flush>
        <x-slot:actions>
            <x-button size="sm" :href="route('seed.submissions.index', ['call' => 'all', 'filter' => 'action'])">Open the queue</x-button>
        </x-slot:actions>
        @if($needs->isEmpty())
            <x-empty icon="check-circle" title="All caught up" compact>Nothing is waiting on GMU in any call. New concept notes and finished review rounds appear here.</x-empty>
        @else
            <div class="overflow-x-auto">
                <table class="data-table min-w-[680px]">
                    <thead><tr><th>Application</th><th>Status</th><th>Waiting since</th><th class="text-right">Next step</th></tr></thead>
                    <tbody>
                        @foreach($needs->take(8) as $n)
                            @php $a = $n['application']; @endphp
                            <tr>
                                <td class="min-w-[16rem]">
                                    <a href="{{ route('seed.submissions.show', $a) }}" class="line-clamp-2 font-medium hover:text-primary">{{ $a->title }}</a>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[12.5px] text-muted-foreground">
                                        @include('seed.partials.call-tag', ['call' => $a->call, 'short' => true])
                                        <span class="font-mono text-xs">{{ $a->reference }}</span>
                                        <span>{{ $a->pi->name }}</span>
                                    </div>
                                </td>
                                <td>@include('seed.partials.status', ['application' => $a])</td>
                                <td class="whitespace-nowrap text-[13px]">{{ $a->updated_at->format('j M Y') }}<div class="text-xs text-muted-foreground">{{ $a->updated_at->diffForHumans() }}</div></td>
                                <td class="text-right">
                                    <a href="{{ $n['toRanking'] ? route('seed.ranking', ['call' => $a->seed_call_id]) : route('seed.submissions.show', $a) }}"
                                       class="inline-flex h-7 items-center whitespace-nowrap rounded-md border px-2.5 text-[12.5px] font-medium transition-colors {{ $step[$n['tone']] ?? $step['ok'] }}">{{ $n['action'] }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        @if($needs->count() > 8)
            <x-slot:footer><a href="{{ route('seed.submissions.index', ['call' => 'all', 'filter' => 'action']) }}" class="font-medium text-primary hover:underline">{{ $needs->count() - 8 }} more in the queue</a></x-slot:footer>
        @endif
    </x-card>

    <div class="grid gap-4">
        {{-- Each call's money --}}
        <x-card title="Calls" description="Money approved against funds available" flush>
            <x-slot:actions><x-button size="sm" variant="ghost" :href="route('seed.calls.index')">All calls</x-button></x-slot:actions>
            <ul class="divide-y divide-border px-4">
                @forelse($cards as $card)
                    @php $c = $card['call']; $share = $c->envelope ? min(100, $card['approved'] / $c->envelope * 100) : 0; @endphp
                    <li class="{{ $c->tintClass() }} space-y-2 py-3.5">
                        <div class="flex items-center gap-2">
                            @include('seed.partials.call-tag', ['call' => $c, 'short' => true])
                            <a href="{{ route('seed.submissions.index', ['call' => $c->id]) }}" class="min-w-0 flex-1 truncate text-[13px] font-medium hover:text-primary">{{ $c->title }}</a>
                            <span class="text-xs {{ $c->status === 'open' ? 'text-ok-ink' : 'text-muted-foreground' }}">{{ \App\Models\Seed\SeedCall::STATUSES[$c->status] }}</span>
                        </div>
                        <x-meter :value="$share" tone="tint" :label="'Approved for ' . $c->code" />
                        <div class="flex justify-between gap-3 text-[12.5px] text-muted-foreground">
                            <span><b class="font-medium text-foreground">TZS {{ $m($card['approved']) }}</b> of {{ $c->envelope ? $m($c->envelope) : 'not set' }}</span>
                            <span>{{ $card['submitted'] }} {{ str('application')->plural($card['submitted']) }}</span>
                        </div>
                    </li>
                @empty
                    <li class="py-6 text-center text-[13px] text-muted-foreground">No calls running. Open a call and it appears here.</li>
                @endforelse
            </ul>
        </x-card>

        {{-- Who still owes reviews --}}
        <x-card title="Reviewers" description="Reviews done, in open rounds" flush>
            <ul class="divide-y divide-border px-4">
                @forelse($load as $r)
                    <li class="flex items-center gap-2.5 py-2.5">
                        <span class="min-w-0 flex-1 truncate text-[13.5px]">{{ $r['name'] }}</span>
                        @if($r['late'])<x-badge tone="danger">{{ $r['late'] }} late</x-badge>@endif
                        <x-badge :tone="$r['done'] === $r['total'] ? 'ok' : 'neutral'">{{ $r['done'] }} of {{ $r['total'] }} done</x-badge>
                    </li>
                @empty
                    <li class="py-6 text-center text-[13px] text-muted-foreground">No open review rounds. Reviewers appear here once GMU assigns them.</li>
                @endforelse
            </ul>
        </x-card>
    </div>
</div>
@endsection
