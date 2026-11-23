@extends('layouts.app')

{{--
    My reviews, from the owner's "Reviews" design: an emerald band; to do, next due
    and done; the reviews to do, soonest due first; earlier reviews by call; and how
    full proposals are scored, for each call this reviewer works in.
--}}

@php
    use App\Support\SeedGrant as S;
    $rec = ['accept' => 'bg-blue-50 text-blue-700', 'revise' => 'bg-warn-soft text-warn-ink', 'reject' => 'bg-danger-soft text-danger-ink'];
    $dueOf = fn ($a) => $a->application->openRound($a->stage)?->due_at;
    $todo = $todo->sortBy(fn ($a) => $dueOf($a)?->timestamp ?? PHP_INT_MAX)->values();
    $nextDue = $todo->map($dueOf)->filter()->sort()->first();
    $nextDays = $nextDue ? (int) now()->startOfDay()->diffInDays($nextDue->copy()->startOfDay(), false) : null;
@endphp

@section('title', 'My reviews')
@section('context', 'Review panel · My reviews')
@section('topbar-colour', 'bg-emerald-700')
@section('own-flash', '1')

@section('content')
<div class="-mx-4 -mt-5 flex flex-col gap-2 bg-emerald-700 px-7 pb-[100px] pt-5 text-white lg:-mx-6 lg:-mt-6">
    <p class="text-[13px] text-emerald-200">{{ now()->format('l, j F Y') }}</p>
    <h1 class="text-[30px] font-semibold leading-tight tracking-[-0.025em] text-white">My reviews</h1>
    <p class="text-[15px] text-emerald-100">You review anonymously. Applicants read your comments, never your name.</p>
</div>

<div class="-mt-[72px] flex flex-col gap-5">
    {{-- Three figures --}}
    <div class="grid gap-4 md:grid-cols-3">
        <div class="flex flex-col gap-2.5 rounded-[14px] border p-5 shadow-lift {{ $todo->isNotEmpty() ? 'border-warn bg-warn text-amber-950' : 'border-border bg-card' }}">
            <span class="text-[13.5px] font-semibold">To do</span>
            <span class="text-[30px] font-semibold leading-tight tracking-[-0.02em] tabular-nums">{{ $todo->count() }}</span>
            <span class="text-[13px] {{ $todo->isNotEmpty() ? 'text-amber-900' : 'text-muted-foreground' }}">{{ $todo->isEmpty() ? 'Nothing is waiting on you' : str('Review')->plural($todo->count()) . ' waiting on you' }}</span>
        </div>
        <div class="flex flex-col gap-2.5 rounded-[14px] border border-border bg-card p-5 shadow-lift">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[13.5px] font-medium">Next due</span>
                @if($nextDue)<span class="inline-flex h-[22px] shrink-0 items-center whitespace-nowrap rounded-md px-2 text-xs font-medium {{ $nextDays < 0 ? 'bg-danger-soft text-danger-ink' : 'bg-sky-100 text-sky-700' }}">{{ $nextDays < 0 ? 'Late' : ($nextDays === 0 ? 'Today' : 'In ' . $nextDays . ' ' . str('day')->plural($nextDays)) }}</span>@endif
            </div>
            <span class="text-[30px] font-semibold leading-tight tracking-[-0.02em] tabular-nums {{ $nextDue && $nextDays < 0 ? 'text-danger-ink' : 'text-sky-700' }}">{{ $nextDue ? $nextDue->format('j M Y') : '—' }}</span>
            <span class="text-[13px] text-muted-foreground">{{ $nextDue ? 'The end of that day, East Africa Time' : 'No review has a due date yet' }}</span>
        </div>
        <div class="flex flex-col gap-2.5 rounded-[14px] border border-border bg-card p-5 shadow-lift">
            <span class="text-[13.5px] font-medium">Done</span>
            <span class="text-[30px] font-semibold leading-tight tracking-[-0.02em] tabular-nums text-emerald-800">{{ $done->count() }}</span>
            <span class="text-[13px] text-muted-foreground">{{ str('Review')->plural($done->count()) }} you have submitted</span>
        </div>
    </div>

    @if(session('success'))
        <div role="status" x-data="{ shown: true }" x-show="shown" class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3.5 text-emerald-800">
            <span class="grid size-[22px] shrink-0 place-items-center rounded-full bg-emerald-700 text-white"><x-icon name="check" class="size-3" /></span>
            <span class="flex-1 text-[13.5px] font-medium">{{ session('success') }}</span>
            <button type="button" @click="shown = false" class="text-[13px] font-medium hover:underline">Dismiss</button>
        </div>
    @endif

    <div class="flex flex-wrap items-start gap-5">
        <div class="flex min-w-0 flex-[1_1_600px] flex-col gap-5">
            {{-- To do --}}
            <section class="overflow-hidden rounded-[14px] border border-border">
                <header class="border-b border-border bg-muted px-[18px] py-3"><h2 class="text-sm font-semibold">To do</h2><p class="text-[13px] text-muted-foreground">Soonest due first</p></header>
                @if($todo->isEmpty())
                    <div class="flex flex-col items-center gap-1.5 bg-card px-5 py-10 text-center">
                        <span class="grid size-10 place-items-center rounded-xl bg-emerald-50 text-emerald-700"><x-icon name="check-circle" class="size-5" /></span>
                        <p class="text-[14.5px] font-semibold">Nothing to review right now</p>
                        <p class="text-[13px] text-muted-foreground">When GMU assigns you a concept note or proposal, it appears here with its due date.</p>
                    </div>
                @else
                    <ul class="bg-card">
                        @foreach($todo as $a)
                            @php
                                $app = $a->application;
                                $round = $app->openRound($a->stage);
                                $due = $round?->due_at;
                                // Late (rose) only once the due day has ended; amber in the last three days before.
                                $late = $due && $due->copy()->endOfDay()->isPast();
                                $soon = ! $late && $due && now()->diffInDays($due->copy()->endOfDay(), false) < 3;
                                $draft = $round?->reviews->firstWhere('seed_reviewer_assignment_id', $a->id);
                                $pending = $a->coi_status === 'pending';
                                [$flag, $flagTone] = match (true) {
                                    $pending => ['Declare interests first', 'bg-danger-soft text-danger-ink'],
                                    (bool) $draft => ['Draft saved', 'bg-warn-soft text-warn-ink'],
                                    $round && $round->number > 1 => ['Applicant responded', 'bg-violet-50 text-violet-700'],
                                    default => [null, ''],
                                };
                            @endphp
                            <li class="flex flex-wrap items-center gap-4 border-b border-gray-100 px-[18px] py-4 last:border-b-0">
                                <span class="w-14 shrink-0 rounded-[10px] py-2 text-center leading-none {{ $late ? 'bg-danger-soft text-danger-ink' : ($soon ? 'bg-warn-soft text-warn-ink' : 'bg-gray-100') }}">
                                    <span class="block text-xl font-semibold tabular-nums">{{ $due?->format('j') ?? '—' }}</span>
                                    <span class="mt-1 block text-[10.5px] font-medium uppercase tracking-[0.06em] {{ $late || $soon ? '' : 'text-muted-foreground' }}">{{ $due?->format('M') }}</span>
                                </span>
                                <div class="flex min-w-0 flex-[1_1_300px] flex-col gap-[5px]">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @include('seed.partials.call-tag', ['call' => $app->call, 'short' => true])
                                        <span class="font-mono text-xs text-muted-foreground">{{ $app->reference }}</span>
                                        <span class="inline-flex h-[22px] items-center whitespace-nowrap rounded-md bg-gray-100 px-2 text-xs font-medium text-gray-700">{{ $a->stage === 'concept' ? 'Concept note' : 'Full proposal' }}{{ $round ? ' · round ' . $round->number : '' }}</span>
                                        @if($flag)<span class="inline-flex h-[22px] items-center whitespace-nowrap rounded-md px-2 text-xs font-medium {{ $flagTone }}">{{ $flag }}</span>@endif
                                    </div>
                                    <p class="text-[14.5px] font-semibold leading-snug {{ $pending ? 'text-muted-foreground' : 'text-foreground' }}">{{ $pending ? 'Title hidden until you declare' : $app->title }}</p>
                                    @if($due)<p class="text-[13px] {{ $late ? 'text-danger-ink' : ($soon ? 'text-warn-ink' : 'text-muted-foreground') }}">Due {{ $due->format('j M Y') }} ({{ $due->copy()->endOfDay()->diffForHumans() }})</p>@endif
                                </div>
                                <a href="{{ route('seed.reviews.show', $a) }}" class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-[7px] px-3.5 text-[13px] font-medium {{ $pending ? 'border border-input bg-card text-foreground hover:bg-muted' : 'bg-foreground text-white hover:bg-gray-800' }}">{{ $pending ? 'Declare' : ($draft ? 'Continue review' : 'Start review') }}</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Earlier reviews, by call --}}
            @if($done->isNotEmpty())
                @php $byCall = $done->groupBy(fn ($a) => $a->application->seed_call_id); @endphp
                <section class="overflow-hidden rounded-[14px] border border-border">
                    <header class="border-b border-border bg-muted px-[18px] py-3"><h2 class="text-sm font-semibold">Earlier reviews</h2><p class="text-[13px] text-muted-foreground">{{ $done->count() }} submitted, by call</p></header>
                    @foreach($byCall as $items)
                        @php $c = $items->first()->application->call; @endphp
                        <div x-data="{ all: false }" class="bg-card">
                            <div class="{{ $c->tintClass() }} flex items-center justify-between gap-3 border-b border-tint-line bg-tint px-[18px] py-2">
                                @include('seed.partials.call-tag', ['call' => $c])
                                <span class="shrink-0 text-xs text-muted-foreground">{{ $items->count() }} {{ str('review')->plural($items->count()) }}</span>
                            </div>
                            <ul>
                                @foreach($items as $a)
                                    @php $last = $a->application->rounds->where('stage', $a->stage)->flatMap->reviews->where('seed_reviewer_assignment_id', $a->id)->whereNotNull('submitted_at')->sortByDesc('submitted_at')->first(); @endphp
                                    <li @if($loop->index >= 5) x-show="all" x-cloak @endif>
                                        <a href="{{ route('seed.reviews.show', $a) }}" class="flex flex-wrap items-center gap-3 border-b border-gray-100 px-[18px] py-3 hover:bg-muted">
                                            <span class="min-w-0 flex-1 leading-snug">
                                                <span class="block truncate text-sm font-medium {{ $a->coi_status === 'conflict' ? 'text-muted-foreground' : 'text-foreground' }}">{{ $a->coi_status === 'conflict' ? 'Conflict declared' : $a->application->title }}</span>
                                                <span class="block text-[13px] text-muted-foreground"><span class="font-mono text-xs">{{ $a->application->reference }}</span> · {{ $a->stage === 'proposal' ? 'Full proposal' : 'Concept note' }}</span>
                                            </span>
                                            @if($last)
                                                <span class="inline-flex h-[22px] shrink-0 items-center whitespace-nowrap rounded-md px-2 text-xs font-medium {{ $rec[$last->recommendation] ?? 'bg-gray-100 text-gray-700' }}">{{ \App\Models\Seed\SeedReview::RECOMMENDATIONS[$last->recommendation] }}</span>
                                                <span class="w-[84px] text-right text-[13px] text-muted-foreground">{{ $last->submitted_at->format('j M Y') }}</span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                            @if($items->count() > 5)
                                <button type="button" class="w-full border-b border-gray-100 px-[18px] py-2 text-left text-[13px] font-medium text-emerald-700 hover:bg-muted" @click="all = ! all"
                                        x-text="all ? 'Show fewer' : 'Show all {{ $items->count() }}'">Show all {{ $items->count() }}</button>
                            @endif
                        </div>
                    @endforeach
                </section>
            @endif
        </div>

        {{-- How full proposals are scored, for each call this reviewer works in --}}
        @if($guideCalls->isNotEmpty())
            <aside class="min-w-[280px] flex-[0_1_340px] xl:sticky xl:top-20">
                <section class="overflow-hidden rounded-[14px] border border-border" x-data="{ shown: {{ $guideCalls->first()->id }} }">
                    <header class="border-b border-border bg-muted px-4 py-3"><h3 class="text-sm font-semibold">How full proposals are scored</h3><p class="text-[13px] text-muted-foreground">Concept notes get accept, revise or reject only</p></header>
                    <div class="bg-card p-4">
                        @if($guideCalls->count() > 1)
                            <div class="mb-3.5 flex flex-wrap gap-1.5" role="tablist" aria-label="Call">
                                @foreach($guideCalls as $c)
                                    <button type="button" role="tab" class="{{ $c->tintClass() }} h-[26px] rounded-[7px] px-[9px] font-mono text-xs font-medium ring-1 ring-inset transition-colors"
                                            :class="shown === {{ $c->id }} ? 'bg-tint-chip text-tint-ink ring-transparent' : 'bg-card text-muted-foreground ring-border hover:text-foreground'"
                                            :aria-selected="shown === {{ $c->id }}" @click="shown = {{ $c->id }}" title="{{ $c->title }}">{{ $c->code }}</button>
                                @endforeach
                            </div>
                        @endif
                        @foreach($guideCalls as $c)
                            @php $form = $c->form(); $top = max(array_column($form->criteria(), 2) ?: [1]) ?: 1; @endphp
                            <div x-show="shown === {{ $c->id }}" @unless($loop->first) x-cloak @endunless>
                                <p class="text-[13px] font-medium">{{ $c->title }}</p>
                                @if($form->scored())
                                    <p class="mb-3.5 mt-0.5 text-xs text-muted-foreground">Weighted scoring starts at the full proposal.</p>
                                    <ul class="flex flex-col gap-3 text-[13px]">
                                        @foreach($form->criteria() as $key => [$name, , $max])
                                            @php $hasMin = isset($form->minimums()[$key]); @endphp
                                            <li>
                                                <div class="flex justify-between gap-3"><span class="{{ $hasMin ? 'font-semibold' : '' }}">{{ $name }}</span><span class="font-mono">{{ S::trimNumber($max) }}</span></div>
                                                <div class="mt-1.5 h-1.5 rounded-full bg-gray-100"><div class="h-full rounded-full {{ $hasMin ? 'bg-emerald-700' : 'bg-gray-300' }}" style="width: {{ $max / $top * 100 }}%"></div></div>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <div class="mt-4 rounded-[10px] bg-emerald-50 p-3.5 text-[13px] leading-normal text-emerald-900">
                                        Fundable at <strong class="font-mono">{{ S::trimNumber($form->passMark()) }}/{{ S::trimNumber($form->maxTotal()) }}</strong> or more{{ $form->minimumsPhrase(', and never below ', ' on ') }}. Don’t mark down a strong study for having no innovation.
                                    </div>
                                @else
                                    <p class="mt-1 text-[13px] text-muted-foreground">This call has no scored review: full proposals get accept, revise or reject too.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            </aside>
        @endif
    </div>
</div>
@endsection
