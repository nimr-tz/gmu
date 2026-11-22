@extends('layouts.gmu')

@section('title', 'The call')
@section('context', 'Seed Grant · FY ' . $call->fiscal_year)

@php use App\Support\SeedGrant; @endphp

@section('content')
<section style="background:var(--brand); color:#fff; border-radius:var(--radius); padding:36px clamp(20px,4vw,40px);">
    <div class="row" style="gap:10px;">
        <span class="pill" style="background:#fff; color:var(--blue-700);">
            {{ $call->acceptingConceptNotes() ? 'Concept notes open' : ($call->status === 'draft' ? 'Draft — not visible to staff' : 'Concept notes closed') }}
        </span>
        <span style="font-size:13px; color:var(--blue-50);">Internal call · DRCP in coordination with GMU</span>
        @can('manage-seed-grants')
            <a href="{{ route('seed.call.edit') }}" class="btn btn-sm" style="margin-left:auto; background:transparent; color:#fff; border-color:rgba(255,255,255,.5);">
                <i class="fas fa-cog" aria-hidden="true"></i> Call settings
            </a>
        @endcan
    </div>
    <h1 style="margin-top:16px; font-size:clamp(28px,3.2vw,40px); line-height:1.1; letter-spacing:-0.03em; max-width:880px;">{{ $call->title }}</h1>
    @if($call->summary)
        <p style="margin-top:14px; font-size:16px; color:var(--blue-50); max-width:720px; line-height:1.6;">{{ $call->summary }}</p>
    @endif
    <dl style="margin:28px 0 0; display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:1px; background:var(--blue-500); border:1px solid var(--blue-500); border-radius:14px; overflow:hidden; max-width:920px;">
        <div style="background:var(--blue-700); padding:16px 18px;"><dt style="font-size:12px; color:var(--blue-100);">Award per project</dt><dd class="mono" style="margin:4px 0 0; font-size:17px;">TZS {{ number_format($call->min_amount / 1e6, 0) }}M – {{ number_format($call->max_amount / 1e6, 0) }}M</dd></div>
        <div style="background:var(--blue-700); padding:16px 18px;"><dt style="font-size:12px; color:var(--blue-100);">Study length</dt><dd class="mono" style="margin:4px 0 0; font-size:17px;">{{ $call->duration_months }} months</dd></div>
        <div style="background:var(--blue-700); padding:16px 18px;"><dt style="font-size:12px; color:var(--blue-100);">Concept notes close</dt><dd class="mono" style="margin:4px 0 0; font-size:17px;">{{ $call->concept_deadline?->format('j M Y') ?? 'To be announced' }}</dd></div>
        <div style="background:var(--blue-700); padding:16px 18px;"><dt style="font-size:12px; color:var(--blue-100);">Concept note length</dt><dd class="mono" style="margin:4px 0 0; font-size:17px;">≤ {{ SeedGrant::CONCEPT_MAX_PAGES }} pages</dd></div>
    </dl>
</section>

<div class="grid grid-wide" style="margin-top:32px;">
    <div class="stack" style="gap:36px;">
        <section>
            <h2 style="font-size:20px;">Why this grant exists</h2>
            <p class="lede" style="margin-top:10px; font-size:15px; line-height:1.65; color:var(--ink-2);">To build the research leadership and independence of early-career NIMR scientists. The grant helps you generate preliminary evidence, test implementation strategies, work with health-system partners and build a path to larger competitive funding.</p>
            <p style="margin-top:10px; font-size:15px; line-height:1.65; color:var(--ink-2);">It does not fund large effectiveness trials, routine service delivery, or descriptive studies without a clear implementation question.</p>
        </section>

        <section>
            <h2 style="font-size:20px;">Who can lead an application</h2>
            <div class="grid grid-2" style="margin-top:16px; gap:12px;">
                @foreach([
                    'NIMR research scientists, laboratory scientists and health laboratory officers, permanent or contract',
                    'Under five years’ service at NIMR',
                    'Never held a research grant above USD 10,000 as PI',
                    'An experienced NIMR scientist agrees to mentor you',
                ] as $rule)
                    <div class="card" style="padding:16px; display:flex; gap:12px;"><i class="fas fa-check" style="color:var(--brand); margin-top:4px;" aria-hidden="true"></i><span>{{ $rule }}</span></div>
                @endforeach
            </div>
            <p class="small muted" style="margin-top:10px;">One application per person as lead PI.</p>
        </section>

        <section>
            <h2 style="font-size:20px;">How it works</h2>
            <p style="margin-top:10px; font-size:15px; color:var(--ink-2);">Two stages. Reviewers may send your concept note or proposal back for revision; you respond and it goes round again.</p>
            <div class="stack-sm" style="margin-top:16px;">
                @foreach([
                    ['1', 'Concept note', 'var(--brand)', [['You submit', 'Online form, ≤ 5 pages, simple budget'], ['GMU screens', 'Eligibility, completeness, budget range'], ['Reviewers respond', 'Accept, revise or reject'], ['Shortlist', 'Invited to stage 2']]],
                    ['2', 'Full proposal', 'var(--panel)', [['You submit', '≤ 10 pages, detailed budget, mentor letter, CV'], ['Scored review', 'Six weighted criteria'], ['Approval', 'GMU, then DRCP, then DG'], ['Agreement', 'Accept online before funds']]],
                ] as [$n, $name, $color, $steps])
                    <div class="card" style="padding:18px;">
                        <div class="row" style="gap:10px;"><span class="mono" style="width:28px; height:28px; border-radius:8px; background:{{ $color }}; color:#fff; display:grid; place-items:center; font-size:13px;">{{ $n }}</span><strong style="font-size:15px;">{{ $name }}</strong></div>
                        <div style="margin-top:14px; display:flex; flex-wrap:wrap; gap:8px;">
                            @foreach($steps as [$t, $s])
                                <div style="flex:1 1 140px; padding:14px; border-radius:12px; background:var(--canvas);"><b style="display:block; font-size:13px;">{{ $t }}</b><span class="tiny muted">{{ $s }}</span></div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section>
            <h2 style="font-size:20px;">Priority areas</h2>
            <p style="margin-top:10px; font-size:15px; color:var(--ink-2);">Your study must address an identifiable implementation problem. Innovation is welcome but not required.</p>
            <div class="chips" style="margin-top:14px;">
                @foreach(['Primary health care and referral', 'Infectious disease prevention, diagnosis and care', 'Maternal, newborn, child and adolescent health', 'NCDs and mental health', 'Community engagement and health workforce', 'Outbreak preparedness and surveillance', 'Digital health and data use', 'Diagnostics and service-delivery innovation', 'MoH and PO-RALG priorities, Vision 2050'] as $area)
                    <span class="chip">{{ $area }}</span>
                @endforeach
            </div>
        </section>

        <section>
            <h2 style="font-size:20px;">How full proposals are scored</h2>
            <div class="card" style="margin-top:16px;">
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Criterion</th><th>What reviewers look for</th><th class="num">Weight</th></tr></thead>
                        <tbody>
                            @foreach($call->form()->criteria() as [$name, $what, $max])
                                <tr><td class="strong" style="white-space:nowrap;">{{ $name }}</td><td class="sub" style="font-size:14px;">{{ $what }}</td><td class="num">{{ $max }}%</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <p style="margin-top:12px; font-size:14px; color:var(--ink-2);">Recommended for funding at <strong class="mono">{{ SeedGrant::trimNumber($call->form()->passMark()) }}/{{ SeedGrant::trimNumber($call->form()->maxTotal()) }}</strong> or above{{ $call->form()->minimumsPhrase(', and only with at least ', ' for ') }}.
                {{ $call->form()->tieBreak() ? 'Ties go to the stronger ' . collect($call->form()->tieBreak())->map(fn ($k) => mb_strtolower($call->form()->criteria()[$k][0]))->implode(', then ') . ' score.' : '' }}</p>
        </section>
    </div>

    <aside class="stack-sm" style="gap:20px;">
        <section class="card">
            <div class="card-body">
                <h2 class="card-title" style="font-size:18px;">Apply</h2>
                @if($application)
                    <p style="margin-top:10px;">You have an application: <span class="mono">{{ $application->reference }}</span></p>
                    <div style="margin-top:8px;">@include('seed.partials.status')</div>
                    <a href="{{ route('seed.mine') }}" class="btn btn-primary btn-block" style="margin-top:16px;">Open my application</a>
                @elseif(! $call->acceptingConceptNotes())
                    <p class="muted" style="margin-top:10px;">This call is not accepting concept notes.</p>
                @elseif($problems)
                    <div class="alert alert-warn" style="margin:14px 0 0;">
                        <i class="fas fa-info-circle" aria-hidden="true"></i>
                        <div>@foreach($problems as $p)<div>{{ $p }}</div>@endforeach</div>
                    </div>
                    @if(auth()->user()->isNreimsLinked())
                        <a href="{{ route('profile.complete') }}" class="btn btn-block" style="margin-top:12px;">Update my details</a>
                    @endif
                @else
                    <div class="alert alert-success" style="margin:14px 0 0;"><i class="fas fa-check" aria-hidden="true"></i><span>Your profile meets the eligibility rules.</span></div>
                    <form method="POST" action="{{ route('seed.apply') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-block" style="margin-top:12px;">Start your concept note</button>
                    </form>
                    <p class="small muted" style="margin-top:12px;">Everything is written online and saves when you press Save. No uploads at this stage.</p>
                @endif
            </div>
        </section>

        <section style="padding:20px 22px; border-radius:var(--radius); border:1px dashed var(--line-2); font-size:14px; color:var(--ink-2);">
            <strong style="display:block; color:var(--ink);">Questions?</strong>
            Contact the Grants Management Unit.
            @if($call->proposal_deadline)
                <div class="small muted" style="margin-top:8px;">Full proposals from shortlisted applicants are due {{ $call->proposal_deadline->format('j M Y') }}.</div>
            @endif
        </section>
    </aside>
</div>
@endsection
