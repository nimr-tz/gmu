@extends('layouts.gmu')

@section('title', 'The call')
@section('context', 'Calls · ' . $call->title)

@php use App\Support\SeedGrant; @endphp

@section('content')
<section style="background:var(--brand); color:#fff; border-radius:var(--radius); padding:36px clamp(20px,4vw,40px);">
    <div class="row" style="gap:10px;">
        <span class="pill" style="background:#fff; color:var(--blue-700);">
            {{ $call->acceptingConceptNotes() ? 'Concept notes open' : ($call->status === 'draft' ? 'Draft — not visible to staff' : 'Concept notes closed') }}
        </span>
        <span style="font-size:13px; color:var(--blue-50);">Internal call · DRCP in coordination with GMU</span>
        @can('manage-seed-grants')
            <a href="{{ route('seed.calls.edit', $call) }}" class="btn btn-sm" style="margin-left:auto; background:transparent; color:#fff; border-color:rgba(255,255,255,.5);">
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

@if($summary)
    {{-- GMU only: where this call stands. --}}
    <section class="card call-summary" aria-label="Where this call stands">
        <div class="card-head">
            <div><div class="card-title">Where this call stands</div>
                <div class="card-note">{{ $summary['submitted'] }} submitted{{ $summary['drafts'] ? ', ' . $summary['drafts'] . ' in draft' : '' }}{{ $summary['closed'] ? ', ' . $summary['closed'] . ' closed' : '' }}. Only GMU sees this.</div></div>
            <div class="call-card-actions">
                <a href="{{ route('seed.submissions.index', ['call' => $call->id]) }}" class="btn btn-sm">Submissions</a>
                <a href="{{ route('seed.submissions.export', ['call' => $call->id]) }}" class="btn btn-sm"><i class="fas fa-file-csv" aria-hidden="true"></i> Export</a>
            </div>
        </div>
        <div class="card-body">
            <ol class="stage-strip" aria-label="Applications at each stage">
                @foreach($summary['stages'] as $stage)
                    <li class="{{ $stage['count'] ? 'has' : '' }}"><span class="n">{{ $stage['count'] }}</span><span class="l">{{ $stage['label'] }}</span></li>
                @endforeach
            </ol>
            <div class="call-summary-grid">
                <dl class="call-card-facts">
                    <div><dt>Requested, still in play</dt><dd class="mono">{{ SeedGrant::tzs($summary['requested']) }}</dd></div>
                    <div><dt>Approved</dt><dd class="mono">{{ SeedGrant::tzs($summary['approved']) }}</dd></div>
                    <div><dt>Funds available</dt><dd class="mono">{{ $call->envelope ? SeedGrant::tzs($call->envelope) : 'Not set' }}</dd></div>
                    <div><dt>Left to commit</dt><dd class="mono">{{ $call->envelope ? SeedGrant::tzs(max(0, $call->envelope - $summary['approved'])) : '—' }}</dd></div>
                </dl>
                <div>
                    <div class="small strong">By centre</div>
                    <ul class="centre-list">
                        @php $maxCentre = max(1, $summary['byCentre']->max() ?? 1); @endphp
                        @forelse($summary['byCentre'] as $centre => $n)
                            <li><span>{{ $centre }}</span><span class="meter"><span style="width:{{ $n / $maxCentre * 100 }}%"></span></span><span class="mono">{{ $n }}</span></li>
                        @empty
                            <li class="small muted">No submissions yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </section>
@endif

<div class="grid grid-wide" style="margin-top:32px;">
    <div class="stack" style="gap:36px;">
        @if($call->form()->aboutParagraphs())
        <section>
            <h2 class="call-section-title">Why this call exists</h2>
            @foreach($call->form()->aboutParagraphs() as $paragraph)
                <p class="call-para">{{ $paragraph }}</p>
            @endforeach
        </section>
        @endif

        <section>
            <h2 style="font-size:20px;">Who can lead an application</h2>
            <div class="grid grid-2" style="margin-top:16px; gap:12px;">
                @foreach($call->form()->eligibilityLines() as $rule)
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
                    ['2', 'Full proposal', 'var(--panel)', [
                        ['You submit', '≤ 10 pages, detailed budget' . collect($call->form()->documents())->filter(fn ($d) => $d[1])->map(fn ($d) => ', ' . mb_strtolower($d[0]))->implode('')],
                        $call->form()->scored() ? ['Scored review', count($call->form()->criteria()) . ' weighted criteria'] : ['Review', 'Accept, revise or reject'],
                        ['Approval', collect($call->form()->approvalLevels())->map(fn ($l) => ['gmu' => 'GMU', 'drcp' => 'DRCP', 'dg' => 'DG'][$l])->implode(', then ')],
                        ['Agreement', 'Accept online before funds']]],
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

        @if($call->form()->priorities())
        <section>
            <h2 class="call-section-title">Priority areas</h2>
            @if($call->form()->prioritiesIntro())<p class="call-para">{{ $call->form()->prioritiesIntro() }}</p>@endif
            <div class="chips call-chips">
                @foreach($call->form()->priorities() as $area)
                    <span class="chip">{{ $area }}</span>
                @endforeach
            </div>
        </section>
        @endif

        @if($call->form()->scored())
        <section>
            <h2 style="font-size:20px;">How full proposals are scored</h2>
            <div class="card" style="margin-top:16px;">
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Criterion</th><th>What reviewers look for</th><th class="num">Maximum score</th></tr></thead>
                        <tbody>
                            @foreach($call->form()->criteria() as [$name, $what, $max])
                                <tr><td class="strong" style="white-space:nowrap;">{{ $name }}</td><td class="sub" style="font-size:14px;">{{ $what }}</td><td class="num mono">{{ SeedGrant::trimNumber($max) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <p style="margin-top:12px; font-size:14px; color:var(--ink-2);">Recommended for funding at <strong class="mono">{{ SeedGrant::trimNumber($call->form()->passMark()) }}/{{ SeedGrant::trimNumber($call->form()->maxTotal()) }}</strong> or above{{ $call->form()->minimumsPhrase(', and only with at least ', ' for ') }}.
                {{ $call->form()->tieBreak() ? 'Ties go to the stronger ' . collect($call->form()->tieBreak())->map(fn ($k) => mb_strtolower($call->form()->criteria()[$k][0]))->implode(', then ') . ' score.' : '' }}</p>
        </section>
        @endif
    </div>

    <aside class="stack-sm" style="gap:20px;">
        <section class="card">
            <div class="card-body">
                <h2 class="card-title" style="font-size:18px;">Apply</h2>
                @if($application)
                    <p style="margin-top:10px;">You have an application: <span class="mono">{{ $application->reference }}</span></p>
                    <div style="margin-top:8px;">@include('seed.partials.status')</div>
                    <a href="{{ route($application->isEditableByApplicant() ? 'seed.applications.edit' : 'seed.applications.show', $application) }}" class="btn btn-primary btn-block" style="margin-top:16px;">Open my application</a>
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
                    <form method="POST" action="{{ route('seed.calls.apply', $call) }}">
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
