@extends('layouts.app')

@section('title', 'My reviews')
@section('context', 'Review panel · My reviews')

@php
    use App\Support\SeedGrant as S;
    $tone = ['accept' => 'ok', 'revise' => 'warn', 'reject' => 'danger'];
    $top = max(array_column($form->criteria(), 2) ?: [1]) ?: 1;
@endphp

@section('content')
<x-page-header title="My reviews" description="You review anonymously. Applicants read your comments, never your name." />

@php
    $nextDue = $todo->map(fn ($a) => $a->application->openRound($a->stage)?->due_at)->filter()->sort()->first();
    $nextDays = $nextDue ? (int) now()->startOfDay()->diffInDays($nextDue->copy()->startOfDay(), false) : null;
@endphp
<div class="mb-5 grid gap-3 sm:grid-cols-3">
    <x-kpi :tone="$todo->isEmpty() ? 'neutral' : 'amber'" title="To do" :value="$todo->count()"
        :note="$todo->isEmpty() ? 'Nothing is waiting on you' : str('Review')->plural($todo->count()) . ' waiting on you'" />
    <x-kpi :tone="$nextDue && $nextDays < 0 ? 'rose' : 'sky'" title="Next due" :value="$nextDue ? $nextDue->format('j M Y') : '—'"
        :badge="$nextDue ? ($nextDays < 0 ? 'Late' : ($nextDays === 0 ? 'Today' : 'In ' . $nextDays . ' ' . str('day')->plural($nextDays))) : null"
        :note="$nextDue ? 'The end of that day, East Africa Time' : 'No review has a due date yet'" />
    <x-kpi tone="blue" title="Done" :value="$done->count()" :note="str('Review')->plural($done->count()) . ' you have submitted'" />
</div>

<div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
    <div class="space-y-5">
        <x-card title="To do" flush>
            <ul class="divide-y divide-border">
                @forelse($todo as $a)
                    @php
                        $app = $a->application;
                        $round = $app->openRound($a->stage);
                        $due = $round?->due_at;
                        // Late (red) only once the due day has ended; amber in the last three days before.
                        $late = $due && $due->copy()->endOfDay()->isPast();
                        $soon = ! $late && $due && now()->diffInDays($due->copy()->endOfDay(), false) < 3;
                        $dueTone = $late ? 'bg-danger-soft text-danger-ink' : ($soon ? 'bg-warn-soft text-warn-ink' : 'bg-muted');
                        $draft = $round?->reviews->firstWhere('seed_reviewer_assignment_id', $a->id);
                    @endphp
                    <li class="flex flex-wrap items-center gap-4 px-5 py-4">
                        <div class="w-14 shrink-0 rounded-lg py-2 text-center {{ $dueTone }}">
                            <div class="text-xl font-semibold leading-none tabular-nums">{{ $due?->format('j') ?? '—' }}</div>
                            <div class="mt-1 text-[10px] font-medium uppercase tracking-wider {{ $late || $soon ? '' : 'text-muted-foreground' }}">{{ $due?->format('M') }}</div>
                        </div>
                        <div class="min-w-0 flex-[1_1_18rem]">
                            <div class="flex flex-wrap items-center gap-2">
                                @include('seed.partials.call-tag', ['call' => $app->call, 'short' => true])
                                <span class="font-mono text-xs text-muted-foreground">{{ $app->reference }}</span>
                                <x-badge>{{ $a->stage === 'concept' ? 'Concept note' : 'Full proposal' }}{{ $round ? ' · round ' . $round->number : '' }}</x-badge>
                                @if($a->coi_status === 'pending')<x-badge tone="danger">Declare interests first</x-badge>
                                @elseif($draft)<x-badge tone="warn">Draft saved</x-badge>
                                @elseif($round && $round->number > 1)<x-badge tone="review">Applicant responded</x-badge>@endif
                            </div>
                            <p class="mt-1.5 font-medium">{{ $a->coi_status === 'pending' ? 'Title hidden until you declare' : $app->title }}</p>
                            @if($due)<p class="mt-0.5 text-[13px] {{ $late ? 'text-danger-ink' : ($soon ? 'text-warn-ink' : 'text-muted-foreground') }}">Due {{ $due->format('j M Y') }} ({{ $due->copy()->endOfDay()->diffForHumans() }})</p>@endif
                        </div>
                        <x-button :variant="$a->coi_status === 'pending' ? 'secondary' : 'primary'" :href="route('seed.reviews.show', $a)">
                            {{ $a->coi_status === 'pending' ? 'Declare' : ($draft ? 'Continue review' : 'Start review') }}
                        </x-button>
                    </li>
                @empty
                    <li><x-empty icon="check-circle" title="Nothing to review right now" compact>When GMU assigns you a concept note or proposal, it appears here with its due date.</x-empty></li>
                @endforelse
            </ul>
        </x-card>

        @if($done->isNotEmpty())
            <x-card title="Earlier reviews" flush>
                <ul class="divide-y divide-border">
                    @foreach($done as $a)
                        @php $last = $a->application->rounds->where('stage', $a->stage)->flatMap->reviews->where('seed_reviewer_assignment_id', $a->id)->whereNotNull('submitted_at')->sortByDesc('submitted_at')->first(); @endphp
                        <li>
                            <a href="{{ route('seed.reviews.show', $a) }}" class="flex flex-wrap items-center gap-3 px-5 py-3.5 hover:bg-muted/50">
                                @include('seed.partials.call-tag', ['call' => $a->application->call, 'short' => true])
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-medium">{{ $a->coi_status === 'conflict' ? 'Conflict declared' : $a->application->title }}</span>
                                    <span class="block text-[13px] text-muted-foreground"><span class="font-mono text-xs">{{ $a->application->reference }}</span> · {{ $a->stage === 'proposal' ? 'Full proposal' : 'Concept note' }}</span>
                                </span>
                                @if($last)
                                    <x-badge :tone="$tone[$last->recommendation] ?? 'neutral'">{{ \App\Models\Seed\SeedReview::RECOMMENDATIONS[$last->recommendation] }}</x-badge>
                                    <span class="w-14 text-right text-[13px] text-muted-foreground">{{ $last->submitted_at->format('j M') }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endif
    </div>

    <x-card title="How full proposals are scored" description="Concept notes get accept, revise or reject only. Weighted scoring starts at stage 2." class="xl:sticky xl:top-24">
        <ul class="space-y-3 text-[13px]">
            @foreach($form->criteria() as $key => [$name, , $max])
                @php $hasMin = isset($form->minimums()[$key]); @endphp
                <li>
                    <div class="flex justify-between gap-3"><span class="{{ $hasMin ? 'font-medium' : '' }}">{{ $name }}</span><span class="font-mono tabular-nums">{{ S::trimNumber($max) }}</span></div>
                    <x-meter class="mt-1.5" :value="$max / $top * 100" :tone="$hasMin ? 'primary' : 'muted'" :label="$name" />
                </li>
            @endforeach
        </ul>
        <div class="mt-5 rounded-lg bg-primary-soft p-3.5 text-[13px] text-foreground">
            Fundable at <strong class="font-mono">{{ S::trimNumber($form->passMark()) }}/{{ S::trimNumber($form->maxTotal()) }}</strong> or more{{ $form->minimumsPhrase(', and never below ', ' on ') }}. Don’t mark down a strong study for having no innovation.
        </div>
    </x-card>
</div>
@endsection
