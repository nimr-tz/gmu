@extends('layouts.gmu')

{{--
    GMU's dashboard across every call: what needs action anywhere, a card per
    call still running, then reviewer load and submissions by centre.
--}}

@php
    use App\Support\SeedGrant as S;
    $maxCentre = max(1, $byCentre->max() ?? 1);
@endphp

@section('title', 'Dashboard')
@section('context', 'Grants · ' . $callCount . ' ' . str('call')->plural($callCount) . ' running')

@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">{{ $callCount }} {{ str('call')->plural($callCount) }} running · {{ $submittedCount }} submitted</p>
        <h1 class="dash-title">{{ $needs->isEmpty() ? 'Nothing needs you right now' : ($needs->count() === 1 ? 'One submission needs GMU' : $needs->count() . ' submissions need GMU') }}</h1>
    </div>
    <div class="page-actions">
        <a href="{{ route('seed.calls.index') }}" class="btn"><i class="fas fa-bullhorn" aria-hidden="true"></i> Calls</a>
        <a href="{{ route('seed.submissions.index', ['call' => 'all', 'filter' => 'action']) }}" class="btn btn-primary">Work through submissions</a>
    </div>
</div>

<div class="stats">
    <div class="stat {{ $overdue ? 'is-danger' : '' }}"><span class="stat-label">Reviews overdue</span><span class="stat-value">{{ $overdue }}</span><span class="stat-note">in open rounds, all calls</span></div>
    <div class="stat"><span class="stat-label">With applicants</span><span class="stat-value">{{ $withApplicants }}</span><span class="stat-note">revising, writing proposals or accepting agreements</span></div>
    <div class="stat"><span class="stat-label">Not eligible</span><span class="stat-value">{{ $ineligible }}</span><span class="stat-note">at screening</span></div>
</div>

{{-- ── A card per call ──────────────────────────────────────────────── --}}
<h2 class="section-title">Calls running</h2>
<div class="call-cards">
    @foreach($cards as $card)
        @php $c = $card['call']; @endphp
        <section class="card call-card">
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

            <dl class="call-card-facts">
                <div><dt>Needs GMU</dt><dd class="{{ $card['needs'] ? 'text-warn' : '' }}">{{ $card['needs'] }}</dd></div>
                <div><dt>Submitted</dt><dd>{{ $card['submitted'] }}@if($card['drafts'])<span class="tiny muted-3"> + {{ $card['drafts'] }} in draft</span>@endif</dd></div>
                <div><dt>Requested</dt><dd class="mono">{{ number_format($card['requested'] / 1e6, 1) }}M</dd></div>
                <div><dt>Approved / available</dt><dd class="mono">{{ number_format($card['approved'] / 1e6, 1) }}M / {{ $c->envelope ? number_format($c->envelope / 1e6, 1) . 'M' : '—' }}</dd></div>
            </dl>

            <div class="call-card-actions">
                <a href="{{ route('seed.submissions.index', ['call' => $c->id]) }}" class="btn btn-sm">Submissions</a>
                <a href="{{ route('seed.ranking', ['call' => $c->id]) }}" class="btn btn-sm">Ranking</a>
                <a href="{{ route('seed.calls.show', $c) }}" class="btn btn-sm">Call page</a>
            </div>
        </section>
    @endforeach
</div>

<div class="grid grid-wide dash-lower">
    <section class="card">
        <div class="card-head"><div class="card-title">Needs your action <span class="mono muted">· {{ $needs->count() }}</span></div>
            <a href="{{ route('seed.submissions.index', ['call' => 'all']) }}" class="small strong">All submissions</a></div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Call</th><th>Application</th><th>Status</th><th>Waiting since</th><th><span class="sr-only">Action</span></th></tr></thead>
                <tbody>
                    @forelse($needs as $n)
                        @php $a = $n['application']; @endphp
                        <tr>
                            <td>@include('seed.partials.call-tag', ['call' => $a->call, 'short' => true])</td>
                            <td class="col-main"><div class="strong">{{ \Illuminate\Support\Str::limit($a->title, 70) }}</div>
                                <div class="sub"><span class="mono nowrap">{{ $a->reference }}</span> · {{ $a->pi->name }}{{ $a->pi->research_center ? ' · ' . $a->pi->research_center : '' }}</div></td>
                            <td>@include('seed.partials.status', ['application' => $a])</td>
                            <td class="sub nowrap">{{ $a->updated_at->format('j M Y') }}<div class="tiny muted-3">{{ $a->updated_at->diffForHumans() }}</div></td>
                            <td class="num"><a href="{{ $n['toRanking'] ? route('seed.ranking', ['call' => $a->seed_call_id]) : route('seed.submissions.show', $a) }}" class="btn btn-sm">{{ $n['action'] }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="table-empty"><i class="fas fa-check-circle" aria-hidden="true"></i>All caught up, in every call.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <aside class="stack">
        <section class="card"><div class="card-body">
            <div class="row-between"><h2 class="card-title">Reviewer load</h2><span class="tiny muted">done / assigned, open rounds, all calls</span></div>
            <ul class="bar-list">
                @forelse($load as $r)
                    <li>
                        <div class="row-between small"><span class="strong">{{ $r['name'] }} @if($r['late'])<span class="pill pill-danger">{{ $r['late'] }} late</span>@endif</span><span class="mono">{{ $r['done'] }} / {{ $r['total'] }}</span></div>
                        <div class="meter {{ $r['late'] ? 'danger' : '' }}"><span style="width:{{ $r['total'] ? $r['done'] / $r['total'] * 100 : 0 }}%"></span></div>
                    </li>
                @empty
                    <li class="small muted">No open review rounds.</li>
                @endforelse
            </ul>
            <p class="tiny muted">Applicants never see reviewer names.</p>
        </div></section>

        <section class="card"><div class="card-body">
            <h2 class="card-title">Submissions by centre, all calls</h2>
            <ul class="centre-list">
                @forelse($byCentre as $centre => $n)
                    <li><span>{{ $centre }}</span><span class="meter"><span style="width:{{ $n / $maxCentre * 100 }}%"></span></span><span class="mono">{{ $n }}</span></li>
                @empty
                    <li class="small muted">No submissions yet.</li>
                @endforelse
            </ul>
        </div></section>
    </aside>
</div>
@endsection
