@extends('layouts.gmu')

@php
    use App\Support\SeedGrant as S;
    use App\Models\Seed\SeedReview;
    $s      = $application->status;
    $pi     = $application->pi;
    $open   = $application->openRound();
    $means  = $application->meanScores();
    $recTone = ['accept' => 'ok', 'revise' => 'warn', 'reject' => 'danger'];
    $canAssign = in_array($s, [S::SCREENED, S::CONCEPT_REVIEW, S::CONCEPT_REVISION, S::PROPOSAL_SUBMITTED, S::PROPOSAL_REVIEW, S::PROPOSAL_REVISION], true);
@endphp

@section('title', $application->reference)
@section('context', 'Submissions · ' . $application->reference)

@section('content')
<p class="crumbs"><a href="{{ route('seed.submissions.index') }}">Submissions</a> / <span class="mono">{{ $application->reference }}</span></p>

<div class="page-head" style="margin-bottom:16px;">
    <div style="flex:1 1 560px; min-width:0;">
        <div class="row" style="gap:8px;"><span class="pill">{{ $stage === 'concept' ? 'Concept note' : 'Full proposal' }}</span>@include('seed.partials.status')</div>
        <h1 style="margin-top:8px; font-size:clamp(22px,2.2vw,28px); line-height:1.25;">{{ $application->title ?: 'Untitled' }}</h1>
        <p class="lede">{{ $pi->name }}{{ $pi->designation ? ' · ' . $pi->designation : '' }}{{ $pi->research_center ? ' · ' . $pi->research_center : '' }}{{ $application->mentor_name ? ' · mentor ' . $application->mentor_name : '' }}</p>
    </div>
    <dl class="row" style="gap:28px; margin:0;">
        <div class="kv"><dt class="k">Requested</dt><dd class="v mono" style="margin:0;">{{ S::tzs($application->requestedAmount()) }}</dd></div>
        <div class="kv"><dt class="k">Submitted</dt><dd class="v" style="margin:0;">{{ $application->concept_submitted_at?->format('j M Y') ?? '—' }}</dd></div>
        @if($means)
            <div class="kv"><dt class="k">Mean score</dt><dd class="v mono" style="margin:0;">{{ $means['total'] }}</dd></div>
        @endif
    </dl>
</div>

<nav class="chips" aria-label="Sections" style="margin-bottom:20px;">
    <a href="?tab=reviews" class="chip {{ $tab === 'reviews' ? 'is-active' : '' }}">Reviews</a>
    <a href="?tab=concept" class="chip {{ $tab === 'concept' ? 'is-active' : '' }}">Concept note</a>
    @if($application->proposal)
        <a href="?tab=proposal" class="chip {{ $tab === 'proposal' ? 'is-active' : '' }}">Full proposal</a>
    @endif
    <a href="?tab=history" class="chip {{ $tab === 'history' ? 'is-active' : '' }}">History</a>
</nav>

<div class="grid grid-wide">
    <div class="stack" style="gap:16px;">
        @if($tab === 'concept' || $tab === 'proposal')
            @include('seed.partials.content', ['stage' => $tab])

        @elseif($tab === 'history')
            <section class="card">
                <div class="card-head"><div class="card-title">History</div></div>
                <div class="list">
                    @if($application->concept_submitted_at)
                        <div class="list-row"><span class="list-main"><span class="list-title">Concept note submitted</span></span><span class="small muted">{{ $application->concept_submitted_at->format('j M Y H:i') }}</span></div>
                    @endif
                    @if($application->screened_at)
                        <div class="list-row"><span class="list-main"><span class="list-title">Screened: {{ $s === S::INELIGIBLE ? 'not eligible' : ($s === S::DRAFT || $s === S::SUBMITTED ? 'returned for correction' : 'eligible') }}</span>
                            <span class="list-sub">{{ $application->screener?->name }}{{ $application->screening_note ? ' — ' . $application->screening_note : '' }}</span></span><span class="small muted">{{ $application->screened_at->format('j M Y H:i') }}</span></div>
                    @endif
                    @foreach($application->rounds as $r)
                        <div class="list-row"><span class="list-main"><span class="list-title">{{ ucfirst($r->stage) }} round {{ $r->number }} opened</span><span class="list-sub">Reviews due {{ $r->due_at?->format('j M Y') }}</span></span><span class="small muted">{{ $r->created_at->format('j M Y') }}</span></div>
                        @if($r->decided_at)
                            <div class="list-row"><span class="list-main"><span class="list-title">Round {{ $r->number }}: {{ \App\Models\Seed\SeedReviewRound::DECISIONS[$r->decision] }}</span><span class="list-sub">{{ $r->decider?->name }}{{ $r->decision_note ? ' — ' . $r->decision_note : '' }}</span></span><span class="small muted">{{ $r->decided_at->format('j M Y H:i') }}</span></div>
                        @endif
                        @if($r->responded_at)
                            <div class="list-row"><span class="list-main"><span class="list-title">Applicant responded</span><span class="list-sub">{{ \Illuminate\Support\Str::limit($r->response, 160) }}</span></span><span class="small muted">{{ $r->responded_at->format('j M Y H:i') }}</span></div>
                        @endif
                    @endforeach
                    @if($application->proposal_submitted_at)
                        <div class="list-row"><span class="list-main"><span class="list-title">Full proposal submitted</span></span><span class="small muted">{{ $application->proposal_submitted_at->format('j M Y H:i') }}</span></div>
                    @endif
                    @foreach($application->approvals->sortBy('id') as $ap)
                        <div class="list-row"><span class="list-main"><span class="list-title">{{ \App\Models\Seed\SeedApproval::LEVELS[$ap->level] }}: {{ \App\Models\Seed\SeedApproval::DECISIONS[$ap->decision] }}</span><span class="list-sub">{{ $ap->decider?->name }}{{ $ap->note ? ' — ' . $ap->note : '' }}</span></span><span class="small muted">{{ $ap->created_at->format('j M Y H:i') }}</span></div>
                    @endforeach
                    @foreach([['agreement_issued_at', 'Agreement issued'], ['agreement_accepted_at', 'Agreement accepted by the PI'], ['signed_agreement_uploaded_at', 'Signed agreement uploaded']] as [$f, $label])
                        @if($application->$f)
                            <div class="list-row"><span class="list-main"><span class="list-title">{{ $label }}</span></span><span class="small muted">{{ $application->$f->format('j M Y H:i') }}</span></div>
                        @endif
                    @endforeach
                </div>
            </section>

        @else
            @forelse($application->rounds->sortByDesc(fn ($r) => [$r->stage === 'proposal', $r->number]) as $round)
                @php $roundAssignments = $application->stageAssignments($round->stage)->filter(fn ($a) => $a->removed_at === null || $round->reviews->contains('seed_reviewer_assignment_id', $a->id)); @endphp
                <section>
                    <div class="row-between" style="margin-bottom:12px;">
                        <h2 style="font-size:16px;">{{ $round->stage === 'proposal' ? 'Full proposal' : 'Concept note' }} · round {{ $round->number }}</h2>
                        <span class="small muted">
                            @if($round->isOpen()) Reviews due {{ $round->due_at?->format('j M Y') }}
                            @else Decided {{ $round->decided_at?->format('j M Y') }}: {{ \App\Models\Seed\SeedReviewRound::DECISIONS[$round->decision] }} @endif
                        </span>
                    </div>
                    <div class="grid grid-2" style="gap:16px;">
                        @foreach($roundAssignments as $a)
                            @php $review = $round->reviews->firstWhere('seed_reviewer_assignment_id', $a->id); @endphp
                            <article class="card">
                                <div class="card-body">
                                    <div class="row-between" style="align-items:flex-start;">
                                        <div><strong>Reviewer {{ $a->label }}</strong><div class="small muted">{{ $a->reviewer->name }} · seen by GMU only</div></div>
                                        @if($review?->isSubmitted())
                                            <span class="pill pill-{{ $recTone[$review->recommendation] ?? '' }}">{{ SeedReview::RECOMMENDATIONS[$review->recommendation] }}</span>
                                        @elseif($a->coi_status === 'conflict')
                                            <span class="pill pill-danger">Conflict declared</span>
                                        @elseif($a->coi_status === 'pending')
                                            <span class="pill pill-warn">Declaration pending</span>
                                        @else
                                            <span class="pill {{ $round->isOverdue() ? 'pill-danger' : '' }}">{{ $round->isOverdue() ? 'Overdue' : 'Not submitted' }}</span>
                                        @endif
                                    </div>
                                    @if($review?->isSubmitted())
                                        @if($round->stage === 'proposal' && $review->scores)
                                            <div class="mono" style="margin-top:12px; font-size:22px;">{{ $review->total }}<span class="small muted"> / 100</span></div>
                                            <div class="tiny muted" style="margin-top:4px;">
                                                @foreach($application->call->form()->criteria() as $key => [$name, , $max]){{ $name }} {{ $review->score($key) }}/{{ $max }}@if(! $loop->last) · @endif @endforeach
                                            </div>
                                        @endif
                                        <p class="doc-text" style="margin-top:12px; font-size:14px;">{{ $review->comments }}</p>
                                        @if($review->confidential_note)
                                            <div class="alert alert-info" style="margin:12px 0 0;"><i class="fas fa-lock" aria-hidden="true"></i><div><strong>Confidential to GMU</strong><div>{{ $review->confidential_note }}</div></div></div>
                                        @endif
                                    @elseif($a->coi_status === 'conflict' && $a->coi_note)
                                        <p class="small muted" style="margin-top:10px;">{{ $a->coi_note }}</p>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                    @if($round->response)
                        <div class="card" style="margin-top:16px;"><div class="card-body"><strong>Applicant’s response</strong> <span class="small muted">· {{ $round->responded_at?->format('j M Y') }}</span><p class="doc-text" style="margin-top:6px; font-size:14px;">{{ $round->response }}</p></div></div>
                    @endif
                </section>
            @empty
                <div class="card"><div class="empty"><i class="fas fa-comments" aria-hidden="true"></i><p>No review rounds yet. {{ $s === S::SUBMITTED ? 'Screen the concept note first.' : 'Assign reviewers and send it out.' }}</p></div></div>
            @endforelse
        @endif
    </div>

    <aside class="stack-sm" style="gap:16px;">
        {{-- What GMU does next depends on where the application is. --}}

        @if($s === S::SUBMITTED)
            <form method="POST" action="{{ route('seed.submissions.screen', $application) }}" class="card">
                @csrf
                <div class="card-body">
                    <h2 class="card-title">Screening</h2>
                    <p class="small muted" style="margin-top:4px;">Eligibility, completeness and compliance with the call.</p>
                    @php
                        $service = $pi->serviceLength();
                        $autoChecks = [
                            'staff'      => ['NIMR staff, permanent or contract', (bool) $pi->employment_type, $pi->employment_type ? \App\Models\User::EMPLOYMENT_TYPES[$pi->employment_type] : 'not recorded'],
                            'service'    => ($years = $application->call->form()->serviceYears())
                                ? ["Under {$years} years at NIMR", (bool) $pi->withinServiceLimit($years), $service ? "{$service['years']} y {$service['months']} m" : 'not recorded']
                                : null,
                            'prior'      => ($priorRule = $application->call->form()->priorGrant())
                                ? ['No prior grant over USD ' . number_format($priorRule['usd']) . ' as PI', $application->declared_no_prior_large_grant, 'declared']
                                : null,
                            'complete'   => ['All sections complete, within limits', empty($complete), $application->pageEstimate('concept') . ' pp'],
                            'budget'     => ['Budget within ' . number_format($application->call->min_amount / 1e6) . 'M–' . number_format($application->call->max_amount / 1e6) . 'M', ! isset($complete['budget']), number_format($application->budgetTotal('concept') / 1e6, 2) . 'M'],
                            'mentor'     => $application->call->form()->mentorWords() !== null
                                ? ['Mentor named, with relevant expertise', filled($application->mentor_name) && filled($application->mentor_suitability), $application->mentor_name ?: '—']
                                : null,
                        ];
                        // Checks for features this call does not use are left out.
                        $autoChecks = array_filter($autoChecks);
                    @endphp
                    <div style="margin-top:12px;">
                        @foreach($autoChecks as $key => [$label, $ok, $detail])
                            <label class="check" style="justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--line-soft);">
                                <span class="row" style="gap:10px; flex-wrap:nowrap;">
                                    <input type="hidden" name="checks[{{ $key }}]" value="0">
                                    <input type="checkbox" name="checks[{{ $key }}]" value="1" @checked($ok)>
                                    <span>{{ $label }}</span>
                                </span>
                                <span class="tiny {{ $ok ? 'muted' : 'text-danger' }}">{{ $detail }}</span>
                            </label>
                        @endforeach
                    </div>
                    <fieldset style="border:0; padding:0; margin:16px 0 0; display:flex; flex-direction:column; gap:8px;">
                        <legend class="label" style="margin-bottom:6px;">Result</legend>
                        <label class="opt"><input type="radio" name="result" value="eligible" @checked(empty($eligibility) && empty($complete))><span><strong>Eligible</strong><span class="opt-sub">Goes on to reviewers</span></span></label>
                        <label class="opt"><input type="radio" name="result" value="return" @checked(empty($eligibility) && ! empty($complete))><span><strong>Return for correction</strong><span class="opt-sub">Back to the applicant to fix and resubmit</span></span></label>
                        <label class="opt"><input type="radio" name="result" value="ineligible" @checked(! empty($eligibility))><span><strong>Not eligible</strong><span class="opt-sub">Closed; the applicant is told why</span></span></label>
                    </fieldset>
                    <x-ui.field label="Note to the applicant" name="screening_note">
                        <textarea name="note" class="textarea" rows="3" style="margin-top:12px;" placeholder="Required if returned or not eligible">{{ old('note') }}</textarea>
                    </x-ui.field>
                    <button class="btn btn-primary btn-block" style="margin-top:14px;" type="submit">Record screening</button>
                </div>
            </form>
        @endif

        @if($canAssign)
            <section class="card">
                <div class="card-body">
                    <div class="row-between"><h2 class="card-title">Reviewers · {{ $stage === 'concept' ? 'concept note' : 'full proposal' }}</h2></div>
                    <ul style="list-style:none; margin:14px 0 0; padding:0; display:flex; flex-direction:column; gap:12px;">
                        @forelse($application->stageAssignments($stage) as $a)
                            <li class="row" style="gap:12px; flex-wrap:nowrap; {{ $a->isActive() ? '' : 'opacity:.75;' }}">
                                <span class="avatar" style="{{ $a->isActive() ? '' : 'background:var(--danger-bg); color:var(--danger-ink);' }}">{{ $a->label }}</span>
                                <span style="flex:1; min-width:0; line-height:1.35;">
                                    <strong style="{{ $a->removed_at ? 'text-decoration:line-through;' : '' }}">{{ $a->reviewer->name }}</strong><br>
                                    <span class="tiny {{ $a->coi_status === 'conflict' ? 'text-danger' : 'muted' }}">
                                        @if($a->removed_at) Removed
                                        @elseif($a->coi_status === 'pending') Declaration pending
                                        @elseif($a->coi_status === 'none') No conflict · {{ $a->coi_declared_at?->format('j M') }}
                                        @else Conflict declared @endif
                                    </span>
                                </span>
                                @if($a->removed_at === null)
                                    <form method="POST" action="{{ route('seed.submissions.unassign', [$application, $a]) }}" onsubmit="return confirm('Remove {{ addslashes($a->reviewer->name) }} from this review?')">
                                        @csrf @method('DELETE')
                                        <button class="icon-btn" type="submit" aria-label="Remove {{ $a->reviewer->name }}"><i class="fas fa-times" aria-hidden="true"></i></button>
                                    </form>
                                @endif
                            </li>
                        @empty
                            <li class="small muted">No reviewers yet.</li>
                        @endforelse
                    </ul>
                    <form method="POST" action="{{ route('seed.submissions.assign', $application) }}" class="row" style="margin-top:16px; flex-wrap:nowrap;">
                        @csrf
                        <label for="reviewer_id" class="sr-only">Add a reviewer</label>
                        <select id="reviewer_id" name="reviewer_id" class="select" required>
                            <option value="">Add a reviewer…</option>
                            @foreach($candidates as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}{{ $c->research_center ? ' · ' . $c->research_center : '' }}</option>
                            @endforeach
                        </select>
                        <button class="btn" type="submit">Add</button>
                    </form>
                    <p class="tiny muted" style="margin-top:10px;">Reviewers are anonymous to the applicant and declare conflicts before they can see the application. Applicants in this call, the DRCP and the DG are not listed.</p>
                </div>
            </section>
        @endif

        @if(in_array($s, [S::SCREENED, S::PROPOSAL_SUBMITTED], true))
            <form method="POST" action="{{ route('seed.submissions.start-review', $application) }}" class="card">
                @csrf
                <div class="card-body">
                    <h2 class="card-title">Send to reviewers</h2>
                    <p class="small muted" style="margin-top:4px;">Needs at least {{ \App\Services\SeedGrant\Workflow::MIN_REVIEWERS }} reviewers. They are told by email and in the portal.</p>
                    <x-ui.field label="Reviews due" name="due_at">
                        <input id="due_at" type="date" name="due_at" class="input" value="{{ now()->addDays(\App\Services\SeedGrant\Workflow::REVIEW_DAYS)->toDateString() }}" style="margin-top:8px;">
                    </x-ui.field>
                    <button class="btn btn-primary btn-block" style="margin-top:14px;" type="submit" @disabled($application->activeAssignments($stage)->count() < \App\Services\SeedGrant\Workflow::MIN_REVIEWERS)>Open round 1</button>
                </div>
            </form>
        @endif

        @if($open)
            <form method="POST" action="{{ route('seed.submissions.decide', $application) }}" class="card">
                @csrf
                <div class="card-body">
                    <h2 class="card-title">Your decision for round {{ $open->number }}</h2>
                    <p class="small muted" style="margin-top:4px;">
                        {{ $roundDone ? 'All reviews are in.' : $open->reviews->filter->isSubmitted()->count() . ' of ' . $application->activeAssignments()->count() . ' reviews in.' }}
                    </p>
                    @if($stage === 'proposal' && $means)
                        <div style="margin-top:12px; padding:12px 14px; border-radius:12px; background:{{ $application->meetsThreshold() ? 'var(--ok-bg)' : 'var(--danger-bg)' }};">
                            <div class="row-between"><span>Mean score</span><strong class="mono">{{ $means['total'] }} / 100</strong></div>
                            <div class="row-between small"><span>Scientific quality</span><span class="mono">{{ $means['science'] }} / 25</span></div>
                            <div class="small" style="margin-top:4px;">{{ $application->meetsThreshold() ? 'Meets the funding threshold.' : 'Below the funding threshold; it cannot be recommended.' }}</div>
                        </div>
                    @endif
                    <fieldset style="border:0; padding:0; margin:14px 0 0; display:flex; flex-direction:column; gap:8px;">
                        <legend class="sr-only">Decision</legend>
                        <label class="opt"><input type="radio" name="decision" value="revise" checked><span><strong>Ask the applicant to revise</strong><span class="opt-sub">Opens a new round with the same reviewers when they respond</span></span></label>
                        <label class="opt"><input type="radio" name="decision" value="advance"><span><strong>{{ $stage === 'concept' ? 'Shortlist' : 'Recommend for funding' }}</strong><span class="opt-sub">{{ $stage === 'concept' ? 'Invite a full proposal' : 'Goes to GMU, DRCP and DG approval' }}</span></span></label>
                        <label class="opt"><input type="radio" name="decision" value="decline"><span><strong>{{ $stage === 'concept' ? 'Do not shortlist' : 'Do not recommend' }}</strong></span></label>
                    </fieldset>
                    <x-ui.field label="Note to the applicant" name="decision_note">
                        <textarea name="decision_note" class="textarea" rows="4" style="margin-top:12px;" placeholder="Required when asking for a revision">{{ old('decision_note') }}</textarea>
                    </x-ui.field>
                    <x-ui.field label="Response due (if revising)" name="response_due_at">
                        <input type="date" name="response_due_at" class="input" value="{{ now()->addDays(\App\Services\SeedGrant\Workflow::RESPONSE_DAYS)->toDateString() }}" style="margin-top:8px;">
                    </x-ui.field>
                    <p class="tiny muted" style="margin-top:8px;">Reviewer names stay hidden from the applicant.</p>
                    <button class="btn btn-primary btn-block" style="margin-top:12px;" type="submit">Record decision</button>
                </div>
            </form>
        @endif

        @if(in_array($s, [S::CONCEPT_REVISION, S::PROPOSAL_REVISION], true))
            <div class="banner banner-warn" style="padding:16px 18px;"><div><strong>With the applicant</strong><p style="font-size:14px; margin-top:2px;">Response due {{ $application->latestRound()?->response_due_at?->format('j M Y') }}.</p></div></div>
        @endif

        @if($s === S::RECOMMENDED)
            <form method="POST" action="{{ route('seed.approvals.decide', $application) }}" class="card">
                @csrf
                <input type="hidden" name="level" value="gmu">
                <input type="hidden" name="decision" value="approved">
                <div class="card-body">
                    <h2 class="card-title">Send for approval</h2>
                    <p class="small muted" style="margin-top:4px;">Records GMU’s sign-off and sends it to the DRCP, then the DG. You can also send several from the ranking.</p>
                    <textarea name="note" class="textarea" rows="2" style="margin-top:12px;" aria-label="Note for the DRCP" placeholder="Optional note for the DRCP"></textarea>
                    <button class="btn btn-primary btn-block" style="margin-top:12px;" type="submit">Send to DRCP</button>
                </div>
            </form>
        @endif

        @if(in_array($s, [S::WITH_DRCP, S::WITH_DG], true))
            <div class="banner banner-info" style="padding:16px 18px;"><div><strong>{{ $s === S::WITH_DRCP ? 'With the DRCP' : 'With the Director General' }}</strong><p style="font-size:14px; margin-top:2px;">Waiting for their decision.</p></div></div>
        @endif

        @if($s === S::APPROVED)
            <form method="POST" action="{{ route('seed.submissions.issue-agreement', $application) }}" class="card">
                @csrf
                <div class="card-body">
                    <h2 class="card-title">Grant agreement</h2>
                    <p class="small muted" style="margin-top:4px;">Approved for {{ S::tzs($application->approved_amount) }}. Issuing it drafts the agreement from the approved proposal and asks the PI to accept online.</p>
                    <button class="btn btn-primary btn-block" style="margin-top:12px;" type="submit">Issue agreement</button>
                </div>
            </form>
        @endif

        @if(in_array($s, [S::AGREEMENT_ISSUED, S::AGREEMENT_ACCEPTED, S::AWARDED], true))
            <section class="card">
                <div class="card-body">
                    <h2 class="card-title">Grant agreement</h2>
                    <p class="small muted" style="margin-top:4px;">
                        @if($s === S::AGREEMENT_ISSUED) Waiting for the PI to accept it online.
                        @elseif($s === S::AGREEMENT_ACCEPTED) Accepted by the PI on {{ $application->agreement_accepted_at->format('j M Y') }}. Upload the fully signed copy.
                        @else Signed copy uploaded {{ $application->signed_agreement_uploaded_at->format('j M Y') }}. @endif
                    </p>
                    <a href="{{ route('seed.applications.agreement', $application) }}" class="btn btn-block" style="margin-top:12px;">Open the agreement</a>
                    @if(in_array($s, [S::AGREEMENT_ACCEPTED, S::AWARDED], true))
                        <form method="POST" action="{{ route('seed.submissions.signed-agreement', $application) }}" enctype="multipart/form-data" style="margin-top:14px;">
                            @csrf
                            <x-ui.field label="Signed copy (PDF)" name="file">
                                <input id="file" type="file" name="file" accept=".pdf" class="input" required>
                            </x-ui.field>
                            <button class="btn btn-primary btn-block" style="margin-top:10px;" type="submit">{{ $s === S::AWARDED ? 'Replace signed copy' : 'Upload signed copy' }}</button>
                        </form>
                    @endif
                </div>
            </section>
        @endif
    </aside>
</div>
@endsection
