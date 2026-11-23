@extends('layouts.app')

{{--
    The call page, from the owner's design (4a): a band in the call's own colour with
    the title and summary, the key facts lifted over it, then why, who, how, priorities
    and scoring, with Apply and Key dates beside. GMU also sees where the call stands.
--}}

@php
    use App\Support\SeedGrant;
    $form = $call->form();
    $days = fn ($date) => $date ? (int) now()->startOfDay()->diffInDays($date->copy()->startOfDay(), false) : null;
    $left = $days($call->concept_deadline);
    $open = $call->acceptingConceptNotes();
    $inDays = fn ($n) => $n === null ? null : ($n < 0 ? 'Closed' : ($n === 0 ? 'Today' : 'In ' . $n . ' ' . str('day')->plural($n)));
    $criterionColour = ['bg-primary', 'bg-violet-500', 'bg-sky-500', 'bg-indigo-500', 'bg-pink-500', 'bg-gray-400'];
    $chipColour = ['bg-blue-50 text-blue-700', 'bg-sky-50 text-sky-700', 'bg-violet-50 text-violet-700', 'bg-indigo-50 text-indigo-700'];
    $stages = [
        ['Concept note', 'border-sky-100 bg-sky-50', 'bg-sky-500', 'text-sky-700', null, [
            ['You submit', 'Online form, ≤ ' . SeedGrant::CONCEPT_MAX_PAGES . ' pages, simple budget'],
            ['GMU screens', 'Eligibility, completeness, budget range'],
            ['Reviewers respond', 'Accept, revise or reject'],
            ['Shortlist', 'Invited to stage 2'],
        ]],
        ['Full proposal', 'border-violet-100 bg-violet-50', 'bg-violet-500', 'text-violet-700',
            'shortlisted applicants' . ($call->proposal_deadline ? ', due ' . $call->proposal_deadline->format('j M Y') : ''), [
            ['You submit', '≤ ' . SeedGrant::PROPOSAL_MAX_PAGES . ' pages, detailed budget' . collect($form->documents())->filter(fn ($d) => $d[1])->map(fn ($d) => ', ' . mb_strtolower($d[0]))->implode('')],
            $form->scored() ? ['Scored review', count($form->criteria()) . ' weighted criteria'] : ['Review', 'Accept, revise or reject'],
            ['Approval', collect($form->approvalLevels())->map(fn ($l) => ['gmu' => 'GMU', 'drcp' => 'DRCP', 'dg' => 'DG'][$l])->implode(', then ')],
            ['Agreement', 'Accept online before funds'],
        ]],
    ];
@endphp

@section('title', $call->title)
@section('context', 'Grants · Calls · ' . $call->code)
@section('topbar-colour', $call->tintClass() . ' bg-tint-ink')

@section('content')
<div class="{{ $call->tintClass() }}">
    {{-- The band, in the call's colour --}}
    <div class="-mx-4 -mt-5 flex flex-col gap-3.5 bg-tint-ink px-6 pb-[100px] pt-5 text-white lg:-mx-6 lg:-mt-6 lg:px-8">
        <div class="flex flex-wrap items-center gap-2.5">
            <span class="rounded-md bg-white/15 px-1.5 py-0.5 font-mono text-xs font-medium">{{ $call->code }}</span>
            <span class="text-[13px] text-white/75">FY {{ $call->fiscal_year }}</span>
            <span class="inline-flex h-[22px] items-center gap-1.5 whitespace-nowrap rounded-md bg-white px-2 text-xs font-medium text-tint-ink">
                <span class="size-1.5 rounded-full bg-tint-solid"></span>
                {{ $open ? 'Concept notes open' : ($call->status === 'draft' ? 'Draft: not visible to staff' : 'Concept notes closed') }}
            </span>
            @can('manage-seed-grants')
                <a href="{{ route('seed.calls.edit', $call) }}" class="ml-auto inline-flex h-8 items-center gap-1.5 rounded-md border border-white/30 px-3 text-[13px] font-medium text-white hover:bg-white/10">
                    <x-icon name="sliders" class="size-3.5" /> Call settings
                </a>
            @endcan
        </div>
        <h1 class="max-w-[900px] text-balance text-[36px] font-semibold leading-[1.15] tracking-[-0.03em] text-white">{{ $call->title }}</h1>
        @if($call->summary)
            <p class="max-w-[820px] text-pretty text-base leading-relaxed text-white/85">{{ $call->summary }}</p>
        @endif
    </div>

    <div class="-mt-[68px] flex flex-col gap-8">
        {{-- Key facts, over the band --}}
        <dl class="grid grid-cols-2 gap-3.5 lg:grid-cols-4">
            <div class="rounded-[14px] border border-border bg-card px-[18px] py-4 shadow-lift"><dt class="text-[12.5px] text-muted-foreground">Award per project</dt><dd class="mt-1 text-[22px] font-semibold tracking-[-0.02em] tabular-nums">TZS {{ number_format($call->min_amount / 1e6, 0) }}M – {{ number_format($call->max_amount / 1e6, 0) }}M</dd></div>
            <div class="rounded-[14px] border border-border bg-card px-[18px] py-4 shadow-lift"><dt class="text-[12.5px] text-muted-foreground">Study length</dt><dd class="mt-1 text-[22px] font-semibold tracking-[-0.02em]">{{ $call->duration_months }} months</dd></div>
            <div class="rounded-[14px] bg-tint-solid px-[18px] py-4 text-white shadow-lift">
                <dt class="flex justify-between gap-2 text-[12.5px] text-white/80"><span>Concept notes close</span>@if($left !== null)<span class="font-semibold text-white">{{ $inDays($left) }}</span>@endif</dt>
                <dd class="mt-1 text-[22px] font-semibold tracking-[-0.02em] tabular-nums">{{ $call->concept_deadline?->format('j M Y') ?? 'To be announced' }}</dd>
            </div>
            <div class="rounded-[14px] border border-border bg-card px-[18px] py-4 shadow-lift"><dt class="text-[12.5px] text-muted-foreground">Concept note length</dt><dd class="mt-1 text-[22px] font-semibold tracking-[-0.02em]">≤ {{ SeedGrant::CONCEPT_MAX_PAGES }} pages</dd></div>
        </dl>

        @if($summary)
            {{-- GMU only: where this call stands --}}
            <x-card title="Where this call stands"
                :description="$summary['submitted'] . ' submitted' . ($summary['drafts'] ? ', ' . $summary['drafts'] . ' in draft' : '') . ($summary['closed'] ? ', ' . $summary['closed'] . ' closed' : '') . '. Only GMU sees this.'">
                <x-slot:actions>
                    <x-button size="sm" :href="route('seed.submissions.index', ['call' => $call->id])">Submissions</x-button>
                    <x-button size="sm" :href="route('seed.submissions.export', ['call' => $call->id])" icon="download">Export</x-button>
                </x-slot:actions>
                <ol class="grid grid-cols-4 gap-1.5 sm:grid-cols-8" aria-label="Applications at each stage">
                    @foreach($summary['stages'] as $stage)
                        <li class="rounded-lg px-1.5 py-2 text-center {{ $stage['count'] ? 'bg-primary-soft' : 'bg-muted' }}">
                            <span class="block text-base tabular-nums {{ $stage['count'] ? 'font-semibold text-ok-ink' : 'text-subtle' }}">{{ $stage['count'] }}</span>
                            <span class="mt-0.5 block text-[11px] leading-tight text-muted-foreground">{{ $stage['label'] }}</span>
                        </li>
                    @endforeach
                </ol>
                <dl class="mt-5 grid grid-cols-2 gap-x-6 gap-y-4 lg:grid-cols-4">
                    <x-stat label="Approved" :value="SeedGrant::tzs($summary['approved'])" mono />
                    <x-stat label="Funds available" :value="$call->envelope ? SeedGrant::tzs($call->envelope) : 'Not set'" mono />
                    <x-stat label="Left to commit" :value="$call->envelope ? SeedGrant::tzs(max(0, $call->envelope - $summary['approved'])) : '—'" mono />
                    <x-stat label="Requested, still in play" :value="SeedGrant::tzs($summary['requested'])" mono />
                </dl>
            </x-card>
        @endif

        <div class="grid items-start gap-10 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div class="flex flex-col gap-11">
                @if($form->aboutParagraphs())
                    <section class="flex flex-col gap-3">
                        <h2 class="text-[19px] font-semibold tracking-[-0.015em]">Why this call exists</h2>
                        <div class="flex max-w-[760px] flex-col gap-3 text-[15px] leading-[1.65] text-gray-700">
                            @foreach($form->aboutParagraphs() as $paragraph)<p class="text-pretty">{{ $paragraph }}</p>@endforeach
                        </div>
                    </section>
                @endif

                <section class="flex flex-col gap-3.5">
                    <h2 class="text-[19px] font-semibold tracking-[-0.015em]">Who can lead an application</h2>
                    <ul class="grid gap-3 sm:grid-cols-2">
                        @foreach($form->eligibilityLines() as $rule)
                            <li class="flex items-start gap-3 rounded-xl border border-tint-line bg-tint p-4 text-sm leading-normal">
                                <span class="grid size-[22px] shrink-0 place-items-center rounded-full bg-tint-solid text-white"><x-icon name="check" class="size-3" /></span>{{ $rule }}
                            </li>
                        @endforeach
                    </ul>
                    <p class="text-[13px] text-muted-foreground">One application per person as lead PI.</p>
                </section>

                <section class="flex flex-col gap-3.5">
                    <div class="flex flex-col gap-1.5">
                        <h2 class="text-[19px] font-semibold tracking-[-0.015em]">How it works</h2>
                        <p class="max-w-[760px] text-[15px] text-gray-700">Two stages. Reviewers may send your concept note or proposal back for revision; you respond and it goes round again.</p>
                    </div>
                    <div class="overflow-hidden rounded-[14px] border border-border">
                        @foreach($stages as $n => [$name, $head, $badge, $ink, $note, $steps])
                            <div class="flex items-center gap-2.5 border-b px-[18px] py-3 {{ $head }} {{ $n ? 'border-t' : '' }}">
                                <span class="grid size-[26px] place-items-center rounded-lg text-[13px] font-semibold text-white {{ $badge }}">{{ $n + 1 }}</span>
                                <span class="font-semibold">{{ $name }}</span>
                                @if($note)<span class="text-[13px] text-muted-foreground">· {{ $note }}</span>@endif
                            </div>
                            <ol class="grid gap-2.5 px-[18px] py-4 sm:grid-cols-2 lg:grid-cols-4">
                                @foreach($steps as $i => [$t, $s])
                                    <li class="flex flex-col gap-0.5 rounded-[10px] bg-muted px-3.5 py-3">
                                        <span class="font-mono text-[11px] {{ $ink }}">{{ str_pad($n * 4 + $i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                        <span class="text-[13.5px] font-medium">{{ $t }}</span>
                                        <span class="text-[12.5px] text-muted-foreground">{{ $s }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        @endforeach
                    </div>
                </section>

                @if($form->priorities())
                    <section class="flex flex-col gap-3.5">
                        <div class="flex flex-col gap-1.5">
                            <h2 class="text-[19px] font-semibold tracking-[-0.015em]">Priority areas</h2>
                            @if($form->prioritiesIntro())<p class="max-w-[760px] text-[15px] leading-relaxed text-gray-700">{{ $form->prioritiesIntro() }}</p>@endif
                        </div>
                        <ul class="flex flex-wrap gap-2">
                            @foreach($form->priorities() as $area)
                                <li class="rounded-full px-3.5 py-[7px] text-[13.5px] font-medium {{ $chipColour[$loop->index % 4] }}">{{ $area }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if($form->scored())
                    @php $max = max(1, $form->maxTotal()); $pass = $form->passMark() / $max * 100; @endphp
                    <section class="flex flex-col gap-3.5">
                        <h2 class="text-[19px] font-semibold tracking-[-0.015em]">How full proposals are scored</h2>
                        <div class="overflow-hidden rounded-[14px] border border-border">
                            <div class="flex flex-col gap-2.5 border-b border-border bg-muted px-5 pb-4 pt-[18px]">
                                <div class="relative" role="img" aria-label="Weights of the criteria, out of {{ SeedGrant::trimNumber($form->maxTotal()) }}; pass mark {{ SeedGrant::trimNumber($form->passMark()) }}">
                                    <div class="flex h-4 gap-0.5 overflow-hidden rounded-[5px]">
                                        @foreach(array_values($form->criteria()) as $i => [$name, , $weight])
                                            <span class="{{ $criterionColour[$i % 6] }}" style="width: {{ $weight / $max * 100 }}%"></span>
                                        @endforeach
                                    </div>
                                    <span class="absolute -bottom-1.5 -top-1.5 w-0.5 rounded-full bg-foreground" style="left: {{ $pass }}%"></span>
                                </div>
                                <div class="relative h-4 font-mono text-[11.5px] text-muted-foreground">
                                    <span class="absolute left-0">0</span>
                                    <span class="absolute -translate-x-1/2 whitespace-nowrap font-medium text-foreground" style="left: {{ $pass }}%">Pass {{ SeedGrant::trimNumber($form->passMark()) }}</span>
                                    <span class="absolute right-0">{{ SeedGrant::trimNumber($form->maxTotal()) }}</span>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="data-table min-w-[560px]">
                                    <thead><tr><th>Criterion</th><th>What reviewers look for</th><th class="text-right">Maximum score</th></tr></thead>
                                    <tbody>
                                        @foreach(array_values($form->criteria()) as $i => [$name, $what, $weight])
                                            <tr>
                                                <td class="whitespace-nowrap font-medium"><span class="inline-flex items-center gap-2"><span class="size-2 rounded-sm {{ $criterionColour[$i % 6] }}"></span>{{ $name }}</span></td>
                                                <td class="text-[13px] text-muted-foreground">{{ $what }}</td>
                                                <td class="num">{{ SeedGrant::trimNumber($weight) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <p class="max-w-[820px] text-sm leading-relaxed text-gray-700">Recommended for funding at <strong class="font-mono font-semibold">{{ SeedGrant::trimNumber($form->passMark()) }}/{{ SeedGrant::trimNumber($form->maxTotal()) }}</strong> or above{{ $form->minimumsPhrase(', and only with at least ', ' for ') }}.
                            {{ $form->tieBreak() ? 'Ties go to the stronger ' . collect($form->tieBreak())->map(fn ($k) => mb_strtolower($form->criteria()[$k][0]))->implode(', then ') . ' score.' : '' }}</p>
                    </section>
                @endif
            </div>

            {{-- Apply, and the dates --}}
            <aside class="flex flex-col gap-4 xl:sticky xl:top-20">
                <section class="overflow-hidden rounded-2xl border border-border shadow-lift">
                    <header class="border-b border-border bg-muted px-[18px] py-3.5"><h3 class="text-sm font-semibold">Apply</h3></header>
                    <div class="flex flex-col gap-3.5 bg-card p-[18px]">
                        @if($application)
                            <p class="text-sm">You have an application: <span class="font-mono">{{ $application->reference }}</span></p>
                            <div>@include('seed.partials.status')</div>
                            <a href="{{ route($application->isEditableByApplicant() ? 'seed.applications.edit' : 'seed.applications.show', $application) }}" class="flex h-11 items-center justify-center gap-2 rounded-lg bg-foreground text-sm font-medium text-white hover:bg-gray-800">Open my application <x-icon name="arrow-right" class="size-4" /></a>
                        @elseif(! $open)
                            <p class="text-sm text-muted-foreground">This call is not accepting concept notes.</p>
                        @elseif($problems)
                            <x-alert tone="warn" icon="info">@foreach($problems as $p)<p>{{ $p }}</p>@endforeach</x-alert>
                            @if(auth()->user()->isNreimsLinked())
                                <x-button class="w-full" :href="route('profile.complete.details', ['change' => 1])">Update my details</x-button>
                            @endif
                        @else
                            <div class="flex items-start gap-2.5 rounded-[10px] border border-blue-100 bg-blue-50 px-3.5 py-3 text-[13.5px] leading-snug text-blue-700">
                                <span class="grid size-5 shrink-0 place-items-center rounded-full bg-primary text-white"><x-icon name="check" class="size-[11px]" /></span>Your profile meets the eligibility rules.
                            </div>
                            <form method="POST" action="{{ route('seed.calls.apply', $call) }}">
                                @csrf
                                <button type="submit" class="flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-foreground text-sm font-medium text-white hover:bg-gray-800">Start your concept note <x-icon name="arrow-right" class="size-4" /></button>
                            </form>
                            <p class="text-[13px] leading-normal text-muted-foreground">Everything is written online and saves when you press Save. No uploads at this stage.</p>
                        @endif
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-border">
                    <header class="border-b border-border bg-muted px-[18px] py-3.5"><h3 class="text-sm font-semibold">Key dates</h3><p class="text-[13px] text-muted-foreground">East Africa Time, end of the day</p></header>
                    <ol class="bg-card px-[18px] py-1.5">
                        <li class="flex items-center gap-3 border-b border-gray-100 py-2.5">
                            <span class="w-11 shrink-0 rounded-lg bg-tint py-1.5 text-center leading-tight text-tint-ink">
                                <span class="block text-[17px] font-semibold">{{ $call->concept_deadline?->format('j') ?? '—' }}</span>
                                <span class="block text-[10.5px] uppercase tracking-[0.06em]">{{ $call->concept_deadline?->format('M') }}</span>
                            </span>
                            <span class="flex-1 text-[13.5px] font-semibold">Concept notes close</span>
                            @if($left !== null)<span class="text-xs font-medium text-tint-ink">{{ $inDays($left) }}</span>@endif
                        </li>
                        <li class="flex items-center gap-3 py-2.5">
                            <span class="w-11 shrink-0 rounded-lg bg-violet-50 py-1.5 text-center leading-tight text-violet-700">
                                <span class="block text-[17px] font-semibold">{{ $call->proposal_deadline?->format('j') ?? '—' }}</span>
                                <span class="block text-[10.5px] uppercase tracking-[0.06em]">{{ $call->proposal_deadline?->format('M') }}</span>
                            </span>
                            <span class="flex-1 leading-snug"><span class="block text-[13.5px]">Full proposals due</span><span class="block text-xs text-muted-foreground">Shortlisted applicants</span></span>
                            <span class="text-xs text-muted-foreground">{{ $call->proposal_deadline?->format('Y') ?? 'To be announced' }}</span>
                        </li>
                    </ol>
                </section>

                <div class="flex flex-col gap-1 rounded-2xl border border-dashed border-input p-[18px] text-sm text-gray-700">
                    <p class="font-medium text-foreground">Questions?</p>
                    <p>Contact the Grants Management Unit.</p>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection
