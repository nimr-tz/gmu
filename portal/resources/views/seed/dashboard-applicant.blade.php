@extends('layouts.app')

@php
    use App\Support\SeedGrant as S;
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $first = \Illuminate\Support\Str::of($user->name)->replaceMatches('/^(Dr\.?|Prof\.?|Mr\.?|Ms\.?|Mrs\.?)\s+/i', '')->before(' ');
    $round = $application?->latestRound();
    $closed = fn ($date) => $date && $date->copy()->endOfDay()->isPast();
@endphp

@section('title', 'Dashboard')
@section('context', 'Home · Dashboard')

@section('content')
<x-page-header :eyebrow="now()->format('l, j F Y')" :title="$greeting . ', ' . $first . '.'">
    @if($call && $call->isVisible())
        <x-slot:actions><x-button :href="route('seed.calls.show', $call)" icon="megaphone">Read the call</x-button></x-slot:actions>
    @endif
</x-page-header>

@if(! $call || ! $call->isVisible())
    <x-card><x-empty icon="megaphone" title="No call is open yet">When NIMR opens a call, it appears here with its deadlines and how to apply.</x-empty></x-card>
@elseif(! $application)
    <section class="{{ $call->tintClass() }} rounded-xl border border-tint-line bg-tint p-6 sm:p-8">
        <div class="max-w-2xl">
            <x-badge :tone="$call->acceptingConceptNotes() ? 'ok' : 'neutral'" dot>{{ $call->acceptingConceptNotes() ? 'Open for concept notes' : 'Concept notes closed' }}</x-badge>
            <h2 class="mt-4 text-2xl">{{ $call->title }}</h2>
            <p class="mt-2 text-[15px] leading-relaxed text-gray-700">
                TZS {{ number_format($call->min_amount / 1e6) }}M–{{ number_format($call->max_amount / 1e6) }}M for a {{ $call->duration_months }}-month study.
                @if($call->concept_deadline) Concept notes {{ $closed($call->concept_deadline) ? 'closed' : 'close' }} {{ $call->concept_deadline->format('j M Y') }}.@endif
            </p>
            <div class="mt-6 flex flex-wrap gap-2">
                <x-button variant="primary" size="lg" :href="route('seed.calls.show', $call)" icon-right="arrow-right">{{ $call->acceptingConceptNotes() && ! $problems ? 'Read the call and apply' : 'Read the call' }}</x-button>
                @if($reviewing)<x-button size="lg" :href="route('seed.reviews.index')" icon="message-square">My reviews</x-button>@endif
            </div>
        </div>
    </section>
@else
    <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-5">
            @if($application->isRevising() && $round)
                <x-alert tone="warn" icon="pencil" title="Reviewers asked you to revise">
                    <p class="mb-1 font-medium">Round {{ $round->number }} · your response is due {{ $round->response_due_at?->format('j M Y') }}</p>
                    @if($round->decision_note)<p class="whitespace-pre-line">{{ $round->decision_note }}</p>@endif
                    <div class="mt-3 flex flex-wrap gap-2">
                        <x-button variant="primary" size="sm" :href="route('seed.applications.edit', $application)">Respond to reviewers</x-button>
                        <x-button size="sm" :href="route('seed.applications.show', $application)">Read their comments</x-button>
                    </div>
                </x-alert>
            @elseif(in_array($application->status, [S::DRAFT, S::SHORTLISTED], true))
                @php $short = $application->status === S::SHORTLISTED; @endphp
                <x-alert :tone="$short ? 'ok' : 'info'" :icon="$short ? 'star' : 'pencil'" :title="$short ? 'You are shortlisted: write your full proposal' : 'Finish and submit your concept note'">
                    {{ $short
                        ? ($call->proposal_deadline ? 'Due ' . $call->proposal_deadline->format('j M Y') . '. ' : '') . 'You will also need the mentor’s commitment letter and your CV.'
                        : ($call->concept_deadline ? 'Concept notes close ' . $call->concept_deadline->format('j M Y') . '.' : 'It saves when you press Save.') }}
                    <div class="mt-3"><x-button variant="primary" size="sm" :href="route('seed.applications.edit', $application)">{{ $short ? 'Write full proposal' : 'Continue writing' }}</x-button></div>
                </x-alert>
            @elseif($application->status === S::AGREEMENT_ISSUED)
                <x-alert tone="warn" icon="pen-line" title="Your grant agreement is ready">
                    Read it and accept it online.
                    <div class="mt-3"><x-button variant="primary" size="sm" :href="route('seed.applications.agreement', $application)">Open the agreement</x-button></div>
                </x-alert>
            @endif

            @include('seed.partials.applicant-figures')

            <x-card title="Where your application is" :description="$application->reference . ' · ' . $application->call->title">
                <x-slot:actions><x-button size="sm" :href="route('seed.applications.show', $application)" icon-right="arrow-right">Open my application</x-button></x-slot:actions>
                <div class="mb-5 flex flex-wrap items-center gap-2.5">
                    @include('seed.partials.call-tag', ['call' => $application->call, 'short' => true])
                    <h2 class="min-w-0 text-[17px] font-semibold leading-snug">{{ $application->title ?: 'Untitled concept note' }}</h2>
                </div>
                @include('seed.partials.track')
            </x-card>
        </div>

        <aside class="space-y-4">
            <x-card title="Key dates" icon="calendar">
                <ul class="divide-y divide-border text-sm">
                    <li class="flex justify-between gap-3 pb-2.5"><span class="text-muted-foreground">Concept notes close</span><span class="font-medium tabular-nums">{{ $call->concept_deadline?->format('j M Y') ?? 'To be announced' }}</span></li>
                    @if($application->isRevising() && $round?->response_due_at)
                        <li class="flex justify-between gap-3 py-2.5"><span class="font-medium text-warn-ink">Your response is due</span><span class="font-semibold tabular-nums text-warn-ink">{{ $round->response_due_at->format('j M Y') }}</span></li>
                    @endif
                    <li class="flex justify-between gap-3 pt-2.5"><span class="text-muted-foreground">Full proposals due</span><span class="font-medium tabular-nums">{{ $call->proposal_deadline?->format('j M Y') ?? 'To be announced' }}</span></li>
                </ul>
            </x-card>

            @if($application->mentor_name)
                <x-card title="Your mentor">
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-primary-soft text-sm font-semibold text-ok-ink">{{ collect(explode(' ', preg_replace('/^(Dr\.?|Prof\.?)\s+/i', '', $application->mentor_name)))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') }}</span>
                        <span class="min-w-0 leading-snug"><span class="block font-medium">{{ $application->mentor_name }}</span><span class="block text-[13px] text-muted-foreground">{{ collect([$application->mentor_designation, $application->mentor_centre])->filter()->implode(' · ') }}</span></span>
                    </div>
                    @if(in_array($application->status, S::CONCEPT_STATUSES, true))
                        <p class="mt-4 text-[13px] text-muted-foreground">A signed commitment letter is needed only if you are shortlisted.</p>
                    @endif
                </x-card>
            @endif

            @if($reviewing)
                <x-button class="w-full" :href="route('seed.reviews.index')" icon="message-square">You also have reviews to do</x-button>
            @endif
        </aside>
    </div>
@endif
@endsection
