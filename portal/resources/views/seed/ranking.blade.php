@extends('layouts.gmu')

@php use App\Support\SeedGrant as S; $canManage = auth()->user()->can('manage-seed-grants'); $lineDrawn = false; @endphp

@section('title', 'Ranking')
@section('context', 'Seed Grant · FY ' . $call->fiscal_year)

@section('content')
<form method="POST" action="{{ route('seed.ranking.send') }}">
    @csrf
    <x-ui.page-head title="Rank and recommend" lede="Full proposals ranked by the reviewers’ mean score. The panel can change its recommendation, never the scores.">
        <x-slot:actions>
            @include('seed.partials.call-picker')
            @if($canManage && $awaitingCount)
                <button type="submit" class="btn btn-primary">{{ ['drcp' => 'Send ticked to DRCP', 'dg' => 'Send ticked to the DG'][$nextAfterGmu] ?? 'Approve ticked' }} <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
            @endif
        </x-slot:actions>
    </x-ui.page-head>

    <div class="stats" style="margin-bottom:24px;">
        <div class="stat"><span class="stat-label">Above the threshold</span><span class="stat-value">{{ $recommendedCount }} of {{ $ranked->count() }}</span></div>
        <div class="stat"><span class="stat-label">Their budgets</span><span class="stat-value">{{ number_format($recommendedTotal / 1e6, 1) }}M</span><span class="stat-note">TZS</span></div>
        <div class="stat"><span class="stat-label">Funds available</span><span class="stat-value" style="{{ $call->envelope ? '' : 'color:var(--muted-2);' }}">{{ $call->envelope ? number_format($call->envelope / 1e6, 1) . 'M' : '—' }}</span>
            <span class="stat-note">{{ $call->envelope ? ($recommendedTotal > $call->envelope ? 'Recommended exceeds the funds' : 'TZS') : 'Set it in call settings' }}</span></div>
        <div class="stat"><span class="stat-label">Waiting for GMU sign-off</span><span class="stat-value">{{ $awaitingCount }}</span></div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table" style="min-width:980px;">
                <thead><tr>
                    @if($canManage)<th style="width:40px;"><span class="sr-only">Select</span></th>@endif
                    <th>Rank</th><th>Proposal</th><th>Mean score</th>@php $form = $call->form(); $minKey = array_key_first($form->minimums()); @endphp
                    @if($minKey)<th class="num">{{ $form->criteria()[$minKey][0] }} /{{ S::trimNumber($form->criteria()[$minKey][2]) }}</th>@endif<th class="num">Budget (TZS)</th><th>Status</th>
                </tr></thead>
                <tbody>
                    @forelse($ranked as $i => $row)
                        @php $a = $row['application']; $m = $row['means']; @endphp
                        @if(! $lineDrawn && ! $row['passes'])
                            @php $lineDrawn = true; @endphp
                            <tr><td colspan="{{ $canManage ? 7 : 6 }}" style="padding:10px 22px; background:var(--panel); color:#fff; font-size:12px; font-weight:600; letter-spacing:.06em; text-transform:uppercase;">
                                Funding threshold · {{ S::trimNumber($form->passMark()) }} overall{{ $form->minimumsPhrase() }}</td></tr>
                        @endif
                        <tr style="{{ $row['passes'] ? '' : 'background:#FAFBFD;' }}">
                            @if($canManage)
                                <td>@if($a->status === S::RECOMMENDED)<input type="checkbox" name="ids[]" value="{{ $a->id }}" checked aria-label="Send {{ $a->reference }}" style="width:18px; height:18px; accent-color:var(--brand);">@endif</td>
                            @endif
                            <td class="mono" style="font-size:18px;">{{ $m ? $i + 1 : '—' }}</td>
                            <td>
                                <a href="{{ $canManage ? route('seed.submissions.show', $a) : route('seed.approvals.show', $a) }}" class="strong" style="color:var(--ink);">{{ $a->title }}</a>
                                <div class="sub"><span class="mono">{{ $a->reference }}</span> · {{ $a->pi->name }}{{ $a->pi->research_center ? ' · ' . $a->pi->research_center : '' }}</div>
                            </td>
                            <td>
                                @if($m)
                                    <div class="row" style="gap:12px; flex-wrap:nowrap;"><span class="mono strong" style="width:40px;">{{ $m['total'] }}</span>
                                        <span class="score-bar {{ $row['passes'] ? '' : 'is-below' }}"><i style="width:{{ $m['total'] }}%"></i><b></b></span>
                                        <span class="tiny muted">{{ $m['count'] }} {{ str('review')->plural($m['count']) }}</span></div>
                                @else
                                    <span class="small muted">Not scored yet</span>
                                @endif
                            </td>
                            @if($minKey)
                            <td class="num">
                                @if($m && ($m[$minKey] ?? 0) < $form->minimums()[$minKey])<span class="pill pill-danger">{{ $m[$minKey] }} · below minimum</span>@else{{ $m[$minKey] ?? '—' }}@endif
                            </td>
                            @endif
                            <td class="num">{{ number_format($a->budgetTotal('proposal')) }}</td>
                            <td>@include('seed.partials.status', ['application' => $a])</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="table-empty"><i class="fas fa-list-ol" aria-hidden="true"></i>No full proposals yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <p class="small muted" style="margin-top:14px; max-width:760px;">Ties go to the higher science score, then feasibility, then relevance. Recommendations are made on each proposal’s page; ticked proposals here are those already recommended.</p>
</form>
@endsection
