@extends('layouts.gmu')

@php
    use App\Support\SeedGrant as S;
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $first = \Illuminate\Support\Str::of($user->name)->replaceMatches('/^(Dr\.?|Prof\.?|Mr\.?|Ms\.?|Mrs\.?)\s+/i', '')->before(' ');
    $round = $application?->latestRound();
@endphp

@section('title', 'Dashboard')
@section('context', $call ? 'Seed Grant · FY ' . $call->fiscal_year : 'NIMR Grants')

@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">{{ now()->format('l, j F Y') }}</p>
        <h1 style="margin-top:6px;">{{ $greeting }}, {{ $first }}.</h1>
    </div>
    @if($call && $call->isVisible())
        <a href="{{ route('seed.calls.show', $call) }}" class="btn">Read the call</a>
    @endif
</div>

@if(! $call || ! $call->isVisible())
    <div class="card"><div class="empty"><i class="fas fa-seedling" aria-hidden="true"></i><p>The seed grant call opens here soon.</p></div></div>
@elseif(! $application)
    <section class="banner banner-ok" style="margin-bottom:24px;">
        <span class="ico"><i class="fas fa-seedling" aria-hidden="true"></i></span>
        <div style="flex:1 1 360px;">
            <h2>{{ $call->acceptingConceptNotes() ? 'The seed grant call is open' : 'The seed grant call' }}</h2>
            <p>TZS {{ number_format($call->min_amount / 1e6) }}M–{{ number_format($call->max_amount / 1e6) }}M for a {{ $call->duration_months }}-month implementation science study.
                @if($call->concept_deadline) Concept notes {{ $call->concept_deadline->isPast() ? 'closed' : 'close' }} {{ $call->concept_deadline->format('j M Y') }}.@endif</p>
            <div class="row" style="margin-top:14px;">
                <a href="{{ route('seed.calls.show', $call) }}" class="btn btn-primary">{{ $call->acceptingConceptNotes() && ! $problems ? 'Read the call and apply' : 'Read the call' }}</a>
                @if($reviewing)<a href="{{ route('seed.reviews.index') }}" class="btn">My reviews</a>@endif
            </div>
        </div>
    </section>
@else
    <div class="grid grid-wide">
        <div class="stack">
            @if($application->isRevising() && $round)
                <section class="banner banner-warn">
                    <span class="ico"><i class="fas fa-comment-dots" aria-hidden="true"></i></span>
                    <div style="flex:1 1 360px;">
                        <div class="row" style="gap:8px 12px;"><h2>Reviewers asked you to revise</h2><span class="pill" style="background:#fff; color:var(--warn-ink);">Round {{ $round->number }} · due {{ $round->response_due_at?->format('j M Y') }}</span></div>
                        <p>{{ $round->decision_note }}</p>
                        <div class="row" style="margin-top:14px;">
                            <a href="{{ route('seed.applications.edit', $application) }}" class="btn btn-primary">Respond to reviewers</a>
                            <a href="{{ route('seed.applications.show', $application) }}" class="btn" style="background:transparent; border-color:#EBCB8B;">Read their comments</a>
                        </div>
                    </div>
                </section>
            @elseif(in_array($application->status, [S::DRAFT, S::SHORTLISTED], true))
                <section class="banner {{ $application->status === S::SHORTLISTED ? 'banner-ok' : 'banner-info' }}">
                    <span class="ico"><i class="fas {{ $application->status === S::SHORTLISTED ? 'fa-star' : 'fa-pen' }}" aria-hidden="true"></i></span>
                    <div style="flex:1 1 360px;">
                        <h2>{{ $application->status === S::SHORTLISTED ? 'You are shortlisted: write your full proposal' : 'Finish and submit your concept note' }}</h2>
                        <p>{{ $application->status === S::SHORTLISTED
                            ? ($call->proposal_deadline ? 'Due ' . $call->proposal_deadline->format('j M Y') . '. ' : '') . 'You will also need the mentor’s commitment letter and your CV.'
                            : ($call->concept_deadline ? 'Concept notes close ' . $call->concept_deadline->format('j M Y') . '.' : 'It saves when you press Save.') }}</p>
                        <a href="{{ route('seed.applications.edit', $application) }}" class="btn btn-primary" style="margin-top:14px;">{{ $application->status === S::SHORTLISTED ? 'Write full proposal' : 'Continue writing' }}</a>
                    </div>
                </section>
            @elseif($application->status === S::AGREEMENT_ISSUED)
                <section class="banner banner-warn">
                    <span class="ico"><i class="fas fa-file-signature" aria-hidden="true"></i></span>
                    <div style="flex:1 1 360px;"><h2>Your grant agreement is ready</h2><p>Read it and accept it online.</p>
                        <a href="{{ route('seed.applications.agreement', $application) }}" class="btn btn-primary" style="margin-top:14px;">Open the agreement</a></div>
                </section>
            @endif

            <section class="card"><div class="card-body">
                <div class="row-between" style="align-items:flex-start;">
                    <div style="min-width:0; flex:1 1 400px;">
                        <div class="row" style="gap:10px;"><span class="mono small muted">{{ $application->reference }}</span>@include('seed.partials.status')</div>
                        <h2 style="margin-top:8px; font-size:20px; line-height:1.3;">{{ $application->title ?: 'Untitled concept note' }}</h2>
                    </div>
                    <a href="{{ route('seed.applications.show', $application) }}" class="btn">Open</a>
                </div>
                <dl class="form-grid" style="margin:20px 0; padding:16px 0; border-top:1px solid var(--line-soft); border-bottom:1px solid var(--line-soft); grid-template-columns:repeat(auto-fit,minmax(150px,1fr));">
                    <div class="kv"><dt class="k">Requested</dt><dd class="v mono" style="margin:0;">{{ S::tzs($application->requestedAmount()) }}</dd></div>
                    <div class="kv"><dt class="k">Submitted</dt><dd class="v" style="margin:0;">{{ $application->concept_submitted_at?->format('j M Y') ?? 'Not yet' }}</dd></div>
                    <div class="kv"><dt class="k">Mentor</dt><dd class="v" style="margin:0;">{{ $application->mentor_name ?: '—' }}</dd></div>
                    <div class="kv"><dt class="k">Review round</dt><dd class="v" style="margin:0;">{{ $round ? $round->number . ($round->stage === 'proposal' ? ' (proposal)' : '') : '—' }}</dd></div>
                </dl>
                <h3 class="eyebrow" style="margin-bottom:14px;">Where your application is</h3>
                @include('seed.partials.track')
            </div></section>
        </div>

        <aside class="stack" style="gap:24px;">
            <section class="card is-dark"><div class="card-body">
                <p class="eyebrow" style="color:var(--blue-300);">Key dates</p>
                <ul style="list-style:none; margin:16px 0 0; padding:0; display:flex; flex-direction:column; gap:14px;">
                    <li class="row-between"><span style="color:#A9B6D3;">Concept notes close</span><span class="mono" style="color:#fff;">{{ $call->concept_deadline?->format('j M') ?? 'TBA' }}</span></li>
                    @if($application->isRevising() && $round?->response_due_at)
                        <li class="row-between"><span style="color:#fff; font-weight:600;">Your response is due</span><span class="mono" style="color:#F8C45C; font-weight:600;">{{ $round->response_due_at->format('j M') }}</span></li>
                    @endif
                    <li class="row-between"><span style="color:#A9B6D3;">Full proposals due</span><span class="mono" style="color:#fff;">{{ $call->proposal_deadline?->format('j M') ?? 'TBA' }}</span></li>
                </ul>
            </div></section>

            @if($application->mentor_name)
                <section class="card"><div class="card-body">
                    <h2 class="card-title">Your mentor</h2>
                    <div class="row" style="margin-top:14px; flex-wrap:nowrap;">
                        <span class="avatar" style="width:44px; height:44px; flex-basis:44px;">{{ collect(explode(' ', preg_replace('/^(Dr\.?|Prof\.?)\s+/i', '', $application->mentor_name)))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') }}</span>
                        <span style="line-height:1.35;"><strong>{{ $application->mentor_name }}</strong><br><span class="small muted">{{ collect([$application->mentor_designation, $application->mentor_centre])->filter()->implode(' · ') }}</span></span>
                    </div>
                    @if(in_array($application->status, S::CONCEPT_STATUSES, true))
                        <p class="small" style="margin-top:14px; color:var(--ink-2);">A signed commitment letter is needed only if you are shortlisted.</p>
                    @endif
                </div></section>
            @endif

            @if($reviewing)
                <a href="{{ route('seed.reviews.index') }}" class="btn btn-block"><i class="fas fa-comments" aria-hidden="true"></i> You also have reviews to do</a>
            @endif
        </aside>
    </div>
@endif
@endsection
