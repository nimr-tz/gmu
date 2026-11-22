@extends('layouts.app')

{{--
    GMU's dashboard across every call:
      key figures → applications by stage and money per call →
      what needs GMU now → reviewer load and centres → a card per call still running.
--}}

@php
    $m = fn ($n) => $n ? number_format($n / 1e6, 1) . 'M' : '0';
    $maxCentre = max(1, $byCentre->max() ?? 1);
    $oldest = $needs->first()['application'] ?? null;
    $swatch = ['concept' => 'bg-chart-1', 'proposal' => 'bg-chart-2', 'approval' => 'bg-chart-3', 'award' => 'bg-chart-4', 'closed' => 'bg-chart-5'];
    $maxRow = max(1, $pipeline->max('total') ?? 1);
@endphp

@section('title', 'Dashboard')
@section('context', 'Grants · Overview')

@section('content')
<x-page-header title="Grants overview" :eyebrow="now()->format('l, j F Y')"
    :description="$callCount . ' ' . str('call')->plural($callCount) . ' running · ' . $submittedCount . ' submitted'">
    <x-slot:actions>
        <x-button :href="route('seed.submissions.export', ['call' => 'all'])" icon="download">Export</x-button>
        <x-button :href="route('seed.calls.index')" icon="megaphone">Calls</x-button>
        <x-button variant="primary" :href="route('seed.submissions.index', ['call' => 'all', 'filter' => 'action'])" icon-right="arrow-right">Work through submissions</x-button>
    </x-slot:actions>
</x-page-header>

{{-- Key figures --}}
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-kpi icon="inbox" title="Needs GMU" caption="Across all calls"
        :value="$kpi['needs']"
        :badge="$kpi['needs'] === 0 ? 'All caught up' : ($kpi['needsOld'] ? $kpi['needsOld'] . ' over 7 days' : 'All recent')"
        :badge-tone="$kpi['needs'] === 0 ? 'ok' : ($kpi['needsOld'] ? 'warn' : 'neutral')"
        :badge-icon="$kpi['needs'] === 0 ? 'check' : ($kpi['needsOld'] ? 'clock' : null)"
        :note="$oldest ? 'Oldest waiting since ' . $oldest->updated_at->format('j M Y') : 'Nothing is waiting on GMU.'"
        :foot="['To screen' => $kpi['screen'], 'To decide' => $kpi['decide']]"
        :href="route('seed.submissions.index', ['call' => 'all', 'filter' => 'action'])" />

    <x-kpi icon="wallet" title="Funds approved" caption="Calls running"
        :value="'TZS ' . $m($kpi['approved'])"
        :badge="$kpi['available'] ? round($kpi['approved'] / $kpi['available'] * 100) . '% committed' : null"
        badge-tone="ok" badge-icon="trending-up"
        :note="$kpi['available'] ? 'TZS ' . $m(max(0, $kpi['available'] - $kpi['approved'])) . ' left to commit' : 'Set funds available on each call to track this.'"
        :foot="['Available' => $kpi['available'] ? $m($kpi['available']) : '—', 'In play' => $m($kpi['inPlay'])]" />

    <x-kpi icon="users" title="Reviews" caption="Open rounds"
        :value="$kpi['pending'] . ' pending'"
        :badge="$kpi['late'] ? $kpi['late'] . ' late' : 'None late'"
        :badge-tone="$kpi['late'] ? 'danger' : 'ok'"
        :badge-icon="$kpi['late'] ? 'alert' : 'check'"
        :note="$kpi['reviewsDone'] . ' ' . str('review')->plural($kpi['reviewsDone']) . ' submitted so far'"
        :foot="['Turnaround' => $kpi['turnaround'] !== null ? $kpi['turnaround'] . ' days' : '—', 'Late' => $kpi['late']]" />

    <x-kpi icon="clock" title="Time to decision" caption="Concept note to decision"
        :value="$kpi['decisionDays'] === null ? '—' : ($kpi['decisionDays'] < 1 ? 'Same day' : $kpi['decisionDays'] . ' days')"
        :note="$kpi['decided'] ? 'Median of ' . $kpi['decided'] . ' decided concept notes' : 'No concept notes decided yet.'"
        :foot="['Decided' => $kpi['decided'], 'Awarded' => $kpi['awarded']]" />
</div>

{{-- Applications by stage, and money per call --}}
<div class="mt-4 grid gap-4 xl:grid-cols-3">
    <x-card class="xl:col-span-2" title="Applications by stage" description="Every call running, by where its applications are now" flush>
        <x-slot:actions><x-button size="sm" :href="route('seed.submissions.index', ['call' => 'all'])" icon="list">All submissions</x-button></x-slot:actions>
        <dl class="grid grid-cols-2 divide-border border-b border-border sm:grid-cols-5 sm:divide-x">
            @foreach($band as $key => $cell)
                <div class="px-5 py-3">
                    <dt class="flex items-center gap-1.5 text-xs text-muted-foreground"><span class="size-2 rounded-sm {{ $swatch[$key] }}"></span>{{ $cell['label'] }}</dt>
                    <dd class="mt-1 text-xl font-semibold tabular-nums">{{ $cell['count'] }}</dd>
                </div>
            @endforeach
        </dl>
        <ul class="divide-y divide-border">
            @foreach($pipeline as $row)
                @php $c = $row['call']; @endphp
                <li class="grid items-center gap-x-4 gap-y-2 px-5 py-3.5 sm:grid-cols-[13rem_minmax(0,1fr)_3rem]">
                    <a href="{{ route('seed.submissions.index', ['call' => $c->id]) }}" class="min-w-0 hover:text-primary">
                        @include('seed.partials.call-tag', ['call' => $c, 'short' => true])
                        <span class="mt-1 block truncate text-[13px] text-muted-foreground">{{ $c->title }}</span>
                    </a>
                    <div class="flex h-2.5 gap-0.5 overflow-hidden rounded-full bg-muted" role="img"
                         aria-label="{{ $c->code }}: @foreach($row['segments'] as $key => $n){{ $groups[$key] }} {{ $n }}@if(! $loop->last), @endif @endforeach">
                        @foreach($row['segments'] as $key => $n)
                            @if($n > 0)<span class="{{ $swatch[$key] }} first:rounded-l-full last:rounded-r-full" style="width: {{ $n / max(1, $maxRow) * 100 }}%" title="{{ $groups[$key] }}: {{ $n }}"></span>@endif
                        @endforeach
                    </div>
                    <span class="text-right font-mono text-sm font-medium tabular-nums">{{ $row['total'] }}</span>
                </li>
            @endforeach
        </ul>
    </x-card>

    <x-card title="Money by call" description="Approved against funds available" flush>
        <ul class="divide-y divide-border px-5">
            @foreach($cards as $card)
                @php $c = $card['call']; $pct = $c->envelope ? min(100, $card['approved'] / $c->envelope * 100) : 0; @endphp
                <li class="space-y-2 py-4">
                    <div class="flex items-center justify-between gap-3">
                        @include('seed.partials.call-tag', ['call' => $c, 'short' => true])
                        <span class="font-mono text-[13px] font-medium tabular-nums">{{ $c->envelope ? round($pct) . '%' : '—' }}</span>
                    </div>
                    <p class="truncate text-[13px] text-gray-700">{{ $c->title }}</p>
                    <x-meter :value="$pct" :label="'Approved for ' . $c->code" />
                    <div class="flex flex-wrap justify-between gap-x-3 text-xs text-muted-foreground">
                        <span>Approved <b class="font-mono font-medium text-foreground">{{ $m($card['approved']) }}</b> of {{ $c->envelope ? $m($c->envelope) : 'not set' }}</span>
                        <span>Requested <b class="font-mono font-medium text-foreground">{{ $m($card['requested']) }}</b></span>
                    </div>
                </li>
            @endforeach
        </ul>
    </x-card>
</div>

{{-- What needs GMU now --}}
<x-card class="mt-4" title="Needs your action" description="Oldest waiting first, across all calls" flush>
    <x-slot:actions>
        <x-badge tone="outline" class="font-mono">{{ $needs->count() }}</x-badge>
        <x-button size="sm" :href="route('seed.submissions.index', ['call' => 'all', 'filter' => 'action'])" icon-right="arrow-right">Open the queue</x-button>
    </x-slot:actions>
    @if($needs->isEmpty())
        <x-empty icon="check-circle" title="All caught up" compact>Nothing is waiting on GMU in any call. New concept notes and finished review rounds appear here.</x-empty>
    @else
        <div class="overflow-x-auto">
            <table class="data-table min-w-[720px]">
                <thead><tr><th>Application</th><th>Status</th><th>Waiting since</th><th class="text-right"><span class="sr-only">Action</span></th></tr></thead>
                <tbody>
                    @foreach($needs->take(8) as $n)
                        @php $a = $n['application']; @endphp
                        <tr>
                            <td class="max-w-md">
                                <a href="{{ route('seed.submissions.show', $a) }}" class="line-clamp-1 font-medium hover:text-primary">{{ $a->title }}</a>
                                <div class="mt-1 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-[13px] text-muted-foreground">
                                    @include('seed.partials.call-tag', ['call' => $a->call, 'short' => true])
                                    <span class="font-mono text-xs">{{ $a->reference }}</span><span>{{ $a->pi->name }}</span>
                                </div>
                            </td>
                            <td>@include('seed.partials.status', ['application' => $a])</td>
                            <td class="whitespace-nowrap text-[13px]">{{ $a->updated_at->format('j M Y') }}<div class="text-xs text-muted-foreground">{{ $a->updated_at->diffForHumans() }}</div></td>
                            <td class="text-right"><x-button size="sm" :href="$n['toRanking'] ? route('seed.ranking', ['call' => $a->seed_call_id]) : route('seed.submissions.show', $a)">{{ $n['action'] }}</x-button></td>
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

{{-- Reviewer load and centres --}}
<div class="mt-4 grid gap-4 lg:grid-cols-2">
    <x-card title="Reviewer load" description="Done of assigned, in open rounds">
        <ul class="space-y-4">
            @forelse($load as $r)
                <li class="space-y-2">
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <span class="flex items-center gap-2 font-medium">{{ $r['name'] }} @if($r['late'])<x-badge tone="danger">{{ $r['late'] }} late</x-badge>@endif</span>
                        <span class="font-mono text-[13px] tabular-nums text-muted-foreground">{{ $r['done'] }} / {{ $r['total'] }}</span>
                    </div>
                    <x-meter :value="$r['total'] ? $r['done'] / $r['total'] * 100 : 0" :tone="$r['late'] ? 'danger' : 'primary'" :label="'Reviews done by ' . $r['name']" />
                </li>
            @empty
                <li class="py-6 text-center text-sm text-muted-foreground">No open review rounds. Reviewers appear here once GMU assigns them.</li>
            @endforelse
        </ul>
    </x-card>
    <x-card title="By centre" description="Submissions, all calls">
        <ul class="grid grid-cols-[minmax(0,auto)_1fr_auto] items-center gap-x-4 gap-y-2.5 text-[13px]">
            @forelse($byCentre as $centre => $n)
                <li class="contents">
                    <span class="truncate">{{ $centre }}</span>
                    <x-meter :value="$n / $maxCentre * 100" :label="$centre" />
                    <span class="font-mono tabular-nums">{{ $n }}</span>
                </li>
            @empty
                <li class="col-span-3 py-6 text-center text-sm text-muted-foreground">No submissions yet.</li>
            @endforelse
        </ul>
    </x-card>
</div>

{{-- A card per call --}}
<div class="mt-4 grid gap-4 2xl:grid-cols-2">
    @foreach($cards as $card)
        @php $c = $card['call']; @endphp
        <section class="{{ $c->tintClass() }} flex flex-col rounded-xl border border-tint-line bg-tint p-1">
            <header class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 px-3 pb-2.5 pt-2">
                <div class="min-w-0">
                    <div class="flex min-w-0 items-center gap-2">
                        @include('seed.partials.call-tag', ['call' => $c])
                        <x-badge :tone="['open' => 'ok', 'closed' => 'neutral'][$c->status] ?? 'outline'" dot>{{ \App\Models\Seed\SeedCall::STATUSES[$c->status] }}</x-badge>
                    </div>
                    <p class="mt-0.5 text-[13px] text-muted-foreground">FY {{ $c->fiscal_year }} ·
                        {{ $c->concept_deadline ? 'concept notes ' . ($c->concept_deadline->copy()->endOfDay()->isPast() ? 'closed ' : 'close ') . $c->concept_deadline->format('j M Y') : 'no deadline set' }}</p>
                </div>
                <div class="flex gap-1.5">
                    <x-button size="sm" :href="route('seed.submissions.index', ['call' => $c->id])">Submissions</x-button>
                    <x-button size="sm" :href="route('seed.ranking', ['call' => $c->id])">Ranking</x-button>
                    <x-button size="sm" :href="route('seed.calls.show', $c)" icon-right="arrow-right">Call page</x-button>
                </div>
            </header>
            <ol class="grid flex-1 grid-cols-4 divide-x divide-y divide-border overflow-hidden rounded-lg border border-border bg-card shadow-xs sm:grid-cols-8 sm:divide-y-0" aria-label="Applications at each stage of {{ $c->title }}">
                @foreach($card['stages'] as $stage)
                    <li class="px-2 py-3 text-center">
                        <span class="block font-mono text-lg tabular-nums {{ $stage['count'] ? 'font-semibold text-foreground' : 'text-subtle' }}">{{ $stage['count'] }}</span>
                        <span class="mt-0.5 block text-[11px] leading-tight text-muted-foreground">{{ $stage['label'] }}</span>
                    </li>
                @endforeach
            </ol>
        </section>
    @endforeach
</div>
@endsection
