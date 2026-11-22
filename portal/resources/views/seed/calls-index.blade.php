@extends('layouts.app')

{{--
    Every call as a card in its own colour. GMU sees all of them (drafts included),
    where their applications are and the money approved; staff see open and closed
    calls and what they can do with each.
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

@section('content')
<x-page-header title="Calls"
    :description="$manage
        ? $total . ' ' . str('call')->plural($total) . ' · ' . ($counts->get('open', 0)) . ' open · a new call starts from the last one’s settings'
        : 'Calls you can apply to, and earlier ones'">
    @if($manage)
        <x-slot:actions><x-button variant="primary" :href="route('seed.calls.create')" icon="plus">New call</x-button></x-slot:actions>
    @endif
</x-page-header>

@if($total > 0 && count($filters) > 2)
    <x-segmented class="mb-4" :items="$filters" :active="$here" label="Show calls" />
@endif

@if($calls->isEmpty())
    <x-card>
        <x-empty icon="megaphone" :title="$manage ? 'No calls yet' : 'No calls are open right now'">
            {{ $manage ? 'Create the first call. Researchers see it once you set it to Open.' : 'When NIMR opens a call, it will appear here.' }}
            @if($manage)
                <x-slot:action><x-button variant="primary" :href="route('seed.calls.create')" icon="plus">New call</x-button></x-slot:action>
            @endif
        </x-empty>
    </x-card>
@else
    <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
        @foreach($calls as $call)
            @php
                $open = $call->status === 'open';
                $s = $stats[$call->id] ?? null;
                $share = $s && $call->envelope ? min(100, $s['approved'] / $call->envelope * 100) : 0;
            @endphp
            <section class="{{ $call->tintClass() }} flex flex-col overflow-hidden rounded-xl border border-tint-line bg-tint">
                <header class="space-y-1.5 px-4 py-3.5">
                    <div class="flex items-center gap-2">
                        @include('seed.partials.call-tag', ['call' => $call, 'short' => true])
                        <span class="text-[12.5px] text-muted-foreground">FY {{ $call->fiscal_year }}</span>
                        <x-badge class="ml-auto" :tone="['open' => 'ok', 'closed' => 'neutral'][$call->status] ?? 'outline'" dot>
                            {{ $open && ! $call->acceptingConceptNotes() ? 'Open · concept notes closed' : SeedCall::STATUSES[$call->status] }}
                        </x-badge>
                    </div>
                    <h2 class="text-[15px] font-semibold leading-snug"><a href="{{ route('seed.calls.show', $call) }}" class="hover:text-primary">{{ $call->title }}</a></h2>
                </header>

                <div class="flex flex-1 flex-col gap-5 border-t border-tint-line bg-card p-4">
                    <dl class="grid grid-cols-3 gap-3">
                        <div>
                            <dt class="text-[12.5px] text-muted-foreground">{{ $call->concept_deadline && $call->concept_deadline->copy()->endOfDay()->isPast() ? 'Concept notes closed' : 'Concept notes close' }}</dt>
                            <dd class="mt-0.5 text-sm font-semibold">{{ $call->concept_deadline?->format('j M Y') ?? 'To be announced' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[12.5px] text-muted-foreground">{{ $manage ? 'Applications' : 'Duration' }}</dt>
                            <dd class="mt-0.5 text-sm font-semibold tabular-nums">{{ $manage ? $call->applications_count : ($call->duration_months ? $call->duration_months . ' months' : '—') }}</dd>
                        </div>
                        <div>
                            <dt class="text-[12.5px] text-muted-foreground">Award, TZS</dt>
                            <dd class="mt-0.5 text-sm font-semibold tabular-nums">{{ $m($call->min_amount) }} – {{ $m($call->max_amount) }}</dd>
                        </div>
                    </dl>

                    @if($s)
                        <div class="space-y-2">
                            <p class="text-[12.5px] text-muted-foreground">Where applications are</p>
                            @if($s['submitted'] === 0)
                                <p class="text-[13px] text-muted-foreground">Nothing submitted yet.</p>
                            @else
                                <div class="flex h-2.5 gap-0.5" role="img"
                                     aria-label="{{ $call->code }}: @foreach(S::STAGE_GROUPS as $key => $g){{ $g[0] }} {{ $s['stages'][$key] }}@if(! $loop->last), @endif @endforeach">
                                    @foreach(S::STAGE_GROUPS as $key => $g)
                                        @if($s['stages'][$key] > 0)<span class="rounded-sm {{ $g[2] }}" style="width: {{ $s['stages'][$key] / $s['submitted'] * 100 }}%"></span>@endif
                                    @endforeach
                                </div>
                                <ul class="flex flex-wrap gap-x-3.5 gap-y-1 text-[12.5px] text-gray-700">
                                    @foreach(S::STAGE_GROUPS as $key => $g)
                                        @if($s['stages'][$key] > 0)
                                            <li class="flex items-center gap-1.5"><span class="size-2 rounded-sm {{ $g[2] }}"></span>{{ $g[0] }} <b class="font-semibold text-foreground">{{ $s['stages'][$key] }}</b></li>
                                        @endif
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        <div class="space-y-2">
                            <div class="flex justify-between text-[12.5px] text-muted-foreground">
                                <span>Funds approved</span>
                                <span><b class="font-semibold text-foreground">TZS {{ $m($s['approved']) }}</b> of {{ $call->envelope ? $m($call->envelope) : 'not set' }}</span>
                            </div>
                            <x-meter :value="$share" tone="tint" :label="'Approved for ' . $call->code" />
                        </div>
                    @elseif($call->summary)
                        <p class="text-[13px] text-gray-700">{{ $call->summary }}</p>
                    @endif
                </div>

                <footer class="flex flex-wrap items-center gap-2 border-t border-border bg-card px-4 py-2.5">
                    @if($manage)
                        <x-button size="sm" :href="route('seed.submissions.index', ['call' => $call->id])">Submissions</x-button>
                        <x-button size="sm" :href="route('seed.ranking', ['call' => $call->id])">Ranking</x-button>
                        <x-button size="sm" variant="ghost" class="ml-auto" :href="route('seed.calls.edit', $call)" icon="sliders" aria-label="Settings for {{ $call->title }}">Settings</x-button>
                    @elseif(isset($mine[$call->id]))
                        <x-button size="sm" :href="route('seed.applications.show', $mine[$call->id])">My application</x-button>
                    @elseif($call->acceptingConceptNotes())
                        <x-button size="sm" variant="primary" :href="route('seed.calls.show', $call)">Read and apply</x-button>
                    @else
                        <x-button size="sm" :href="route('seed.calls.show', $call)">Read the call</x-button>
                    @endif
                </footer>
            </section>
        @endforeach
    </div>
@endif
@endsection
