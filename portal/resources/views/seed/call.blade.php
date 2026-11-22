@extends('layouts.app')

@section('title', 'The call')
@section('context', 'Calls · ' . $call->title)

@php use App\Support\SeedGrant; $form = $call->form(); @endphp

@section('content')
{{-- The call, as researchers read it --}}
<section class="relative overflow-hidden rounded-2xl bg-primary px-6 py-8 text-white shadow-sm sm:px-10 sm:py-10">
    <div class="pointer-events-none absolute -right-24 -top-24 size-80 rounded-full bg-white/[0.06]"></div>
    <div class="pointer-events-none absolute -bottom-32 right-24 size-72 rounded-full bg-white/[0.04]"></div>
    <div class="relative">
        <div class="flex flex-wrap items-center gap-3">
            <span class="inline-flex h-6 items-center gap-1.5 rounded-md bg-white px-2 text-xs font-medium text-blue-700">
                <span class="size-1.5 rounded-full bg-primary"></span>
                {{ $call->acceptingConceptNotes() ? 'Concept notes open' : ($call->status === 'draft' ? 'Draft: not visible to staff' : 'Concept notes closed') }}
            </span>
            <span class="font-mono text-[13px] text-blue-100">{{ $call->code }} · FY {{ $call->fiscal_year }}</span>
            @can('manage-seed-grants')
                <a href="{{ route('seed.calls.edit', $call) }}" class="ml-auto inline-flex h-8 items-center gap-2 rounded-md border border-white/40 px-3 text-[13px] font-medium text-white hover:bg-white/10">
                    <x-icon name="sliders" class="size-3.5" /> Call settings
                </a>
            @endcan
        </div>
        <h1 class="mt-5 max-w-4xl text-3xl font-semibold leading-tight tracking-tight text-white sm:text-[40px] sm:leading-[1.1]">{{ $call->title }}</h1>
        @if($call->summary)
            <p class="mt-4 max-w-3xl text-base leading-relaxed text-blue-50">{{ $call->summary }}</p>
        @endif
        <dl class="mt-8 grid max-w-4xl grid-cols-2 gap-px overflow-hidden rounded-xl bg-white/15 ring-1 ring-white/15 lg:grid-cols-4">
            @foreach([
                ['Award per project', 'TZS ' . number_format($call->min_amount / 1e6, 0) . 'M – ' . number_format($call->max_amount / 1e6, 0) . 'M'],
                ['Study length', $call->duration_months . ' months'],
                ['Concept notes close', $call->concept_deadline?->format('j M Y') ?? 'To be announced'],
                ['Concept note length', '≤ ' . SeedGrant::CONCEPT_MAX_PAGES . ' pages'],
            ] as [$k, $v])
                <div class="bg-blue-700/80 px-4 py-4"><dt class="text-xs text-blue-100">{{ $k }}</dt><dd class="mt-1 font-mono text-[15px]">{{ $v }}</dd></div>
            @endforeach
        </dl>
    </div>
</section>

@if($summary)
    {{-- GMU only: where this call stands --}}
    <x-card class="mt-6" title="Where this call stands" icon="chart"
        :description="$summary['submitted'] . ' submitted' . ($summary['drafts'] ? ', ' . $summary['drafts'] . ' in draft' : '') . ($summary['closed'] ? ', ' . $summary['closed'] . ' closed' : '') . '. Only GMU sees this.'">
        <x-slot:actions>
            <x-button size="sm" :href="route('seed.submissions.index', ['call' => $call->id])">Submissions</x-button>
            <x-button size="sm" :href="route('seed.submissions.export', ['call' => $call->id])" icon="download">Export</x-button>
        </x-slot:actions>
        <ol class="grid grid-cols-4 gap-1.5 sm:grid-cols-8" aria-label="Applications at each stage">
            @foreach($summary['stages'] as $stage)
                <li class="rounded-lg px-1.5 py-2 text-center {{ $stage['count'] ? 'bg-primary-soft' : 'bg-muted' }}">
                    <span class="block font-mono text-base tabular-nums {{ $stage['count'] ? 'font-semibold text-ok-ink' : 'text-subtle' }}">{{ $stage['count'] }}</span>
                    <span class="mt-0.5 block text-[10.5px] leading-tight text-muted-foreground">{{ $stage['label'] }}</span>
                </li>
            @endforeach
        </ol>
        <div class="mt-6 grid gap-6 lg:grid-cols-[3fr_2fr]">
            <dl class="grid grid-cols-2 gap-x-6 gap-y-4">
                <x-stat label="Requested, still in play" :value="SeedGrant::tzs($summary['requested'])" mono />
                <x-stat label="Approved" :value="SeedGrant::tzs($summary['approved'])" mono />
                <x-stat label="Funds available" :value="$call->envelope ? SeedGrant::tzs($call->envelope) : 'Not set'" mono />
                <x-stat label="Left to commit" :value="$call->envelope ? SeedGrant::tzs(max(0, $call->envelope - $summary['approved'])) : '—'" mono />
            </dl>
            <div>
                <p class="mb-3 text-[13px] font-medium">By centre</p>
                @php $maxCentre = max(1, $summary['byCentre']->max() ?? 1); @endphp
                <ul class="grid grid-cols-[minmax(0,auto)_1fr_auto] items-center gap-x-3 gap-y-2 text-[13px]">
                    @forelse($summary['byCentre'] as $centre => $n)
                        <li class="contents"><span class="truncate">{{ $centre }}</span><x-meter :value="$n / $maxCentre * 100" :label="$centre" /><span class="font-mono">{{ $n }}</span></li>
                    @empty
                        <li class="col-span-3 text-muted-foreground">No submissions yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </x-card>
@endif

<div class="mt-8 grid items-start gap-8 xl:grid-cols-[minmax(0,1fr)_22rem]">
    <div class="space-y-10">
        @if($form->aboutParagraphs())
            <section>
                <h2 class="text-xl">Why this call exists</h2>
                <div class="mt-3 max-w-3xl space-y-3 text-[15px] leading-relaxed text-gray-700">
                    @foreach($form->aboutParagraphs() as $paragraph)<p>{{ $paragraph }}</p>@endforeach
                </div>
            </section>
        @endif

        <section>
            <h2 class="text-xl">Who can lead an application</h2>
            <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach($form->eligibilityLines() as $rule)
                    <li class="flex gap-3 rounded-xl border border-border bg-card p-4 text-sm shadow-xs">
                        <span class="grid size-5 shrink-0 place-items-center rounded-full bg-primary-soft text-primary"><x-icon name="check" class="size-3" /></span>{{ $rule }}
                    </li>
                @endforeach
            </ul>
            <p class="mt-3 text-[13px] text-muted-foreground">One application per person as lead PI.</p>
        </section>

        <section>
            <h2 class="text-xl">How it works</h2>
            <p class="mt-2 max-w-3xl text-[15px] text-gray-700">Two stages. Reviewers may send your concept note or proposal back for revision; you respond and it goes round again.</p>
            <div class="mt-4 space-y-3">
                @foreach([
                    ['1', 'Concept note', 'bg-primary', [['You submit', 'Online form, ≤ 5 pages, simple budget'], ['GMU screens', 'Eligibility, completeness, budget range'], ['Reviewers respond', 'Accept, revise or reject'], ['Shortlist', 'Invited to stage 2']]],
                    ['2', 'Full proposal', 'bg-violet-500', [
                        ['You submit', '≤ 10 pages, detailed budget' . collect($form->documents())->filter(fn ($d) => $d[1])->map(fn ($d) => ', ' . mb_strtolower($d[0]))->implode('')],
                        $form->scored() ? ['Scored review', count($form->criteria()) . ' weighted criteria'] : ['Review', 'Accept, revise or reject'],
                        ['Approval', collect($form->approvalLevels())->map(fn ($l) => ['gmu' => 'GMU', 'drcp' => 'DRCP', 'dg' => 'DG'][$l])->implode(', then ')],
                        ['Agreement', 'Accept online before funds']]],
                ] as [$n, $name, $color, $steps])
                    <div class="rounded-xl border border-border bg-card p-5 shadow-xs">
                        <div class="flex items-center gap-2.5"><span class="grid size-7 place-items-center rounded-lg font-mono text-[13px] text-white {{ $color }}">{{ $n }}</span><span class="font-semibold">{{ $name }}</span></div>
                        <ol class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach($steps as [$t, $s])
                                <li class="rounded-lg bg-muted px-3.5 py-3"><span class="block text-[13px] font-medium">{{ $t }}</span><span class="mt-0.5 block text-xs text-muted-foreground">{{ $s }}</span></li>
                            @endforeach
                        </ol>
                    </div>
                @endforeach
            </div>
        </section>

        @if($form->priorities())
            <section>
                <h2 class="text-xl">Priority areas</h2>
                @if($form->prioritiesIntro())<p class="mt-2 max-w-3xl text-[15px] leading-relaxed text-gray-700">{{ $form->prioritiesIntro() }}</p>@endif
                <ul class="mt-4 flex flex-wrap gap-2">
                    @foreach($form->priorities() as $area)
                        <li class="rounded-full border border-border bg-card px-3 py-1 text-[13px] shadow-xs">{{ $area }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if($form->scored())
            <section>
                <h2 class="text-xl">How full proposals are scored</h2>
                <x-card class="mt-4" flush>
                    <div class="overflow-x-auto">
                        <table class="data-table min-w-[560px]">
                            <thead><tr><th>Criterion</th><th>What reviewers look for</th><th class="text-right">Maximum score</th></tr></thead>
                            <tbody>
                                @foreach($form->criteria() as [$name, $what, $max])
                                    <tr><td class="whitespace-nowrap font-medium">{{ $name }}</td><td class="text-[13px] text-muted-foreground">{{ $what }}</td><td class="num">{{ SeedGrant::trimNumber($max) }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
                <p class="mt-3 max-w-3xl text-sm text-gray-700">Recommended for funding at <strong class="font-mono">{{ SeedGrant::trimNumber($form->passMark()) }}/{{ SeedGrant::trimNumber($form->maxTotal()) }}</strong> or above{{ $form->minimumsPhrase(', and only with at least ', ' for ') }}.
                    {{ $form->tieBreak() ? 'Ties go to the stronger ' . collect($form->tieBreak())->map(fn ($k) => mb_strtolower($form->criteria()[$k][0]))->implode(', then ') . ' score.' : '' }}</p>
            </section>
        @endif
    </div>

    <aside class="space-y-4 xl:sticky xl:top-24">
        <x-card title="Apply">
            @if($application)
                <p class="text-sm">You have an application: <span class="font-mono">{{ $application->reference }}</span></p>
                <div class="mt-2">@include('seed.partials.status')</div>
                <x-button variant="primary" class="mt-4 w-full" :href="route($application->isEditableByApplicant() ? 'seed.applications.edit' : 'seed.applications.show', $application)">Open my application</x-button>
            @elseif(! $call->acceptingConceptNotes())
                <p class="text-sm text-muted-foreground">This call is not accepting concept notes.</p>
            @elseif($problems)
                <x-alert tone="warn" icon="info">@foreach($problems as $p)<p>{{ $p }}</p>@endforeach</x-alert>
                @if(auth()->user()->isNreimsLinked())
                    <x-button class="mt-3 w-full" :href="route('profile.complete')">Update my details</x-button>
                @endif
            @else
                <x-alert tone="ok">Your profile meets the eligibility rules.</x-alert>
                <form method="POST" action="{{ route('seed.calls.apply', $call) }}">
                    @csrf
                    <x-button type="submit" variant="primary" size="lg" class="mt-3 w-full" icon-right="arrow-right">Start your concept note</x-button>
                </form>
                <p class="mt-3 text-[13px] text-muted-foreground">Everything is written online and saves when you press Save. No uploads at this stage.</p>
            @endif
        </x-card>

        <div class="rounded-xl border border-dashed border-input p-5 text-sm text-gray-700">
            <p class="font-medium text-foreground">Questions?</p>
            <p class="mt-1">Contact the Grants Management Unit.</p>
            @if($call->proposal_deadline)
                <p class="mt-2 text-[13px] text-muted-foreground">Full proposals from shortlisted applicants are due {{ $call->proposal_deadline->format('j M Y') }}.</p>
            @endif
        </div>
    </aside>
</div>
@endsection
