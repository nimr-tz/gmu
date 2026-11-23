@extends('layouts.app')

{{--
    The concept note and full proposal form, from the owner's "Concept Note" design:
    a blue band with the call and the deadline, a save bar that stays in view (save
    state, sections done, length, Save, Check and submit), the section list with
    what is done, the sections in the call document's order, and a check window
    before submitting. Unsaved text is kept on this device until it is saved.
--}}

@php
    use App\Support\ApplicantView as V;
    use App\Support\SeedGrant;
    $call      = $application->call;
    $isConcept = $stage === 'concept';
    $maxPages  = $isConcept ? SeedGrant::CONCEPT_MAX_PAGES : SeedGrant::PROPOSAL_MAX_PAGES;
    $pages     = $application->pageEstimate($stage);
    $lines     = $application->budgetLines($stage);
    $blankRows = max(3, 6 - $lines->count());
    $cell      = 'h-9 w-full rounded-md border-transparent bg-transparent px-2 text-sm shadow-none transition-colors hover:border-border focus:border-primary focus:bg-card focus:ring-2 focus:ring-primary/20';
    $revising  = $application->isRevising();
    $deadline  = $isConcept ? $call->concept_deadline : $call->proposal_deadline;
    $noun      = $isConcept ? 'concept note' : 'full proposal';

    // Section list: [anchor, label, state ok|no|over].
    $nav = [];
    if ($isConcept) {
        $priorDone = ! $form->priorGrant() || $application->priorLargeGrant() === 'no'
            || ($application->priorLargeGrant() === 'yes' && filled($application->conceptField('prior_grant_details')));
        $aDone = $priorDone &&($form->mentorWords() === null || ($application->mentor_name && $application->mentor_suitability));
        $nav[] = ['project', 'A. Applicant', $aDone ? 'ok' : 'no'];
        $nav[] = ['s-title', 'Title', $application->title ? 'ok' : 'no'];
    }
    foreach ($sections as $key => [$heading, , $limit]) {
        $w = SeedGrant::wordCount($application->sectionText($stage, $key));
        $needed = ! $isConcept || $form->sectionRequired($key, $application->choice($key));
        $answered = ! $isConcept || ! isset($form->choices()[$key]) || $application->choice($key) !== null;
        $nav[] = ['s-' . $key, $isConcept ? preg_replace('/^(\d+|[A-Z])\. /', '$1. ', \Illuminate\Support\Str::before($heading, ' and ')) : $heading,
                  $w > $limit ? 'over' : (($w === 0 && $needed) || ! $answered ? 'no' : 'ok')];
    }
    $nav[] = ['budget', $isConcept ? 'D. Budget' : 'Budget', $lines->isEmpty() ? 'no' : 'ok'];
    if ($isConcept && $form->declarations()) {
        $nav[] = ['declaration', 'E. Declaration', count(array_intersect(array_keys($form->declarations()), $application->declarations())) === count($form->declarations()) ? 'ok' : 'no'];
    }
    if (! $isConcept) {
        $nav[] = ['documents', 'Documents', $application->documents->isEmpty() ? 'no' : 'ok'];
    }
    $doneCount = collect($nav)->where(2, 'ok')->count();
    $donePct   = (int) round($doneCount / max(1, count($nav)) * 100);
    $leftCount = count($nav) - $doneCount;
    $todo      = collect($nav)->reject(fn ($n) => $n[2] === 'ok');

    // A section's letter or number, and its title without it.
    $badge = fn ($heading, $i) => preg_match('/^(\d+|[A-Z])\. /', $heading, $mm) ? $mm[1] : (string) ($i + 1);
    $title = fn ($heading) => preg_replace('/^(\d+|[A-Z])\. /', '', $heading);
    // The small word meter under each answer.
    $meterBar = fn ($w, $limit) => $w > $limit ? 'bg-danger' : ($limit && $w / $limit > 0.9 ? 'bg-warn' : 'bg-primary');
    $meter = function ($id, $w, $limit) use ($meterBar) {
        return '<div class="mt-1.5 flex items-center gap-2.5"><div class="h-[3px] flex-1 overflow-hidden rounded-full bg-gray-100"><div data-bar-for="' . e($id) . '" class="h-full rounded-full transition-[width] ' . $meterBar($w, $limit) . '" style="width: ' . min(100, $limit ? $w / $limit * 100 : 0) . '%"></div></div>'
            . '<span data-count-for="' . e($id) . '" class="font-mono text-xs tabular-nums ' . ($w > $limit ? 'text-danger-ink' : 'text-muted-foreground') . '">' . $w . ' / ' . $limit . ' words</span></div>';
    };
    $sectionHead = 'flex items-start gap-3 border-b border-border bg-muted px-[18px] py-3.5';
@endphp

@section('title', $isConcept ? 'Concept note' : 'Full proposal')
@section('context', 'My application · ' . $application->reference . ' · ' . ($isConcept ? 'Concept note' : 'Full proposal'))
@section('topbar-colour', 'bg-primary')

@section('content')
<div x-data="{ check: false }" @keydown.escape.window="check = false">
<form id="app-form" method="POST" action="{{ route('seed.applications.update', $application) }}" data-draft-key="seed-draft-{{ $application->id }}-{{ $stage }}" data-saved-at="{{ $application->updated_at->timestamp }}">
    @csrf @method('PUT')

    {{-- The band --}}
    <div class="-mx-4 -mt-5 flex flex-col gap-2 bg-primary px-7 pb-[22px] pt-[18px] text-white lg:-mx-6 lg:-mt-6">
        <div class="flex flex-wrap items-center gap-2.5">
            <span class="rounded-md bg-white/15 px-1.5 py-0.5 font-mono text-xs font-medium">{{ $call->code }}</span>
            <span class="text-[13px] text-blue-100">{{ $call->title }}</span>
            <span class="inline-flex h-[22px] shrink-0 items-center gap-1.5 whitespace-nowrap rounded-md bg-white/15 px-2 text-xs font-medium text-white"><span class="size-1.5 rounded-full {{ $revising ? 'bg-amber-300' : 'bg-gray-300' }}"></span>{{ $application->statusLabel() }}</span>
        </div>
        <h1 class="text-balance text-[26px] font-semibold leading-tight tracking-[-0.02em] text-white">{{ $application->title ?: ($isConcept ? 'Untitled concept note' : 'Full proposal') }}</h1>
        <p class="text-sm text-blue-100">
            @if($revising && $round?->response_due_at)
                Your response to the reviewers is due {{ $round->response_due_at->format('j M Y') }}{{ ($d = V::daysTo($round->response_due_at)) !== null && $d >= 0 ? ', ' . mb_strtolower(V::when($round->response_due_at)) : '' }}.
            @elseif($deadline)
                {{ $isConcept ? 'Concept notes close' : 'Full proposals are due' }} {{ $deadline->format('j M Y') }}{{ ($d = V::daysTo($deadline)) !== null && $d >= 0 ? ', ' . mb_strtolower(V::when($deadline)) : '' }}.
            @endif
            {{ $isConcept ? 'Write online; nothing is uploaded at this stage.' : 'Upload the mentor letter and your CV under Documents.' }}
        </p>
    </div>

    {{-- The save bar: stays in view while writing --}}
    <div class="sticky top-12 z-20 -mx-4 mb-7 flex flex-wrap items-center gap-x-7 gap-y-3 border-b border-border bg-card/95 px-7 py-3 backdrop-blur lg:-mx-6">
        <div class="flex min-w-[220px] flex-1 items-center gap-2.5">
            <span data-save-dot class="size-2 rounded-full bg-primary"></span>
            <span data-save-state class="text-[13px] text-gray-700">Saved {{ $application->updated_at->diffForHumans() }}</span>
        </div>
        <div class="flex min-w-[130px] flex-[0_1_170px] flex-col gap-1.5">
            <div class="flex justify-between text-xs"><span class="whitespace-nowrap text-muted-foreground">Sections done</span><b class="whitespace-nowrap font-mono font-medium">{{ $doneCount }} / {{ count($nav) }}</b></div>
            <div class="h-1.5 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-primary" style="width: {{ $donePct }}%"></div></div>
        </div>
        <div class="flex min-w-[130px] flex-[0_1_170px] flex-col gap-1.5">
            <div class="flex justify-between text-xs"><span class="text-muted-foreground">Length</span><b class="whitespace-nowrap font-mono font-medium {{ $pages > $maxPages ? 'text-danger-ink' : '' }}" data-pages>{{ $pages }} / {{ $maxPages }} pages</b></div>
            <div class="h-1.5 overflow-hidden rounded-full bg-gray-100"><div data-pages-bar class="h-full rounded-full transition-[width] {{ $pages > $maxPages ? 'bg-danger' : 'bg-primary' }}" style="width: {{ min(100, $pages / $maxPages * 100) }}%"></div></div>
        </div>
        <div class="flex gap-2">
            <button type="submit" data-save class="inline-flex h-9 items-center gap-1.5 whitespace-nowrap rounded-[7px] border border-input bg-card px-3.5 text-[13px] font-medium text-foreground shadow-xs hover:bg-muted"><x-icon name="save" class="size-4" />Save</button>
            <button type="button" @click="check = true" class="inline-flex h-9 items-center gap-1.5 whitespace-nowrap rounded-[7px] bg-foreground px-3.5 text-[13px] font-medium text-white hover:bg-gray-800">{{ $revising ? 'Check and send' : 'Check and submit' }}<x-icon name="arrow-right" class="size-4" /></button>
        </div>
    </div>

    <x-alert tone="info" class="mb-6 hidden" data-restored title="Unsaved text restored">
        Text you typed on this device since your last save has been put back. Press Save to keep it.
        <x-slot:actions><x-button size="sm" variant="ghost" data-discard>Discard it</x-button></x-slot:actions>
    </x-alert>

    <div class="flex flex-wrap items-start gap-8">
        {{-- Sections, with what is done --}}
        <aside class="flex flex-[0_0_250px] flex-col gap-3.5 max-lg:hidden lg:sticky lg:top-[7.5rem]">
            <div class="flex items-center gap-3.5 rounded-[14px] border border-border bg-muted p-3.5">
                <span class="relative grid size-[52px] shrink-0 place-items-center">
                    <svg viewBox="0 0 36 36" class="absolute inset-0 size-full -rotate-90" aria-hidden="true">
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke-width="4" class="stroke-gray-200"></circle>
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke-width="4" class="stroke-primary" stroke-dasharray="{{ round($donePct / 100 * 97.4, 1) }} 97.4"></circle>
                    </svg>
                    <span class="relative font-mono text-[11px] font-medium">{{ $donePct }}%</span>
                </span>
                <div class="leading-snug"><p class="text-[13.5px] font-semibold">{{ $doneCount }} of {{ count($nav) }} sections done</p><p class="text-[12.5px] text-muted-foreground">{{ $leftCount === 0 ? 'Nothing left to write' : $leftCount . ' still to do' }}</p></div>
            </div>
            <ul class="flex flex-col gap-px" aria-label="Sections">
                @foreach($nav as [$anchor, $label, $state])
                    <li>
                        <a href="#{{ $anchor }}" class="flex items-center gap-2.5 rounded-md px-2 py-1.5 text-[13px] text-gray-700 hover:bg-gray-100 hover:text-foreground">
                            @if($state === 'ok')<span class="grid size-4 shrink-0 place-items-center rounded-full bg-primary text-white"><x-icon name="check" class="size-2.5" /></span>
                            @elseif($state === 'over')<span class="grid size-4 shrink-0 place-items-center rounded-full bg-danger-soft text-[10px] font-bold text-danger-ink">!</span>
                            @else<span class="size-4 shrink-0 rounded-full border-[1.5px] border-gray-300"></span>@endif
                            <span class="truncate">{{ $label }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
            @if($problems)
                <div class="rounded-xl border border-warn-line bg-warn-soft p-3.5">
                    <p class="text-[13px] font-semibold text-warn-ink">Before you can submit</p>
                    <ul class="mt-2 flex list-disc flex-col gap-1 pl-4 text-[12.5px] leading-snug text-warn-ink">@foreach($problems as $p)<li>{{ $p }}</li>@endforeach</ul>
                    <p class="mt-2 text-[11px] text-warn-ink/80">As of your last save.</p>
                </div>
            @else
                <div class="rounded-xl border border-blue-100 bg-blue-50 p-3.5"><p class="text-[13px] font-semibold text-blue-700">Ready to submit</p><p class="mt-1 text-[12.5px] text-blue-700">Everything required is in place.</p></div>
            @endif
        </aside>

        <div class="flex min-w-0 max-w-[980px] flex-[1_1_520px] flex-col gap-5">
            @if($isConcept && $application->status === SeedGrant::DRAFT && $application->screened_at && $application->screening_note)
                <x-alert tone="warn" icon="rotate-ccw" title="GMU returned your concept note for correction">
                    <p class="whitespace-pre-line">{{ $application->screening_note }}</p>
                    <p class="mt-1 text-[13px]">Make the change, then submit again.</p>
                </x-alert>
            @endif

            @if($round)
                <section id="response" class="scroll-mt-40 overflow-hidden rounded-[14px] border border-warn-line">
                    <header class="flex items-start gap-3 border-b border-warn-line bg-warn-soft px-[18px] py-3.5">
                        <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-warn text-white"><x-icon name="message-square" class="size-3.5" /></span>
                        <div><h2 class="text-[15px] font-semibold">Your response to the reviewers</h2><p class="text-[13px] text-gray-700">Say what you changed for each comment, or why you did not.{{ $round->response_due_at ? ' Due ' . $round->response_due_at->format('j M Y') . '.' : '' }}</p></div>
                    </header>
                    <div class="bg-card p-5"><textarea name="response" class="textarea" rows="6" aria-label="Your response to the reviewers" data-keep>{{ old('response') }}</textarea></div>
                </section>
            @endif

            @if($application->rounds->whereNotNull('decision')->isNotEmpty())
                <details class="group" @if($round) open @endif>
                    <summary class="mb-3 flex cursor-pointer list-none items-center gap-2 text-sm font-semibold [&::-webkit-details-marker]:hidden">
                        <x-icon name="chevron-right" class="text-subtle transition-transform group-open:rotate-90" /> Reviewer feedback
                    </summary>
                    @include('seed.partials.feedback', ['compact' => true])
                </details>
            @endif

            @if($isConcept)
                @php
                    $pi = $application->pi;
                    $service = $pi->serviceLength();
                    $prior = old('prior_grant', $application->priorLargeGrant());
                    $coInv = old('co_investigators', $application->conceptField('co_investigators'));
                    $expertise = old('mentor_suitability', $application->mentor_suitability);
                    $priorDetails = old('prior_grant_details', $application->conceptField('prior_grant_details'));
                @endphp
                {{-- A --}}
                <section id="project" class="scroll-mt-40 overflow-hidden rounded-[14px] border border-border">
                    <header class="{{ $sectionHead }}">
                        <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-primary text-[13px] font-semibold text-white">A</span>
                        <div><h2 class="text-[15px] font-semibold">Applicant and project information</h2><p class="text-[13px] text-muted-foreground">Your details come from your profile. If any is wrong, ask GMU to correct it.</p></div>
                    </header>
                    <div class="flex flex-col gap-[22px] bg-card p-5">
                        <dl class="grid grid-cols-[repeat(auto-fit,minmax(170px,1fr))] gap-x-5 gap-y-4 rounded-xl border border-blue-100 bg-blue-50/60 p-4">
                            @foreach([
                                'Lead applicant (PI)'       => $pi->name,
                                'Current designation'       => $pi->designation,
                                'NIMR centre'               => $pi->research_center,
                                'Department or section'     => $pi->department,
                                'Email'                     => $pi->email,
                                'Telephone'                 => $pi->phone,
                                'Employment'                => \App\Models\User::EMPLOYMENT_TYPES[$pi->employment_type] ?? null,
                                'Date of first appointment' => $pi->first_appointment_date?->format('j M Y'),
                            ] as $k => $v)
                                <div class="min-w-0"><dt class="text-xs text-muted-foreground">{{ $k }}</dt><dd class="mt-0.5 truncate text-[13.5px] font-medium">{{ $v ?: '—' }}</dd>
                                    @if($k === 'Date of first appointment' && $service)<dd class="mt-0.5 text-xs text-blue-700">{{ $service['years'] }} y {{ $service['months'] }} m{{ $form->serviceYears() && $pi->withinServiceLimit($form->serviceYears()) ? ', under ' . $form->serviceYears() . ' years' : '' }}</dd>@endif
                                </div>
                            @endforeach
                        </dl>

                        @if($priorRule = $form->priorGrant())
                            <fieldset class="flex flex-col gap-2.5">
                                <legend class="mb-2.5 text-[13.5px] font-medium">Have you previously received a research grant exceeding USD {{ number_format($priorRule['usd']) }} as Principal Investigator?</legend>
                                <div class="flex gap-2">
                                    @foreach(['no' => 'No', 'yes' => 'Yes'] as $value => $label)
                                        <label class="flex h-9 cursor-pointer items-center gap-2 rounded-lg border border-input px-3.5 text-[13.5px] has-[:checked]:border-primary has-[:checked]:bg-blue-50"><input type="radio" class="radio" name="prior_grant" value="{{ $value }}" @checked($prior === $value) data-reveal="prior-details" data-reveal-on="yes">{{ $label }}</label>
                                    @endforeach
                                </div>
                                <div id="prior-details" @if($prior !== 'yes') hidden @endif>
                                    <label for="prior_grant_details" class="label">Grant title, funding organisation and amount awarded</label>
                                    <textarea id="prior_grant_details" name="prior_grant_details" class="textarea mt-1.5" rows="2" data-words data-limit="{{ $priorRule['words'] }}">{{ $priorDetails }}</textarea>
                                    {!! $meter('prior_grant_details', SeedGrant::wordCount($priorDetails), $priorRule['words']) !!}
                                </div>
                            </fieldset>
                        @endif

                        <div class="grid gap-4 sm:grid-cols-2">
                            @if($form->mentorWords() !== null)
                                <x-field label="Proposed scientific mentor’s full name" name="mentor_name">
                                    <input id="mentor_name" name="mentor_name" class="input" value="{{ old('mentor_name', $application->mentor_name) }}" data-keep>
                                </x-field>
                                <x-field label="Mentor’s designation" name="mentor_designation">
                                    <input id="mentor_designation" name="mentor_designation" class="input" value="{{ old('mentor_designation', $application->mentor_designation) }}" data-keep>
                                </x-field>
                                <x-field class="sm:col-span-2" label="Mentor’s NIMR centre or section" name="mentor_centre">
                                    <input id="mentor_centre" name="mentor_centre" class="input" value="{{ old('mentor_centre', $application->mentor_centre) }}" data-keep>
                                </x-field>
                                <div class="sm:col-span-2">
                                    <label for="mentor_suitability" class="label">The mentor’s expertise relevant to the proposed study</label>
                                    <textarea id="mentor_suitability" name="mentor_suitability" class="textarea mt-1.5" rows="3" data-words data-limit="{{ $form->mentorWords() }}">{{ $expertise }}</textarea>
                                    {!! $meter('mentor_suitability', SeedGrant::wordCount($expertise), $form->mentorWords()) !!}
                                </div>
                            @endif
                            @if($form->coInvestigatorWords() !== null)
                                <div class="sm:col-span-2">
                                    <label for="co_investigators" class="label">Proposed co-investigators or collaborators <span class="font-normal text-muted-foreground">(if any)</span></label>
                                    <p class="text-xs text-muted-foreground">Names, designations and institutional affiliations.</p>
                                    <textarea id="co_investigators" name="co_investigators" class="textarea mt-1.5" rows="2" data-words data-limit="{{ $form->coInvestigatorWords() }}">{{ $coInv }}</textarea>
                                    {!! $meter('co_investigators', SeedGrant::wordCount($coInv), $form->coInvestigatorWords()) !!}
                                </div>
                            @endif
                        </div>
                        <p class="text-[13px] text-muted-foreground">Total funding requested is taken from your budget below: TZS {{ number_format($call->min_amount) }} to {{ number_format($call->max_amount) }}.</p>
                    </div>
                </section>

                {{-- B: the title --}}
                <section id="s-title" class="scroll-mt-40 overflow-hidden rounded-[14px] border border-border">
                    <header class="{{ $sectionHead }} items-center">
                        <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-sky-500 text-[13px] font-semibold text-white">B</span>
                        <h2 class="text-[15px] font-semibold">Concept note technical questions</h2>
                    </header>
                    <div class="bg-card p-5">
                        @php $titleText = old('title', $application->title); @endphp
                        <label for="title" class="label">Proposed project title</label>
                        <input id="title" name="title" class="input mt-1.5 h-[42px] text-[15px] font-medium" maxlength="255" value="{{ $titleText }}" data-words data-limit="{{ $form->titleWords() }}">
                        {!! $meter('title', SeedGrant::wordCount($titleText), $form->titleWords()) !!}
                    </div>
                </section>
            @endif

            {{-- The questions --}}
            @foreach(array_keys($sections) as $i => $key)
                @php
                    [$heading, $guidance, $limit] = $sections[$key];
                    $text = old("sections.$key", $application->sectionText($stage, $key));
                    $choice = $isConcept ? ($form->choices()[$key] ?? null) : null;
                    $answer = $choice ? old("choices.$key", $application->choice($key)) : null;
                    $showText = ! $choice || in_array($answer, $choice['describe'], true);
                @endphp
                <section id="s-{{ $key }}" class="scroll-mt-40 overflow-hidden rounded-[14px] border border-border bg-card">
                    <div class="flex flex-col gap-3 px-5 py-[18px]">
                        <div class="flex items-start gap-3">
                            <span class="grid h-7 min-w-7 shrink-0 place-items-center rounded-lg bg-sky-100 px-1.5 font-mono text-[12.5px] font-medium text-sky-700">{{ $badge($heading, $i) }}</span>
                            <div class="flex flex-col gap-0.5 pt-0.5">
                                <h3 class="text-[15px] font-semibold leading-snug"><label for="sec-{{ $key }}">{{ $title($heading) }}</label></h3>
                                @if($choice)<p class="text-pretty text-[13px] leading-normal text-muted-foreground">{{ $choice['question'] }}</p>
                                @elseif($guidance)<p class="text-pretty text-[13px] leading-normal text-muted-foreground">{{ $guidance }}</p>@endif
                            </div>
                        </div>
                        @if($choice)
                            <fieldset class="flex flex-wrap gap-2 pl-10">
                                <legend class="sr-only">{{ $title($heading) }}</legend>
                                @foreach($choice['options'] as $value => $label)
                                    <label class="flex h-9 cursor-pointer items-center gap-2 rounded-lg border border-input px-3.5 text-[13.5px] has-[:checked]:border-primary has-[:checked]:bg-blue-50"><input type="radio" class="radio" name="choices[{{ $key }}]" value="{{ $value }}" @checked($answer === $value)
                                           data-reveal="describe-{{ $key }}" data-reveal-on="{{ implode(',', $choice['describe']) }}">{{ $label }}</label>
                                @endforeach
                            </fieldset>
                        @endif
                        <div id="describe-{{ $key }}" class="flex flex-col gap-1.5 pl-10" @unless($showText) hidden @endunless>
                            @if($choice)<span class="text-[13px] font-medium">{{ $guidance }}</span>@endif
                            <textarea id="sec-{{ $key }}" name="sections[{{ $key }}]" class="textarea text-[14.5px] leading-[1.65]" rows="{{ $limit <= 150 ? 4 : 7 }}" placeholder="Write here"
                                      data-words data-page data-limit="{{ $limit }}">{{ $text }}</textarea>
                            {!! $meter('sec-' . $key, SeedGrant::wordCount($text), $limit) !!}
                        </div>
                    </div>
                </section>
            @endforeach

            {{-- References: optional, outside the word and page limits --}}
            @php $refs = old('references', $application->references($stage)); @endphp
            <section id="references" class="scroll-mt-40 overflow-hidden rounded-[14px] border border-border bg-card">
                <div class="flex flex-col gap-3 px-5 py-[18px]">
                    <div class="flex items-start gap-3">
                        <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-sky-100 text-sky-700"><x-icon name="list" class="size-3.5" /></span>
                        <div class="flex flex-col gap-0.5 pt-0.5">
                            <h3 class="text-[15px] font-semibold leading-snug"><label for="references-text">References <span class="font-normal text-muted-foreground">(optional)</span></label></h3>
                            <p class="text-pretty text-[13px] leading-normal text-muted-foreground">The sources you cite, one per line. They do not count towards the word limits or the {{ $maxPages }}-page length.</p>
                        </div>
                    </div>
                    <div class="pl-10">
                        <textarea id="references-text" name="references" class="textarea text-[13.5px] leading-[1.6]" rows="5" placeholder="Author, title, journal, year">{{ $refs }}</textarea>
                    </div>
                </div>
            </section>

            {{-- D: the budget --}}
            <section id="budget" class="scroll-mt-40 overflow-hidden rounded-[14px] border border-border">
                <header class="{{ $sectionHead }}">
                    <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-indigo-500 text-[13px] font-semibold text-white">@if($isConcept)D@else<x-icon name="wallet" class="size-3.5" />@endif</span>
                    <div><h2 class="text-[15px] font-semibold">{{ $isConcept ? 'Estimated budget' : 'Detailed budget' }}</h2><p class="text-[13px] text-muted-foreground">{{ $isConcept ? 'A detailed quarterly budget comes at stage 2.' : 'From the NIMR finance template: four categories by quarter.' }}</p></div>
                </header>
                <div class="overflow-x-auto bg-card">
                    <table class="w-full text-sm {{ $isConcept ? 'min-w-[460px]' : 'min-w-[860px]' }}" data-budget>
                        <thead class="bg-gray-50 text-xs text-muted-foreground">
                            <tr>
                                @unless($isConcept)<th class="w-40 border-b border-border px-3 py-2 pl-5 text-left font-medium">Category</th>@endunless
                                <th class="border-b border-border px-3 py-2 text-left font-medium {{ $isConcept ? 'pl-5' : '' }}">Item</th>
                                @if($isConcept)
                                    <th class="w-56 border-b border-border px-3 py-2 pr-5 text-right font-medium">Amount (TZS)</th>
                                @else
                                    @foreach(['q1', 'q2', 'q3', 'q4'] as $q)<th class="w-32 border-b border-border px-3 py-2 text-right font-medium last:pr-5">{{ strtoupper($q) }}</th>@endforeach
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($lines->concat(array_fill(0, $blankRows, null)) as $i => $line)
                                <tr data-row>
                                    @unless($isConcept)
                                        <td class="py-1 pl-3 pr-1">
                                            <select name="budget[{{ $i }}][category]" class="select {{ $cell }} pr-7" aria-label="Category" data-category>
                                                <option value="">—</option>
                                                @foreach(SeedGrant::BUDGET_CATEGORIES as $value => $label)
                                                    <option value="{{ $value }}" @selected($line?->category === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    @endunless
                                    <td class="py-1 {{ $isConcept ? 'pl-3' : '' }} pr-1"><input name="budget[{{ $i }}][item]" class="input {{ $cell }}" aria-label="Item" value="{{ $line?->item }}" maxlength="255" placeholder="Describe the item"></td>
                                    @if($isConcept)
                                        <td class="py-1 pr-3"><input name="budget[{{ $i }}][amount]" class="input {{ $cell }} text-right font-mono" aria-label="Amount" inputmode="numeric" value="{{ $line ? number_format($line->q1) : '' }}" data-amount placeholder="0"></td>
                                    @else
                                        @foreach(['q1', 'q2', 'q3', 'q4'] as $q)
                                            <td class="py-1 pr-1 last:pr-3"><input name="budget[{{ $i }}][{{ $q }}]" class="input {{ $cell }} text-right font-mono" aria-label="{{ strtoupper($q) }}" inputmode="numeric" value="{{ $line && $line->$q ? number_format($line->$q) : '' }}" data-amount placeholder="0"></td>
                                        @endforeach
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col gap-3.5 bg-card px-5 pb-5 pt-3">
                    <button type="button" data-add-row class="inline-flex h-7 items-center gap-1.5 self-start rounded-md px-2.5 text-[12.5px] font-medium text-muted-foreground hover:bg-gray-100 hover:text-foreground"><x-icon name="plus" class="size-3.5" />Add line</button>
                    @php $minPct = $call->max_amount ? $call->min_amount / $call->max_amount * 100 : 0; @endphp
                    <div class="flex flex-col gap-2.5 rounded-xl bg-muted px-[18px] py-4">
                        <div class="flex items-baseline justify-between"><span class="text-sm font-semibold">Total</span><span class="font-mono text-[22px] font-semibold tabular-nums" data-total>{{ SeedGrant::tzs($application->budgetTotal($stage)) }}</span></div>
                        <div class="relative h-2.5 overflow-hidden rounded-full bg-card ring-1 ring-inset ring-border" data-range>
                            <div class="absolute inset-y-0 left-0 z-10 rounded-full bg-primary transition-[width]" style="width: 0%"></div>
                            <div class="absolute inset-y-0 right-0 bg-blue-50" style="left: {{ $minPct }}%" aria-hidden="true"></div>
                        </div>
                        <div class="relative h-4 font-mono text-[11.5px] text-muted-foreground">
                            <span class="absolute left-0">0</span>
                            <span class="absolute -translate-x-1/2 whitespace-nowrap" style="left: {{ $minPct }}%">{{ number_format($call->min_amount) }} min</span>
                            <span class="absolute right-0">{{ number_format($call->max_amount) }} max</span>
                        </div>
                        <p class="text-[13px] font-medium" data-range-note></p>
                        @unless($isConcept)<p class="text-[13px]" data-equipment-note></p>@endunless
                    </div>
                </div>
            </section>

            @if($isConcept && $form->declarations())
                @php $ticked = old('declarations', $application->declarations()); @endphp
                {{-- E: the declaration --}}
                <section id="declaration" class="scroll-mt-40 overflow-hidden rounded-[14px] border border-border">
                    <header class="{{ $sectionHead }}">
                        <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-foreground text-[13px] font-semibold text-white">E</span>
                        <div><h2 class="text-[15px] font-semibold">Applicant declaration</h2><p class="text-[13px] text-muted-foreground">Tick every statement. Submitting from your account stands as your signature and date.</p></div>
                    </header>
                    <div class="flex flex-col gap-2.5 bg-card px-5 py-[18px]">
                        @foreach($form->declarations() as $value => $statement)
                            <label class="flex cursor-pointer items-start gap-3 rounded-[10px] border border-border px-3.5 py-3 text-sm leading-normal hover:bg-gray-50">
                                <input type="checkbox" class="checkbox mt-0.5" name="declarations[]" value="{{ $value }}" @checked(in_array($value, (array) $ticked, true))>
                                <span>{{ $statement }}</span>
                            </label>
                        @endforeach
                        <p class="mt-1 text-[13px] text-muted-foreground">Applicant: <strong class="font-semibold text-foreground">{{ $application->pi->name }}</strong></p>
                    </div>
                </section>
            @endif

            <div class="flex justify-end gap-2 pb-6 pt-1">
                <button type="submit" class="h-10 rounded-[7px] border border-input bg-card px-4 text-sm font-medium text-foreground hover:bg-muted">Save</button>
                <button type="button" @click="check = true" class="inline-flex h-10 items-center gap-1.5 rounded-[7px] bg-foreground px-4 text-sm font-medium text-white hover:bg-gray-800">{{ $revising ? 'Check and send' : 'Check and submit' }}<x-icon name="arrow-right" class="size-4" /></button>
            </div>
        </div>
    </div>
</form>

@unless($isConcept)
    <div class="mt-2 lg:ml-[calc(250px+2rem)] lg:max-w-[980px]">
        <section id="documents" class="scroll-mt-40 overflow-hidden rounded-[14px] border border-border">
            <header class="{{ $sectionHead }}">
                <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-foreground text-white"><x-icon name="paperclip" class="size-3.5" /></span>
                <div><h2 class="text-[15px] font-semibold">Supporting documents</h2><p class="text-[13px] text-muted-foreground">PDF or Word, up to 10 MB each.</p></div>
            </header>
            <div class="bg-card p-5">
                <ul class="divide-y divide-border overflow-hidden rounded-lg border border-border">
                    @forelse($application->documents as $doc)
                        <li class="flex items-center gap-3 px-4 py-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-muted font-mono text-[10px] font-medium text-muted-foreground">{{ strtoupper(pathinfo($doc->file_name, PATHINFO_EXTENSION)) }}</span>
                            <span class="min-w-0 flex-1"><span class="block text-sm font-medium">{{ $doc->kindLabel() }}</span>
                                <a href="{{ route('seed.applications.documents.download', [$application, $doc]) }}" class="block truncate text-[13px] text-primary hover:underline">{{ $doc->file_name }}</a></span>
                            <form method="POST" action="{{ route('seed.applications.documents.destroy', [$application, $doc]) }}" onsubmit="return confirm('Remove this document?')">
                                @csrf @method('DELETE')
                                <x-button type="submit" size="sm" variant="danger" icon="trash">Remove</x-button>
                            </form>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-sm text-muted-foreground">Nothing uploaded yet.</li>
                    @endforelse
                </ul>
                <ul class="mt-3 flex flex-wrap gap-2 text-xs">
                    @foreach($form->documents() as $value => [$label, $required])
                        @php $has = $application->documents->contains('kind', $value); @endphp
                        <li><x-badge :tone="$has ? 'ok' : ($required ? 'warn' : 'neutral')" :icon="$has ? 'check' : null">{{ $label }}{{ $required ? '' : ' (optional)' }}</x-badge></li>
                    @endforeach
                </ul>
                <form method="POST" action="{{ route('seed.applications.documents.store', $application) }}" enctype="multipart/form-data" class="mt-5 grid items-end gap-3 border-t border-border pt-5 sm:grid-cols-[12rem_minmax(0,1fr)_auto]">
                    @csrf
                    <x-field label="Document" name="kind">
                        <select id="kind" name="kind" class="select">
                            @foreach($form->documents() as $value => [$label, $required])
                                <option value="{{ $value }}">{{ $label }}{{ $required ? '' : ' (optional)' }}</option>
                            @endforeach
                        </select>
                    </x-field>
                    <x-field label="File" name="file">
                        <input id="file" type="file" name="file" accept=".pdf,.doc,.docx" required
                               class="block w-full text-sm text-muted-foreground file:mr-3 file:h-9 file:rounded-md file:border file:border-solid file:border-border file:bg-card file:px-3 file:text-sm file:font-medium file:text-foreground hover:file:bg-muted">
                    </x-field>
                    <x-button type="submit" icon="upload">Upload</x-button>
                </form>
            </div>
        </section>
    </div>
@endunless

{{-- The check window, before submitting --}}
<div x-cloak x-show="check" x-transition.opacity class="fixed inset-0 z-50 grid place-items-center bg-foreground/50 p-6" @click.self="check = false" role="dialog" aria-modal="true" aria-labelledby="check-title">
    <div class="flex max-h-[calc(100vh-48px)] w-full max-w-[620px] flex-col overflow-auto rounded-[18px] bg-card shadow-pop">
        <header class="flex flex-col gap-1 border-b border-border px-6 pb-4 pt-5">
            <p class="font-mono text-xs text-muted-foreground">{{ $application->reference }} · {{ $isConcept ? 'Concept note' : 'Full proposal' }}</p>
            <h2 id="check-title" class="text-xl font-semibold tracking-[-0.015em]">{{ $problems ? 'Almost there: ' . count($problems) . ' ' . (count($problems) === 1 ? 'thing' : 'things') . ' to finish' : ($revising ? 'Ready to send your response' : 'Ready to submit your ' . $noun) }}</h2>
        </header>
        <div class="flex flex-col gap-4 px-6 py-[18px]">
            <div class="grid grid-cols-3 gap-2.5">
                <div class="rounded-[10px] bg-muted px-3.5 py-3"><p class="text-xs text-muted-foreground">Sections done</p><p class="mt-0.5 font-mono text-base font-medium">{{ $doneCount }} / {{ count($nav) }}</p></div>
                <div class="rounded-[10px] bg-muted px-3.5 py-3"><p class="text-xs text-muted-foreground">Length</p><p class="mt-0.5 font-mono text-base font-medium">{{ $pages }} / {{ $maxPages }} pages</p></div>
                <div class="rounded-[10px] bg-muted px-3.5 py-3"><p class="text-xs text-muted-foreground">Requested</p><p class="mt-0.5 font-mono text-base font-medium">TZS {{ number_format($application->budgetTotal($stage) / 1e6, 2) }}M</p></div>
            </div>
            <p class="text-xs text-muted-foreground" data-check-unsaved hidden>You have unsaved changes. This check is as of your last save: save to check again.</p>
            @if($problems)
                <div class="rounded-xl border border-warn-line bg-warn-soft px-4 py-3.5">
                    <p class="text-[13.5px] font-semibold text-warn-ink">Fix these first</p>
                    <ul class="mt-2 flex flex-col gap-1.5">
                        @foreach($todo as [$anchor, $label])
                            <li class="flex items-center gap-2 text-[13.5px] text-warn-ink"><span class="size-1.5 shrink-0 rounded-full bg-warn"></span><span class="flex-1">{{ $label }}</span><a href="#{{ $anchor }}" @click="check = false" class="text-[13px] font-medium text-warn-ink underline">Go there</a></li>
                        @endforeach
                        @foreach($problems as $p)
                            <li class="flex items-center gap-2 text-[13.5px] text-warn-ink"><span class="size-1.5 shrink-0 rounded-full bg-warn"></span><span class="flex-1">{{ $p }}</span></li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="flex flex-wrap gap-1.5">
                    @foreach($nav as [, $label])<span class="inline-flex h-[26px] items-center gap-1.5 rounded-[7px] bg-blue-50 px-2.5 text-[12.5px] font-medium text-blue-700"><x-icon name="check" class="size-[11px]" />{{ $label }}</span>@endforeach
                </div>
                <div class="text-pretty rounded-xl bg-muted px-4 py-3.5 text-[13.5px] leading-relaxed text-gray-700">
                    @if($revising)
                        <b class="font-semibold text-foreground">When you send it,</b> your revised version and your response go back to the reviewers. Submitting from your account stands as your signature and date.
                    @else
                        <b class="font-semibold text-foreground">When you submit,</b> the {{ $noun }} is locked and goes to GMU {{ $isConcept ? 'for screening' : 'for review' }}. You cannot change it afterwards unless GMU returns it for correction. Submitting from your account stands as your signature and date.
                    @endif
                </div>
            @endif
        </div>
        <footer class="flex justify-end gap-2 border-t border-border px-6 pb-5 pt-3.5">
            <button type="button" @click="check = false" class="h-10 rounded-[7px] border border-input bg-card px-4 text-sm font-medium text-foreground hover:bg-muted">Go back and edit</button>
            @if($problems)
                <button type="submit" form="app-form" class="h-10 rounded-[7px] bg-foreground px-4 text-sm font-medium text-white hover:bg-gray-800">Save and check again</button>
            @else
                <button type="submit" form="app-form" formaction="{{ route('seed.applications.submit', $application) }}" class="inline-flex h-10 items-center gap-2 rounded-[7px] bg-primary px-4 text-sm font-semibold text-white hover:bg-blue-700">
                    <x-icon name="send" class="size-4" />{{ $revising ? 'Send response to reviewers' : ($isConcept ? 'Submit concept note' : 'Submit full proposal') }}
                </button>
            @endif
        </footer>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var min = {{ (int) $call->min_amount }}, max = {{ (int) $call->max_amount }};
    var cap = {{ (int) ($form->equipmentCap() ?? 100) }}, perPage = {{ SeedGrant::WORDS_PER_PAGE }}, maxPages = {{ $maxPages }};
    var form = document.getElementById('app-form');
    function words(t) { t = t.trim(); return t ? t.split(/\s+/).length : 0; }
    function num(v) { return parseInt(String(v).replace(/[^0-9]/g, ''), 10) || 0; }
    function tone(el, bad, good) { el.classList.remove('text-danger-ink', 'text-blue-700', 'text-muted-foreground'); el.classList.add(bad ? 'text-danger-ink' : good); }
    function bar(el, cls) { el.classList.remove('bg-primary', 'bg-warn', 'bg-danger'); el.classList.add(cls); }

    // Word counts, their meters, and the page estimate.
    var areas = form.querySelectorAll('[data-words]');
    function updateWords() {
        var all = 0;
        areas.forEach(function (a) {
            var w = words(a.value), limit = +a.dataset.limit;
            var out = document.querySelector('[data-count-for="' + a.id + '"]'), meter = document.querySelector('[data-bar-for="' + a.id + '"]');
            if (a.hasAttribute('data-page')) all += w;
            if (out) { out.textContent = w + ' / ' + limit + ' words'; tone(out, w > limit, 'text-muted-foreground'); }
            if (meter) { meter.style.width = Math.min(100, limit ? w / limit * 100 : 0) + '%'; bar(meter, w > limit ? 'bg-danger' : (limit && w / limit > 0.9 ? 'bg-warn' : 'bg-primary')); }
            a.setAttribute('aria-invalid', w > limit ? 'true' : 'false');
        });
        var pages = Math.round(all / perPage * 10) / 10, pb = document.querySelector('[data-pages-bar]'), pt = document.querySelector('[data-pages]');
        pt.textContent = pages + ' / ' + maxPages + ' pages';
        pt.classList.toggle('text-danger-ink', pages > maxPages);
        pb.style.width = Math.min(100, pages / maxPages * 100) + '%';
        bar(pb, pages > maxPages ? 'bg-danger' : 'bg-primary');
    }
    areas.forEach(function (a) { a.addEventListener('input', updateWords); });

    // Budget totals, the award range and the equipment limit.
    var table = form.querySelector('[data-budget]');
    function updateBudget() {
        var total = 0, equipment = 0;
        table.querySelectorAll('[data-row]').forEach(function (row) {
            var sum = 0;
            row.querySelectorAll('[data-amount]').forEach(function (i) { sum += num(i.value); });
            total += sum;
            var cat = row.querySelector('[data-category]');
            if (cat && cat.value === 'equipment') equipment += sum;
        });
        document.querySelector('[data-total]').textContent = 'TZS ' + total.toLocaleString('en-US');
        var fill = document.querySelector('[data-range]').firstElementChild, ok = total >= min && total <= max;
        fill.style.width = Math.min(100, total / max * 100) + '%';
        bar(fill, total > 0 && !ok ? 'bg-danger' : 'bg-primary');
        var note = document.querySelector('[data-range-note]');
        note.textContent = total === 0 ? 'Add lines to see your total against the award range.' : ok ? 'Within the allowed range.' : (total < min ? 'Below the minimum award.' : 'Above the maximum award.');
        tone(note, total > 0 && !ok, total === 0 ? 'text-muted-foreground' : 'text-blue-700');
        var eq = document.querySelector('[data-equipment-note]');
        if (eq && total > 0) {
            var pct = equipment / total * 100;
            eq.textContent = 'Equipment ' + pct.toFixed(1) + '% of the budget (limit ' + cap + '%).';
            tone(eq, pct > cap, 'text-muted-foreground');
        }
    }
    table.addEventListener('input', updateBudget);
    table.addEventListener('change', updateBudget);

    document.querySelector('[data-add-row]').addEventListener('click', function () {
        var rows = table.querySelectorAll('[data-row]'), last = rows[rows.length - 1], next = last.cloneNode(true), index = rows.length;
        next.querySelectorAll('input, select').forEach(function (el) {
            el.name = el.name.replace(/budget\[\d+\]/, 'budget[' + index + ']');
            el.value = '';
        });
        last.parentNode.appendChild(next);
        next.querySelector('input').focus();
    });

    // Questions that ask for a description only for some answers.
    form.querySelectorAll('[data-reveal]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            var target = document.getElementById(radio.dataset.reveal);
            if (target && radio.checked) target.hidden = radio.dataset.revealOn.split(',').indexOf(radio.value) === -1;
        });
    });

    // Nothing typed is lost: unsaved text is kept on this device until it is saved,
    // and put back if the page is reopened after a dropped connection.
    var key = form.dataset.draftKey, savedAt = +form.dataset.savedAt, dirty = false;
    var state = document.querySelector('[data-save-state]'), dot = document.querySelector('[data-save-dot]'), unsaved = document.querySelector('[data-check-unsaved]');
    function markDirty() {
        dirty = true;
        state.textContent = 'Unsaved changes · kept on this device until you save';
        dot.classList.remove('bg-primary'); dot.classList.add('bg-warn');
        if (unsaved) unsaved.hidden = false;
    }
    var kept = form.querySelectorAll('textarea, [data-keep], input[name^="budget"], select[name^="budget"]');
    function store() {
        var data = {};
        kept.forEach(function (el) { if (el.name) data[el.name + '#' + Array.prototype.indexOf.call(kept, el)] = el.value; });
        try { localStorage.setItem(key, JSON.stringify({ savedAt: savedAt, data: data })); } catch (e) {}
    }
    try {
        var draft = JSON.parse(localStorage.getItem(key) || 'null');
        if (draft && draft.savedAt === savedAt) {
            var changed = false;
            kept.forEach(function (el, i) {
                var v = draft.data[el.name + '#' + i];
                if (typeof v === 'string' && v !== el.value) { el.value = v; changed = true; }
            });
            if (changed) { markDirty(); document.querySelector('[data-restored]').classList.remove('hidden'); }
        } else if (draft) { localStorage.removeItem(key); }
    } catch (e) {}
    form.addEventListener('input', function () { markDirty(); store(); });
    form.addEventListener('submit', function () { dirty = false; try { localStorage.removeItem(key); } catch (e) {} });
    window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
    var discard = document.querySelector('[data-discard]');
    if (discard) discard.addEventListener('click', function () { try { localStorage.removeItem(key); } catch (e) {} dirty = false; location.reload(); });

    updateWords();
    updateBudget();
})();
</script>
@endpush
