@extends('layouts.gmu')

@php
    use App\Support\SeedGrant as S;
    $who = ['gmu' => 'GMU sign-off', 'drcp' => 'DRCP clearance', 'dg' => 'your decision'][$level];
    $queueTotal = $queue->sum(fn ($r) => $r['application']->budgetTotal('proposal'));
    $approvedTotal = $decided->sum('approved_amount');
@endphp

@section('title', 'Approvals')
@section('context', 'Seed Grant · FY ' . $call->fiscal_year)

@section('content')
<div class="row-between"><p class="eyebrow">{{ $call->title }} · FY {{ $call->fiscal_year }}</p>@include('seed.partials.call-picker')</div>
<x-ui.page-head :title="$queue->count() === 0 ? 'Nothing is waiting for ' . $who : ($queue->count() === 1 ? 'One project is waiting for ' . $who : $queue->count() . ' projects are waiting for ' . $who)"
    :lede="'Each was scored by the review panel and recommended. Approval runs ' . collect($call->form()->approvalLevels())->map(fn ($l) => ['gmu' => 'GMU', 'drcp' => 'the DRCP', 'dg' => 'the Director General'][$l])->implode(', then ') . '.'" />

<section class="card is-dark" style="padding:24px; display:flex; flex-wrap:wrap; gap:24px 48px; align-items:center; margin-bottom:24px;">
    <div style="flex:1 1 340px; min-width:0;">
        <div class="row-between"><span class="small" style="color:#A9B6D3;">Approved so far, against the funds available</span>
            <span class="mono small" style="color:#C7D2EA;">{{ number_format($approvedTotal / 1e6, 1) }}M of {{ $call->envelope ? number_format($call->envelope / 1e6, 1) . 'M' : 'not set' }}</span></div>
        <div style="margin-top:10px; height:12px; border-radius:99px; background:#1C2B57; overflow:hidden;">
            <div style="width:{{ $call->envelope ? min(100, $approvedTotal / max(1, $call->envelope) * 100) : 0 }}%; height:100%; background:var(--blue-400);"></div>
        </div>
    </div>
    <dl class="row" style="gap:36px; margin:0;">
        <div><dt class="small" style="color:#A9B6D3;">Waiting for you</dt><dd class="mono" style="margin:2px 0 0; font-size:24px; color:#fff;">TZS {{ number_format($queueTotal / 1e6, 1) }}M</dd></div>
        <div><dt class="small" style="color:#A9B6D3;">Approved</dt><dd class="mono" style="margin:2px 0 0; font-size:24px; color:#fff;">{{ $decided->count() }}</dd></div>
    </dl>
</section>

<div class="grid grid-wide">
    <section class="card">
        <div class="table-wrap">
            <table class="table" style="min-width:760px;">
                <thead><tr><th>Rank</th><th>Project</th><th>Score</th><th class="num">Amount (TZS)</th><th>Sign-off</th><th><span class="sr-only">Open</span></th></tr></thead>
                <tbody>
                    @forelse($queue as $i => $row)
                        @php $a = $row['application']; @endphp
                        <tr>
                            <td class="mono" style="font-size:18px; color:var(--muted-2);">{{ $i + 1 }}</td>
                            <td><div class="strong">{{ $a->title }}</div><div class="sub">{{ $a->pi->name }}{{ $a->pi->research_center ? ' · ' . $a->pi->research_center : '' }} · <span class="mono">{{ $a->reference }}</span></div></td>
                            <td><span class="pill pill-ok mono" style="height:30px; font-size:14px;">{{ $row['means']['total'] ?? '—' }}</span></td>
                            <td class="num">{{ number_format($a->budgetTotal('proposal')) }}</td>
                            <td>
                                <span class="chain">
                                    @foreach(['gmu' => 'GMU', 'drcp' => 'DRCP', 'dg' => 'DG'] as $l => $label)
                                        <span class="{{ $l === $level ? 'is-now' : (array_search($l, ['gmu', 'drcp', 'dg']) < array_search($level, ['gmu', 'drcp', 'dg']) ? 'is-done' : '') }}">{{ $label }}</span>
                                    @endforeach
                                </span>
                            </td>
                            <td style="text-align:right;"><a href="{{ route('seed.approvals.show', $a) }}" class="btn btn-sm {{ $i === 0 ? 'btn-primary' : '' }}">Decide</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="table-empty"><i class="fas fa-check-circle" aria-hidden="true"></i>Nothing waiting.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <aside class="stack-sm" style="gap:20px;">
        @if($inProgress->isNotEmpty())
            <section class="card"><div class="card-body">
                <h2 class="card-title">Elsewhere in approval</h2>
                <ul style="list-style:none; margin:12px 0 0; padding:0; display:flex; flex-direction:column; gap:10px; font-size:14px;">
                    @foreach($inProgress as $a)<li class="row-between"><span class="mono small">{{ $a->reference }}</span>@include('seed.partials.status', ['application' => $a])</li>@endforeach
                </ul>
            </div></section>
        @endif
        <section class="card"><div class="card-body">
            <h2 class="card-title">Not recommended</h2>
            <p class="small muted" style="margin-top:4px;">Full proposals the panel did not recommend. For information; no decision needed.</p>
            <ul style="list-style:none; margin:12px 0 0; padding:0; display:flex; flex-direction:column; gap:10px; font-size:13px;">
                @forelse($notRecommended as $row)
                    <li class="row-between" style="flex-wrap:nowrap;"><span>{{ $row['application']->reference }} · {{ \Illuminate\Support\Str::limit($row['application']->title, 38) }}</span><span class="mono">{{ $row['means']['total'] ?? '—' }}</span></li>
                @empty
                    <li class="muted">None.</li>
                @endforelse
            </ul>
        </div></section>
        <section class="card"><div class="card-body">
            <h2 class="card-title">When the DG approves</h2>
            <ol style="margin:12px 0 0; padding-left:20px; display:flex; flex-direction:column; gap:8px; color:var(--ink-2);">
                <li>The PI is told in the portal.</li>
                <li>GMU issues the grant agreement, drafted from the approved proposal.</li>
                <li>The PI accepts it online; GMU uploads the signed copy.</li>
            </ol>
        </div></section>
    </aside>
</div>
@endsection
