{{--
    One application on the applicant's dashboard (the owner's design, 2a): a head in the
    call's colour, the title with the money and the date that matters, the eight steps
    as a bar, and, when it needs them, what to do. Expects $application.
--}}
@php
    use App\Support\ApplicantView as V;
    use App\Support\SeedGrant as S;
    $a       = $application;
    $call    = $a->call;
    $round   = $a->latestRound();
    $current = $a->trackIndex();
    $bucket  = V::bucket($a);
    $awarded = $a->status === S::AWARDED;
    [$dateLabel, $date, $dateInk] = V::cardDate($a);

    // The current step's colour: amber when it needs them, rose when stopped, violet in review, blue otherwise.
    $nowColour = ['needs' => 'bg-warn ring-2 ring-amber-100', 'closed' => 'bg-danger ring-2 ring-red-100', 'review' => 'bg-review ring-2 ring-violet-100'][$bucket] ?? 'bg-primary ring-2 ring-blue-100';
    $next = S::TRACK[$current + 1] ?? null;
@endphp
<section class="{{ $call->tintClass() }} overflow-hidden rounded-[14px] border border-tint-line">
    <header class="flex items-center gap-2.5 border-b border-tint-line bg-tint px-4 py-3">
        <span class="rounded-md bg-tint-chip px-1.5 py-0.5 font-mono text-xs font-medium text-tint-ink">{{ $call->code }}</span>
        <span class="min-w-0 flex-1 truncate text-[13px] font-medium text-gray-700">{{ $call->title }}</span>
        @include('seed.partials.status', ['application' => $a])
    </header>
    <div class="flex flex-col gap-4 bg-card px-5 py-[18px]">
        <div class="flex flex-wrap items-start justify-between gap-x-5 gap-y-3">
            <div class="flex min-w-0 flex-[1_1_20rem] flex-col gap-1">
                <span class="font-mono text-xs text-muted-foreground">{{ $a->reference }}</span>
                <h3 class="text-[17px] font-semibold leading-snug tracking-[-0.01em]"><a href="{{ route('seed.applications.show', $a) }}" class="hover:text-primary">{{ $a->title ?: 'Untitled concept note' }}</a></h3>
            </div>
            <div class="flex shrink-0 gap-6 text-right">
                <div>
                    <p class="text-xs text-muted-foreground">{{ $a->approved_amount ? 'Approved' : 'Requested' }}</p>
                    <p class="font-mono text-sm font-medium {{ $a->approved_amount ? 'text-ok-ink' : '' }}">TZS {{ number_format($a->approved_amount ?: $a->requestedAmount()) }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">{{ $dateLabel }}</p>
                    <p class="font-mono text-sm font-medium {{ $date && V::daysTo($date) < 0 && V::needsYou($a) ? 'text-danger-ink' : $dateInk }}">{{ $date?->format('j M Y') ?? '—' }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-8 gap-1" role="img" aria-label="Step {{ $current + 1 }} of 8: {{ S::TRACK[$current][0] }}">
            @foreach(S::TRACK as $i => $step)
                <span class="h-1.5 rounded-[3px] {{ $awarded ? 'bg-award' : ($i < $current ? 'bg-primary' : ($i === $current ? $nowColour : 'bg-gray-100')) }}"></span>
            @endforeach
        </div>

        @if($awarded)
            <div class="-mt-2 flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground">
                <span>All 8 steps done · signed copy on file</span>
                <a href="{{ route('seed.applications.show', $a) }}" class="inline-flex items-center gap-1 text-[13px] font-medium">Open the agreement <x-icon name="arrow-right" class="size-3.5" /></a>
            </div>
        @elseif(V::needsYou($a))
            <div class="-mt-2 flex flex-wrap justify-between gap-2 text-xs text-muted-foreground">
                <span>Step {{ $current + 1 }} of 8 · {{ S::TRACK[$current][0] }}{{ $round ? ', round ' . $round->number : '' }}</span>
                @if($next)<span>Next: {{ $next[0] }}, {{ mb_strtolower($next[1]) }}</span>@endif
            </div>
            @php
                [$head, $body, $buttons] = match (true) {
                    $a->isRevising() => ['Reviewers asked you to revise', $round?->decision_note,
                        [['Read their comments', route('seed.applications.show', $a), false], ['Respond to reviewers', route('seed.applications.edit', $a), true]]],
                    $a->status === S::DRAFT && $a->screened_at && $a->screening_note => ['GMU returned your concept note for correction', $a->screening_note,
                        [['Correct and submit', route('seed.applications.edit', $a), true]]],
                    $a->status === S::DRAFT => ['Finish and submit your concept note', $call->concept_deadline ? 'Concept notes close ' . $call->concept_deadline->format('j M Y') . ', at the end of that day.' : 'It saves when you press Save.',
                        [['Continue writing', route('seed.applications.edit', $a), true]]],
                    $a->status === S::SHORTLISTED => ['You are shortlisted: write your full proposal', ($call->proposal_deadline ? 'Due ' . $call->proposal_deadline->format('j M Y') . '. ' : '') . 'You will also need the mentor’s commitment letter and your CV.',
                        [['Write full proposal', route('seed.applications.edit', $a), true]]],
                    default => ['Your grant agreement is ready', 'Read it and accept it online.',
                        [['Open the agreement', route('seed.applications.agreement', $a), true]]],
                };
            @endphp
            <div class="flex flex-wrap items-center gap-4 rounded-[10px] border border-warn-line bg-warn-soft px-4 py-3.5">
                <div class="flex min-w-0 flex-[1_1_16rem] flex-col gap-1">
                    <p class="text-[13.5px] font-semibold text-warn-ink">{{ $head }}</p>
                    @if($body)<p class="text-pretty text-[13.5px] leading-normal text-gray-700">{{ $body }}</p>@endif
                </div>
                <div class="flex shrink-0 gap-2">
                    @foreach($buttons as [$label, $url, $primary])
                        <a href="{{ $url }}" class="inline-flex h-8 items-center rounded-md px-3 text-[13px] font-medium {{ $primary ? 'bg-foreground text-white hover:bg-gray-800' : 'border border-input bg-card text-foreground hover:bg-muted' }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>
        @else
            <div class="-mt-2 flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground">
                <span>Step {{ $current + 1 }} of 8 · {{ S::TRACK[$current][0] }}{{ $round ? ', round ' . $round->number : '' }} · {{ $bucket === 'closed' ? 'this application has closed' : 'nothing is due from you' }}</span>
                <a href="{{ route('seed.applications.show', $a) }}" class="inline-flex items-center gap-1 text-[13px] font-medium">Open application <x-icon name="arrow-right" class="size-3.5" /></a>
            </div>
        @endif
    </div>
</section>
