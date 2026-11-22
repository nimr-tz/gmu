@extends('layouts.gmu')

{{--
    GMU's dashboard across every call:
      key figures → applications by stage (chart) and money per call →
      what needs action, reviewer load → a card per call still running.
--}}

@php
    use App\Support\SeedGrant as S;
    $m = fn ($n) => $n ? number_format($n / 1e6, 1) . 'M' : '0';
    $maxCentre = max(1, $byCentre->max() ?? 1);
    $oldest = $needs->first()['application'] ?? null;

    // Chart geometry (SVG user units).
    $chartW = 640; $chartH = 250; $base = 210; $top = 28; $left = 36;
    $maxTotal = max(1, $pipeline->max('total') ?? 1);
    $step = max(1, (int) ceil($maxTotal / 4));
    $scaleMax = $step * 4;
    $slot = ($chartW - $left - 8) / max(1, $pipeline->count());
    $barW = min(84, $slot * 0.46);
@endphp

@section('title', 'Dashboard')
@section('context', 'Grants · Overview')

@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">{{ now()->format('l, j F Y') }}</p>
        <h1 class="dash-title">Grants overview</h1>
        <p class="lede">{{ $callCount }} {{ str('call')->plural($callCount) }} running · {{ $submittedCount }} submitted</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('seed.submissions.export', ['call' => 'all']) }}" class="btn btn-quiet"><x-ui.icon name="download" /> Export</a>
        <a href="{{ route('seed.calls.index') }}" class="btn btn-quiet"><x-ui.icon name="megaphone" /> Calls</a>
        <a href="{{ route('seed.submissions.index', ['call' => 'all', 'filter' => 'action']) }}" class="btn btn-primary">Work through submissions <x-ui.icon name="arrow-right" /></a>
    </div>
</div>

{{-- ── Key figures ──────────────────────────────────────────────────── --}}
<div class="kpi-grid">
    <x-ui.kpi icon="inbox" title="Needs GMU" caption="Across all calls"
        :value="$kpi['needs']"
        :badge="$kpi['needs'] === 0 ? 'All caught up' : ($kpi['needsOld'] ? $kpi['needsOld'] . ' over 7 days' : 'All recent')"
        :badge-tone="$kpi['needs'] === 0 ? 'ok' : ($kpi['needsOld'] ? 'warn' : 'neutral')"
        :badge-icon="$kpi['needs'] === 0 ? 'check' : ($kpi['needsOld'] ? 'alert' : null)"
        :note="$oldest ? 'Oldest waiting since ' . $oldest->updated_at->format('j M Y') : 'Nothing is waiting on GMU.'"
        :foot="['To screen' => $kpi['screen'], 'To decide' => $kpi['decide']]"
        :href="route('seed.submissions.index', ['call' => 'all', 'filter' => 'action'])" />

    <x-ui.kpi icon="wallet" title="Funds approved" caption="Calls running"
        :value="'TZS ' . $m($kpi['approved'])"
        :badge="$kpi['available'] ? round($kpi['approved'] / $kpi['available'] * 100) . '% committed' : null"
        badge-tone="ok" badge-icon="trending-up"
        :note="$kpi['available'] ? 'TZS ' . $m(max(0, $kpi['available'] - $kpi['approved'])) . ' left to commit' : 'Set funds available on each call to track this.'"
        :foot="['Available' => $kpi['available'] ? $m($kpi['available']) : '—', 'In play' => $m($kpi['inPlay'])]" />

    <x-ui.kpi icon="users" title="Reviews" caption="Open rounds"
        :value="$kpi['pending'] . ' pending'"
        :badge="$kpi['late'] ? $kpi['late'] . ' late' : 'None late'"
        :badge-tone="$kpi['late'] ? 'danger' : 'ok'"
        :badge-icon="$kpi['late'] ? 'alert' : 'check'"
        :note="$kpi['reviewsDone'] . ' ' . str('review')->plural($kpi['reviewsDone']) . ' submitted so far'"
        :foot="['Turnaround' => $kpi['turnaround'] !== null ? $kpi['turnaround'] . ' days' : '—', 'Late' => $kpi['late']]" />

    <x-ui.kpi icon="clock" title="Time to decision" caption="Concept note to decision"
        :value="$kpi['decisionDays'] === null ? '—' : ($kpi['decisionDays'] < 1 ? 'Same day' : $kpi['decisionDays'] . ' days')"
        :note="$kpi['decided'] ? 'Median of ' . $kpi['decided'] . ' decided concept notes' : 'No concept notes decided yet.'"
        :foot="['Decided' => $kpi['decided'], 'Awarded' => $kpi['awarded']]" />
</div>

{{-- ── Applications by stage, and money per call ─────────────────────── --}}
<div class="dash-row">
    <section class="panel">
        <div class="panel-head">
            <div><h2 class="panel-title">Applications by stage</h2><p class="panel-caption">Every call running, by where its applications are now</p></div>
            <a href="{{ route('seed.submissions.index', ['call' => 'all']) }}" class="btn btn-quiet btn-sm"><x-ui.icon name="list" /> All submissions</a>
        </div>
        <div class="metric-band">
            @foreach($band as $key => $cell)
                <div class="metric"><span class="metric-label"><span class="swatch seg-{{ $key }}"></span>{{ $cell['label'] }}</span><span class="metric-value">{{ $cell['count'] }}</span></div>
            @endforeach
        </div>
        <div class="panel-body">
            <svg class="chart" viewBox="0 0 {{ $chartW }} {{ $chartH }}" role="img" aria-label="Applications in each part of the process, by call">
                @for($i = 0; $i <= 4; $i++)
                    @php $y = $base - ($base - $top) * $i / 4; @endphp
                    <line class="chart-grid" x1="{{ $left }}" x2="{{ $chartW - 8 }}" y1="{{ $y }}" y2="{{ $y }}" />
                    <text class="chart-axis" x="{{ $left - 8 }}" y="{{ $y + 4 }}" text-anchor="end">{{ $step * $i }}</text>
                @endfor
                @foreach($pipeline as $i => $row)
                    @php $x = $left + $slot * $i + ($slot - $barW) / 2; $y = $base; @endphp
                    <g class="chart-bar">
                        @foreach($row['segments'] as $key => $n)
                            @if($n > 0)
                                @php $h = ($base - $top) * $n / $scaleMax; $y -= $h; @endphp
                                <rect class="seg-{{ $key }}" x="{{ $x }}" y="{{ $y }}" width="{{ $barW }}" height="{{ max(0, $h - 2) }}" rx="4"><title>{{ $groups[$key] }}: {{ $n }}</title></rect>
                            @endif
                        @endforeach
                        <text class="chart-total" x="{{ $x + $barW / 2 }}" y="{{ $y - 8 }}" text-anchor="middle">{{ $row['total'] }}</text>
                        <text class="chart-label" x="{{ $x + $barW / 2 }}" y="{{ $base + 20 }}" text-anchor="middle">{{ $row['call']->code }}</text>
                    </g>
                @endforeach
            </svg>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head"><div><h2 class="panel-title">Money by call</h2><p class="panel-caption">Approved against funds available</p></div></div>
        <ul class="funds-list">
            @foreach($cards as $card)
                @php $c = $card['call']; $pct = $c->envelope ? min(100, $card['approved'] / $c->envelope * 100) : 0; @endphp
                <li>
                    <div class="row-between">@include('seed.partials.call-tag', ['call' => $c, 'short' => true])
                        <span class="funds-pct">{{ $c->envelope ? round($pct) . '%' : '—' }}</span></div>
                    <span class="funds-name">{{ $c->title }}</span>
                    <div class="meter funds-meter"><span style="width:{{ $pct }}%"></span></div>
                    <div class="funds-figures"><span>Approved <b>{{ $m($card['approved']) }}</b></span><span>of {{ $c->envelope ? $m($c->envelope) : 'not set' }}</span><span>Requested <b>{{ $m($card['requested']) }}</b></span></div>
                </li>
            @endforeach
        </ul>
    </section>
</div>

{{-- ── What needs GMU, then reviewer load and centres ─────────────────── --}}
<section class="panel dash-full">
        <div class="panel-head">
            <div><h2 class="panel-title">Needs your action <span class="panel-count">{{ $needs->count() }}</span></h2><p class="panel-caption">Oldest waiting first, across all calls</p></div>
            <a href="{{ route('seed.submissions.index', ['call' => 'all', 'filter' => 'action']) }}" class="btn btn-quiet btn-sm">Open the queue <x-ui.icon name="arrow-right" /></a>
        </div>
        <div class="table-wrap">
            <table class="table table-quiet">
                <thead><tr><th>Application</th><th>Status</th><th>Waiting since</th><th><span class="sr-only">Action</span></th></tr></thead>
                <tbody>
                    @forelse($needs->take(8) as $n)
                        @php $a = $n['application']; @endphp
                        <tr>
                            <td class="col-main"><div class="strong line-clamp">{{ $a->title }}</div>
                                <div class="sub need-meta">@include('seed.partials.call-tag', ['call' => $a->call, 'short' => true])<span class="mono nowrap">{{ $a->reference }}</span><span>{{ $a->pi->name }}</span></div></td>
                            <td>@include('seed.partials.status', ['application' => $a])</td>
                            <td class="sub nowrap">{{ $a->updated_at->format('j M Y') }}<div class="tiny muted-3">{{ $a->updated_at->diffForHumans() }}</div></td>
                            <td class="num"><a href="{{ $n['toRanking'] ? route('seed.ranking', ['call' => $a->seed_call_id]) : route('seed.submissions.show', $a) }}" class="btn btn-sm">{{ $n['action'] }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="table-empty"><x-ui.icon name="check" /> All caught up, in every call.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($needs->count() > 8)
            <div class="panel-foot"><a href="{{ route('seed.submissions.index', ['call' => 'all', 'filter' => 'action']) }}" class="small strong">{{ $needs->count() - 8 }} more in the queue</a></div>
        @endif
</section>

<div class="dash-row dash-row-even">
        <section class="panel">
            <div class="panel-head"><div><h2 class="panel-title">Reviewer load</h2><p class="panel-caption">Done of assigned, open rounds</p></div></div>
            <ul class="bar-list panel-pad">
                @forelse($load as $r)
                    <li>
                        <div class="row-between small"><span class="strong">{{ $r['name'] }} @if($r['late'])<span class="pill pill-danger">{{ $r['late'] }} late</span>@endif</span><span class="mono">{{ $r['done'] }} / {{ $r['total'] }}</span></div>
                        <div class="meter {{ $r['late'] ? 'danger' : '' }}"><span style="width:{{ $r['total'] ? $r['done'] / $r['total'] * 100 : 0 }}%"></span></div>
                    </li>
                @empty
                    <li class="small muted">No open review rounds.</li>
                @endforelse
            </ul>
        </section>
        <section class="panel">
            <div class="panel-head"><div><h2 class="panel-title">By centre</h2><p class="panel-caption">Submissions, all calls</p></div></div>
            <ul class="centre-list panel-pad">
                @forelse($byCentre as $centre => $n)
                    <li><span>{{ $centre }}</span><span class="meter"><span style="width:{{ $n / $maxCentre * 100 }}%"></span></span><span class="mono">{{ $n }}</span></li>
                @empty
                    <li class="small muted">No submissions yet.</li>
                @endforelse
            </ul>
        </section>
</div>

{{-- ── A card per call ──────────────────────────────────────────────── --}}
<h2 class="section-title">Calls running</h2>
<div class="call-cards">
    @foreach($cards as $card)
        @php $c = $card['call']; @endphp
        <section class="card call-card {{ $c->tintClass() }}">
            <div class="call-card-head">
                <div class="call-card-name">
                    @include('seed.partials.call-tag', ['call' => $c])
                    <span class="tiny muted-3">FY {{ $c->fiscal_year }} ·
                        {{ $c->concept_deadline ? 'concept notes ' . ($c->concept_deadline->copy()->endOfDay()->isPast() ? 'closed ' : 'close ') . $c->concept_deadline->format('j M Y') : 'no deadline set' }}</span>
                </div>
                <x-ui.pill :tone="['open' => 'ok', 'draft' => null, 'closed' => 'info'][$c->status] ?? null">{{ \App\Models\Seed\SeedCall::STATUSES[$c->status] }}</x-ui.pill>
            </div>
            <ol class="stage-strip" aria-label="Applications at each stage of {{ $c->title }}">
                @foreach($card['stages'] as $stage)
                    <li class="{{ $stage['count'] ? 'has' : '' }}"><span class="n">{{ $stage['count'] }}</span><span class="l">{{ $stage['label'] }}</span></li>
                @endforeach
            </ol>
            <div class="call-card-actions">
                <a href="{{ route('seed.submissions.index', ['call' => $c->id]) }}" class="btn btn-sm">Submissions</a>
                <a href="{{ route('seed.ranking', ['call' => $c->id]) }}" class="btn btn-sm">Ranking</a>
                <a href="{{ route('seed.calls.show', $c) }}" class="btn btn-sm">Call page</a>
            </div>
        </section>
    @endforeach
</div>
@endsection
