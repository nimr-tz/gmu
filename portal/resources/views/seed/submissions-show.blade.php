@extends('layouts.app')

{{--
    One application as GMU sees it, from the owner's "GMU" design (ink band):
    status and figures → the eight steps → reviews, content or history in tabs →
    beside them, the one thing GMU does next (screen, assign, open a round, decide,
    sign off, issue the agreement, upload the signed copy) or who it is waiting on.
--}}

@php
    use App\Support\SeedGrant as S;
    use App\Models\Seed\SeedReview;
    use App\Services\SeedGrant\Workflow as W;
    $form   = $application->call->form();
    $s      = $application->status;
    $pi     = $application->pi;
    $open   = $application->openRound();
    $means  = $application->meanScores();
    $maxTotal = $form->maxTotal() ?: 100;
    $stageShort = $stage === 'concept' ? 'concept note' : 'full proposal';
    $active = $application->activeAssignments($stage)->count();
    $canAssign = in_array($s, [S::SCREENED, S::CONCEPT_REVIEW, S::CONCEPT_REVISION, S::PROPOSAL_SUBMITTED, S::PROPOSAL_REVIEW, S::PROPOSAL_REVISION], true);
    $next = $form->nextApprovalLevel('gmu');
    $later = collect($form->approvalLevels())->reject(fn ($l) => $l === 'gmu')->map(fn ($l) => ['drcp' => 'the DRCP', 'dg' => 'the Director General'][$l]);
    $sendLabel = ['drcp' => 'Send to the DRCP', 'dg' => 'Send to the Director General'][$next] ?? 'Approve';
    $gmuStep = \App\Support\GmuNext::step($application, app(W::class));
    $roundNo = ($application->latestRound($stage)?->number ?? 0) + 1;
    $tabs = array_filter([
        'reviews'  => 'Reviews',
        'concept'  => 'Concept note',
        'proposal' => $application->proposal ? 'Full proposal' : null,
        'history'  => 'History',
    ]);

    // Status chip on the band: white, in the status's own ink.
    $tone = ['award' => 'award', 'ok' => 'ok', 'warn' => 'warn', 'danger' => 'danger', 'navy' => 'review', 'info' => 'neutral'][$application->statusTone()] ?? 'neutral';
    $chip = ['ok' => ['text-ok-ink', 'bg-ok'], 'warn' => ['text-warn-ink', 'bg-warn'], 'danger' => ['text-danger-ink', 'bg-danger'], 'review' => ['text-review-ink', 'bg-review'], 'award' => ['text-award-ink', 'bg-award'], 'neutral' => ['text-gray-700', 'bg-gray-400']][$tone];

    // What happens next, in GMU's words.
    $due = fn ($d) => $d?->format('j M Y') ?? '—';
    $nextStep = match (true) {
        $s === S::SUBMITTED => ['Screen it', 'Check eligibility and completeness'],
        in_array($s, [S::SCREENED, S::PROPOSAL_SUBMITTED], true) => $active < W::MIN_REVIEWERS ? ['Assign reviewers', 'Then open round ' . $roundNo] : ['Send to reviewers', 'Open round ' . $roundNo],
        in_array($s, [S::CONCEPT_REVIEW, S::PROPOSAL_REVIEW], true) => $roundDone ? ['Decide round ' . $open?->number, 'All reviews are in'] : ['With reviewers', 'Reviews due ' . $due($open?->due_at)],
        in_array($s, [S::CONCEPT_REVISION, S::PROPOSAL_REVISION], true) => ['With the applicant', 'Response due ' . $due($application->latestRound()?->response_due_at)],
        $s === S::SHORTLISTED => ['With the applicant', 'Full proposal due ' . $due($application->call->proposal_deadline)],
        $s === S::DRAFT => ['With the applicant', 'Returned for correction'],
        $s === S::RECOMMENDED => ['Sign off', $next ? 'Send it to ' . $later->first() : 'Approve it'],
        $s === S::WITH_DRCP => ['With the DRCP', 'Waiting for their decision'],
        $s === S::WITH_DG => ['With the DG', 'Waiting for the final decision'],
        $s === S::APPROVED => ['Issue agreement', 'Draft it from the approved proposal'],
        $s === S::AGREEMENT_ISSUED => ['With the PI', 'Waiting for them to accept online'],
        $s === S::AGREEMENT_ACCEPTED => ['Upload signed copy', 'The PI accepted on ' . $due($application->agreement_accepted_at)],
        $s === S::AWARDED => ['Awarded', 'Signed copy on file'],
        default => ['Closed', $application->statusLabel()],
    };
    $nextTile = $gmuStep
        ? ($gmuStep[1] === 'warn' ? 'border-violet-700 bg-violet-700 text-white' : 'border-warn bg-warn text-amber-950')
        : ($s === S::AWARDED ? 'border-award-line bg-award-soft text-award-ink' : 'border-border bg-card');
    $nextNoteInk = $gmuStep ? ($gmuStep[1] === 'warn' ? 'text-violet-200' : 'text-amber-900') : ($s === S::AWARDED ? 'text-award-ink' : 'text-muted-foreground');

    $submittedAt = $application->proposal_submitted_at ?? $application->concept_submitted_at;
    $meets = $means ? $application->meetsThreshold() : null;

    // The eight steps, drawn compactly: done in ink, now in amber, stopped in rose, Awarded in green.
    $current = $application->trackIndex();
    $stopped = in_array($s, S::CLOSED_STATUSES, true);
    $awarded = $s === S::AWARDED;

    $avatar = ['A' => 'bg-violet-50 text-violet-700', 'B' => 'bg-sky-100 text-sky-700', 'C' => 'bg-pink-100 text-pink-700', 'D' => 'bg-warn-soft text-warn-ink'];
    $recBadge = ['accept' => 'bg-blue-50 text-ok-ink', 'revise' => 'bg-warn-soft text-warn-ink', 'reject' => 'bg-danger-soft text-danger-ink'];
    $action = 'overflow-hidden rounded-2xl border border-warn-line bg-card shadow-lift';
    $actionHead = 'border-b border-warn-line bg-warn-soft px-[18px] py-3.5';
    $ink = 'h-11 w-full rounded-[9px] bg-foreground text-sm font-medium text-white hover:bg-gray-800 disabled:cursor-not-allowed disabled:bg-gray-200 disabled:text-gray-500';
    $event = fn ($title, $when, $sub = null) => compact('title', 'when', 'sub');
@endphp

@section('title', $application->reference)
@section('context', 'Grants · Submissions · ' . $application->reference)
@section('topbar-colour', 'bg-foreground')
@section('own-flash', '1')

@section('content')
<div class="-mx-4 -mt-5 flex flex-col gap-2.5 bg-foreground px-7 pb-[100px] pt-[18px] text-white lg:-mx-6 lg:-mt-6">
    <a href="{{ route('seed.submissions.index', ['call' => $application->seed_call_id]) }}" class="inline-flex items-center gap-1.5 self-start text-[13px] font-medium text-gray-300 hover:text-white"><x-icon name="arrow-left" class="size-3.5" />Submissions</a>
    <div class="flex flex-wrap items-center gap-2">
        <span class="inline-flex h-[22px] items-center gap-1.5 whitespace-nowrap rounded-md bg-white px-2 text-xs font-semibold {{ $chip[0] }}"><span class="size-1.5 rounded-full {{ $chip[1] }}"></span>{{ $application->statusLabel() }}</span>
        <span class="inline-flex h-[22px] items-center whitespace-nowrap rounded-md bg-white/10 px-2 text-xs font-medium">{{ $stage === 'concept' ? 'Concept note' : 'Full proposal' }}</span>
        @include('seed.partials.call-tag', ['call' => $application->call, 'short' => true])
        <span class="whitespace-nowrap font-mono text-[12.5px] text-gray-300">{{ $application->reference }}</span>
    </div>
    <h1 class="max-w-[920px] text-balance text-[28px] font-semibold leading-tight tracking-[-0.025em] text-white">{{ $application->title ?: 'Untitled' }}</h1>
    <p class="text-[15px] text-gray-300">{{ $pi->name }}{{ $pi->designation ? ' · ' . $pi->designation : '' }}{{ $pi->research_center ? ' · ' . $pi->research_center : '' }}{{ $application->mentor_name ? ' · mentor ' . $application->mentor_name : '' }}</p>
</div>

<div class="-mt-[72px] flex flex-col gap-5">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="flex flex-col gap-2 rounded-[14px] border p-5 shadow-lift {{ $nextTile }}">
            <span class="text-[13.5px] font-semibold">{{ $gmuStep ? 'Waiting for GMU' : 'Next step' }}</span>
            <span class="text-2xl font-semibold leading-tight tracking-[-0.02em]">{{ $nextStep[0] }}</span>
            <span class="text-[13px] {{ $nextNoteInk }}">{{ $nextStep[1] }}</span>
        </div>
        <div class="flex flex-col gap-2 rounded-[14px] border border-border bg-card p-5 shadow-lift">
            <span class="text-[13.5px] font-medium">Requested</span>
            <span class="font-mono text-2xl font-semibold leading-tight tracking-[-0.02em] text-ok-ink">TZS {{ number_format($application->requestedAmount() / 1e6, 1) }}M</span>
            <span class="text-[13px] text-muted-foreground">TZS {{ number_format($application->requestedAmount()) }} · {{ $stage === 'proposal' ? 'detailed budget' : 'concept-note budget' }}</span>
        </div>
        <div class="flex flex-col gap-2 rounded-[14px] border border-border bg-card p-5 shadow-lift">
            <span class="text-[13.5px] font-medium">Submitted</span>
            <span class="text-2xl font-semibold leading-tight tracking-[-0.02em]">{{ $submittedAt?->format('j M Y') ?? 'Not yet' }}</span>
            <span class="text-[13px] text-muted-foreground">{{ $application->proposal_submitted_at ? 'Full proposal · concept note ' . $application->concept_submitted_at?->format('j M Y') : 'Concept note' }}</span>
        </div>
        <div class="flex flex-col gap-2 rounded-[14px] border border-border bg-card p-5 shadow-lift">
            <span class="text-[13.5px] font-medium">{{ $means ? 'Mean score' : 'Reviews' }}</span>
            @if($means)
                <span class="text-2xl font-semibold leading-tight tracking-[-0.02em] tabular-nums">{{ $means['total'] }} / {{ S::trimNumber($maxTotal) }}</span>
                <div class="relative h-1.5 rounded-full bg-gray-100" role="img" aria-label="Mean {{ $means['total'] }}; pass mark {{ S::trimNumber($form->passMark()) }}">
                    <div class="absolute inset-y-0 left-0 rounded-full {{ $meets ? 'bg-primary' : 'bg-gray-400' }}" style="width: {{ min(100, $means['total'] / $maxTotal * 100) }}%"></div>
                    <span class="absolute -bottom-1 -top-1 w-0.5 rounded-sm bg-foreground" style="left: {{ $form->passMark() / $maxTotal * 100 }}%"></span>
                </div>
                <span class="text-[13px] text-muted-foreground">{{ $meets ? 'Above the funding line of ' . S::trimNumber($form->passMark()) : ($means['total'] >= $form->passMark() ? 'Below a criterion minimum' : 'Below the funding line of ' . S::trimNumber($form->passMark())) }}</span>
            @else
                <span class="text-2xl font-semibold leading-tight tracking-[-0.02em] tabular-nums">{{ $open ? $open->reviews->filter->isSubmitted()->count() . ' of ' . $application->activeAssignments()->count() . ' in' : 'None yet' }}</span>
                <span class="text-[13px] text-muted-foreground">{{ $stage === 'concept' ? 'Concept notes are not scored' : 'Scores show once reviews are in' }}</span>
            @endif
        </div>
    </div>

    <ol aria-label="Progress" class="grid grid-cols-4 gap-1.5 rounded-[14px] border border-border px-[18px] py-4 sm:grid-cols-8">
        @foreach(S::TRACK as $i => [$label])
            @php
                $done = $i < $current || $awarded;
                $now = $i === $current && ! $awarded;
                $colour = $now && $stopped ? 'bg-danger' : ($done ? ($awarded ? 'bg-award' : 'bg-foreground') : ($now ? 'bg-warn' : 'bg-gray-200'));
            @endphp
            <li class="flex flex-col gap-2" @if($now) aria-current="step" @endif>
                <span class="h-1 rounded-full {{ $colour }}"></span>
                <span class="flex items-center gap-1.5 text-[12.5px] {{ $now ? 'font-semibold' : 'font-medium' }} {{ $done || $now ? 'text-foreground' : 'text-subtle' }}">
                    <span class="grid size-4 shrink-0 place-items-center rounded-full text-white {{ $colour }}">@if($done)<x-icon name="check" class="size-2.5" />@endif</span>{{ $label }}
                </span>
            </li>
        @endforeach
    </ol>

    @if(session('success'))
        <div role="status" class="flex items-center gap-3 rounded-xl border border-ok-line bg-ok-soft px-4 py-3.5 text-ok-ink">
            <span class="grid size-[22px] shrink-0 place-items-center rounded-full bg-primary text-white"><x-icon name="check" class="size-3" /></span>
            <span class="flex-1 text-[13.5px] font-medium">{{ session('success') }}</span>
            <a href="{{ route('seed.submissions.index', ['call' => $application->seed_call_id]) }}" class="text-[13px] font-semibold hover:underline">Back to submissions</a>
        </div>
    @endif

    <div class="flex flex-wrap items-start gap-6">
        <div class="flex min-w-0 flex-[1_1_600px] flex-col gap-[18px]">
            <nav class="flex gap-6 overflow-x-auto border-b border-border" aria-label="Sections">
                @foreach($tabs as $key => $label)
                    <a href="?tab={{ $key }}" @if($tab === $key) aria-current="page" @endif
                       class="-mb-px whitespace-nowrap border-b-2 px-0.5 pb-3 text-sm font-medium {{ $tab === $key ? 'border-foreground text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground' }}">{{ $label }}</a>
                @endforeach
            </nav>

            @if($tab === 'concept' || $tab === 'proposal')
                <section class="overflow-hidden rounded-[14px] border border-border">
                    <header class="border-b border-border bg-muted px-[18px] py-3"><h2 class="text-sm font-semibold">{{ $tab === 'proposal' ? 'Full proposal' : 'Concept note' }}</h2><p class="text-[13px] text-muted-foreground">Read only · as the applicant submitted it</p></header>
                    <div class="bg-card p-5">@include('seed.partials.content', ['stage' => $tab])</div>
                </section>

            @elseif($tab === 'history')
                @php
                    $events = collect();
                    if ($application->concept_submitted_at) $events->push($event('Concept note submitted', $application->concept_submitted_at));
                    if ($application->screened_at) $events->push($event('Screened: ' . ($s === S::INELIGIBLE ? 'not eligible' : ($s === S::DRAFT || $s === S::SUBMITTED ? 'returned for correction' : 'eligible')), $application->screened_at, trim(($application->screener?->name ?? '') . ($application->screening_note ? ' — ' . $application->screening_note : ''))));
                    foreach ($application->rounds as $r) {
                        $events->push($event(($r->stage === 'proposal' ? 'Full proposal' : 'Concept note') . ' round ' . $r->number . ' opened', $r->created_at, 'Reviews due ' . $r->due_at?->format('j M Y')));
                        if ($r->decided_at) $events->push($event('Round ' . $r->number . ': ' . \App\Models\Seed\SeedReviewRound::DECISIONS[$r->decision], $r->decided_at, trim(($r->decider?->name ?? '') . ($r->decision_note ? ' — ' . $r->decision_note : ''))));
                        if ($r->responded_at) $events->push($event('Applicant responded', $r->responded_at, \Illuminate\Support\Str::limit($r->response, 160)));
                    }
                    if ($application->proposal_submitted_at) $events->push($event('Full proposal submitted', $application->proposal_submitted_at));
                    foreach ($application->approvals->sortBy('id') as $ap) {
                        $events->push($event(\App\Models\Seed\SeedApproval::LEVELS[$ap->level] . ': ' . \App\Models\Seed\SeedApproval::DECISIONS[$ap->decision], $ap->created_at, trim(($ap->decider?->name ?? '') . ($ap->note ? ' — ' . $ap->note : ''))));
                    }
                    foreach ([['agreement_issued_at', 'Agreement issued'], ['agreement_accepted_at', 'Agreement accepted by the PI'], ['signed_agreement_uploaded_at', 'Signed agreement uploaded']] as [$f, $label]) {
                        if ($application->$f) $events->push($event($label, $application->$f));
                    }
                    $events = $events->sortBy(fn ($e) => $e['when'])->values();
                @endphp
                <section class="overflow-hidden rounded-[14px] border border-border">
                    <header class="border-b border-border bg-muted px-[18px] py-3"><h2 class="text-sm font-semibold">History</h2><p class="text-[13px] text-muted-foreground">Every step, in the order it happened</p></header>
                    <ol class="bg-card px-5 py-[18px]">
                        @forelse($events as $e)
                            @php $last = $loop->last; @endphp
                            <li class="flex gap-3.5">
                                <span class="flex flex-col items-center"><span class="mt-1 size-3 rounded-full {{ $last ? 'bg-foreground ring-[3px] ring-gray-200' : 'bg-gray-300 ring-[3px] ring-white' }}"></span><span class="my-1.5 w-px flex-1 {{ $last ? 'bg-transparent' : 'bg-border' }}"></span></span>
                                <div class="min-w-0 flex-1 pb-[18px]">
                                    <div class="flex flex-wrap justify-between gap-x-4 gap-y-1"><p class="text-sm font-medium">{{ $e['title'] }}</p><time class="whitespace-nowrap text-[12.5px] text-muted-foreground">{{ $e['when']->format('j M Y H:i') }}</time></div>
                                    @if($e['sub'])<p class="mt-0.5 text-[13px] text-muted-foreground">{{ $e['sub'] }}</p>@endif
                                </div>
                            </li>
                        @empty
                            <li class="text-sm text-muted-foreground">Nothing has happened yet.</li>
                        @endforelse
                    </ol>
                </section>

            @else
                @forelse($application->rounds->sortByDesc(fn ($r) => [$r->stage === 'proposal', $r->number]) as $round)
                    @php
                        $roundAssignments = $application->stageAssignments($round->stage)->filter(fn ($a) => $a->removed_at === null || $round->reviews->contains('seed_reviewer_assignment_id', $a->id));
                        $inCount = $round->reviews->filter->isSubmitted()->count();
                    @endphp
                    <section class="flex flex-col gap-3">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <h2 class="text-[15px] font-semibold">{{ $round->stage === 'proposal' ? 'Full proposal' : 'Concept note' }} · round {{ $round->number }}</h2>
                            <span class="text-[13px] {{ $round->isOpen() ? 'text-review-ink' : 'text-muted-foreground' }}">
                                @if($round->isOpen()) Reviews due {{ $round->due_at?->format('j M Y') }} · {{ $inCount }} of {{ $roundAssignments->count() }} in
                                @else Decided {{ $round->decided_at?->format('j M Y') }}: {{ \App\Models\Seed\SeedReviewRound::DECISIONS[$round->decision] }} @endif
                            </span>
                        </div>
                        <div class="grid gap-3.5 md:grid-cols-2">
                            @foreach($roundAssignments as $a)
                                @php $review = $round->reviews->firstWhere('seed_reviewer_assignment_id', $a->id); @endphp
                                <article class="flex flex-col gap-3 rounded-[14px] border border-border bg-card p-[18px]">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-center gap-2.5">
                                            <span class="grid size-8 place-items-center rounded-full font-mono text-[13px] font-semibold {{ $avatar[$a->label] ?? 'bg-gray-100 text-gray-700' }}">{{ $a->label }}</span>
                                            <span class="leading-tight"><span class="block text-sm font-semibold">Reviewer {{ $a->label }}</span><span class="block text-xs text-muted-foreground">{{ $a->reviewer->name }} · seen by GMU only</span></span>
                                        </div>
                                        @if($review?->isSubmitted())
                                            <span class="inline-flex h-[22px] items-center whitespace-nowrap rounded-md px-2 text-xs font-medium {{ $recBadge[$review->recommendation] ?? 'bg-gray-100 text-gray-700' }}">{{ SeedReview::RECOMMENDATIONS[$review->recommendation] }}</span>
                                        @elseif($a->coi_status === 'conflict')
                                            <span class="inline-flex h-[22px] items-center whitespace-nowrap rounded-md bg-danger-soft px-2 text-xs font-medium text-danger-ink">Conflict declared</span>
                                        @elseif($a->coi_status === 'pending')
                                            <span class="inline-flex h-[22px] items-center whitespace-nowrap rounded-md bg-warn-soft px-2 text-xs font-medium text-warn-ink">Declaration pending</span>
                                        @else
                                            <span class="inline-flex h-[22px] items-center whitespace-nowrap rounded-md px-2 text-xs font-medium {{ $round->isOverdue() ? 'bg-danger-soft text-danger-ink' : 'bg-gray-100 text-gray-700' }}">{{ $round->isOverdue() ? 'Overdue' : 'Not submitted' }}</span>
                                        @endif
                                    </div>
                                    @if($review?->isSubmitted())
                                        @if($round->stage === 'proposal' && $review->scores)
                                            <div>
                                                <p class="font-mono text-[22px] tabular-nums">{{ $review->total }}<span class="text-[13px] text-muted-foreground"> / {{ S::trimNumber($maxTotal) }}</span></p>
                                                <p class="mt-0.5 text-xs leading-normal text-muted-foreground">@foreach($form->criteria() as $key => [$name, , $max]){{ \Illuminate\Support\Str::before($name, ' and') }} {{ $review->score($key) }}/{{ S::trimNumber($max) }}@if(! $loop->last) · @endif @endforeach</p>
                                            </div>
                                        @endif
                                        <p class="text-pretty text-[13.5px] leading-relaxed text-gray-800">{{ $review->comments }}</p>
                                        @if($review->confidential_note)
                                            <div class="flex gap-2.5 rounded-[10px] bg-gray-100 px-3 py-2.5 text-[13px] leading-normal text-gray-700"><x-icon name="lock" class="mt-0.5 size-3.5 shrink-0" /><span><b class="font-semibold text-foreground">Confidential to GMU.</b> {{ $review->confidential_note }}</span></div>
                                        @endif
                                    @elseif($a->coi_status === 'conflict')
                                        @if($a->coi_note)<p class="text-[13px] text-muted-foreground">{{ $a->coi_note }}</p>@endif
                                    @elseif($a->coi_status === 'none' && $round->isOpen())
                                        <p class="text-[13px] text-muted-foreground">Declared no conflict. Review due {{ $round->due_at?->format('j M Y') }}.</p>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                        @if($round->response)
                            <div class="overflow-hidden rounded-[14px] border border-border"><header class="border-b border-border bg-muted px-4 py-2.5 text-[13.5px] font-semibold">Applicant’s response <span class="font-normal text-muted-foreground">· {{ $round->responded_at?->format('j M Y') }}</span></header><p class="whitespace-pre-line bg-card px-4 py-3.5 text-[13.5px] leading-relaxed">{{ $round->response }}</p></div>
                        @endif
                    </section>
                @empty
                    <div class="rounded-[14px] border border-dashed border-gray-300 px-5 py-10 text-center">
                        <p class="text-[14.5px] font-semibold">No review rounds yet</p>
                        <p class="mt-1 text-[13px] text-muted-foreground">{{ $s === S::SUBMITTED ? 'Screen the concept note first.' : ($s === S::INELIGIBLE ? 'Closed at screening.' : 'Assign reviewers and send it out.') }}</p>
                    </div>
                @endforelse
            @endif
        </div>

        {{-- What GMU does next depends on where the application is. --}}
        <aside class="flex min-w-[300px] flex-[0_1_380px] flex-col gap-4 xl:sticky xl:top-20">
            @if($s === S::SUBMITTED)
                @php
                    $service = $pi->serviceLength();
                    $autoChecks = array_filter([
                        'staff'    => ['NIMR staff, permanent or contract', (bool) $pi->employment_type, $pi->employment_type ? \App\Models\User::EMPLOYMENT_TYPES[$pi->employment_type] : 'not recorded'],
                        'service'  => ($years = $form->serviceYears())
                            ? ["Under {$years} years at NIMR", (bool) $pi->withinServiceLimit($years), $service ? "{$service['years']} y {$service['months']} m" : 'not recorded']
                            : null,
                        'prior'    => ($priorRule = $form->priorGrant())
                            ? ['Grants over USD ' . number_format($priorRule['usd']) . ' as PI disclosed', $application->priorLargeGrant() !== null,
                               ['no' => 'none', 'yes' => 'yes, see section A'][$application->priorLargeGrant()] ?? 'not answered']
                            : null,
                        'complete' => ['All sections complete, within limits', empty($complete), $application->pageEstimate('concept') . ' pp'],
                        'budget'   => ['Budget within ' . number_format($application->call->min_amount / 1e6) . 'M–' . number_format($application->call->max_amount / 1e6) . 'M', ! isset($complete['budget']), number_format($application->budgetTotal('concept') / 1e6, 2) . 'M'],
                        'mentor'   => $form->mentorWords() !== null
                            ? ['Mentor named, with relevant expertise', filled($application->mentor_name) && filled($application->mentor_suitability), $application->mentor_name ?: '—']
                            : null,
                    ]);
                    $defaultResult = ! empty($eligibility) ? 'ineligible' : (! empty($complete) ? 'return' : 'eligible');
                @endphp
                <form method="POST" action="{{ route('seed.submissions.screen', $application) }}" class="{{ $action }}"
                      x-data="{ result: @js(old('result', $defaultResult)), note: @js(old('note', '')), tried: false }"
                      @submit="tried = true; if (result !== 'eligible' && ! note.trim()) $event.preventDefault()">
                    @csrf
                    <header class="{{ $actionHead }}"><h2 class="text-[15px] font-semibold text-amber-900">Screening</h2><p class="text-[13px] text-amber-800">Eligibility, completeness and compliance with the call</p></header>
                    <div class="flex flex-col gap-3.5 px-[18px] pb-[18px] pt-2">
                        <ul>
                            @foreach($autoChecks as $key => [$label, $ok, $detail])
                                <li class="border-b border-gray-100">
                                    <label class="flex cursor-pointer items-center justify-between gap-3 py-2.5 text-[13.5px]">
                                        <span class="flex items-center gap-2.5">
                                            <input type="hidden" name="checks[{{ $key }}]" value="0">
                                            <input type="checkbox" class="checkbox text-foreground" name="checks[{{ $key }}]" value="1" @checked($ok)>{{ $label }}
                                        </span>
                                        <span class="shrink-0 text-xs {{ $ok ? 'text-muted-foreground' : 'font-semibold text-danger-ink' }}">{{ $detail }}</span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                        <fieldset class="flex flex-col gap-2">
                            <legend class="mb-2 text-[13px] font-medium">Result</legend>
                            @foreach(['eligible' => ['Eligible', 'Goes on to reviewers', false], 'return' => ['Return for correction', 'Back to the applicant to fix and resubmit', false], 'ineligible' => ['Not eligible', 'Closed; the applicant is told why', true]] as $value => [$label, $sub, $danger])
                                <label class="flex cursor-pointer items-start gap-3 rounded-[10px] border-[1.5px] px-[13px] py-[11px]"
                                       :class="result === '{{ $value }}' ? '{{ $danger ? 'border-danger bg-danger-soft/40' : 'border-foreground bg-gray-50' }}' : 'border-input bg-card'">
                                    <input type="radio" name="result" value="{{ $value }}" x-model="result" class="radio mt-0.5 {{ $danger ? 'text-danger-ink' : 'text-foreground' }}">
                                    <span class="leading-snug"><span class="block text-sm font-semibold {{ $danger ? 'text-danger-ink' : '' }}">{{ $label }}</span><span class="block text-[12.5px] text-muted-foreground">{{ $sub }}</span></span>
                                </label>
                            @endforeach
                        </fieldset>
                        <label class="flex flex-col gap-1.5">
                            <span class="text-[13px] font-medium">Note to the applicant <span class="font-normal text-muted-foreground" x-text="result === 'eligible' ? '(optional)' : '(required)'"></span></span>
                            <textarea name="note" class="textarea" rows="3" x-model="note" :class="tried && result !== 'eligible' && ! note.trim() && 'border-danger'"></textarea>
                            <span class="text-[12.5px] text-danger-ink" x-show="tried && result !== 'eligible' && ! note.trim()" x-cloak>Write a note to the applicant.</span>
                        </label>
                        <button type="submit" class="{{ $ink }}">Record screening</button>
                    </div>
                </form>
            @endif

            @if($canAssign)
                <section class="overflow-hidden rounded-[14px] border border-border bg-card">
                    <header class="flex items-center justify-between gap-2 border-b border-border bg-muted px-4 py-3"><h3 class="text-sm font-semibold">Reviewers · {{ $stageShort }}</h3><span class="text-[12.5px] {{ $active < W::MIN_REVIEWERS ? 'text-warn-ink' : 'text-muted-foreground' }}">{{ $active }} of {{ W::MIN_REVIEWERS }} needed</span></header>
                    <ul class="flex flex-col px-4 py-2">
                        @forelse($application->stageAssignments($stage) as $a)
                            <li class="flex items-center gap-3 border-b border-gray-100 py-[9px] last:border-0">
                                <span class="grid size-[30px] shrink-0 place-items-center rounded-full font-mono text-[12.5px] font-semibold {{ $a->isActive() ? ($avatar[$a->label] ?? 'bg-gray-100 text-gray-700') : 'bg-danger-soft text-danger-ink' }}">{{ $a->label }}</span>
                                <span class="min-w-0 flex-1 leading-tight">
                                    <span class="block truncate text-[13.5px] font-medium {{ $a->removed_at ? 'line-through opacity-70' : '' }}">{{ $a->reviewer->name }}</span>
                                    <span class="block text-xs {{ $a->removed_at || $a->coi_status === 'conflict' ? 'text-danger-ink' : ($a->coi_status === 'pending' ? 'text-amber-800' : 'text-muted-foreground') }}">
                                        @if($a->removed_at) Removed
                                        @elseif($a->coi_status === 'pending') Declaration pending
                                        @elseif($a->coi_status === 'none') No conflict · {{ $a->coi_declared_at?->format('j M Y') }}
                                        @else Conflict declared @endif
                                    </span>
                                </span>
                                @if($a->removed_at === null)
                                    <form method="POST" action="{{ route('seed.submissions.unassign', [$application, $a]) }}" onsubmit="return confirm('Remove {{ addslashes($a->reviewer->name) }} from this review?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" aria-label="Remove {{ $a->reviewer->name }}" class="grid size-7 place-items-center rounded-md text-subtle hover:bg-gray-100 hover:text-foreground"><x-icon name="x" class="size-3.5" /></button>
                                    </form>
                                @endif
                            </li>
                        @empty
                            <li class="py-2 text-[13px] text-muted-foreground">No reviewers yet.</li>
                        @endforelse
                    </ul>
                    <form method="POST" action="{{ route('seed.submissions.assign', $application) }}" class="flex gap-2 px-4 pb-3 pt-1">
                        @csrf
                        <label for="reviewer_id" class="sr-only">Add a reviewer</label>
                        <select id="reviewer_id" name="reviewer_id" class="select h-[34px] min-w-0 flex-1 text-[13px]" required>
                            <option value="">Add a reviewer…</option>
                            @foreach($candidates as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}{{ $c->research_center ? ' · ' . $c->research_center : '' }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="inline-flex h-[34px] items-center gap-1.5 rounded-lg border border-input bg-card px-3 text-[13px] font-medium hover:bg-gray-100"><x-icon name="plus" class="size-3.5" />Add</button>
                    </form>
                    <p class="px-4 pb-3.5 text-xs leading-normal text-muted-foreground">Reviewers are anonymous to the applicant and declare conflicts before they can see the application. Applicants in this call, the DRCP and the DG are not listed.</p>
                </section>
            @endif

            @if(in_array($s, [S::SCREENED, S::PROPOSAL_SUBMITTED], true))
                @php $short = $active < W::MIN_REVIEWERS; @endphp
                <form method="POST" action="{{ route('seed.submissions.start-review', $application) }}" class="{{ $action }}">
                    @csrf
                    <header class="{{ $actionHead }}"><h2 class="text-[15px] font-semibold text-amber-900">Send to reviewers</h2><p class="text-[13px] text-amber-800">Needs at least {{ W::MIN_REVIEWERS }} reviewers. They are told by email and in the portal.</p></header>
                    <div class="flex flex-col gap-3 px-[18px] py-4">
                        <label class="flex flex-col gap-1.5"><span class="text-[13px] font-medium">Reviews due</span><input type="date" name="due_at" class="input" value="{{ old('due_at', now()->addDays(W::REVIEW_DAYS)->toDateString()) }}"></label>
                        <button type="submit" class="{{ $ink }}" @disabled($short)>Open round {{ $roundNo }}</button>
                        @if($short)<p class="text-center text-[12.5px] text-muted-foreground">Add {{ W::MIN_REVIEWERS - $active }} more {{ str('reviewer')->plural(W::MIN_REVIEWERS - $active) }} first.</p>@endif
                    </div>
                </form>
            @endif

            @if($open)
                @php
                    $chainText = collect($form->approvalLevels())->map(fn ($l) => ['gmu' => 'GMU', 'drcp' => 'the DRCP', 'dg' => 'the Director General'][$l])->implode(', then ');
                    $afterText = [
                        'revise'  => 'The applicant gets your note and the reviewers’ comments by letter only, and responds by the date you set.',
                        'advance' => $stage === 'concept'
                            ? 'The applicant is invited to write the full proposal' . ($application->call->proposal_deadline ? ', due ' . $application->call->proposal_deadline->format('j M Y') : '') . '.'
                            : 'It goes to approval: ' . $chainText . '.',
                        'decline' => 'The application closes. The applicant is told, with the reviewers’ comments by letter only.',
                    ];
                @endphp
                <form method="POST" action="{{ route('seed.submissions.decide', $application) }}" class="{{ $action }}"
                      x-data="{ decision: @js(old('decision', 'revise')), note: @js(old('decision_note', '')), tried: false, after: @js($afterText) }"
                      @submit="tried = true; if (decision === 'revise' && ! note.trim()) $event.preventDefault()">
                    @csrf
                    <header class="{{ $actionHead }}"><h2 class="text-[15px] font-semibold text-amber-900">Your decision for round {{ $open->number }}</h2><p class="text-[13px] text-amber-800">{{ $roundDone ? 'All reviews are in.' : $open->reviews->filter->isSubmitted()->count() . ' of ' . $application->activeAssignments()->count() . ' reviews in.' }}</p></header>
                    <div class="flex flex-col gap-3.5 px-[18px] py-4">
                        @if($stage === 'proposal' && $means)
                            <div class="flex flex-col gap-1 rounded-[10px] px-3.5 py-3 text-[13px] {{ $meets ? 'bg-blue-50' : 'bg-danger-soft text-danger-ink' }}">
                                <div class="flex justify-between"><span>Mean score</span><b class="font-mono font-semibold">{{ $means['total'] }} / {{ S::trimNumber($maxTotal) }}</b></div>
                                @foreach($form->minimums() as $key => $min)
                                    <div class="flex justify-between {{ $meets ? 'text-gray-700' : '' }}"><span>{{ $form->criteria()[$key][0] }} (at least {{ S::trimNumber($min) }})</span><span class="font-mono">{{ $means[$key] ?? '—' }} / {{ S::trimNumber($form->criteria()[$key][2]) }}</span></div>
                                @endforeach
                                <p class="pt-1 font-semibold {{ $meets ? 'text-ok-ink' : '' }}">{{ $meets ? 'Meets the funding threshold.' : 'Below the funding threshold; it cannot be recommended.' }}</p>
                            </div>
                        @endif
                        <fieldset class="flex flex-col gap-2">
                            <legend class="sr-only">Decision</legend>
                            @foreach([
                                'revise'  => ['Ask the applicant to revise', 'Opens a new round with the same reviewers when they respond', false],
                                'advance' => [$stage === 'concept' ? 'Shortlist' : 'Recommend for funding', $stage === 'concept' ? 'Invite a full proposal' : 'Goes to approval', false],
                                'decline' => [$stage === 'concept' ? 'Do not shortlist' : 'Do not recommend', '', true],
                            ] as $value => [$label, $sub, $danger])
                                <label class="flex cursor-pointer items-start gap-3 rounded-[10px] border-[1.5px] px-[13px] py-[11px]"
                                       :class="decision === '{{ $value }}' ? '{{ $danger ? 'border-danger bg-danger-soft/40' : 'border-foreground bg-gray-50' }}' : 'border-input bg-card'">
                                    <input type="radio" name="decision" value="{{ $value }}" x-model="decision" class="radio mt-0.5 {{ $danger ? 'text-danger-ink' : 'text-foreground' }}">
                                    <span class="leading-snug"><span class="block text-sm font-semibold {{ $danger ? 'text-danger-ink' : '' }}">{{ $label }}</span>@if($sub)<span class="block text-[12.5px] text-muted-foreground">{{ $sub }}</span>@endif</span>
                                </label>
                            @endforeach
                        </fieldset>
                        <label class="flex flex-col gap-1.5">
                            <span class="text-[13px] font-medium">Note to the applicant <span class="font-normal text-muted-foreground" x-text="decision === 'revise' ? '(required)' : '(optional)'"></span></span>
                            <textarea name="decision_note" class="textarea" rows="4" x-model="note" :class="tried && decision === 'revise' && ! note.trim() && 'border-danger'"></textarea>
                            <span class="text-[12.5px] text-danger-ink" x-show="tried && decision === 'revise' && ! note.trim()" x-cloak>Write a note to the applicant.</span>
                            <span class="text-xs text-muted-foreground">Reviewer names stay hidden from the applicant.</span>
                        </label>
                        <label class="flex flex-col gap-1.5" x-show="decision === 'revise'">
                            <span class="text-[13px] font-medium">Response due</span>
                            <input type="date" name="response_due_at" class="input" value="{{ old('response_due_at', now()->addDays(W::RESPONSE_DAYS)->toDateString()) }}" :disabled="decision !== 'revise'">
                        </label>
                        <p class="rounded-[10px] bg-muted px-3.5 py-3 text-[13px] leading-normal text-gray-700"><b class="font-semibold text-foreground">What happens next:</b> <span x-text="after[decision]">{{ $afterText['revise'] }}</span></p>
                        <button type="submit" class="{{ $ink }}">Record decision</button>
                    </div>
                </form>
            @endif

            @if($s === S::RECOMMENDED)
                <form method="POST" action="{{ route('seed.approvals.decide', $application) }}" class="{{ $action }}">
                    @csrf
                    <input type="hidden" name="level" value="gmu">
                    <input type="hidden" name="decision" value="approved">
                    <header class="{{ $actionHead }}"><h2 class="text-[15px] font-semibold text-amber-900">Send for approval</h2><p class="text-[13px] text-amber-800">Records GMU’s sign-off{{ $later->isNotEmpty() ? ' and sends it on: ' . $later->implode(', then ') : '' }}</p></header>
                    <div class="flex flex-col gap-3.5 px-[18px] py-4">
                        @if($means)
                            <div class="flex flex-col gap-1 rounded-[10px] bg-blue-50 px-3.5 py-3 text-[13px]">
                                <div class="flex justify-between"><span>Mean score</span><b class="font-mono font-semibold">{{ $means['total'] }} / {{ S::trimNumber($maxTotal) }}</b></div>
                                @foreach($form->minimums() as $key => $min)
                                    <div class="flex justify-between text-gray-700"><span>{{ $form->criteria()[$key][0] }} (at least {{ S::trimNumber($min) }})</span><span class="font-mono">{{ $means[$key] ?? '—' }} / {{ S::trimNumber($form->criteria()[$key][2]) }}</span></div>
                                @endforeach
                                <p class="pt-1 font-semibold text-ok-ink">{{ $meets ? 'Meets the funding threshold.' : 'Below the funding threshold.' }}</p>
                            </div>
                        @endif
                        <label class="flex flex-col gap-1.5"><span class="text-[13px] font-medium">Note <span class="font-normal text-muted-foreground">(optional)</span></span><textarea name="note" class="textarea" rows="2">{{ old('note') }}</textarea></label>
                        <button type="submit" class="{{ $ink }} inline-flex items-center justify-center gap-2">{{ $sendLabel }}<x-icon name="arrow-right" class="size-4" /></button>
                        <p class="text-center text-xs text-muted-foreground">You can also send several from the <a href="{{ route('seed.ranking', ['call' => $application->seed_call_id]) }}" class="font-medium text-ok-ink hover:underline">ranking</a>.</p>
                    </div>
                </form>
            @endif

            @if($s === S::APPROVED)
                @php $conditions = $application->approvals->where('decision', 'conditions')->sortByDesc('id')->first()?->note; @endphp
                <form method="POST" action="{{ route('seed.submissions.issue-agreement', $application) }}" class="{{ $action }}">
                    @csrf
                    <header class="{{ $actionHead }}"><h2 class="text-[15px] font-semibold text-amber-900">Grant agreement</h2><p class="text-[13px] text-amber-800">Approved for {{ S::tzs($application->approved_amount) }}</p></header>
                    <div class="flex flex-col gap-3.5 px-[18px] py-4">
                        <p class="text-[13.5px] leading-relaxed text-gray-700">Issuing it drafts the agreement from the approved proposal and asks the PI to accept online.</p>
                        @if($conditions)<div class="rounded-[10px] bg-muted px-3.5 py-3 text-[13px] leading-normal"><b class="font-semibold">Conditions from the DG:</b> {{ $conditions }}</div>@endif
                        <button type="submit" class="{{ $ink }}">Issue agreement</button>
                    </div>
                </form>
            @endif

            @if(in_array($s, [S::AGREEMENT_ISSUED, S::AGREEMENT_ACCEPTED, S::AWARDED], true))
                @php
                    [$agBox, $agHead, $agTitle, $agSub, $agNote] = match ($s) {
                        S::AWARDED => ['border-award-line', 'border-award-line bg-award-soft', 'text-award-ink', 'text-award-ink', 'Signed copy uploaded ' . $application->signed_agreement_uploaded_at?->format('j M Y')],
                        S::AGREEMENT_ACCEPTED => ['border-warn-line shadow-lift', 'border-warn-line bg-warn-soft', 'text-amber-900', 'text-amber-800', 'Accepted by the PI on ' . $application->agreement_accepted_at?->format('j M Y') . '. Upload the fully signed copy.'],
                        default => ['border-border', 'border-border bg-muted', 'text-foreground', 'text-muted-foreground', 'Waiting for the PI to accept it online.'],
                    };
                @endphp
                <section class="overflow-hidden rounded-2xl border bg-card {{ $agBox }}">
                    <header class="border-b px-[18px] py-3.5 {{ $agHead }}"><h2 class="text-[15px] font-semibold {{ $agTitle }}">Grant agreement</h2><p class="text-[13px] {{ $agSub }}">{{ $agNote }}</p></header>
                    <div class="flex flex-col gap-3 px-[18px] py-4">
                        <a href="{{ route('seed.applications.agreement', $application) }}" class="flex items-center gap-2.5 rounded-[10px] border border-border px-3.5 py-3 text-[13.5px] font-medium hover:bg-gray-50"><x-icon name="file-text" class="size-4 text-muted-foreground" /><span class="flex-1">Open the agreement</span><span class="font-mono text-xs text-muted-foreground">{{ $application->reference }}</span></a>
                        @if(in_array($s, [S::AGREEMENT_ACCEPTED, S::AWARDED], true))
                            <form method="POST" action="{{ route('seed.submissions.signed-agreement', $application) }}" enctype="multipart/form-data" class="flex flex-col gap-2.5 border-t border-gray-100 pt-3"
                                  x-data="{ file: '', tried: false }" @submit="tried = true; if (! file) $event.preventDefault()">
                                @csrf
                                <span class="text-[13px] font-medium">Signed copy (PDF)</span>
                                <label class="relative flex cursor-pointer items-center gap-2.5 rounded-[10px] border-[1.5px] border-dashed bg-gray-50 p-3.5 text-[13px] text-gray-700" :class="tried && ! file ? 'border-danger' : 'border-gray-300'">
                                    <x-icon name="upload" class="size-4" />
                                    <span class="min-w-0 flex-1 truncate" x-text="file || 'Choose the signed PDF'">Choose the signed PDF</span>
                                    <input type="file" name="file" accept=".pdf" class="absolute size-px opacity-0" @change="file = $event.target.files[0]?.name || ''">
                                </label>
                                <span class="text-[12.5px] text-danger-ink" x-show="tried && ! file" x-cloak>Choose the signed PDF first.</span>
                                <button type="submit" class="{{ $ink }} inline-flex items-center justify-center gap-2">{{ $s === S::AWARDED ? 'Replace signed copy' : 'Upload signed copy' }}</button>
                            </form>
                        @endif
                    </div>
                </section>
            @endif

            @php
                $info = match ($s) {
                    S::WITH_DRCP => ['With the DRCP', 'Waiting for their decision. The PI is not told until the Director General decides.', 'warn'],
                    S::WITH_DG => ['With the Director General', 'Waiting for the final decision.', 'warn'],
                    S::CONCEPT_REVISION, S::PROPOSAL_REVISION => ['With the applicant', 'Response due ' . $due($application->latestRound()?->response_due_at) . '. A new round opens with the same reviewers when they respond.', 'warn'],
                    S::INELIGIBLE => ['Not eligible', trim(($application->screening_note ?: 'Closed at screening.') . ' The applicant has been told why.'), 'danger'],
                    S::CONCEPT_DECLINED => ['Not shortlisted', 'Closed. The applicant has been told, with the reviewers’ comments by letter only.', 'danger'],
                    S::NOT_RECOMMENDED => ['Not recommended', 'Below the funding line. The applicant has been told, with the reviewers’ comments by letter only.', 'danger'],
                    S::SHORTLISTED => ['With the applicant', 'Invited to write the full proposal' . ($application->call->proposal_deadline ? ', due ' . $application->call->proposal_deadline->format('j M Y') : '') . '.', 'ok'],
                    S::DRAFT => ['Returned for correction', 'Back with the applicant to fix and resubmit.', 'neutral'],
                    default => null,
                };
                $infoTone = ['warn' => ['border-warn-line bg-warn-soft', 'text-warn-ink'], 'danger' => ['border-danger-line bg-danger-soft', 'text-danger-ink'], 'ok' => ['border-ok-line bg-ok-soft', 'text-ok-ink'], 'neutral' => ['border-border bg-muted', 'text-gray-700']];
            @endphp
            @if($info && ! $open)
                <div class="rounded-[14px] border px-[18px] py-4 {{ $infoTone[$info[2]][0] }}"><p class="text-sm font-semibold {{ $infoTone[$info[2]][1] }}">{{ $info[0] }}</p><p class="mt-1 text-[13.5px] leading-normal text-gray-700">{{ $info[1] }}</p></div>
            @endif
        </aside>
    </div>
</div>
@endsection
