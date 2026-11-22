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
    $tone = ['ok' => 'blue', 'warn' => 'amber', 'danger' => 'rose', 'navy' => 'violet'][$application->statusTone()] ?? 'neutral';

    // The next date: what is due, from whom, by when.
    [$dateTitle, $date, $dateNote] = match (true) {
        $application->isRevising() && (bool) $round?->response_due_at => ['Your response is due', $round->response_due_at, 'Revise and respond to the reviewers'],
        $s === S::DRAFT && $open($call->concept_deadline)             => ['Concept notes close', $call->concept_deadline, 'Submit before the end of that day'],
        $s === S::SHORTLISTED && $open($call->proposal_deadline)       => ['Full proposal due', $call->proposal_deadline, 'With the mentor letter and your CV'],
        in_array($s, [S::CONCEPT_REVIEW, S::PROPOSAL_REVIEW], true) && (bool) $round?->due_at => ['Reviews due', $round->due_at, 'You will be told when GMU decides'],
        default => ['Next date', null, 'Nothing is due from you right now'],
    };
    $daysLeft = $date ? (int) now()->startOfDay()->diffInDays($date->copy()->startOfDay(), false) : null;
@endphp
<div class="grid gap-3 sm:grid-cols-3">
    <x-kpi :tone="$tone" title="Your application" :value="$application->statusLabel()"
        :note="$round ? 'Review round ' . $round->number . ($round->stage === 'proposal' ? ', full proposal' : ', concept note') : 'Reference ' . $application->reference" />

    @if($application->approved_amount)
        <x-kpi tone="blue" title="Approved" :value="$m($application->approved_amount)" note="The award in your grant agreement" />
    @else
        <x-kpi tone="blue" title="Requested" :value="$m($application->requestedAmount())"
            :note="'The call awards ' . $m($call->min_amount) . ' – ' . $m($call->max_amount)" />
    @endif

    <x-kpi tone="sky" :title="$dateTitle"
        :value="$date ? $date->format('j M Y') : '—'"
        :badge="$date ? ($daysLeft <= 0 ? 'Today' : 'In ' . $daysLeft . ' ' . str('day')->plural($daysLeft)) : null"
        :note="$dateNote" />
</div>
