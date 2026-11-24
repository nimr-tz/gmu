@extends('layouts.app')

{{--
    The applicant's dashboard, from the owner's designs:
      2a  with applications: what needs them, one card per application in its call's
          colour, key dates across their calls, and the money.
      3a  before they apply: the calls open for concept notes, how applying works,
          the dates, and calls that have closed.
--}}

@php
    use App\Support\ApplicantView as V;
    use App\Support\SeedGrant as S;
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $first = \Illuminate\Support\Str::of($user->name)->replaceMatches('/^(Dr\.?|Prof\.?|Mr\.?|Ms\.?|Mrs\.?)\s+/i', '')->before(' ');
    $words = fn ($n) => [0 => 'No', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five'][$n] ?? (string) $n;
    $m = fn ($n) => 'TZS ' . rtrim(rtrim(number_format($n / 1e6, 2), '0'), '.') . 'M';
    $chip = fn ($date) => $date ? [$date->format('j'), $date->format('M')] : ['—', ''];
    // A date's right-hand label: "In N days" when near, the year when far off.
    $label = fn ($date) => ($d = V::daysTo($date)) !== null && $d <= 60 ? V::when($date) : $date?->format('Y');
@endphp

@section('title', 'Dashboard')
@section('context', 'Home · Dashboard')
@section('topbar-colour', 'bg-primary')

@section('content')
@if($applications->isEmpty())
    {{-- ───────────── 3a: before they apply ───────────── --}}
    <div class="-mx-4 -mt-5 flex flex-wrap items-end justify-between gap-6 bg-primary px-7 pb-[104px] pt-5 text-white lg:-mx-6 lg:-mt-6">
        <div class="flex flex-col gap-2">
            <p class="text-[13px] text-blue-200">{{ now()->format('l, j F Y') }}</p>
            <h1 class="text-[30px] font-semibold tracking-[-0.025em] text-white">{{ $greeting }}, {{ $first }}.</h1>
            <p class="text-[15px] text-blue-100">
                @if($openCalls->isEmpty())
                    No call is open for concept notes right now.
                @else
                    {{ $words($openCalls->count()) }} {{ $openCalls->count() === 1 ? 'call is' : 'calls are' }} open for concept notes.
                    {{ $openCalls->count() === 1 ? 'It closes' : 'The first closes' }} on {{ $openCalls->first()->concept_deadline?->format('j M Y') ?? 'a date to be announced' }}.
                @endif
            </p>
        </div>
        <a href="{{ route('seed.calls.index') }}" class="inline-flex h-9 items-center gap-1.5 rounded-[7px] border border-white/25 bg-white/10 px-3.5 text-[13px] font-medium text-white hover:bg-white/20"><x-icon name="megaphone" class="size-4" />All calls</a>
    </div>

    <div class="-mt-20 flex flex-col gap-6">
        @if($openCalls->isEmpty())
            <section class="rounded-2xl border border-border bg-card shadow-lift">
                <x-empty icon="megaphone" title="No call is open yet">When NIMR opens a call, it appears here with its deadlines and how to apply.</x-empty>
            </section>
        @else
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach($openCalls as $call)
                    @php $form = $call->form(); $left = V::daysTo($call->concept_deadline); $elapsed = $call->conceptWindowElapsed(); @endphp
                    <section class="{{ $call->tintClass() }} flex flex-col overflow-hidden rounded-2xl border border-border bg-card shadow-lift">
                        <div class="flex flex-col gap-3 bg-tint-ink px-[22px] py-5 text-white">
                            <div class="flex items-center gap-2">
                                <span class="rounded-md bg-white/15 px-1.5 py-0.5 font-mono text-xs font-medium">{{ $call->code }}</span>
                                <span class="text-[12.5px] text-white/75">{{ $call->fiscal_year }}</span>
                                <span class="flex-1"></span>
                                <span class="inline-flex h-[22px] shrink-0 items-center gap-1.5 whitespace-nowrap rounded-md bg-white px-2 text-xs font-medium text-blue-700"><span class="size-1.5 rounded-full bg-primary"></span>Open for concept notes</span>
                            </div>
                            <h2 class="text-pretty text-[21px] font-semibold leading-tight tracking-[-0.015em] text-white">{{ $call->title }}</h2>
                        </div>
                        <div class="flex flex-1 flex-col gap-[18px] px-[22px] py-5">
                            @if($call->summary)<p class="text-pretty text-sm leading-relaxed text-gray-700">{{ $call->summary }}</p>@endif
                            @if($line = collect($form->eligibilityLines())->first())
                                <p class="-mt-2 text-[13px] leading-normal text-muted-foreground"><b class="font-medium text-gray-700">Who can apply:</b> {{ $line }}</p>
                            @endif
                            <div class="grid grid-cols-3 overflow-hidden rounded-xl border border-border">
                                <div class="flex flex-col gap-0.5 px-3.5 py-3"><span class="text-xs text-muted-foreground">Award</span><span class="font-mono text-[15px] font-medium">{{ $m($call->min_amount) }}–{{ $m($call->max_amount) }}</span></div>
                                <div class="flex flex-col gap-0.5 border-l border-border px-3.5 py-3"><span class="text-xs text-muted-foreground">Study length</span><span class="font-mono text-[15px] font-medium">{{ $call->duration_months }} months</span></div>
                                <div class="flex flex-col gap-0.5 border-l border-border bg-tint px-3.5 py-3 text-tint-ink"><span class="text-xs">Concept notes close</span><span class="font-mono text-[15px] font-medium">{{ $call->concept_deadline?->format('j M Y') ?? 'To be announced' }}</span></div>
                            </div>
                            @if($left !== null)
                                <div class="flex flex-col gap-1.5">
                                    <div class="flex justify-between text-[12.5px]"><span class="text-muted-foreground">Time left to send a concept note</span><span class="font-semibold text-tint-ink">{{ $left === 0 ? 'Today' : $left . ' ' . str('day')->plural($left) }}</span></div>
                                    @if($elapsed !== null)
                                        <div class="h-1.5 rounded-full bg-tint-chip" role="progressbar" aria-valuenow="{{ round($elapsed) }}" aria-valuemin="0" aria-valuemax="100" aria-label="Share of the concept-note window gone"><div class="h-full rounded-full bg-tint-solid" style="width: {{ $elapsed }}%"></div></div>
                                    @endif
                                </div>
                            @endif
                            <div class="mt-auto flex gap-2">
                                <a href="{{ route('seed.calls.show', $call) }}" class="inline-flex h-10 items-center gap-1.5 rounded-[7px] bg-foreground px-4 text-sm font-medium text-white hover:bg-gray-800">Read the call and apply <x-icon name="arrow-right" class="size-4" /></a>
                            </div>
                        </div>
                    </section>
                @endforeach
            </div>
        @endif

        {{-- How applying works --}}
        <section class="overflow-hidden rounded-[14px] border border-border">
            <header class="border-b border-border bg-muted px-4 py-3"><h3 class="text-sm font-semibold">How applying works</h3><p class="text-[13px] text-muted-foreground">Eight steps, the same for every call. You start with a short concept note.</p></header>
            <ol class="grid grid-cols-4 gap-x-2 gap-y-5 bg-card px-5 py-[22px] lg:grid-cols-8">
                @foreach(S::TRACK as $i => [$title, $sub])
                    <li>
                        <div class="flex items-center gap-1.5">
                            <span class="grid size-[26px] shrink-0 place-items-center rounded-full font-mono text-[11px] font-medium {{ $i === 0 ? 'bg-foreground text-white' : ($loop->last ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-700') }}">{{ $i + 1 }}</span>
                            <span class="h-0.5 flex-1 rounded-full bg-border {{ $loop->last ? 'invisible' : '' }}"></span>
                        </div>
                        <p class="mt-2.5 text-[13px] {{ $i === 0 ? 'font-semibold' : 'font-medium' }}">{{ $title }}</p>
                        <p class="mt-0.5 text-xs text-muted-foreground">{{ $sub }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        <div class="grid items-start gap-4 lg:grid-cols-2">
            <section class="overflow-hidden rounded-[14px] border border-border">
                <header class="border-b border-border bg-muted px-4 py-3"><h3 class="text-sm font-semibold">Key dates</h3><p class="text-[13px] text-muted-foreground">Open calls, East Africa Time, end of the day</p></header>
                <ol class="bg-card px-4 py-2">
                    @php
                        $dates = $openCalls->flatMap(fn ($c) => array_filter([
                            $c->concept_deadline ? [$c->concept_deadline, 'Concept notes close', $c, true] : null,
                            $c->proposal_deadline ? [$c->proposal_deadline, 'Full proposals due', $c, false] : null,
                        ]))->sortBy(fn ($d) => $d[0])->values();
                    @endphp
                    @forelse($dates as [$date, $what, $c, $strong])
                        @php [$day, $mon] = $chip($date); @endphp
                        <li class="{{ $c->tintClass() }} flex items-center gap-3 py-2.5 {{ $loop->last ? '' : 'border-b border-gray-100' }}">
                            <span class="w-11 shrink-0 rounded-lg py-1.5 text-center leading-tight {{ $strong ? 'bg-tint text-tint-ink' : 'bg-gray-100' }}"><span class="block text-[17px] font-semibold">{{ $day }}</span><span class="block text-[10.5px] uppercase tracking-[0.06em] {{ $strong ? '' : 'text-muted-foreground' }}">{{ $mon }}</span></span>
                            <span class="min-w-0 flex-1 leading-snug"><span class="block text-[13.5px] {{ $strong ? 'font-semibold' : '' }}">{{ $what }}</span><span class="flex items-center gap-1.5 text-xs text-muted-foreground"><span class="size-1.5 rounded-full bg-tint-solid"></span>{{ $c->code }}{{ $strong ? '' : ' · if shortlisted' }}</span></span>
                            <span class="text-xs {{ $strong ? 'font-medium text-tint-ink' : 'text-muted-foreground' }}">{{ $label($date) }}</span>
                        </li>
                    @empty
                        <li class="py-6 text-center text-[13px] text-muted-foreground">No dates yet. They appear when NIMR opens a call.</li>
                    @endforelse
                </ol>
            </section>
            <section class="overflow-hidden rounded-[14px] border border-border">
                <header class="border-b border-border bg-muted px-4 py-3"><h3 class="text-sm font-semibold">Closed calls</h3><p class="text-[13px] text-muted-foreground">No longer taking concept notes</p></header>
                <ul class="bg-card px-4 py-2">
                    @forelse($closedCalls as $c)
                        <li class="flex items-center gap-2.5 py-3 {{ $loop->last ? '' : 'border-b border-gray-100' }}">
                            @include('seed.partials.call-tag', ['call' => $c, 'short' => true])
                            <span class="min-w-0 flex-1 leading-snug"><a href="{{ route('seed.calls.show', $c) }}" class="block text-[13.5px] font-medium text-foreground hover:text-primary">{{ $c->title }}</a><span class="block text-xs text-muted-foreground">{{ $c->fiscal_year }} · {{ $m($c->min_amount) }}–{{ $m($c->max_amount) }} · {{ $c->duration_months }} months</span></span>
                            <x-badge dot>Closed</x-badge>
                        </li>
                    @empty
                        <li class="py-6 text-center text-[13px] text-muted-foreground">None yet.</li>
                    @endforelse
                </ul>
                <div class="border-t border-border bg-muted px-4 py-3 text-[13px] text-muted-foreground">When NIMR opens a new call, it appears at the top of this page with its deadlines.</div>
            </section>
        </div>
    </div>
@else
    {{-- ───────────── 2a: with applications ───────────── --}}
    @php
        $sorted = $applications->sortBy(fn ($a) => [V::needsYou($a) ? 0 : 1, -($a->updated_at?->timestamp ?? 0)])->values();
        $needing = $sorted->filter(fn ($a) => V::needsYou($a))
            ->sortBy(fn ($a) => V::task($a)[1]?->timestamp ?? PHP_INT_MAX)->values();
        $firstNeed = $needing->first();
        $task = $firstNeed ? V::task($firstNeed) : null;
        $callCount = $applications->pluck('seed_call_id')->unique()->count();

        $sentence = $applications->count() === 1
            ? 'You have one application, in the ' . $applications->first()->call->title . '.'
            : 'You have ' . $applications->count() . ' applications in ' . $callCount . ' ' . str('call')->plural($callCount) . '.';
        $sentence .= match (true) {
            $needing->isEmpty() => ' Nothing needs you right now.',
            $needing->count() === 1 => ' One needs you: ' . $task[3] . ($task[1] ? ' by ' . $task[1]->format('j M Y') : '') . '.',
            default => ' ' . $words($needing->count()) . ' need you.',
        };

        // The applications tile: how many sit where.
        $buckets = [
            'needs'    => ['to act on', 'bg-warn'],
            'review'   => ['in review', 'bg-review'],
            'approval' => ['in approval', 'bg-sky-500'],
            'awarded'  => ['awarded', 'bg-award'],
            'closed'   => ['closed', 'bg-gray-300'],
        ];
        $byBucket = $applications->groupBy(fn ($a) => V::bucket($a))->map->count();

        // Money: approved first, else requested; closed applications left out.
        $live = $applications->reject(fn ($a) => in_array($a->status, S::CLOSED_STATUSES, true));
        $approved = $live->whereIn('status', S::APPROVED_STATUSES);
        $open = $live->whereNotIn('status', S::APPROVED_STATUSES);
        $money = $live->map(fn ($a) => ['a' => $a, 'amount' => $a->approved_amount ?: $a->requestedAmount()])->filter(fn ($r) => $r['amount'] > 0)->sortByDesc('amount')->values();
        $moneyTotal = max(1, $money->sum('amount'));

        // Key dates across their calls.
        $dates = collect();
        foreach ($applications as $a) {
            $round = $a->latestRound();
            if ($a->isRevising() && $round?->response_due_at) {
                $dates->push([$round->response_due_at, 'Your revision is due', $a->call, '', 'mine']);
            } elseif ($a->status === S::SHORTLISTED && $a->call->proposal_deadline) {
                $dates->push([$a->call->proposal_deadline, 'Your full proposal is due', $a->call, '', 'mine']);
            } elseif (in_array($a->status, [S::CONCEPT_REVIEW, S::PROPOSAL_REVIEW], true) && $round?->due_at) {
                $dates->push([$round->due_at, 'Reviews due', $a->call, '', 'theirs']);
            }
        }
        foreach ($applications->pluck('call')->unique('id') as $c) {
            if ($c->acceptingConceptNotes() && $c->concept_deadline) {
                $dates->push([$c->concept_deadline, 'Concept notes close', $c, '', 'call']);
            }
            if ($c->proposal_deadline && $applications->where('seed_call_id', $c->id)->contains(fn ($a) => in_array($a->status, S::CONCEPT_STATUSES, true))) {
                $dates->push([$c->proposal_deadline, 'Full proposals due', $c, ' · if shortlisted', 'call']);
            }
        }
        // Coming dates, and any of theirs already missed.
        $dates = $dates->filter(fn ($d) => V::daysTo($d[0]) >= 0 || $d[4] === 'mine')->sortBy(fn ($d) => $d[0])->values();
    @endphp

    <div class="-mx-4 -mt-5 flex flex-wrap items-end justify-between gap-6 bg-primary px-7 pb-24 pt-5 text-white lg:-mx-6 lg:-mt-6">
        <div class="flex max-w-3xl flex-col gap-2">
            <p class="text-[13px] text-blue-200">{{ now()->format('l, j F Y') }}</p>
            <h1 class="text-[30px] font-semibold tracking-[-0.025em] text-white">{{ $greeting }}, {{ $first }}.</h1>
            <p class="text-[15px] text-blue-100">{{ $sentence }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('seed.calls.index') }}" class="inline-flex h-9 items-center gap-1.5 rounded-[7px] border border-white/25 bg-white/10 px-3.5 text-[13px] font-medium text-white hover:bg-white/20"><x-icon name="megaphone" class="size-4" />Browse calls</a>
            <a href="{{ route('seed.mine') }}" class="inline-flex h-9 items-center gap-1.5 rounded-[7px] bg-white px-3.5 text-[13px] font-semibold text-blue-700 hover:bg-blue-50">{{ $applications->count() === 1 ? 'My application' : 'My applications' }} <x-icon name="arrow-right" class="size-4" /></a>
        </div>
    </div>

    <div class="-mt-[72px] flex flex-col gap-4">
        {{-- Three figures --}}
        <div class="grid gap-4 lg:grid-cols-3">
            @if($task)
                @php $late = $task[1] && V::daysTo($task[1]) < 0; @endphp
                <a href="{{ route($task[4] === 'agreement' ? 'seed.applications.agreement' : 'seed.applications.edit', $firstNeed) }}"
                   class="flex flex-col gap-3 rounded-[14px] p-5 shadow-lift {{ $late ? 'bg-danger text-white' : 'bg-warn text-amber-950' }}">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-[13.5px] font-semibold">Needs you</span>
                        @if($task[1])<span class="inline-flex h-[22px] items-center rounded-md px-2 text-xs font-semibold {{ $late ? 'bg-white/20' : 'bg-amber-950/10' }}">{{ V::when($task[1]) }}</span>@endif
                    </div>
                    <span class="text-[28px] font-semibold tracking-[-0.02em]">{{ $task[0] }}{{ $task[1] ? ' ' . $task[1]->format('j M') : '' }}</span>
                    <span class="text-[13px] {{ $late ? 'text-white/90' : 'text-amber-900' }}">{{ $late ? 'This date has passed. Send it now, or contact GMU.' : $task[2] }}</span>
                </a>
            @else
                <div class="flex flex-col gap-3 rounded-[14px] border border-border bg-card p-5 shadow-lift">
                    <span class="text-[13.5px] font-medium">Needs you</span>
                    <span class="text-[28px] font-semibold tracking-[-0.02em]">Nothing</span>
                    <span class="text-[13px] text-muted-foreground">Nothing is due from you right now</span>
                </div>
            @endif

            <div class="flex flex-col gap-3 rounded-[14px] border border-border bg-card p-5 shadow-lift">
                <div class="flex items-center justify-between"><span class="text-[13.5px] font-medium">Applications</span><span class="text-[12.5px] text-muted-foreground">{{ $callCount }} {{ str('call')->plural($callCount) }}</span></div>
                <span class="text-[28px] font-semibold tracking-[-0.02em] tabular-nums">{{ $applications->count() }}</span>
                <div class="flex h-2 gap-0.5 overflow-hidden rounded-full" role="img" aria-label="@foreach($buckets as $key => [$what]){{ $byBucket[$key] ?? 0 }} {{ $what }}@if(! $loop->last), @endif @endforeach">
                    @foreach($buckets as $key => [$what, $colour])
                        @if($byBucket[$key] ?? 0)<span class="{{ $colour }}" style="flex: {{ $byBucket[$key] }}"></span>@endif
                    @endforeach
                </div>
                <div class="flex flex-wrap gap-x-3.5 gap-y-1 text-[12.5px] text-gray-700">
                    @foreach($buckets as $key => [$what, $colour])
                        @if($byBucket[$key] ?? 0)<span class="flex items-center gap-1.5"><span class="size-2 rounded-sm {{ $colour }}"></span>{{ $byBucket[$key] }} {{ $what }}</span>@endif
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col gap-3 rounded-[14px] border border-border bg-card p-5 shadow-lift">
                @if($approved->isNotEmpty())
                    <div class="flex items-center justify-between"><span class="text-[13.5px] font-medium">Awarded to you</span><span class="inline-flex h-[22px] items-center rounded-md bg-blue-50 px-2 text-xs font-medium text-blue-700">{{ $approved->count() }} {{ str('grant')->plural($approved->count()) }}</span></div>
                    <span class="text-[28px] font-semibold tracking-[-0.02em] tabular-nums text-blue-700">{{ $m($approved->sum('approved_amount')) }}</span>
                    <span class="text-[13px] text-muted-foreground">{{ $open->isNotEmpty() ? $m($open->sum(fn ($a) => $a->requestedAmount())) . ' more requested in the ' . ($open->count() === 1 ? 'open application' : $words($open->count()) . ' open applications') : 'Nothing else requested' }}</span>
                @else
                    <div class="flex items-center justify-between"><span class="text-[13.5px] font-medium">Requested</span><span class="text-[12.5px] text-muted-foreground">{{ $open->count() }} open</span></div>
                    <span class="text-[28px] font-semibold tracking-[-0.02em] tabular-nums text-blue-700">{{ $m($open->sum(fn ($a) => $a->requestedAmount())) }}</span>
                    <span class="text-[13px] text-muted-foreground">Across your open applications</span>
                @endif
            </div>
        </div>

        <div class="grid items-start gap-4 xl:grid-cols-[minmax(0,1fr)_380px]">
            {{-- The applications --}}
            <div class="flex flex-col gap-4">
                <div id="applications" class="scroll-mt-20 pt-2"><h2 class="text-base font-semibold">Your applications</h2><p class="text-[13px] text-muted-foreground">What needs you first, then the rest by date</p></div>
                @foreach($sorted as $application)
                    @include('seed.partials.application-card')
                @endforeach
            </div>

            <div class="flex flex-col gap-4 pt-2">
                <section class="overflow-hidden rounded-[14px] border border-border">
                    <header class="border-b border-border bg-muted px-4 py-3"><h3 class="text-sm font-semibold">Key dates</h3><p class="text-[13px] text-muted-foreground">All your calls, East Africa Time</p></header>
                    <ol class="bg-card px-4 py-2">
                        @forelse($dates as [$date, $what, $c, $suffix, $whose])
                            @php [$day, $mon] = $chip($date); $mine = $whose === 'mine'; $late = $mine && V::daysTo($date) < 0; @endphp
                            <li class="{{ $c->tintClass() }} flex items-center gap-3 py-2.5 {{ $loop->last ? '' : 'border-b border-gray-100' }}">
                                <span class="w-11 shrink-0 rounded-lg py-1.5 text-center leading-tight {{ $late ? 'bg-danger-soft text-danger-ink' : ($mine ? 'bg-warn-soft text-warn-ink' : 'bg-gray-100') }}"><span class="block text-[17px] font-semibold">{{ $day }}</span><span class="block text-[10.5px] uppercase tracking-[0.06em] {{ $mine ? '' : 'text-muted-foreground' }}">{{ $mon }}</span></span>
                                <span class="min-w-0 flex-1 leading-snug"><span class="block text-[13.5px] {{ $mine ? 'font-semibold' : '' }}">{{ $what }}</span><span class="flex items-center gap-1.5 text-xs text-muted-foreground"><span class="size-1.5 rounded-full bg-tint-solid"></span>{{ $c->code }}{{ $suffix }}</span></span>
                                <span class="text-xs {{ $late ? 'font-medium text-danger-ink' : ($mine ? 'font-medium text-warn-ink' : 'text-muted-foreground') }}">{{ $label($date) }}</span>
                            </li>
                        @empty
                            <li class="py-6 text-center text-[13px] text-muted-foreground">No dates ahead for your applications.</li>
                        @endforelse
                    </ol>
                </section>

                @if($money->isNotEmpty())
                    <section class="overflow-hidden rounded-[14px] border border-border">
                        <header class="border-b border-border bg-muted px-4 py-3"><h3 class="text-sm font-semibold">Money by call</h3><p class="text-[13px] text-muted-foreground">TZS {{ number_format($money->sum('amount')) }} across your applications</p></header>
                        <div class="flex flex-col gap-4 bg-card px-5 py-[18px]">
                            <div class="flex h-3.5 gap-0.5 overflow-hidden rounded-[5px]" role="img" aria-label="Money by call">
                                @foreach($money as $row)<span class="{{ $row['a']->call->tintClass() }} bg-tint-solid" style="width: {{ $row['amount'] / $moneyTotal * 100 }}%"></span>@endforeach
                            </div>
                            <ul class="flex flex-col gap-2.5 text-[13px]">
                                @foreach($money as $row)
                                    <li class="{{ $row['a']->call->tintClass() }} flex items-center gap-2">
                                        <span class="size-2 rounded-sm bg-tint-solid"></span>
                                        <span class="flex-1 text-gray-700">{{ $row['a']->call->code }} <span class="text-muted-foreground">· {{ $row['a']->approved_amount ? 'approved' : 'requested' }}</span></span>
                                        <span class="font-mono">{{ number_format($row['amount']) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </section>
                @endif
            </div>
        </div>
    </div>
@endif
@endsection
