@extends('layouts.app')

{{--
    One application, as its applicant sees it: the owner's "Application" design.
    A blue band with the call, status, title and one line; three tiles over it; the
    main column (what happened and what to do, the reviewers' feedback by round,
    what was submitted, read only); a side column with the eight steps, the dates
    and the mentor. Statuses the design doesn't draw use its closest card.
--}}

@php
    use App\Support\SeedGrant as S;
    $call    = $application->call;
    $s       = $application->status;
    $round   = $application->latestRound();
    $stage   = $application->stage();
    $shown   = $stage === 'proposal' && $application->proposal ? 'proposal' : 'concept';
    $form    = $call->form();
    $closed  = in_array($s, S::CLOSED_STATUSES, true);
    $levels  = collect($form->approvalLevels())->map(fn ($l) => ['gmu' => 'GMU', 'drcp' => 'the DRCP', 'dg' => 'the Director General'][$l])->implode(', then ');

    // Which of the design's cards this status uses, its headline, and one line for the band.
    [$card, $headline, $lead] = match ($s) {
        S::CONCEPT_REVIEW, S::PROPOSAL_REVIEW => ['review', 'With reviewers', 'Round ' . ($round?->number ?? 1) . ' is under review' . ($round?->due_at ? ', due ' . $round->due_at->format('j M Y') : '') . '. You will be told when GMU decides.'],
        S::CONCEPT_REVISION, S::PROPOSAL_REVISION => ['revision', 'Reviewers asked you to revise', 'Read their comments below, revise, and send your response' . ($round?->response_due_at ? ' by ' . $round->response_due_at->format('j M Y') : '') . '.'],
        S::SHORTLISTED => ['shortlisted', 'You are shortlisted', 'Shortlisting does not guarantee an award.' . ($call->proposal_deadline ? ' Your full proposal is due ' . $call->proposal_deadline->format('j M Y') . '.' : '')],
        S::CONCEPT_DECLINED => ['closed', 'Not shortlisted', 'Thank you for applying. The reviewers’ comments are below.'],
        S::NOT_RECOMMENDED => ['closed', 'Not recommended for funding', 'The reviewers’ comments are below.'],
        S::INELIGIBLE => ['closed', 'Not eligible', 'GMU checked your concept note before review.'],
        S::AWARDED => ['awarded', 'Awarded', 'The signed agreement is on file.'],
        S::AGREEMENT_ISSUED => ['awarded', 'Your grant agreement is ready', 'Read it and accept it online.'],
        S::AGREEMENT_ACCEPTED => ['awarded', 'Agreement accepted', 'GMU is collecting the remaining signatures.'],
        S::APPROVED => ['awarded', 'Approved', 'GMU will issue your grant agreement.'],
        S::SUBMITTED => ['info', 'Submitted and waiting for GMU screening', 'GMU checks eligibility, completeness and the budget range before reviewers see it.'],
        S::SCREENED => ['info', 'Passed screening', 'GMU is assigning reviewers.'],
        S::PROPOSAL_SUBMITTED => ['info', 'Full proposal submitted', 'GMU is assigning reviewers for the scored review.'],
        S::RECOMMENDED, S::WITH_DRCP, S::WITH_DG => ['info', 'Recommended for funding', 'Now going through institutional approval: ' . $levels . '.'],
        default => ['info', 'Not submitted yet', 'Finish your concept note and submit it from the form.'],
    };
    $closedText = match ($s) {
        S::INELIGIBLE => $application->screening_note,
        default => $round?->decision_note,
    };

    // The status pill on the band: white, with the status colour.
    $pill = ['award' => ['text-award-ink', 'bg-award'], 'ok' => ['text-blue-700', 'bg-primary'], 'warn' => ['text-warn-ink', 'bg-warn'], 'danger' => ['text-danger-ink', 'bg-danger'], 'navy' => ['text-review-ink', 'bg-review']][$application->statusTone()] ?? ['text-gray-700', 'bg-gray-400'];
    // The "info" card's head, by the status colour.
    $infoHead = ['ok' => ['border-blue-100 bg-blue-50', 'bg-primary'], 'warn' => ['border-warn-line bg-warn-soft', 'bg-warn']][$application->statusTone()] ?? ['border-border bg-muted', 'bg-gray-500'];

    // The eight steps, top to bottom.
    $current = $application->trackIndex();
    $awarded = $s === S::AWARDED;
    $nowTone = match (true) {
        $closed => ['bg-danger-soft ring-danger', 'bg-danger', 'text-danger-ink'],
        $card === 'revision' => ['bg-warn-soft ring-warn', 'bg-warn', 'text-warn-ink'],
        $card === 'review' => ['bg-review-soft ring-review', 'bg-review', 'text-review-ink'],
        default => ['bg-blue-50 ring-primary', 'bg-primary', 'text-blue-700'],
    };
    $stepLine = $awarded ? 'All 8 steps done' : ($closed ? 'Closed at step ' . ($current + 1) . ' of 8' : 'Step ' . ($current + 1) . ' of 8');

    // Dates: what has happened, and the one that matters next.
    $dates = collect();
    if ($application->concept_submitted_at) $dates->push([$application->proposal_submitted_at ? 'Concept note submitted' : 'Submitted', $application->concept_submitted_at, false]);
    foreach ($application->rounds->whereNotNull('decided_at')->sortBy('decided_at') as $r) {
        $dates->push([match ($r->decision) {
            'advance' => $r->stage === 'proposal' ? 'Recommended' : 'Shortlisted',
            'decline' => 'Decided',
            default => ($r->stage === 'proposal' ? 'Proposal round ' : 'Round ') . $r->number . ' decided',
        }, $r->decided_at, false]);
    }
    if ($application->proposal_submitted_at) $dates->push(['Full proposal submitted', $application->proposal_submitted_at, false]);
    if ($approved = $application->approvals->where('decision', '!=', 'returned')->sortBy('created_at')->last()) {
        if (in_array($s, S::APPROVED_STATUSES, true)) $dates->push(['Approved', $approved->created_at, false]);
    }
    if ($application->agreement_accepted_at) $dates->push(['Agreement accepted', $application->agreement_accepted_at, false]);
    $next = match (true) {
        $application->isRevising() && (bool) $round?->response_due_at => ['Your response due', $round->response_due_at],
        $card === 'review' && (bool) $round?->due_at => ['Reviews due', $round->due_at],
        $s === S::SHORTLISTED && (bool) $call->proposal_deadline => ['Full proposal due', $call->proposal_deadline],
        default => null,
    };
    if ($next) $dates->push([$next[0], $next[1], true]);

    $avatar = ['A' => 'bg-violet-50 text-violet-700', 'B' => 'bg-sky-50 text-sky-700', 'C' => 'bg-indigo-50 text-indigo-700'];
    $rec = ['accept' => 'bg-blue-50 text-blue-700', 'revise' => 'bg-warn-soft text-warn-ink', 'reject' => 'bg-danger-soft text-danger-ink'];
    $decided = $application->rounds->filter(fn ($r) => $r->decision !== null)->sortByDesc(fn ($r) => [$r->stage === 'proposal', $r->number]);
    $sections = $form->sections($shown);
    $card_ = 'overflow-hidden rounded-[14px] border border-border';
    $head_ = 'border-b border-border bg-muted px-[18px] py-3';
@endphp

@section('title', $application->reference)
@section('context', 'My application · ' . $application->reference)
@section('topbar-colour', 'bg-primary')

@section('content')
{{-- The band --}}
<div class="-mx-4 -mt-5 flex flex-col gap-2.5 bg-primary px-7 pb-[100px] pt-5 text-white lg:-mx-6 lg:-mt-6">
    <div class="flex flex-wrap items-center gap-2.5">
        <span class="rounded-md bg-white/15 px-1.5 py-0.5 font-mono text-xs font-medium">{{ $call->code }}</span>
        <span class="text-[13px] text-blue-100">{{ $call->title }}</span>
        <span class="inline-flex h-[22px] shrink-0 items-center gap-1.5 whitespace-nowrap rounded-md bg-white px-2 text-xs font-semibold {{ $pill[0] }}"><span class="size-1.5 rounded-full {{ $pill[1] }}"></span>{{ $application->statusLabel() }}</span>
    </div>
    <h1 class="max-w-[900px] text-balance text-[28px] font-semibold leading-tight tracking-[-0.025em] text-white">{{ $application->title ?: 'Untitled concept note' }}</h1>
    <p class="max-w-[820px] text-pretty text-[15px] text-blue-100">{{ $lead }}</p>
</div>

<div class="-mt-[72px] flex flex-col gap-5">
    @include('seed.partials.applicant-figures')

    <div class="flex flex-wrap items-start gap-5">
        {{-- The main column --}}
        <div class="flex min-w-0 flex-[1_1_560px] flex-col gap-5">

            @if($card === 'review')
                <section class="{{ $card_ }}">
                    <header class="flex items-start gap-3.5 border-b border-violet-100 bg-violet-50 px-5 py-[18px]">
                        <span class="grid size-9 shrink-0 place-items-center rounded-[10px] bg-review text-white"><x-icon name="clock" class="size-[18px]" /></span>
                        <div><h2 class="text-base font-semibold">{{ $headline }}</h2><p class="mt-0.5 text-[13.5px] leading-normal text-gray-700">{{ $lead }}</p></div>
                    </header>
                    <div class="flex flex-col gap-3.5 bg-card px-5 py-[18px]">
                        <p class="text-[13.5px] font-semibold">When the reviews are in, GMU tells you one of three things</p>
                        <div class="grid gap-2.5 sm:grid-cols-3">
                            @foreach($stage === 'proposal' ? [
                                ['Recommended', 'It goes for approval: ' . $levels . '.', 'bg-blue-50', 'text-blue-700'],
                                ['Revise', 'You answer the reviewers and send it again.', 'bg-warn-soft', 'text-warn-ink'],
                                ['Not recommended', 'You receive the comments and the decision.', 'bg-gray-100', 'text-gray-700'],
                            ] : [
                                ['Shortlisted', 'You write the full proposal' . ($call->proposal_deadline ? ' by ' . $call->proposal_deadline->format('j M Y') : '') . '.', 'bg-blue-50', 'text-blue-700'],
                                ['Revise', 'You answer the reviewers and send it again.', 'bg-warn-soft', 'text-warn-ink'],
                                ['Not shortlisted', 'You receive the comments and the decision.', 'bg-gray-100', 'text-gray-700'],
                            ] as [$what, $means, $bg, $ink])
                                <div class="flex flex-col gap-1 rounded-xl p-3.5 {{ $bg }}"><span class="text-[13.5px] font-semibold {{ $ink }}">{{ $what }}</span><span class="text-[13px] leading-snug text-gray-700">{{ $means }}</span></div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @elseif($card === 'info')
                <section class="overflow-hidden rounded-[14px] border {{ $infoHead[0] }}">
                    <div class="flex flex-wrap items-start gap-3.5 px-5 py-[18px]">
                        <span class="grid size-9 shrink-0 place-items-center rounded-[10px] text-white {{ $infoHead[1] }}"><x-icon :name="$s === S::DRAFT ? 'pencil' : 'hourglass'" class="size-[18px]" /></span>
                        <div class="min-w-0 flex-[1_1_320px]"><h2 class="text-base font-semibold">{{ $headline }}</h2><p class="mt-0.5 text-[13.5px] leading-normal text-gray-700">{{ $lead }}</p></div>
                        @can('update', $application)
                            <a href="{{ route('seed.applications.edit', $application) }}" class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg bg-foreground px-4 text-sm font-medium text-white hover:bg-gray-800">Continue writing <x-icon name="arrow-right" class="size-4" /></a>
                        @endcan
                    </div>
                </section>
            @elseif($card === 'revision')
                <section class="overflow-hidden rounded-[14px] border border-warn-line bg-warn-soft">
                    <div class="flex flex-wrap items-start gap-4 px-5 py-[18px]">
                        <span class="grid size-9 shrink-0 place-items-center rounded-[10px] bg-warn text-amber-950"><x-icon name="pen-line" class="size-[18px]" /></span>
                        <div class="flex min-w-0 flex-[1_1_320px] flex-col gap-1.5">
                            <h2 class="text-base font-semibold text-amber-900">{{ $headline }}</h2>
                            <p class="text-pretty text-[13.5px] leading-normal text-gray-700">{{ $lead }}</p>
                        </div>
                        @can('update', $application)
                            <a href="{{ route('seed.applications.edit', $application) }}" class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg bg-foreground px-4 text-sm font-medium text-white hover:bg-gray-800">Revise and respond <x-icon name="arrow-right" class="size-4" /></a>
                        @endcan
                    </div>
                </section>
            @elseif($card === 'shortlisted')
                <section class="overflow-hidden rounded-[14px] border border-blue-100">
                    <div class="flex flex-wrap items-start gap-4 bg-blue-50/60 p-5">
                        <span class="grid size-9 shrink-0 place-items-center rounded-[10px] bg-primary text-white"><x-icon name="check" class="size-[18px]" /></span>
                        <div class="flex min-w-0 flex-[1_1_320px] flex-col gap-1.5">
                            <h2 class="text-base font-semibold text-blue-700">{{ $headline }}</h2>
                            <p class="text-pretty text-[13.5px] leading-normal text-gray-700">Write your full proposal, detailed budget and upload the mentor letter and CV{{ $call->proposal_deadline ? ' by ' . $call->proposal_deadline->format('j M Y') : '' }}. Shortlisting does not guarantee an award.</p>
                        </div>
                        @can('update', $application)
                            <a href="{{ route('seed.applications.edit', $application) }}" class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg bg-primary px-4 text-sm font-semibold text-white hover:bg-blue-700">Write full proposal <x-icon name="arrow-right" class="size-4" /></a>
                        @endcan
                    </div>
                    <div class="flex flex-col gap-3 border-t border-blue-100 bg-card px-5 py-[18px]">
                        <p class="text-[13.5px] font-semibold">What the full proposal needs</p>
                        <ul class="grid gap-2.5 sm:grid-cols-2">
                            <li class="flex items-center gap-2.5 rounded-[10px] border border-border px-3.5 py-3 text-[13.5px]"><span class="grid size-7 shrink-0 place-items-center rounded-lg bg-blue-50 font-mono text-[11.5px] font-semibold text-blue-700">{{ S::PROPOSAL_MAX_PAGES }}</span>Up to {{ S::PROPOSAL_MAX_PAGES }} pages, written online</li>
                            <li class="flex items-center gap-2.5 rounded-[10px] border border-border px-3.5 py-3 text-[13.5px]"><span class="grid size-7 shrink-0 place-items-center rounded-lg bg-sky-50 text-sky-700"><x-icon name="chart" class="size-3.5" /></span>A detailed quarterly budget</li>
                            @foreach(collect($form->documents())->filter(fn ($d) => $d[1]) as [$label])
                                <li class="flex items-center gap-2.5 rounded-[10px] border border-border px-3.5 py-3 text-[13.5px]"><span class="grid size-7 shrink-0 place-items-center rounded-lg {{ $loop->first ? 'bg-violet-50 text-violet-700' : 'bg-indigo-50 text-indigo-700' }}"><x-icon :name="$loop->first ? 'file-text' : 'user'" class="size-3.5" /></span>{{ $label }}</li>
                            @endforeach
                        </ul>
                        @if($form->scored())
                            <p class="text-[13px] text-muted-foreground">Scored on {{ count($form->criteria()) === 6 ? 'six' : count($form->criteria()) }} criteria. Recommended at {{ S::trimNumber($form->passMark()) }}/{{ S::trimNumber($form->maxTotal()) }} or above.</p>
                        @endif
                    </div>
                </section>
            @elseif($card === 'closed')
                <section class="{{ $card_ }}">
                    <div class="flex items-start gap-4 bg-gray-50 p-5">
                        <span class="grid size-9 shrink-0 place-items-center rounded-[10px] bg-gray-700 text-white"><x-icon name="file-text" class="size-[18px]" /></span>
                        <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                            <h2 class="text-base font-semibold">{{ $headline }}</h2>
                            @if($closedText)<p class="text-pretty whitespace-pre-line text-[13.5px] leading-normal text-gray-700">{{ $closedText }}</p>@endif
                        </div>
                    </div>
                </section>
            @elseif($card === 'awarded')
                @php $cats = $application->budgetByCategory(); $catTotal = max(1, array_sum($cats)); $catColour = ['personnel' => 'bg-primary', 'equipment' => 'bg-violet-500', 'research' => 'bg-sky-500', 'indirect' => 'bg-indigo-500']; @endphp
                @php $green = $s === S::AWARDED; @endphp
                <section class="overflow-hidden rounded-[14px] border {{ $green ? 'border-award-line' : 'border-blue-100' }}">
                    <div class="flex flex-wrap items-center gap-4 p-5 {{ $green ? 'bg-award-soft' : 'bg-blue-50/60' }}">
                        <div class="flex min-w-0 flex-[1_1_320px] flex-col gap-1">
                            <h2 class="text-base font-semibold {{ $green ? 'text-award-ink' : 'text-blue-700' }}">{{ $headline }}</h2>
                            <p class="text-[13.5px] leading-normal text-gray-700">{{ $lead }}@if($application->agreement_accepted_at) You accepted it online on {{ $application->agreement_accepted_at->format('j M Y') }}.@endif</p>
                        </div>
                        @if($application->agreement_issued_at)
                            <a href="{{ route('seed.applications.agreement', $application) }}" class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg px-4 text-sm font-medium text-white {{ $s === S::AGREEMENT_ISSUED ? 'bg-primary font-semibold hover:bg-blue-700' : 'bg-foreground hover:bg-gray-800' }}"><x-icon name="file-text" class="size-4" />{{ $s === S::AGREEMENT_ISSUED ? 'Open the agreement' : 'Grant agreement' }}</a>
                        @endif
                    </div>
                    @if(array_sum($cats) > 0)
                        <div class="flex flex-col gap-4 border-t bg-card p-5 {{ $green ? 'border-award-line' : 'border-blue-100' }}">
                            <div class="flex flex-wrap items-baseline justify-between gap-3"><h3 class="text-sm font-semibold">Approved budget</h3><span class="text-[13px] text-muted-foreground">Requested {{ S::tzs($application->budgetTotal('proposal')) }}{{ $application->approved_amount ? ' · approved ' . S::tzs($application->approved_amount) : '' }}</span></div>
                            <div class="flex h-3.5 gap-0.5 overflow-hidden rounded-[5px]" role="img" aria-label="Budget by category">
                                @foreach($cats as $k => $v)@if($v > 0)<span class="{{ $catColour[$k] ?? 'bg-gray-400' }}" style="width: {{ $v / $catTotal * 100 }}%"></span>@endif @endforeach
                            </div>
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                @foreach($cats as $k => $v)
                                    <div class="flex flex-col gap-0.5"><span class="flex items-center gap-1.5 text-[13px] text-muted-foreground"><span class="size-2 rounded-sm {{ $catColour[$k] ?? 'bg-gray-400' }}"></span>{{ S::BUDGET_CATEGORIES[$k] ?? $k }}</span><span class="font-mono text-[15px] font-medium">{{ number_format($v) }}</span></div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>
            @endif

            {{-- Reviewer feedback, by decided round, newest first --}}
            <section id="feedback" class="flex flex-col gap-3.5">
                <div><h2 class="text-[17px] font-semibold tracking-[-0.01em]">Reviewer feedback</h2><p class="text-[13px] text-muted-foreground">Reviewers are shown by letter, never by name.</p></div>
                @forelse($decided as $r)
                    @php
                        [$badge, $badgeTone] = ['revise' => ['Revision requested', 'bg-warn-soft text-warn-ink'], 'advance' => [$r->stage === 'proposal' ? 'Recommended' : 'Shortlisted', 'bg-blue-50 text-blue-700'], 'decline' => ['Not taken forward', 'bg-danger-soft text-danger-ink']][$r->decision];
                        $badgeDot = ['revise' => 'bg-warn', 'advance' => 'bg-primary', 'decline' => 'bg-danger'][$r->decision];
                    @endphp
                    <article class="{{ $card_ }}">
                        <header class="flex items-center justify-between gap-3 border-b border-border bg-muted px-[18px] py-3">
                            <div><h3 class="text-sm font-semibold">{{ $r->stage === 'proposal' ? 'Full proposal' : 'Concept note' }} · round {{ $r->number }}</h3><p class="text-[13px] text-muted-foreground">Decided {{ $r->decided_at?->format('j M Y') }}</p></div>
                            <span class="inline-flex h-[22px] shrink-0 items-center gap-1.5 whitespace-nowrap rounded-md px-2 text-xs font-medium {{ $badgeTone }}"><span class="size-1.5 rounded-full {{ $badgeDot }}"></span>{{ $badge }}</span>
                        </header>
                        <div class="flex flex-col gap-3.5 bg-card px-[18px] py-4">
                            @if($r->decision_note)
                                <div class="flex items-start gap-3 rounded-[10px] border border-blue-100 bg-blue-50 px-3.5 py-3">
                                    <x-icon name="message-square" class="mt-0.5 size-4 shrink-0 text-blue-700" />
                                    <div><p class="text-[13px] font-semibold text-blue-700">From GMU</p><p class="mt-0.5 whitespace-pre-line text-pretty text-[13.5px] leading-relaxed text-gray-800">{{ $r->decision_note }}</p></div>
                                </div>
                            @endif
                            @foreach($r->reviews->filter->isSubmitted()->sortBy(fn ($rv) => $rv->assignment->label) as $review)
                                @php $letter = $review->assignment->label; @endphp
                                <div class="grid grid-cols-[32px_minmax(0,1fr)] gap-3">
                                    <span class="grid size-8 place-items-center rounded-full text-[13px] font-semibold {{ $avatar[$letter] ?? 'bg-gray-100 text-gray-700' }}">{{ $letter }}</span>
                                    <div class="flex flex-col gap-1.5 border-b border-gray-100 pb-3.5">
                                        <div class="flex min-h-8 items-center justify-between gap-2.5"><span class="text-sm font-semibold">Reviewer {{ $letter }}</span><span class="inline-flex h-[22px] shrink-0 items-center whitespace-nowrap rounded-md px-2 text-xs font-medium {{ $rec[$review->recommendation] ?? 'bg-gray-100 text-gray-700' }}">{{ \App\Models\Seed\SeedReview::RECOMMENDATIONS[$review->recommendation] ?? '' }}</span></div>
                                        <p class="whitespace-pre-line text-pretty text-[13.5px] leading-relaxed text-gray-800">{{ $review->comments }}</p>
                                    </div>
                                </div>
                            @endforeach
                            @if($r->response)
                                <div class="rounded-[10px] bg-muted px-3.5 py-3"><p class="text-[13px] font-semibold">Your response <span class="font-normal text-muted-foreground">· {{ $r->responded_at?->format('j M Y') }}</span></p><p class="mt-1 whitespace-pre-line text-[13.5px] leading-relaxed text-gray-800">{{ $r->response }}</p></div>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="flex flex-col items-center gap-1.5 rounded-[14px] border border-dashed border-input px-5 py-7 text-center">
                        <span class="grid size-9 place-items-center rounded-[10px] bg-gray-100 text-muted-foreground"><x-icon name="message-square" class="size-[18px]" /></span>
                        <p class="text-sm font-semibold">No reviewer feedback yet</p>
                        <p class="text-[13px] text-muted-foreground">Feedback appears here once GMU has decided a review round.</p>
                    </div>
                @endforelse
            </section>

            {{-- What was submitted, read only --}}
            @php $submittedAt = $shown === 'proposal' ? $application->proposal_submitted_at : $application->concept_submitted_at; @endphp
            <section class="flex flex-col gap-3.5" x-data="{ all: false }">
                <div><h2 class="text-[17px] font-semibold tracking-[-0.01em]">{{ $shown === 'proposal' ? 'Full proposal' : 'Concept note' }}</h2>
                    <p class="text-[13px] text-muted-foreground">{{ $submittedAt ? 'Submitted by ' . $application->pi->name . ' on ' . $submittedAt->format('j M Y') . ' · read only' : 'Not submitted yet · read only here' }}</p></div>

                @if($shown === 'concept')
                    @php $prior = $application->priorLargeGrant(); @endphp
                    <div class="{{ $card_ }}">
                        <header class="{{ $head_ }}"><h3 class="text-sm font-semibold">A. Applicant and project information</h3></header>
                        <dl class="grid gap-x-6 gap-y-4 bg-card px-[18px] py-4 sm:grid-cols-[repeat(auto-fit,minmax(220px,1fr))]">
                            @if($form->mentorWords() !== null)
                                <div><dt class="text-xs text-muted-foreground">Proposed mentor</dt><dd class="mt-0.5 text-sm font-medium">{{ $application->mentor_name ?: '—' }}</dd><dd class="text-[13px] text-muted-foreground">{{ collect([$application->mentor_designation, $application->mentor_centre])->filter()->implode(' · ') }}</dd></div>
                            @endif
                            @if($priorRule = $form->priorGrant())
                                <div><dt class="text-xs text-muted-foreground">Prior grant above USD {{ number_format($priorRule['usd']) }} as PI</dt><dd class="mt-0.5 text-sm font-medium">{{ ['no' => 'No', 'yes' => 'Yes'][$prior] ?? 'Not answered' }}</dd></div>
                            @endif
                            <div><dt class="text-xs text-muted-foreground">Total funding requested</dt><dd class="mt-0.5 font-mono text-sm font-medium">{{ S::tzs($application->budgetTotal('concept')) }}</dd></div>
                        </dl>
                    </div>
                @endif

                <div class="{{ $card_ }}">
                    <header class="{{ $head_ }}"><h3 class="text-sm font-semibold">{{ $shown === 'concept' ? 'B. Technical questions and C. workplan' : 'The full proposal' }}</h3></header>
                    <div class="bg-card px-[18px] py-1">
                        @foreach($sections as $key => [$heading])
                            @php
                                $text = $application->sectionText($shown, $key);
                                $choice = $shown === 'concept' ? ($form->choices()[$key] ?? null) : null;
                                $answer = $choice ? $application->choice($key) : null;
                            @endphp
                            <div class="border-b border-gray-100 py-4 last:border-b-0" @if($loop->index >= 3) x-show="all" x-cloak @endif>
                                <h4 class="text-sm font-semibold">{{ $heading }}</h4>
                                @if($choice)<p class="mt-1.5 text-sm leading-relaxed text-gray-800">{{ $choice['question'] }} <span class="font-medium">{{ $choice['options'][$answer] ?? 'Not answered' }}</span></p>@endif
                                @if($text !== '')<p class="mt-1.5 whitespace-pre-line text-pretty text-sm leading-relaxed text-gray-800">{{ $text }}</p>
                                @elseif(! $choice)<p class="mt-1.5 text-sm italic text-muted-foreground">Not written yet.</p>@endif
                            </div>
                        @endforeach
                    </div>
                    @if(count($sections) > 3)
                        <button type="button" @click="all = ! all" class="h-11 w-full border-t border-border bg-card text-[13.5px] font-medium text-primary hover:bg-blue-50/60"
                                x-text="all ? 'Show fewer sections' : 'Show all {{ count($sections) }} sections'">Show all {{ count($sections) }} sections</button>
                    @endif
                </div>

                <div class="{{ $card_ }}">
                    <header class="flex items-center justify-between border-b border-border bg-muted px-[18px] py-3"><h3 class="text-sm font-semibold">{{ $shown === 'concept' ? 'Estimated budget' : 'Detailed budget' }}</h3><span class="font-mono text-base font-semibold">{{ S::tzs($application->budgetTotal($shown)) }}</span></header>
                    <table class="w-full bg-card text-sm">
                        <tbody>
                            @forelse($application->budgetLines($shown) as $line)
                                <tr>
                                    <td class="border-b border-gray-100 py-[11px] pl-[18px] pr-3">{{ $line->item }}@if($shown === 'proposal')<span class="block text-xs text-muted-foreground">{{ S::BUDGET_CATEGORIES[$line->category] ?? '' }}</span>@endif</td>
                                    <td class="whitespace-nowrap border-b border-gray-100 py-[11px] pl-3 pr-[18px] text-right font-mono">{{ number_format($line->total()) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="py-8 text-center text-muted-foreground">No budget yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($shown === 'proposal' && $application->documents->isNotEmpty())
                    <div class="{{ $card_ }}">
                        <header class="{{ $head_ }}"><h3 class="text-sm font-semibold">Supporting documents</h3></header>
                        <ul class="divide-y divide-gray-100 bg-card">
                            @foreach($application->documents as $doc)
                                <li><a class="flex items-center gap-3 px-[18px] py-3 hover:bg-muted" href="{{ route('seed.applications.documents.download', [$application, $doc]) }}">
                                    <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-muted font-mono text-[10px] font-medium text-muted-foreground">{{ strtoupper(pathinfo($doc->file_name, PATHINFO_EXTENSION)) }}</span>
                                    <span class="min-w-0 flex-1"><span class="block text-sm font-medium text-foreground">{{ $doc->kindLabel() }}</span><span class="block truncate text-[13px] text-muted-foreground">{{ $doc->file_name }}</span></span>
                                    <x-icon name="download" class="text-subtle" />
                                </a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>
        </div>

        {{-- The side column --}}
        <aside class="flex min-w-[280px] flex-[0_1_340px] flex-col gap-4">
            <section class="{{ $card_ }}">
                <header class="{{ $head_ }} px-4"><h3 class="text-sm font-semibold">Where your application is</h3><p class="text-[13px] text-muted-foreground">{{ $stepLine }}</p></header>
                <ol class="bg-card px-[18px] py-4" aria-label="Where the application is">
                    @foreach(S::TRACK as $i => [$title, $sub])
                        @php $done = $awarded || $i < $current; $now = ! $awarded && $i === $current; @endphp
                        <li class="grid grid-cols-[24px_1fr] gap-3" @if($now) aria-current="step" @endif>
                            <div class="flex flex-col items-center">
                                @if($done)<span class="grid size-6 place-items-center rounded-full bg-primary text-white"><x-icon name="check" class="size-3" /></span>
                                @elseif($now)<span class="grid size-6 place-items-center rounded-full ring-2 {{ $nowTone[0] }}"><span class="size-2 rounded-full {{ $nowTone[1] }}"></span></span>
                                @else<span class="grid size-6 place-items-center rounded-full border border-input font-mono text-[10px] text-subtle">{{ $i + 1 }}</span>@endif
                                @unless($loop->last)<span class="min-h-3.5 w-0.5 flex-1 {{ $done ? 'bg-primary' : 'bg-border' }}"></span>@endunless
                            </div>
                            <div class="pb-3 leading-snug">
                                <p class="text-[13.5px] {{ $now ? 'font-semibold' : 'font-medium' }} {{ $done || $now ? 'text-foreground' : ($closed ? 'text-subtle' : 'text-muted-foreground') }}">{{ $title }}</p>
                                <p class="text-[12.5px] {{ $now ? 'font-medium ' . $nowTone[2] : 'text-subtle' }}">{{ $now ? $application->statusLabel() . ($round && ! $closed ? ' · round ' . $round->number : '') : $sub }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>

            @if($dates->isNotEmpty())
                <section class="{{ $card_ }}">
                    <header class="{{ $head_ }} px-4"><h3 class="text-sm font-semibold">Dates</h3></header>
                    <ul class="bg-card px-4 py-1.5">
                        @foreach($dates as [$what, $when, $hot])
                            <li class="flex justify-between gap-3 border-b border-gray-100 py-2.5 text-[13.5px] last:border-b-0"><span class="{{ $hot ? 'font-semibold text-foreground' : 'text-gray-700' }}">{{ $what }}</span><span class="whitespace-nowrap font-mono text-[12.5px]">{{ $when->format('j M Y') }}</span></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if($application->mentor_name)
                <section class="flex items-center gap-3 rounded-[14px] border border-border p-4">
                    <span class="grid size-10 shrink-0 place-items-center rounded-full bg-blue-50 text-sm font-semibold text-blue-700">{{ collect(explode(' ', preg_replace('/^(Dr\.?|Prof\.?)\s+/i', '', $application->mentor_name)))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') }}</span>
                    <span class="min-w-0 flex-1 leading-snug"><span class="block text-xs text-muted-foreground">Your mentor</span><span class="block text-sm font-medium">{{ $application->mentor_name }}</span><span class="block text-[13px] text-muted-foreground">{{ collect([$application->mentor_designation, $application->mentor_centre])->filter()->implode(' · ') }}</span></span>
                </section>
            @endif

            @if($otherCall)
                <a href="{{ route('seed.calls.show', $otherCall) }}" class="{{ $otherCall->tintClass() }} flex items-center gap-3 rounded-[14px] border border-tint-line bg-tint p-4 text-foreground hover:bg-tint-chip">
                    <span class="rounded-md bg-tint-chip px-1.5 py-0.5 font-mono text-xs font-medium text-tint-ink">{{ $otherCall->code }}</span>
                    <span class="min-w-0 flex-1 leading-snug"><span class="block text-[13.5px] font-semibold">{{ $otherCall->title }}</span><span class="block text-[12.5px] text-muted-foreground">Open{{ $otherCall->concept_deadline ? ' · concept notes close ' . $otherCall->concept_deadline->format('j M Y') : '' }}</span></span>
                    <x-icon name="arrow-right" class="size-4 text-tint-ink" />
                </a>
            @endif
        </aside>
    </div>
</div>
@endsection
