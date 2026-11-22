@extends('layouts.gmu')

@php
    use App\Support\SeedGrant as S;
    use App\Models\Seed\SeedApproval;
    $myTurn = $application->status === \App\Http\Controllers\SeedGrant\ApprovalsController::QUEUE_STATUS[$level];
    $total  = $application->budgetTotal('proposal');
    $cats   = $application->budgetByCategory();
    $shades = ['personnel' => 'var(--blue-900)', 'equipment' => 'var(--blue-400)', 'research' => 'var(--brand)', 'indirect' => 'var(--blue-200)'];
    $steps  = ['gmu' => 'GMU sign-off', 'drcp' => 'DRCP clearance', 'dg' => 'Director General'];
@endphp

@section('title', 'Approve ' . $application->reference)
@section('context', 'Approvals · ' . $application->reference)

@section('content')
<p class="crumbs"><a href="{{ route('seed.approvals.index') }}">Approvals</a> / <span class="mono">{{ $application->reference }}</span></p>

<div class="page-head">
    <div style="flex:1 1 540px; min-width:0;">
        <div class="row" style="gap:8px;">@include('seed.partials.status')@if($means)<span class="pill">Mean score {{ $means['total'] }}</span>@endif</div>
        <h1 style="margin-top:10px; font-size:clamp(22px,2.4vw,28px); line-height:1.25;">{{ $application->title }}</h1>
        <p class="lede">{{ $application->pi->name }}, Principal Investigator{{ $application->pi->research_center ? ' · ' . $application->pi->research_center : '' }}{{ $application->mentor_name ? ' · mentor ' . $application->mentor_name : '' }}</p>
    </div>
    <div style="text-align:right;"><div class="small muted">Award requested</div><div class="mono" style="font-size:26px;">{{ S::tzs($total) }}</div></div>
</div>

<ol class="row" aria-label="Approval chain" style="list-style:none; margin:0 0 24px; padding:0; gap:10px; align-items:stretch;">
    @foreach($steps as $l => $label)
        @php $ap = $application->approvalAt($l); $done = $ap && $ap->decision !== 'returned'; $now = ! $done && $application->status === \App\Http\Controllers\SeedGrant\ApprovalsController::QUEUE_STATUS[$l]; @endphp
        <li class="card" style="flex:1 1 220px; padding:16px 18px; display:flex; gap:12px; {{ $now ? 'background:var(--panel); border-color:var(--panel); color:#fff;' : '' }}">
            <span style="flex:0 0 28px; width:28px; height:28px; border-radius:50%; display:grid; place-items:center; {{ $done ? 'background:var(--brand); color:#fff;' : ($now ? 'background:#fff; color:var(--panel);' : 'background:#EEF1F5; color:var(--muted-2);') }}">
                @if($done)<i class="fas fa-check" style="font-size:12px;" aria-hidden="true"></i>@else<span class="mono small">{{ $loop->iteration }}</span>@endif
            </span>
            <span><strong style="display:block;">{{ $label }}</strong>
                <span class="small" style="{{ $now ? 'color:#C7D2EA;' : 'color:var(--muted-2);' }}">
                    @if($done){{ $ap->decider?->name }} · {{ $ap->created_at->format('j M') }}{{ $ap->note ? ' — “' . \Illuminate\Support\Str::limit($ap->note, 70) . '”' : '' }}
                    @elseif($now){{ $l === $level ? 'Your decision' : 'Waiting' }}
                    @else Not yet @endif
                </span></span>
        </li>
    @endforeach
</ol>

<div class="grid grid-wide">
    <div class="stack" style="gap:20px;">
        @if($means)
            <section class="card"><div class="card-body">
                <div class="row-between"><h2 class="card-title">How the panel scored it</h2>
                    <span class="small muted">{{ $means['count'] }} {{ str('reviewer')->plural($means['count']) }}</span></div>
                <div style="margin-top:10px;">
                    @foreach($application->call->form()->criteria() as $key => [$name, , $max])
                        <div style="display:grid; grid-template-columns:minmax(0,1fr) 150px 72px; gap:16px; align-items:center; padding:10px 0; border-bottom:1px solid var(--line-soft);">
                            <span class="{{ $key === 'science' ? 'strong' : '' }}">{{ $name }}</span>
                            <span class="meter"><span style="width:{{ $means[$key] / $max * 100 }}%; {{ $key === 'science' ? 'background:var(--blue-900);' : '' }}"></span></span>
                            <span class="mono" style="text-align:right;">{{ $means[$key] }}/{{ $max }}</span>
                        </div>
                    @endforeach
                    <div class="row-between" style="padding-top:12px;"><strong>Total</strong><strong class="mono">{{ $means['total'] }} / 100</strong></div>
                </div>
            </div></section>
        @endif

        <section class="card"><div class="card-body">
            <div class="row-between"><h2 class="card-title">Budget</h2><span class="small muted">Detailed budget from the full proposal</span></div>
            @if($total > 0)
                <div style="margin-top:16px; display:flex; height:14px; border-radius:99px; overflow:hidden; gap:2px;" role="img"
                     aria-label="Budget split: @foreach($cats as $k => $v){{ S::BUDGET_CATEGORIES[$k] }} {{ round($v / $total * 100) }}%@if(! $loop->last), @endif @endforeach">
                    @foreach($cats as $k => $v)@if($v > 0)<span style="width:{{ $v / $total * 100 }}%; background:{{ $shades[$k] }};"></span>@endif @endforeach
                </div>
            @endif
            <dl class="form-grid" style="margin-top:16px; grid-template-columns:repeat(auto-fit,minmax(140px,1fr));">
                @foreach($cats as $k => $v)
                    <div><dt class="small row" style="gap:8px;"><span style="width:10px; height:10px; border-radius:3px; background:{{ $shades[$k] }};"></span>{{ S::BUDGET_CATEGORIES[$k] }}</dt>
                        <dd class="mono" style="margin:4px 0 0; font-size:16px;">{{ number_format($v) }}</dd>
                        @if($k === 'equipment' && $total > 0)<dd class="tiny" style="margin:2px 0 0; color:{{ $v / $total * 100 > ($application->call->form()->equipmentCap() ?? 100) ? 'var(--danger-ink)' : 'var(--blue-700)' }};">{{ number_format($v / $total * 100, 1) }}%, limit {{ $application->call->equipment_cap_percent }}%</dd>@endif
                    </div>
                @endforeach
            </dl>
            <div style="margin-top:18px; padding-top:16px; border-top:1px solid var(--line-soft); display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px; text-align:center;">
                @foreach($application->budgetByQuarter() as $q => $v)
                    <div style="padding:10px; border-radius:10px; background:var(--canvas);"><div class="tiny muted">{{ $q }}</div><div class="mono">{{ number_format($v / 1e6, 2) }}M</div></div>
                @endforeach
            </div>
        </div></section>

        <details class="card"><summary class="card-body" style="cursor:pointer; font-weight:600;">Read the full proposal</summary>
            <div style="padding:0 22px 22px;" class="stack-sm">@include('seed.partials.content', ['stage' => 'proposal'])</div></details>
    </div>

    <aside class="stack-sm" style="gap:16px;">
        @if($myTurn)
            <form method="POST" action="{{ route('seed.approvals.decide', $application) }}" class="card">
                @csrf
                <div class="card-body">
                    <h2 class="card-title">Your decision</h2>
                    <fieldset style="border:0; padding:0; margin:14px 0 0; display:flex; flex-direction:column; gap:8px;">
                        <legend class="sr-only">Decision</legend>
                        <label class="opt"><input type="radio" name="decision" value="approved" checked><span><strong>{{ $level === 'gmu' ? 'Send to the DRCP' : ($level === 'drcp' ? 'Clear for the Director General' : 'Approve') }}</strong></span></label>
                        @if($level === 'dg')
                            <label class="opt"><input type="radio" name="decision" value="conditions"><span><strong>Approve with conditions</strong><span class="opt-sub">Conditions go into the grant agreement</span></span></label>
                        @endif
                        @if($level !== 'gmu')
                            <label class="opt"><input type="radio" name="decision" value="returned"><span><strong>Return to GMU</strong><span class="opt-sub">With a reason; nothing is sent to the PI</span></span></label>
                        @endif
                    </fieldset>
                    <x-ui.field :label="$level === 'dg' ? 'Conditions or note' : 'Note'" name="note">
                        <textarea name="note" class="textarea" rows="3" style="margin-top:12px;">{{ old('note') }}</textarea>
                    </x-ui.field>
                    <button class="btn btn-primary btn-block" style="margin-top:14px;" type="submit">{{ $level === 'dg' ? 'Record decision and notify' : 'Record decision' }}</button>
                    <p class="tiny muted" style="margin-top:10px;">Recorded in the audit register with your name and the time.</p>
                </div>
            </form>
        @else
            <div class="banner banner-info" style="padding:16px 18px;"><div><strong>No decision needed from you</strong><p style="font-size:14px; margin-top:2px;">This application is “{{ $application->statusLabel() }}”.</p></div></div>
        @endif
        @if($application->approval_conditions)
            <section class="card"><div class="card-body"><h2 class="card-title">Conditions</h2><p class="doc-text" style="margin-top:8px; font-size:14px;">{{ $application->approval_conditions }}</p></div></section>
        @endif
    </aside>
</div>
@endsection
