@extends('layouts.app')

@php
    use App\Support\SeedGrant as S;
    $stage = $application->stage();
    $s = $application->status;
    $round = $application->latestRound();
    $shown = $stage === 'proposal' && $application->proposal ? 'proposal' : 'concept';

    // What happened, and what happens next: [tone, icon, headline, detail].
    $banner = match ($s) {
        S::SUBMITTED          => ['info', 'hourglass', 'Submitted and waiting for GMU screening', 'GMU checks eligibility, completeness and the budget range before reviewers see it.'],
        S::SCREENED           => ['info', 'users', 'Passed screening', 'GMU is assigning reviewers.'],
        S::INELIGIBLE         => ['danger', 'x-circle', 'Not eligible', $application->screening_note],
        S::CONCEPT_REVIEW, S::PROPOSAL_REVIEW => ['info', 'message-square', 'With reviewers', 'Round ' . ($round?->number ?? 1) . ' is under review' . ($round?->due_at ? ', due ' . $round->due_at->format('j M Y') : '') . '. You will be told when GMU decides.'],
        S::CONCEPT_REVISION, S::PROPOSAL_REVISION => ['warn', 'pencil', 'Reviewers asked you to revise', 'Read their comments below, revise, and send your response' . ($round?->response_due_at ? ' by ' . $round->response_due_at->format('j M Y') : '') . '.'],
        S::CONCEPT_DECLINED   => ['danger', 'x-circle', 'Not shortlisted', $round?->decision_note ?: 'Thank you for applying. The reviewers’ comments are below.'],
        S::SHORTLISTED        => ['ok', 'star', 'You are shortlisted', 'Write your full proposal, detailed budget and upload the mentor letter and CV' . ($application->call->proposal_deadline ? ' by ' . $application->call->proposal_deadline->format('j M Y') : '') . '. Shortlisting does not guarantee an award.'],
        S::PROPOSAL_SUBMITTED => ['info', 'hourglass', 'Full proposal submitted', 'GMU is assigning reviewers for the scored review.'],
        S::NOT_RECOMMENDED    => ['danger', 'x-circle', 'Not recommended for funding', $round?->decision_note ?: 'The reviewers’ comments are below.'],
        S::RECOMMENDED, S::WITH_DRCP, S::WITH_DG => ['ok', 'award', 'Recommended for funding', 'Now going through institutional approval: ' . collect($application->call->form()->approvalLevels())->map(fn ($l) => ['gmu' => 'GMU', 'drcp' => 'the DRCP', 'dg' => 'the Director General'][$l])->implode(', then ') . '.'],
        S::APPROVED           => ['ok', 'check-circle', 'Approved', 'GMU will issue your grant agreement.'],
        S::AGREEMENT_ISSUED   => ['warn', 'pen-line', 'Your grant agreement is ready', 'Read and accept it online.'],
        S::AGREEMENT_ACCEPTED => ['ok', 'check-circle', 'Agreement accepted', 'GMU is collecting the remaining signatures.'],
        S::AWARDED            => ['ok', 'award', 'Awarded', 'The signed agreement is on file.'],
        default               => null,
    };
@endphp

@section('title', $application->reference)
@section('context', 'My application · ' . $application->reference)

@section('content')
<x-page-header :title="$application->title ?: 'Untitled concept note'" :back="route('dashboard')" back-label="Dashboard">
    <x-slot:meta>
        <div class="mt-2 flex flex-wrap items-center gap-2.5 text-[13px] text-muted-foreground">
            @include('seed.partials.status')
            @include('seed.partials.call-tag', ['call' => $application->call, 'short' => true])
            <span class="font-mono text-xs">{{ $application->reference }}</span>
        </div>
    </x-slot:meta>
    <x-slot:actions>
        @if($application->agreement_issued_at)
            <x-button :variant="$s === S::AGREEMENT_ISSUED ? 'primary' : 'secondary'" :href="route('seed.applications.agreement', $application)" icon="pen-line">Grant agreement</x-button>
        @endif
        @can('update', $application)
            <x-button variant="primary" :href="route('seed.applications.edit', $application)" icon="pencil">{{ $application->isRevising() ? 'Revise and respond' : ($s === S::SHORTLISTED ? 'Write full proposal' : 'Continue writing') }}</x-button>
        @endcan
    </x-slot:actions>
</x-page-header>

@if($banner)
    <x-alert :tone="$banner[0]" :icon="$banner[1]" :title="$banner[2]" class="mb-5">{{ $banner[3] }}</x-alert>
@endif

@include('seed.partials.applicant-figures')

<x-card class="my-5" title="Where your application is" description="Eight steps from concept note to award">
    @include('seed.partials.track')
</x-card>

<div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_24rem]">
    <div>
        <h2 class="mb-3 text-lg">{{ $shown === 'proposal' ? 'Full proposal' : 'Concept note' }}</h2>
        @include('seed.partials.content', ['stage' => $shown])
    </div>
    <aside>
        <h2 class="mb-3 text-lg">Reviewer feedback</h2>
        @include('seed.partials.feedback')
    </aside>
</div>
@endsection
