@extends('layouts.app')

{{--
    Every call as a card in its own colour. GMU sees all of them (drafts included), where
    their applications are and the money approved, from the owner's "GMU Calls" design
    (ink band). Staff see open and closed calls and what they can do with each.
--}}

@php
    use App\Models\Seed\SeedCall;
    use App\Support\SeedGrant as S;
    $m = fn ($n) => $n ? number_format($n / 1e6, 1) . 'M' : '0';
    $filters = [route('seed.calls.index') => ['All', $total]];
    foreach (['open' => 'Open', 'draft' => 'Draft', 'closed' => 'Closed'] as $key => $label) {
        if ($counts->get($key)) {
            $filters[route('seed.calls.index', ['show' => $key])] = [$label, $counts->get($key)];
        }
    }
    $here = $show === 'all' ? route('seed.calls.index') : route('seed.calls.index', ['show' => $show]);
@endphp

@section('title', 'Calls')
@section('context', 'Grants · Calls')
@if($manage)
    @section('topbar-colour', 'bg-foreground')
@else
    @section('topbar-colour', 'bg-blue-700')
@endif

@section('content')
@if($manage)
    <div class="-mx-4 -mt-5 flex flex-wrap items-start justify-between gap-5 bg-foreground px-7 pb-[100px] pt-5 text-white lg:-mx-6 lg:-mt-6">
        <div class="flex min-w-0 flex-col gap-3">
            <h1 class="text-[30px] font-semibold leading-tight tracking-[-0.025em] text-white">Calls</h1>
            <p class="text-[15px] text-gray-300">{{ $total }} {{ str('call')->plural($total) }} · {{ $counts->get('open', 0) }} open · a new call starts from the last one’s settings</p>
            @if($total > 0 && count($filters) > 2)
                <nav class="flex gap-0.5 self-start rounded-[9px] bg-gray-800 p-[3px]" aria-label="Show calls">
                    @foreach($filters as $url => [$label, $n])
                        <a href="{{ $url }}" @if($url === $here) aria-current="page" @endif
                           class="inline-flex h-7 items-center gap-1.5 rounded-[7px] px-2.5 text-[13px] font-medium {{ $url === $here ? 'bg-white text-foreground' : 'text-gray-300 hover:text-white' }}">{{ $label }}<span class="text-[11.5px] font-semibold text-gray-500">{{ $n }}</span></a>
                    @endforeach
                </nav>
            @endif
        </div>
        <a href="{{ route('seed.calls.create') }}" class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-lg bg-white px-3.5 text-[13px] font-semibold text-foreground hover:bg-gray-100"><x-icon name="plus" class="size-3.5" />New call</a>
    </div>

    <div class="-mt-[72px]">
        @if($calls->isEmpty())
            <section class="rounded-2xl border border-border bg-card px-6 py-12 text-center shadow-pop">
                <p class="text-[14.5px] font-semibold">No calls yet</p>
                <p class="mt-1 text-[13px] text-muted-foreground">Create the first call. Researchers see it once you set it to Open.</p>
            </section>
        @else
            <div class="grid gap-[18px] [grid-template-columns:repeat(auto-fill,minmax(min(100%,380px),1fr))]">
                @foreach($calls as $call)
                    @php
                        $open = $call->status === 'open';
                        $s = $stats[$call->id];
                        $share = $call->envelope ? min(100, $s['approved'] / $call->envelope * 100) : 0;
                        $closedNotes = $call->concept_deadline && $call->concept_deadline->copy()->endOfDay()->isPast();
                        $badge = match ($call->status) {
                            'open'  => $call->acceptingConceptNotes() ? 'Open · concept notes close ' . $call->concept_deadline?->format('j M') : 'Open · concept notes closed',
                            'draft' => 'Draft · hidden from staff',
                            default => 'Closed',
                        };
                    @endphp
                    <section class="{{ $call->tintClass() }} flex flex-col overflow-hidden rounded-2xl border border-tint-line bg-card shadow-pop">
                        <header class="flex flex-col gap-2 bg-tint px-[18px] py-4">
                            <div class="flex items-center gap-2">
                                <span class="rounded-md bg-tint-chip px-1.5 py-0.5 font-mono text-xs font-medium text-tint-ink">{{ $call->code }}</span>
                                <span class="text-[12.5px] text-muted-foreground">FY {{ $call->fiscal_year }}</span>
                                <span class="ml-auto inline-flex h-[22px] items-center gap-1.5 whitespace-nowrap rounded-md px-2 text-xs font-medium {{ $open ? 'bg-blue-50 text-ok-ink' : 'bg-card text-gray-700 ring-1 ring-inset ring-input' }}"><span class="size-1.5 rounded-full {{ $open ? 'bg-primary' : 'bg-gray-400' }}"></span>{{ $badge }}</span>
                            </div>
                            <h2 class="text-balance text-base font-semibold leading-snug"><a href="{{ route('seed.calls.show', $call) }}" class="hover:text-tint-ink">{{ $call->title }}</a></h2>
                        </header>
                        <div class="flex flex-1 flex-col gap-[18px] border-t border-tint-line p-[18px]">
                            <dl class="grid grid-cols-3 gap-3">
                                <div><dt class="text-[12.5px] text-muted-foreground">{{ $closedNotes ? 'Concept notes closed' : 'Concept notes close' }}</dt><dd class="mt-0.5 text-sm font-semibold">{{ $call->concept_deadline?->format('j M Y') ?? 'To be announced' }}</dd></div>
                                <div><dt class="text-[12.5px] text-muted-foreground">Applications</dt><dd class="mt-0.5 text-sm font-semibold tabular-nums">{{ $s['submitted'] }}</dd></div>
                                <div><dt class="text-[12.5px] text-muted-foreground">Award, TZS</dt><dd class="mt-0.5 font-mono text-sm font-semibold">{{ $m($call->min_amount) }} – {{ $m($call->max_amount) }}</dd></div>
                            </dl>
                            <div class="flex flex-col gap-2">
                                <p class="text-[12.5px] text-muted-foreground">Where applications are</p>
                                @if($s['submitted'] === 0)
                                    <p class="text-[13px] text-muted-foreground">{{ $call->status === 'draft' ? 'Nothing submitted yet. Researchers see the call once you set it to Open.' : 'Nothing submitted yet.' }}</p>
                                @else
                                    <div class="flex h-2.5 gap-0.5 overflow-hidden rounded" role="img"
                                         aria-label="{{ $call->code }}: @foreach(S::STAGE_GROUPS as $key => $g){{ $g[0] }} {{ $s['stages'][$key] }}@if(! $loop->last), @endif @endforeach">
                                        @foreach(S::STAGE_GROUPS as $key => $g)
                                            @if($s['stages'][$key] > 0)<span class="{{ $g[2] }}" style="width: {{ $s['stages'][$key] / $s['submitted'] * 100 }}%"></span>@endif
                                        @endforeach
                                    </div>
                                    <ul class="flex flex-wrap gap-x-3.5 gap-y-1 text-[12.5px] text-gray-700">
                                        @foreach(S::STAGE_GROUPS as $key => $g)
                                            @if($s['stages'][$key] > 0)<li class="flex items-center gap-1.5"><span class="size-2 rounded-sm {{ $g[2] }}"></span>{{ $g[0] }} <b class="font-semibold text-foreground">{{ $s['stages'][$key] }}</b></li>@endif
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                            <div class="flex flex-col gap-2">
                                <div class="flex justify-between text-[12.5px] text-muted-foreground"><span>Funds approved</span><span><b class="font-semibold text-foreground">TZS {{ $m($s['approved']) }}</b> of {{ $call->envelope ? $m($call->envelope) : 'not set' }}</span></div>
                                <div class="h-2 overflow-hidden rounded-full bg-tint ring-1 ring-inset ring-tint-line" role="img" aria-label="Approved for {{ $call->code }}"><div class="h-full rounded-full bg-tint-solid" style="width: {{ $share }}%"></div></div>
                            </div>
                        </div>
                        <footer class="flex items-center gap-2 border-t border-border bg-gray-50 px-3.5 py-2.5">
                            <a href="{{ route('seed.submissions.index', ['call' => $call->id]) }}" class="inline-flex h-[30px] items-center rounded-[7px] border border-input bg-card px-[11px] text-[12.5px] font-medium hover:bg-gray-100">Submissions</a>
                            <a href="{{ route('seed.ranking', ['call' => $call->id]) }}" class="inline-flex h-[30px] items-center rounded-[7px] border border-input bg-card px-[11px] text-[12.5px] font-medium hover:bg-gray-100">Ranking</a>
                            <a href="{{ route('seed.calls.edit', $call) }}" class="ml-auto inline-flex h-[30px] items-center gap-1.5 rounded-[7px] px-2.5 text-[12.5px] font-medium text-gray-700 hover:bg-gray-100" aria-label="Settings for {{ $call->title }}"><x-icon name="sliders" class="size-3.5" />Settings</a>
                        </footer>
                    </section>
                @endforeach
            </div>
        @endif
    </div>
@else
    {{-- Staff: the calls they can apply to, and earlier ones (the owner's "Staff Pages" design, its blue band). --}}
    <div class="-mx-4 -mt-5 flex flex-wrap items-start justify-between gap-5 bg-blue-700 px-7 pb-[100px] pt-5 text-white lg:-mx-6 lg:-mt-6">
        <div class="flex min-w-0 flex-col gap-3">
            <h1 class="text-[30px] font-semibold leading-tight tracking-[-0.025em] text-white">Calls</h1>
            <p class="text-[15px] text-blue-100">Calls you can apply to, and earlier ones</p>
            @if($total > 0 && count($filters) > 2)
                <nav class="flex gap-0.5 self-start rounded-[9px] bg-white/15 p-[3px]" aria-label="Show calls">
                    @foreach($filters as $url => [$label, $n])
                        <a href="{{ $url }}" @if($url === $here) aria-current="page" @endif
                           class="inline-flex h-7 items-center gap-1.5 rounded-[7px] px-2.5 text-[13px] font-medium {{ $url === $here ? 'bg-white text-blue-700' : 'text-white hover:bg-white/10' }}">{{ $label }}<span class="text-[11.5px] font-semibold opacity-70">{{ $n }}</span></a>
                    @endforeach
                </nav>
            @endif
        </div>
    </div>

    <div class="-mt-[72px]">
        @if($calls->isEmpty())
            <section class="rounded-2xl border border-border bg-card px-6 py-12 text-center shadow-pop">
                <p class="text-[14.5px] font-semibold">No calls are open right now</p>
                <p class="mt-1 text-[13px] text-muted-foreground">When NIMR opens a call, it will appear here, and you will be told by email.</p>
            </section>
        @else
            <div class="grid gap-[18px] [grid-template-columns:repeat(auto-fill,minmax(min(100%,380px),1fr))]">
                @foreach($calls as $call)
                    @php
                        $accepting = $call->acceptingConceptNotes();
                        $closedNotes = $call->concept_deadline && $call->concept_deadline->copy()->endOfDay()->isPast();
                        $mineHere = $mine[$call->id] ?? null;
                        $daysLeft = $accepting && $call->concept_deadline ? (int) now()->startOfDay()->diffInDays($call->concept_deadline->copy()->startOfDay()) : null;
                        $open = $call->status === 'open';
                        $badge = $open ? ($accepting && $call->concept_deadline ? 'Open · closes ' . $call->concept_deadline->format('j M') : ($accepting ? 'Open' : 'Open · concept notes closed')) : 'Closed';
                    @endphp
                    <section class="{{ $call->tintClass() }} flex flex-col overflow-hidden rounded-2xl border border-tint-line bg-card shadow-pop">
                        <header class="flex flex-col gap-2 bg-tint px-[18px] py-4">
                            <div class="flex items-center gap-2">
                                <span class="rounded-md bg-tint-chip px-1.5 py-0.5 font-mono text-xs font-medium text-tint-ink">{{ $call->code }}</span>
                                <span class="text-[12.5px] text-muted-foreground">FY {{ $call->fiscal_year }}</span>
                                <span class="ml-auto inline-flex h-[22px] items-center gap-1.5 whitespace-nowrap rounded-md px-2 text-xs font-medium {{ $open ? 'bg-blue-50 text-ok-ink' : 'bg-card text-gray-700 ring-1 ring-inset ring-input' }}"><span class="size-1.5 rounded-full {{ $open ? 'bg-primary' : 'bg-gray-400' }}"></span>{{ $badge }}</span>
                            </div>
                            <h2 class="text-balance text-base font-semibold leading-snug">{{ $call->title }}</h2>
                        </header>
                        <div class="flex flex-1 flex-col gap-4 border-t border-tint-line p-[18px]">
                            @if($daysLeft !== null && ! $mineHere)
                                <div class="flex items-center gap-3 rounded-xl bg-blue-700 px-3.5 py-3 text-white">
                                    <span class="text-[26px] font-semibold leading-none tracking-[-0.02em] tabular-nums">{{ $daysLeft }}</span>
                                    <span class="leading-tight"><span class="block text-[13px] font-semibold">{{ $daysLeft === 0 ? 'Closes today' : str('day')->plural($daysLeft) . ' left' }}</span><span class="block text-[12.5px] text-blue-100">Concept notes close {{ $call->concept_deadline->format('j M Y') }}, end of day</span></span>
                                </div>
                            @endif
                            <dl class="grid grid-cols-3 gap-3">
                                <div><dt class="text-[12.5px] text-muted-foreground">{{ $closedNotes ? 'Concept notes closed' : 'Concept notes close' }}</dt><dd class="mt-0.5 text-sm font-semibold">{{ $call->concept_deadline?->format('j M Y') ?? 'To be announced' }}</dd></div>
                                <div><dt class="text-[12.5px] text-muted-foreground">Duration</dt><dd class="mt-0.5 text-sm font-semibold">{{ $call->duration_months ? $call->duration_months . ' months' : '—' }}</dd></div>
                                <div><dt class="text-[12.5px] text-muted-foreground">Award, TZS</dt><dd class="mt-0.5 font-mono text-sm font-semibold">{{ $m($call->min_amount) }} – {{ $m($call->max_amount) }}</dd></div>
                            </dl>
                            @if($call->summary)<p class="text-pretty text-[13.5px] leading-normal text-gray-700">{{ $call->summary }}</p>@endif
                            @if($mineHere)
                                <div class="flex items-center gap-2.5 rounded-[10px] border border-border bg-muted px-3 py-2.5">
                                    @include('seed.partials.status', ['application' => $mineHere])
                                    <span class="min-w-0 flex-1 truncate text-[13px]">{{ $mineHere->title ?: 'Untitled' }}</span>
                                    <span class="font-mono text-xs text-muted-foreground">{{ $mineHere->reference }}</span>
                                </div>
                            @endif
                        </div>
                        <footer class="flex items-center gap-2 border-t border-border bg-gray-50 px-3.5 py-2.5">
                            @if($mineHere)
                                <a href="{{ route('seed.applications.show', $mineHere) }}" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-input bg-card px-3 text-[13px] font-medium hover:bg-gray-100">My application<x-icon name="arrow-right" class="size-3.5" /></a>
                                <a href="{{ route('seed.calls.show', $call) }}" class="inline-flex h-8 items-center rounded-lg px-2.5 text-[13px] font-medium text-gray-700 hover:bg-gray-100 hover:text-foreground">Read the call</a>
                            @elseif($accepting)
                                <a href="{{ route('seed.calls.show', $call) }}" class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-blue-700 px-3 text-[13px] font-medium text-white hover:bg-blue-800">Read and apply<x-icon name="arrow-right" class="size-3.5" /></a>
                            @else
                                <a href="{{ route('seed.calls.show', $call) }}" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-input bg-card px-3 text-[13px] font-medium hover:bg-gray-100">Read the call<x-icon name="arrow-right" class="size-3.5" /></a>
                            @endif
                        </footer>
                    </section>
                @endforeach
            </div>
        @endif
    </div>
@endif
@endsection
