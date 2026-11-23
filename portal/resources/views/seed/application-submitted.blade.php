@extends('layouts.app')

{{--
    Straight after submitting, from the owner's "Concept Note" design (Submitted):
    the confirmation on a blue band, the reference, the money and where it is now
    lifted over it, the eight steps, and what happens next.
--}}

@php
    use App\Support\SeedGrant;
    $call      = $application->call;
    $isConcept = $application->status === SeedGrant::SUBMITTED;
    $stage     = $isConcept ? 'concept' : 'proposal';
    $noun      = $isConcept ? 'concept note' : 'full proposal';
    $at        = $isConcept ? $application->concept_submitted_at : $application->proposal_submitted_at;
    $maxPages  = $isConcept ? SeedGrant::CONCEPT_MAX_PAGES : SeedGrant::PROPOSAL_MAX_PAGES;
    $next = $isConcept ? [
        ['GMU screens your concept note', 'Eligibility, completeness and the budget range. If something needs correcting, GMU returns it to you with a note and you can submit again.', 'bg-blue-50 text-blue-700'],
        ['Reviewers respond', 'They accept, ask for a revision, or reject. Reviewers stay anonymous; you see their comments by letter only.', 'bg-violet-50 text-violet-700'],
        ['If shortlisted, you write the full proposal', ($call->proposal_deadline ? 'Due ' . $call->proposal_deadline->format('j M Y') . ', with' : 'With') . ' the mentor’s commitment letter and your CV.', 'bg-gray-100 text-gray-700'],
    ] : [
        ['GMU assigns reviewers', 'For the scored review of your full proposal.', 'bg-blue-50 text-blue-700'],
        ['Reviewers score it', 'Against the call’s criteria. They may ask for a revision; you respond and it goes round again.', 'bg-violet-50 text-violet-700'],
        ['If recommended, it goes for approval', collect($call->form()->approvalLevels())->map(fn ($l) => ['gmu' => 'GMU', 'drcp' => 'the DRCP', 'dg' => 'the Director General'][$l])->implode(', then ') . ', then your grant agreement.', 'bg-gray-100 text-gray-700'],
    ];
@endphp

@section('title', 'Submitted')
@section('context', 'My application · ' . $application->reference . ' · Submitted')
@section('topbar-colour', 'bg-primary')

@section('content')
<div class="-mx-4 -mt-5 flex flex-col items-start gap-3.5 bg-primary px-8 pb-[110px] pt-9 text-white lg:-mx-6 lg:-mt-6">
    <span class="grid size-12 place-items-center rounded-[14px] bg-white text-primary"><x-icon name="check" class="size-[26px]" /></span>
    <h1 class="text-[34px] font-semibold leading-tight tracking-[-0.03em] text-white">Your {{ $noun }} is submitted.</h1>
    <p class="max-w-[760px] text-pretty text-base leading-relaxed text-blue-100">
        GMU received it on {{ $at?->format('j M Y') }} at {{ $at?->format('H:i') }}. You cannot change it now.
        {{ $isConcept ? 'GMU will check eligibility, completeness and the budget range before reviewers see it.' : 'GMU will assign reviewers for the scored review.' }}
    </p>
</div>

<div class="-mt-[76px] flex flex-col gap-[18px]">
    <div class="grid gap-4 md:grid-cols-3">
        <div class="flex flex-col gap-2 rounded-[14px] border border-border bg-card px-5 py-[18px] shadow-lift"><span class="text-[13px] text-muted-foreground">Reference</span><span class="font-mono text-[22px] font-medium">{{ $application->reference }}</span><span class="text-[12.5px] text-muted-foreground">Quote it if you contact GMU</span></div>
        <div class="flex flex-col gap-2 rounded-[14px] border border-border bg-card px-5 py-[18px] shadow-lift"><span class="text-[13px] text-muted-foreground">Requested</span><span class="font-mono text-[22px] font-medium text-blue-700">{{ SeedGrant::tzs($application->budgetTotal($stage)) }}</span><span class="text-[12.5px] text-muted-foreground">{{ $application->pageEstimate($stage) }} of {{ $maxPages }} pages · {{ count($call->form()->sections($stage)) }} sections</span></div>
        <div class="flex flex-col gap-2 rounded-[14px] border border-border bg-card px-5 py-[18px] shadow-lift">
            <div class="flex items-center justify-between gap-2"><span class="text-[13px] text-muted-foreground">Status</span>@include('seed.partials.status')</div>
            <span class="text-[22px] font-semibold tracking-[-0.02em]">With GMU</span>
            <span class="text-[12.5px] text-muted-foreground">Nothing is due from you right now</span>
        </div>
    </div>

    <x-card title="Where your application is" description="Eight steps from concept note to award">
        @include('seed.partials.track')
    </x-card>

    <div class="grid items-start gap-4 lg:grid-cols-2">
        <section class="overflow-hidden rounded-[14px] border border-border">
            <header class="border-b border-border bg-muted px-[18px] py-3"><h3 class="text-sm font-semibold">What happens next</h3></header>
            <ol class="flex flex-col gap-4 bg-card px-5 py-[18px]">
                @foreach($next as [$head, $body, $chip])
                    <li class="grid grid-cols-[32px_1fr] gap-3.5">
                        <span class="grid size-8 place-items-center rounded-[10px] font-mono text-xs font-medium {{ $chip }}">{{ $loop->iteration }}</span>
                        <div><p class="text-sm font-semibold">{{ $head }}</p><p class="mt-0.5 text-[13.5px] leading-normal text-gray-700">{{ $body }}</p></div>
                    </li>
                @endforeach
            </ol>
        </section>
        <div class="flex flex-col gap-4">
            <section class="flex items-start gap-3 rounded-[14px] border border-blue-100 bg-blue-50 px-[18px] py-4">
                <x-icon name="bell" class="mt-px size-[18px] shrink-0 text-blue-700" />
                <p class="text-pretty text-[13.5px] leading-normal text-blue-700">You will hear from GMU in your notifications and by email at {{ $application->pi->email }} at each next step.</p>
            </section>
            <div class="flex flex-col gap-2">
                <a href="{{ route('dashboard') }}" class="flex h-[42px] items-center justify-center gap-2 rounded-lg bg-foreground text-sm font-medium text-white hover:bg-gray-800">Back to dashboard <x-icon name="arrow-right" class="size-4" /></a>
                <a href="{{ route('seed.applications.show', $application) }}" class="flex h-[42px] items-center justify-center rounded-lg border border-input bg-card text-sm font-medium text-foreground hover:bg-muted">Open my application</a>
            </div>
        </div>
    </div>
</div>
@endsection
