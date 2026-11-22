@extends('layouts.app')

@php
    use App\Support\SeedGrant as S;
    use App\Models\Seed\SeedReview;
    use App\Services\SeedGrant\Workflow as W;
    $form   = $application->call->form();
    $s      = $application->status;
    $pi     = $application->pi;
    $open   = $application->openRound();
    $means  = $application->meanScores();
    $recTone = ['accept' => 'ok', 'revise' => 'warn', 'reject' => 'danger'];
    $canAssign = in_array($s, [S::SCREENED, S::CONCEPT_REVIEW, S::CONCEPT_REVISION, S::PROPOSAL_SUBMITTED, S::PROPOSAL_REVIEW, S::PROPOSAL_REVISION], true);
    $next = $form->nextApprovalLevel('gmu');
    $sendLabel = ['drcp' => 'Send to the DRCP', 'dg' => 'Send to the Director General'][$next] ?? 'Approve';
    $tabs = array_filter([
        'reviews'  => 'Reviews',
        'concept'  => 'Concept note',
        'proposal' => $application->proposal ? 'Full proposal' : null,
        'history'  => 'History',
    ]);
    $event = fn ($title, $when, $sub = null, $icon = 'circle') => compact('title', 'when', 'sub', 'icon');
@endphp

@section('title', $application->reference)
@section('context', 'Submissions · ' . $application->reference)

@section('content')
<x-page-header :title="$application->title ?: 'Untitled'" :back="route('seed.submissions.index', ['call' => $application->seed_call_id])" back-label="Submissions"
    :description="$pi->name . ($pi->designation ? ' · ' . $pi->designation : '') . ($pi->research_center ? ' · ' . $pi->research_center : '') . ($application->mentor_name ? ' · mentor ' . $application->mentor_name : '')">
    <x-slot:meta>
        <div class="mt-3 flex flex-wrap items-center gap-2.5">
            @include('seed.partials.status')
            <x-badge>{{ $stage === 'concept' ? 'Concept note' : 'Full proposal' }}</x-badge>
            @include('seed.partials.call-tag', ['call' => $application->call, 'short' => true])
            <span class="font-mono text-xs text-muted-foreground">{{ $application->reference }}</span>
        </div>
    </x-slot:meta>
    <x-slot:actions>
        <dl class="flex gap-6 rounded-xl border border-border bg-card px-5 py-3 shadow-xs">
            <x-stat label="Requested" :value="S::tzs($application->requestedAmount())" mono />
            <x-stat label="Submitted" :value="$application->concept_submitted_at?->format('j M Y') ?? '—'" />
            @if($means)<x-stat label="Mean score" :value="$means['total'] . ' / ' . S::trimNumber($form->maxTotal())" mono />@endif
        </dl>
    </x-slot:actions>
</x-page-header>

<nav class="mb-6 flex gap-6 overflow-x-auto border-b border-border" aria-label="Sections">
    @foreach($tabs as $key => $label)
        <a href="?tab={{ $key }}" class="-mb-px whitespace-nowrap border-b-2 px-0.5 pb-3 text-sm font-medium transition-colors {{ $tab === $key ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground hover:border-input hover:text-foreground' }}"
           @if($tab === $key) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>

<div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
    <div class="min-w-0 space-y-6">
        @if($tab === 'concept' || $tab === 'proposal')
            @include('seed.partials.content', ['stage' => $tab])

        @elseif($tab === 'history')
            @php
                $events = collect();
                if ($application->concept_submitted_at) $events->push($event('Concept note submitted', $application->concept_submitted_at, null, 'send'));
                if ($application->screened_at) $events->push($event('Screened: ' . ($s === S::INELIGIBLE ? 'not eligible' : ($s === S::DRAFT || $s === S::SUBMITTED ? 'returned for correction' : 'eligible')), $application->screened_at, trim(($application->screener?->name ?? '') . ($application->screening_note ? ' — ' . $application->screening_note : '')), 'clipboard-check'));
                foreach ($application->rounds as $r) {
                    $events->push($event(ucfirst($r->stage === 'proposal' ? 'full proposal' : 'concept note') . ' round ' . $r->number . ' opened', $r->created_at, 'Reviews due ' . $r->due_at?->format('j M Y'), 'users'));
                    if ($r->decided_at) $events->push($event('Round ' . $r->number . ': ' . \App\Models\Seed\SeedReviewRound::DECISIONS[$r->decision], $r->decided_at, trim(($r->decider?->name ?? '') . ($r->decision_note ? ' — ' . $r->decision_note : '')), 'scale'));
                    if ($r->responded_at) $events->push($event('Applicant responded', $r->responded_at, \Illuminate\Support\Str::limit($r->response, 160), 'message-square'));
                }
                if ($application->proposal_submitted_at) $events->push($event('Full proposal submitted', $application->proposal_submitted_at, null, 'send'));
                foreach ($application->approvals->sortBy('id') as $ap) {
                    $events->push($event(\App\Models\Seed\SeedApproval::LEVELS[$ap->level] . ': ' . \App\Models\Seed\SeedApproval::DECISIONS[$ap->decision], $ap->created_at, trim(($ap->decider?->name ?? '') . ($ap->note ? ' — ' . $ap->note : '')), 'shield-check'));
                }
                foreach ([['agreement_issued_at', 'Agreement issued', 'file-text'], ['agreement_accepted_at', 'Agreement accepted by the PI', 'pen-line'], ['signed_agreement_uploaded_at', 'Signed agreement uploaded', 'upload']] as [$f, $label, $icon]) {
                    if ($application->$f) $events->push($event($label, $application->$f, null, $icon));
                }
                $events = $events->sortBy(fn ($e) => $e['when'])->values();
            @endphp
            <x-card title="History" description="Every step, in the order it happened">
                <ol class="relative space-y-6 before:absolute before:bottom-2 before:left-[15px] before:top-2 before:w-px before:bg-border">
                    @forelse($events as $e)
                        <li class="relative flex gap-4">
                            <span class="relative grid size-8 shrink-0 place-items-center rounded-full border border-border bg-card text-gray-600 shadow-xs"><x-icon :name="$e['icon']" class="size-3.5" /></span>
                            <div class="min-w-0 flex-1 pt-1">
                                <div class="flex flex-wrap items-baseline justify-between gap-x-4">
                                    <p class="text-sm font-medium">{{ $e['title'] }}</p>
                                    <time class="whitespace-nowrap text-xs text-muted-foreground">{{ $e['when']->format('j M Y H:i') }}</time>
                                </div>
                                @if($e['sub'])<p class="mt-0.5 text-[13px] text-muted-foreground">{{ $e['sub'] }}</p>@endif
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-muted-foreground">Nothing has happened yet.</li>
                    @endforelse
                </ol>
            </x-card>

        @else
            @forelse($application->rounds->sortByDesc(fn ($r) => [$r->stage === 'proposal', $r->number]) as $round)
                @php $roundAssignments = $application->stageAssignments($round->stage)->filter(fn ($a) => $a->removed_at === null || $round->reviews->contains('seed_reviewer_assignment_id', $a->id)); @endphp
                <section>
                    <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
                        <h2 class="text-base">{{ $round->stage === 'proposal' ? 'Full proposal' : 'Concept note' }} · round {{ $round->number }}</h2>
                        <span class="text-[13px] text-muted-foreground">
                            @if($round->isOpen()) Reviews due {{ $round->due_at?->format('j M Y') }}
                            @else Decided {{ $round->decided_at?->format('j M Y') }}: {{ \App\Models\Seed\SeedReviewRound::DECISIONS[$round->decision] }} @endif
                        </span>
                    </div>
                    <div class="grid gap-4 lg:grid-cols-2">
                        @foreach($roundAssignments as $a)
                            @php $review = $round->reviews->firstWhere('seed_reviewer_assignment_id', $a->id); @endphp
                            <article class="rounded-xl border border-border bg-card p-5 shadow-xs">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <span class="grid size-8 place-items-center rounded-full bg-muted font-mono text-sm font-medium">{{ $a->label }}</span>
                                        <div class="leading-tight"><p class="text-sm font-semibold">Reviewer {{ $a->label }}</p><p class="text-xs text-muted-foreground">{{ $a->reviewer->name }} · seen by GMU only</p></div>
                                    </div>
                                    @if($review?->isSubmitted())
                                        <x-badge :tone="$recTone[$review->recommendation] ?? 'neutral'">{{ SeedReview::RECOMMENDATIONS[$review->recommendation] }}</x-badge>
                                    @elseif($a->coi_status === 'conflict')
                                        <x-badge tone="danger">Conflict declared</x-badge>
                                    @elseif($a->coi_status === 'pending')
                                        <x-badge tone="warn">Declaration pending</x-badge>
                                    @else
                                        <x-badge :tone="$round->isOverdue() ? 'danger' : 'neutral'">{{ $round->isOverdue() ? 'Overdue' : 'Not submitted' }}</x-badge>
                                    @endif
                                </div>
                                @if($review?->isSubmitted())
                                    @if($round->stage === 'proposal' && $review->scores)
                                        <p class="mt-4 font-mono text-2xl tabular-nums">{{ $review->total }}<span class="text-sm text-muted-foreground"> / {{ S::trimNumber($form->maxTotal()) }}</span></p>
                                        <p class="mt-1 text-xs text-muted-foreground">
                                            @foreach($form->criteria() as $key => [$name, , $max]){{ $name }} {{ $review->score($key) }}/{{ S::trimNumber($max) }}@if(! $loop->last) · @endif @endforeach
                                        </p>
                                    @endif
                                    <p class="prose-text mt-3">{{ $review->comments }}</p>
                                    @if($review->confidential_note)
                                        <x-alert tone="neutral" icon="lock" title="Confidential to GMU" class="mt-3">{{ $review->confidential_note }}</x-alert>
                                    @endif
                                @elseif($a->coi_status === 'conflict' && $a->coi_note)
                                    <p class="mt-3 text-[13px] text-muted-foreground">{{ $a->coi_note }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                    @if($round->response)
                        <x-card class="mt-4" title="Applicant’s response" :description="$round->responded_at?->format('j M Y')"><p class="prose-text">{{ $round->response }}</p></x-card>
                    @endif
                </section>
            @empty
                <x-card><x-empty icon="message-square" title="No review rounds yet">{{ $s === S::SUBMITTED ? 'Screen the concept note first.' : 'Assign reviewers and send it out.' }}</x-empty></x-card>
            @endforelse
        @endif
    </div>

    {{-- What GMU does next depends on where the application is. --}}
    <aside class="space-y-4 xl:sticky xl:top-24">
        @if($s === S::SUBMITTED)
            @php
                $service = $pi->serviceLength();
                $autoChecks = array_filter([
                    'staff'    => ['NIMR staff, permanent or contract', (bool) $pi->employment_type, $pi->employment_type ? \App\Models\User::EMPLOYMENT_TYPES[$pi->employment_type] : 'not recorded'],
                    'service'  => ($years = $form->serviceYears())
                        ? ["Under {$years} years at NIMR", (bool) $pi->withinServiceLimit($years), $service ? "{$service['years']} y {$service['months']} m" : 'not recorded']
                        : null,
                    'prior'    => ($priorRule = $form->priorGrant())
                        ? ['No prior grant over USD ' . number_format($priorRule['usd']) . ' as PI', $application->declared_no_prior_large_grant, 'declared']
                        : null,
                    'complete' => ['All sections complete, within limits', empty($complete), $application->pageEstimate('concept') . ' pp'],
                    'budget'   => ['Budget within ' . number_format($application->call->min_amount / 1e6) . 'M–' . number_format($application->call->max_amount / 1e6) . 'M', ! isset($complete['budget']), number_format($application->budgetTotal('concept') / 1e6, 2) . 'M'],
                    'mentor'   => $form->mentorWords() !== null
                        ? ['Mentor named, with relevant expertise', filled($application->mentor_name) && filled($application->mentor_suitability), $application->mentor_name ?: '—']
                        : null,
                ]);
            @endphp
            <form method="POST" action="{{ route('seed.submissions.screen', $application) }}">
                @csrf
                <x-card title="Screening" description="Eligibility, completeness and compliance with the call." icon="clipboard-check">
                    <ul class="divide-y divide-border">
                        @foreach($autoChecks as $key => [$label, $ok, $detail])
                            <li>
                                <label class="flex items-center justify-between gap-3 py-2.5 text-sm">
                                    <span class="flex items-center gap-2.5">
                                        <input type="hidden" name="checks[{{ $key }}]" value="0">
                                        <input type="checkbox" class="checkbox" name="checks[{{ $key }}]" value="1" @checked($ok)>
                                        {{ $label }}
                                    </span>
                                    <span class="shrink-0 text-xs {{ $ok ? 'text-muted-foreground' : 'font-medium text-danger-ink' }}">{{ $detail }}</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                    <fieldset class="mt-4 space-y-2">
                        <legend class="label mb-2">Result</legend>
                        <x-choice name="result" value="eligible" label="Eligible" sub="Goes on to reviewers" :checked="empty($eligibility) && empty($complete)" />
                        <x-choice name="result" value="return" label="Return for correction" sub="Back to the applicant to fix and resubmit" :checked="empty($eligibility) && ! empty($complete)" />
                        <x-choice name="result" value="ineligible" label="Not eligible" sub="Closed; the applicant is told why" :checked="! empty($eligibility)" tone="danger" />
                    </fieldset>
                    <x-field class="mt-4" label="Note to the applicant" name="note" help="Required if returned or not eligible.">
                        <textarea id="note" name="note" class="textarea" rows="3">{{ old('note') }}</textarea>
                    </x-field>
                    <x-button type="submit" variant="primary" class="mt-4 w-full">Record screening</x-button>
                </x-card>
            </form>
        @endif

        @if($canAssign)
            <x-card :title="'Reviewers · ' . ($stage === 'concept' ? 'concept note' : 'full proposal')" icon="users">
                <ul class="space-y-3">
                    @forelse($application->stageAssignments($stage) as $a)
                        <li class="flex items-center gap-3 {{ $a->isActive() ? '' : 'opacity-70' }}">
                            <span class="grid size-8 shrink-0 place-items-center rounded-full font-mono text-sm font-medium {{ $a->isActive() ? 'bg-muted' : 'bg-danger-soft text-danger-ink' }}">{{ $a->label }}</span>
                            <span class="min-w-0 flex-1 leading-tight">
                                <span class="block truncate text-sm font-medium {{ $a->removed_at ? 'line-through' : '' }}">{{ $a->reviewer->name }}</span>
                                <span class="block text-xs {{ $a->coi_status === 'conflict' ? 'text-danger-ink' : 'text-muted-foreground' }}">
                                    @if($a->removed_at) Removed
                                    @elseif($a->coi_status === 'pending') Declaration pending
                                    @elseif($a->coi_status === 'none') No conflict · {{ $a->coi_declared_at?->format('j M Y') }}
                                    @else Conflict declared @endif
                                </span>
                            </span>
                            @if($a->removed_at === null)
                                <form method="POST" action="{{ route('seed.submissions.unassign', [$application, $a]) }}" onsubmit="return confirm('Remove {{ addslashes($a->reviewer->name) }} from this review?')">
                                    @csrf @method('DELETE')
                                    <x-button type="submit" variant="ghost" size="icon-sm" aria-label="Remove {{ $a->reviewer->name }}"><x-icon name="x" /></x-button>
                                </form>
                            @endif
                        </li>
                    @empty
                        <li class="text-sm text-muted-foreground">No reviewers yet.</li>
                    @endforelse
                </ul>
                <form method="POST" action="{{ route('seed.submissions.assign', $application) }}" class="mt-4 flex gap-2">
                    @csrf
                    <label for="reviewer_id" class="sr-only">Add a reviewer</label>
                    <select id="reviewer_id" name="reviewer_id" class="select min-w-0 flex-1" required>
                        <option value="">Add a reviewer…</option>
                        @foreach($candidates as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}{{ $c->research_center ? ' · ' . $c->research_center : '' }}</option>
                        @endforeach
                    </select>
                    <x-button type="submit" icon="user-plus">Add</x-button>
                </form>
                <p class="mt-3 text-xs text-muted-foreground">Reviewers are anonymous to the applicant and declare conflicts before they can see the application. Applicants in this call, the DRCP and the DG are not listed.</p>
            </x-card>
        @endif

        @if(in_array($s, [S::SCREENED, S::PROPOSAL_SUBMITTED], true))
            @php $short = $application->activeAssignments($stage)->count() < W::MIN_REVIEWERS; @endphp
            <form method="POST" action="{{ route('seed.submissions.start-review', $application) }}">
                @csrf
                <x-card title="Send to reviewers" :description="'Needs at least ' . W::MIN_REVIEWERS . ' reviewers. They are told by email and in the portal.'" icon="send">
                    <x-field label="Reviews due" name="due_at">
                        <input id="due_at" type="date" name="due_at" class="input" value="{{ now()->addDays(W::REVIEW_DAYS)->toDateString() }}">
                    </x-field>
                    <x-button type="submit" variant="primary" class="mt-4 w-full" :disabled="$short">Open round 1</x-button>
                    @if($short)<p class="mt-2 text-xs text-muted-foreground">Add {{ W::MIN_REVIEWERS - $application->activeAssignments($stage)->count() }} more {{ str('reviewer')->plural(W::MIN_REVIEWERS - $application->activeAssignments($stage)->count()) }} first.</p>@endif
                </x-card>
            </form>
        @endif

        @if($open)
            <form method="POST" action="{{ route('seed.submissions.decide', $application) }}">
                @csrf
                <x-card :title="'Your decision for round ' . $open->number" icon="scale"
                        :description="$roundDone ? 'All reviews are in.' : $open->reviews->filter->isSubmitted()->count() . ' of ' . $application->activeAssignments()->count() . ' reviews in.'">
                    @if($stage === 'proposal' && $means)
                        @php $meets = $application->meetsThreshold(); @endphp
                        <div class="mb-4 space-y-1 rounded-lg p-3.5 text-sm {{ $meets ? 'bg-ok-soft text-foreground' : 'bg-danger-soft text-danger-ink' }}">
                            <div class="flex justify-between"><span>Mean score</span><strong class="font-mono">{{ $means['total'] }} / {{ S::trimNumber($form->maxTotal()) }}</strong></div>
                            @foreach($form->minimums() as $key => $min)
                                <div class="flex justify-between text-[13px]"><span>{{ $form->criteria()[$key][0] }} (at least {{ S::trimNumber($min) }})</span><span class="font-mono">{{ $means[$key] ?? '—' }} / {{ S::trimNumber($form->criteria()[$key][2]) }}</span></div>
                            @endforeach
                            <p class="pt-1 text-[13px] font-medium">{{ $meets ? 'Meets the funding threshold.' : 'Below the funding threshold; it cannot be recommended.' }}</p>
                        </div>
                    @endif
                    <fieldset class="space-y-2">
                        <legend class="sr-only">Decision</legend>
                        <x-choice name="decision" value="revise" label="Ask the applicant to revise" sub="Opens a new round with the same reviewers when they respond" checked />
                        <x-choice name="decision" value="advance" :label="$stage === 'concept' ? 'Shortlist' : 'Recommend for funding'" :sub="$stage === 'concept' ? 'Invite a full proposal' : 'Goes to approval: ' . collect($form->approvalLevels())->map(fn ($l) => ['gmu' => 'GMU', 'drcp' => 'DRCP', 'dg' => 'DG'][$l])->implode(', then ')" />
                        <x-choice name="decision" value="decline" :label="$stage === 'concept' ? 'Do not shortlist' : 'Do not recommend'" tone="danger" />
                    </fieldset>
                    <x-field class="mt-4" label="Note to the applicant" name="decision_note" help="Required when asking for a revision. Reviewer names stay hidden from the applicant.">
                        <textarea id="decision_note" name="decision_note" class="textarea" rows="4">{{ old('decision_note') }}</textarea>
                    </x-field>
                    <x-field class="mt-4" label="Response due, if revising" name="response_due_at">
                        <input id="response_due_at" type="date" name="response_due_at" class="input" value="{{ now()->addDays(W::RESPONSE_DAYS)->toDateString() }}">
                    </x-field>
                    <x-button type="submit" variant="primary" class="mt-4 w-full">Record decision</x-button>
                </x-card>
            </form>
        @endif

        @if(in_array($s, [S::CONCEPT_REVISION, S::PROPOSAL_REVISION], true))
            <x-alert tone="warn" title="With the applicant">Response due {{ $application->latestRound()?->response_due_at?->format('j M Y') }}.</x-alert>
        @endif

        @if($s === S::RECOMMENDED)
            <form method="POST" action="{{ route('seed.approvals.decide', $application) }}">
                @csrf
                <input type="hidden" name="level" value="gmu">
                <input type="hidden" name="decision" value="approved">
                <x-card title="Send for approval" icon="shield-check"
                        :description="'Records GMU’s sign-off' . ($next ? ' and sends it on: ' . collect($form->approvalLevels())->reject(fn ($l) => $l === 'gmu')->map(fn ($l) => ['drcp' => 'the DRCP', 'dg' => 'the Director General'][$l])->implode(', then ') : '') . '. You can also send several from the ranking.'">
                    <x-field label="Note" name="note" optional>
                        <textarea id="note" name="note" class="textarea" rows="2">{{ old('note') }}</textarea>
                    </x-field>
                    <x-button type="submit" variant="primary" class="mt-4 w-full" icon-right="arrow-right">{{ $sendLabel }}</x-button>
                </x-card>
            </form>
        @endif

        @if(in_array($s, [S::WITH_DRCP, S::WITH_DG], true))
            <x-alert tone="warn" icon="hourglass" :title="$s === S::WITH_DRCP ? 'With the DRCP' : 'With the Director General'">Waiting for their decision.</x-alert>
        @endif

        @if($s === S::APPROVED)
            <form method="POST" action="{{ route('seed.submissions.issue-agreement', $application) }}">
                @csrf
                <x-card title="Grant agreement" icon="file-text" :description="'Approved for ' . S::tzs($application->approved_amount) . '. Issuing it drafts the agreement from the approved proposal and asks the PI to accept online.'">
                    <x-button type="submit" variant="primary" class="w-full">Issue agreement</x-button>
                </x-card>
            </form>
        @endif

        @if(in_array($s, [S::AGREEMENT_ISSUED, S::AGREEMENT_ACCEPTED, S::AWARDED], true))
            <x-card title="Grant agreement" icon="file-text">
                <p class="text-[13px] text-muted-foreground">
                    @if($s === S::AGREEMENT_ISSUED) Waiting for the PI to accept it online.
                    @elseif($s === S::AGREEMENT_ACCEPTED) Accepted by the PI on {{ $application->agreement_accepted_at->format('j M Y') }}. Upload the fully signed copy.
                    @else Signed copy uploaded {{ $application->signed_agreement_uploaded_at->format('j M Y') }}. @endif
                </p>
                <x-button class="mt-3 w-full" :href="route('seed.applications.agreement', $application)">Open the agreement</x-button>
                @if(in_array($s, [S::AGREEMENT_ACCEPTED, S::AWARDED], true))
                    <form method="POST" action="{{ route('seed.submissions.signed-agreement', $application) }}" enctype="multipart/form-data" class="mt-4 border-t border-border pt-4">
                        @csrf
                        <x-field label="Signed copy (PDF)" name="file">
                            <input id="file" type="file" name="file" accept=".pdf" required
                                   class="block w-full text-sm text-muted-foreground file:mr-3 file:h-9 file:rounded-md file:border file:border-solid file:border-border file:bg-card file:px-3 file:text-sm file:font-medium file:text-foreground hover:file:bg-muted">
                        </x-field>
                        <x-button type="submit" variant="primary" class="mt-3 w-full" icon="upload">{{ $s === S::AWARDED ? 'Replace signed copy' : 'Upload signed copy' }}</x-button>
                    </form>
                @endif
            </x-card>
        @endif
    </aside>
</div>
@endsection
