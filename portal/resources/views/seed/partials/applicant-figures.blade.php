{{--
    The applicant's three plain answers: where the application stands, the money,
    and the next date that matters to them. Expects $application.
--}}
@php
    use App\Support\SeedGrant as S;
    $call  = $application->call;
    $round = $application->latestRound();
    $s     = $application->status;
    $m     = fn ($n) => 'TZS ' . number_format($n / 1e6, 1) . 'M';
    $open  = fn ($date) => $date && ! $date->copy()->endOfDay()->isPast();

    // Status colour → tile colour (the same meanings as the status pill).
    $tone = ['award' => 'green', 'ok' => 'blue', 'warn' => 'amber', 'danger' => 'rose', 'navy' => 'violet'][$application->statusTone()] ?? 'neutral';

    // The next date: what is due, from whom, by when. $mine: the applicant has to meet it.
    [$dateTitle, $date, $dateNote, $mine] = match (true) {
        $application->isRevising() && (bool) $round?->response_due_at => ['Your response is due', $round->response_due_at, 'Revise and respond to the reviewers', true],
        $s === S::DRAFT && $open($call->concept_deadline)             => ['Concept notes close', $call->concept_deadline, 'Submit before the end of that day', true],
        $s === S::SHORTLISTED && $open($call->proposal_deadline)       => ['Full proposal due', $call->proposal_deadline, 'With the mentor letter and your CV', true],
        in_array($s, [S::CONCEPT_REVIEW, S::PROPOSAL_REVIEW], true) && (bool) $round?->due_at => ['Reviews due', $round->due_at, 'You will be told when GMU decides', false],
        default => ['Next date', null, 'Nothing is due from you right now', false],
    };

    // A date means the end of that day. Late only once that day has passed.
    $daysLeft = $date ? (int) now()->startOfDay()->diffInDays($date->copy()->startOfDay(), false) : null;
    $late = $date && $daysLeft < 0;
    $dateBadge = ! $date ? null : ($late ? 'Late' : ($daysLeft === 0 ? 'Today' : 'In ' . $daysLeft . ' ' . str('day')->plural($daysLeft)));
    $dateTone = match (true) {
        $late && $mine   => 'rose',      // the applicant missed it
        $late            => 'neutral',   // the reviewers are late; not the applicant's to fix
        $mine && $daysLeft < 3 => 'amber', // the applicant's, and close
        default          => 'sky',
    };
    if ($late) {
        $dateNote = $mine ? 'This date has passed. Send it now, or contact GMU.' : 'The reviewers are late; GMU is following up.';
    }
@endphp
@php
    // The owner's Application design: the status tile solid in the status colour; money and the date on white.
    $solid = [
        'blue'    => ['border-blue-700 bg-blue-700', 'text-blue-100', 'text-white', 'text-blue-100'],
        'green'   => ['border-award-ink bg-award-ink', 'text-award-line', 'text-white', 'text-award-line'],
        'amber'   => ['border-warn bg-warn', 'text-amber-950', 'text-amber-950', 'text-amber-900'],
        'rose'    => ['border-danger-ink bg-danger-ink', 'text-red-100', 'text-white', 'text-red-100'],
        'violet'  => ['border-review-ink bg-review-ink', 'text-violet-100', 'text-white', 'text-violet-200'],
        'neutral' => ['border-gray-600 bg-gray-600', 'text-gray-100', 'text-white', 'text-gray-200'],
    ][$tone];
    $dateInk = ['rose' => ['text-danger-ink', 'bg-danger-soft text-danger-ink'], 'amber' => ['text-warn-ink', 'bg-warn-soft text-warn-ink'],
                'neutral' => ['text-gray-700', 'bg-gray-100 text-gray-700'], 'sky' => ['text-sky-700', 'bg-sky-100 text-sky-700']][$dateTone];
    $tile = 'flex flex-col gap-2.5 rounded-[14px] border p-5 shadow-lift';
@endphp
<div class="grid gap-4 md:grid-cols-3">
    <div class="{{ $tile }} {{ $solid[0] }}">
        <span class="text-[13.5px] font-medium {{ $solid[1] }}">Your application</span>
        <span class="text-[26px] font-semibold leading-tight tracking-[-0.02em] {{ $solid[2] }}">{{ $application->statusLabel() }}</span>
        <span class="text-[13px] {{ $solid[3] }}">{{ $round ? 'Review round ' . $round->number . ($round->stage === 'proposal' ? ', full proposal' : ', concept note') : 'Reference ' . $application->reference }}</span>
    </div>

    <div class="{{ $tile }} border-border bg-card">
        <span class="text-[13.5px] font-medium">{{ $application->approved_amount ? 'Approved' : 'Requested' }}</span>
        <span class="text-[26px] font-semibold leading-tight tracking-[-0.02em] tabular-nums text-blue-700">{{ $m($application->approved_amount ?: $application->requestedAmount()) }}</span>
        <span class="text-[13px] text-muted-foreground">{{ $application->approved_amount ? 'The award in your grant agreement' : 'The call awards ' . $m($call->min_amount) . ' – ' . $m($call->max_amount) }}</span>
    </div>

    <div class="{{ $tile }} border-border bg-card">
        <div class="flex items-center justify-between gap-2">
            <span class="text-[13.5px] font-medium">{{ $dateTitle }}</span>
            @if($dateBadge)<span class="inline-flex h-[22px] shrink-0 items-center whitespace-nowrap rounded-md px-2 text-xs font-medium {{ $dateInk[1] }}">{{ $dateBadge }}</span>@endif
        </div>
        <span class="text-[26px] font-semibold leading-tight tracking-[-0.02em] tabular-nums {{ $date ? $dateInk[0] : 'text-subtle' }}">{{ $date ? $date->format('j M Y') : '—' }}</span>
        <span class="text-[13px] text-muted-foreground">{{ $dateNote }}</span>
    </div>
</div>
