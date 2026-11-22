@extends('layouts.app')

@php
    use App\Support\SeedGrant;
    $call      = $application->call;
    $isConcept = $stage === 'concept';
    $maxPages  = $isConcept ? SeedGrant::CONCEPT_MAX_PAGES : SeedGrant::PROPOSAL_MAX_PAGES;
    $pages     = $application->pageEstimate($stage);
    $lines     = $application->budgetLines($stage);
    $blankRows = max(3, 6 - $lines->count());
    $count     = 'mt-1.5 text-right font-mono text-xs tabular-nums text-muted-foreground';
    $cell      = 'h-9 w-full rounded-md border-transparent bg-transparent px-2 text-sm shadow-none transition-colors hover:border-border focus:border-primary focus:bg-card focus:ring-2 focus:ring-primary/20';

    // Section list: [anchor, label, state ok|no|over].
    $nav = [];
    if ($isConcept) {
        $aDone = (! $form->priorGrant() || $application->priorLargeGrant() === 'no') && ($form->mentorWords() === null || ($application->mentor_name && $application->mentor_suitability));
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
@endphp

@section('title', $isConcept ? 'Concept note' : 'Full proposal')
@section('context', 'My application · ' . $application->reference . ' · ' . ($isConcept ? 'Concept note' : 'Full proposal'))

@section('content')
<form id="app-form" method="POST" action="{{ route('seed.applications.update', $application) }}" data-draft-key="seed-draft-{{ $application->id }}-{{ $stage }}" data-saved-at="{{ $application->updated_at->timestamp }}">
    @csrf @method('PUT')

    {{-- Save bar: stays in view while writing --}}
    <div class="sticky top-12 z-20 -mx-4 -mt-5 mb-6 border-b border-border bg-card/90 px-4 py-3 backdrop-blur lg:-mx-6 lg:-mt-6 lg:px-6">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
            <div class="min-w-0 flex-[1_1_20rem]">
                <div class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                    @include('seed.partials.status')
                    <span class="font-mono">{{ $application->reference }}</span>
                    <span>·</span>
                    <span data-save-state>Saved {{ $application->updated_at->diffForHumans() }}</span>
                </div>
                <h1 class="mt-1 truncate text-lg font-semibold">{{ $application->title ?: ($isConcept ? 'Untitled concept note' : 'Full proposal') }}</h1>
            </div>
            <div class="w-40">
                <div class="flex justify-between text-xs"><span class="text-muted-foreground">Length</span><b class="font-mono font-medium" data-pages>{{ $pages }} / {{ $maxPages }} pages</b></div>
                <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-gray-100"><div data-pages-bar class="h-full rounded-full {{ $pages > $maxPages ? 'bg-danger' : 'bg-primary' }}" style="width: {{ min(100, $pages / $maxPages * 100) }}%"></div></div>
            </div>
            <div class="flex gap-2">
                <x-button type="submit" icon="save" data-save>Save</x-button>
                <x-button type="submit" variant="primary" formaction="{{ route('seed.applications.submit', $application) }}" icon="send"
                          onclick="return confirm('{{ $application->isRevising() ? 'Send your revised version and response to the reviewers?' : ($isConcept ? 'Submit your concept note? You cannot change it after it is submitted.' : 'Submit your full proposal? You cannot change it after it is submitted.') }}')">
                    {{ $application->isRevising() ? 'Send response to reviewers' : ($isConcept ? 'Submit concept note' : 'Submit full proposal') }}
                </x-button>
            </div>
        </div>
    </div>

    <x-alert tone="info" class="mb-6 hidden" data-restored title="Unsaved text restored">
        Text you typed on this device since your last save has been put back. Press Save to keep it.
        <x-slot:actions><x-button size="sm" variant="ghost" data-discard>Discard it</x-button></x-slot:actions>
    </x-alert>

    @if($isConcept && $application->status === SeedGrant::DRAFT && $application->screened_at && $application->screening_note)
        <x-alert tone="warn" icon="rotate-ccw" title="GMU returned your concept note for correction" class="mb-6">
            <p class="whitespace-pre-line">{{ $application->screening_note }}</p>
            <p class="mt-1 text-[13px]">Make the change, then submit again.</p>
        </x-alert>
    @endif

    <div class="grid items-start gap-8 lg:grid-cols-[14rem_minmax(0,1fr)]">
        {{-- Sections, with what is done --}}
        <aside class="space-y-4 lg:sticky lg:top-32">
            <nav aria-label="Sections" class="hidden lg:block">
                <div class="mb-3 rounded-xl border border-border bg-muted px-3 py-2.5">
                    <p class="flex items-center justify-between text-[13px]"><span class="font-medium">Sections done</span><span class="font-semibold tabular-nums">{{ $doneCount }} of {{ count($nav) }}</span></p>
                    <x-meter class="mt-2" :value="$doneCount / max(1, count($nav)) * 100" label="Sections done" />
                </div>
                <ul class="space-y-0.5">
                    @foreach($nav as [$anchor, $label, $state])
                        <li>
                            <a href="#{{ $anchor }}" class="flex items-center gap-2.5 rounded-md px-2 py-1.5 text-[13px] text-muted-foreground hover:bg-muted hover:text-foreground">
                                @if($state === 'ok')<span class="grid size-4 shrink-0 place-items-center rounded-full bg-ok-soft text-ok-ink"><x-icon name="check" class="size-2.5" /></span>
                                @elseif($state === 'over')<span class="grid size-4 shrink-0 place-items-center rounded-full bg-danger-soft text-[10px] font-bold text-danger-ink">!</span>
                                @else<span class="size-4 shrink-0 rounded-full border-[1.5px] border-input"></span>@endif
                                <span class="truncate">{{ $label }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
            @if($problems)
                <div class="rounded-xl border border-warn-line bg-warn-soft p-4">
                    <p class="text-[13px] font-semibold text-warn-ink">Before you can submit</p>
                    <ul class="mt-2 list-disc space-y-1 pl-4 text-xs text-warn-ink">@foreach($problems as $p)<li>{{ $p }}</li>@endforeach</ul>
                    <p class="mt-2 text-[11px] text-warn-ink/80">As of your last save.</p>
                </div>
            @else
                <x-alert tone="ok" title="Ready to submit">Everything required is in place.</x-alert>
            @endif
        </aside>

        <div class="min-w-0 space-y-6">
            @if($round)
                <x-card tone="warn" id="response" title="Your response to the reviewers" icon="message-square"
                        :description="'Say what you changed for each comment, or why you did not. Due ' . $round->response_due_at?->format('j M Y') . '.'">
                    <textarea name="response" class="textarea" rows="6" aria-label="Your response to the reviewers" data-keep>{{ old('response') }}</textarea>
                </x-card>
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
                <x-card id="project" class="scroll-mt-40" title="A. Applicant and project information" description="Your details come from your profile. If any is wrong, ask GMU to correct it.">
                    <dl class="grid gap-x-6 gap-y-4 rounded-lg bg-muted/60 p-4 sm:grid-cols-2">
                        <x-stat label="Lead applicant (PI)" :value="$pi->name" />
                        <x-stat label="Current designation" :value="$pi->designation ?: '—'" />
                        <x-stat label="NIMR centre" :value="$pi->research_center ?: '—'" />
                        <x-stat label="Department or section" :value="$pi->department ?: '—'" />
                        <x-stat label="Email" :value="$pi->email" />
                        <x-stat label="Telephone" :value="$pi->phone ?: '—'" />
                        <x-stat label="Employment" :value="\App\Models\User::EMPLOYMENT_TYPES[$pi->employment_type] ?? '—'" />
                        <x-stat label="Date of first appointment" :value="$pi->first_appointment_date?->format('j M Y') ?? '—'"
                                :note="$service ? $service['years'] . ' y ' . $service['months'] . ' m' . ($form->serviceYears() && $pi->withinServiceLimit($form->serviceYears()) ? ', under ' . $form->serviceYears() . ' years' : '') : null" />
                    </dl>

                    @if($priorRule = $form->priorGrant())
                        <fieldset class="mt-6">
                            <legend class="label">Have you previously received a research grant exceeding USD {{ number_format($priorRule['usd']) }} as Principal Investigator?</legend>
                            <div class="mt-2 flex gap-6">
                                @foreach(['no' => 'No', 'yes' => 'Yes'] as $value => $label)
                                    <label class="flex items-center gap-2 text-sm"><input type="radio" class="radio" name="prior_grant" value="{{ $value }}" @checked($prior === $value) data-reveal="prior-details" data-reveal-on="yes">{{ $label }}</label>
                                @endforeach
                            </div>
                            <div id="prior-details" class="mt-3" @if($prior !== 'yes') hidden @endif>
                                <label for="prior_grant_details" class="label">Grant title, funding organisation and amount awarded</label>
                                <textarea id="prior_grant_details" name="prior_grant_details" class="textarea mt-1.5" rows="2" data-words data-limit="{{ $priorRule['words'] }}">{{ $priorDetails }}</textarea>
                                <div class="{{ $count }}" data-count-for="prior_grant_details">{{ SeedGrant::wordCount($priorDetails) }} / {{ $priorRule['words'] }} words</div>
                                <p class="mt-1 text-[13px] text-danger-ink">The call is open only to researchers who have not held a grant above USD {{ number_format($priorRule['usd']) }} as PI.</p>
                            </div>
                        </fieldset>
                    @endif

                    <div class="mt-6 grid gap-x-4 gap-y-5 sm:grid-cols-2">
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
                                <div class="{{ $count }}" data-count-for="mentor_suitability">{{ SeedGrant::wordCount($expertise) }} / {{ $form->mentorWords() }} words</div>
                            </div>
                        @endif
                        @if($form->coInvestigatorWords() !== null)
                            <div class="sm:col-span-2">
                                <label for="co_investigators" class="label">Proposed co-investigators or collaborators <span class="font-normal text-muted-foreground">(if any)</span></label>
                                <p class="text-xs text-muted-foreground">Names, designations and institutional affiliations.</p>
                                <textarea id="co_investigators" name="co_investigators" class="textarea mt-1.5" rows="2" data-words data-limit="{{ $form->coInvestigatorWords() }}">{{ $coInv }}</textarea>
                                <div class="{{ $count }}" data-count-for="co_investigators">{{ SeedGrant::wordCount($coInv) }} / {{ $form->coInvestigatorWords() }} words</div>
                            </div>
                        @endif
                    </div>
                    <p class="mt-5 text-[13px] text-muted-foreground">Total funding requested is taken from your budget below: TZS {{ number_format($call->min_amount) }} to {{ number_format($call->max_amount) }}.</p>
                </x-card>

                <x-card id="s-title" class="scroll-mt-40" title="B. Concept note technical questions">
                    @php $titleText = old('title', $application->title); @endphp
                    <label for="title" class="label">Proposed project title</label>
                    <input id="title" name="title" class="input mt-1.5 text-[15px] font-medium" maxlength="255" value="{{ $titleText }}" data-words data-limit="{{ $form->titleWords() }}">
                    <div class="{{ $count }}" data-count-for="title">{{ SeedGrant::wordCount($titleText) }} / {{ $form->titleWords() }} words</div>
                </x-card>
            @endif

            @foreach($sections as $key => [$heading, $guidance, $limit])
                @php
                    $text = old("sections.$key", $application->sectionText($stage, $key));
                    $choice = $isConcept ? ($form->choices()[$key] ?? null) : null;
                    $answer = $choice ? old("choices.$key", $application->choice($key)) : null;
                    $showText = ! $choice || in_array($answer, $choice['describe'], true);
                @endphp
                <x-card id="s-{{ $key }}" class="scroll-mt-40">
                    @if($choice)
                        <fieldset>
                            <legend class="text-[15px] font-semibold">{{ $heading }}</legend>
                            <p class="mt-1 text-[13px] text-muted-foreground">{{ $choice['question'] }}</p>
                            <div class="mt-3 flex flex-wrap gap-x-6 gap-y-2">
                                @foreach($choice['options'] as $value => $label)
                                    <label class="flex items-center gap-2 text-sm"><input type="radio" class="radio" name="choices[{{ $key }}]" value="{{ $value }}" @checked($answer === $value)
                                           data-reveal="describe-{{ $key }}" data-reveal-on="{{ implode(',', $choice['describe']) }}">{{ $label }}</label>
                                @endforeach
                            </div>
                        </fieldset>
                        <div id="describe-{{ $key }}" class="mt-4" @unless($showText) hidden @endunless>
                            <label for="sec-{{ $key }}" class="label">{{ $guidance }}</label>
                    @else
                        <div>
                            <label for="sec-{{ $key }}" class="block text-[15px] font-semibold">{{ $heading }}</label>
                            @if($guidance)<p class="mt-1 text-[13px] leading-relaxed text-muted-foreground">{{ $guidance }}</p>@endif
                    @endif
                            <textarea id="sec-{{ $key }}" name="sections[{{ $key }}]" class="textarea mt-3 text-[14.5px]" rows="{{ $limit <= 150 ? 5 : 9 }}"
                                      data-words data-page data-limit="{{ $limit }}">{{ $text }}</textarea>
                            <div class="{{ $count }}" data-count-for="sec-{{ $key }}">{{ SeedGrant::wordCount($text) }} / {{ $limit }} words</div>
                        </div>
                </x-card>
            @endforeach

            <x-card id="budget" class="scroll-mt-40" :title="$isConcept ? 'D. Estimated budget' : 'Detailed budget'"
                    :description="$isConcept ? 'A detailed quarterly budget comes at stage 2.' : 'From the NIMR finance template: four categories by quarter.'">
                <div class="-mx-5 overflow-x-auto border-y border-border">
                    <table class="w-full text-sm {{ $isConcept ? 'min-w-[460px]' : 'min-w-[860px]' }}" data-budget>
                        <thead class="bg-muted/60 text-xs text-muted-foreground">
                            <tr>
                                @unless($isConcept)<th class="w-40 px-3 py-2 pl-5 text-left font-medium">Category</th>@endunless
                                <th class="px-3 py-2 text-left font-medium {{ $isConcept ? 'pl-5' : '' }}">Item</th>
                                @if($isConcept)
                                    <th class="w-48 px-3 py-2 pr-5 text-right font-medium">Amount (TZS)</th>
                                @else
                                    @foreach(['q1', 'q2', 'q3', 'q4'] as $q)<th class="w-32 px-3 py-2 text-right font-medium last:pr-5">{{ strtoupper($q) }}</th>@endforeach
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/70">
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
                <x-button size="sm" variant="ghost" class="mt-3" icon="plus" data-add-row>Add line</x-button>

                <div class="mt-4 rounded-lg bg-muted/70 p-4">
                    <div class="flex items-baseline justify-between"><span class="text-sm font-semibold">Total</span><span class="font-mono text-xl font-semibold tabular-nums" data-total>{{ SeedGrant::tzs($application->budgetTotal($stage)) }}</span></div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-card ring-1 ring-inset ring-border" data-range><div class="h-full rounded-full bg-primary transition-[width]" style="width: 0%"></div></div>
                    <div class="mt-1.5 flex justify-between font-mono text-xs text-muted-foreground"><span>{{ number_format($call->min_amount) }} min</span><span>{{ number_format($call->max_amount) }} max</span></div>
                    <p class="mt-2 text-[13px]" data-range-note></p>
                    @unless($isConcept)<p class="mt-1 text-[13px]" data-equipment-note></p>@endunless
                </div>
            </x-card>

            @if($isConcept && $form->declarations())
                @php $ticked = old('declarations', $application->declarations()); @endphp
                <x-card id="declaration" class="scroll-mt-40" title="E. Applicant declaration" description="Tick every statement. Submitting from your account stands as your signature and date.">
                    <div class="space-y-3">
                        @foreach($form->declarations() as $value => $statement)
                            <label class="flex items-start gap-3 text-sm">
                                <input type="checkbox" class="checkbox mt-0.5" name="declarations[]" value="{{ $value }}" @checked(in_array($value, (array) $ticked, true))>
                                <span>{{ $statement }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-4 text-[13px] text-muted-foreground">Applicant: <strong class="text-foreground">{{ $application->pi->name }}</strong></p>
                </x-card>
            @endif
        </div>
    </div>
</form>

@unless($isConcept)
    <div class="mt-6 lg:ml-[calc(14rem+2rem)]">
        <x-card id="documents" class="scroll-mt-40" title="Supporting documents" description="PDF or Word, up to 10 MB each." icon="paperclip">
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
        </x-card>
    </div>
@endunless
@endsection

@push('scripts')
<script>
(function () {
    var min = {{ (int) $call->min_amount }}, max = {{ (int) $call->max_amount }};
    var cap = {{ (int) ($form->equipmentCap() ?? 100) }}, perPage = {{ SeedGrant::WORDS_PER_PAGE }}, maxPages = {{ $maxPages }};
    var form = document.getElementById('app-form');
    function words(t) { t = t.trim(); return t ? t.split(/\s+/).length : 0; }
    function num(v) { return parseInt(String(v).replace(/[^0-9]/g, ''), 10) || 0; }
    function tone(el, bad, good) { el.classList.remove('text-danger-ink', 'text-blue-700', 'text-muted-foreground', 'font-medium'); el.classList.add.apply(el.classList, bad ? ['text-danger-ink', 'font-medium'] : [good]); }

    // Word counts and the page estimate.
    var areas = form.querySelectorAll('[data-words]');
    function updateWords() {
        var all = 0;
        areas.forEach(function (a) {
            var w = words(a.value), limit = +a.dataset.limit, out = document.querySelector('[data-count-for="' + a.id + '"]');
            if (a.hasAttribute('data-page')) all += w;
            out.textContent = w + ' / ' + limit + ' words';
            tone(out, w > limit, 'text-muted-foreground');
            a.setAttribute('aria-invalid', w > limit ? 'true' : 'false');
        });
        var pages = Math.round(all / perPage * 10) / 10, bar = document.querySelector('[data-pages-bar]');
        document.querySelector('[data-pages]').textContent = pages + ' / ' + maxPages + ' pages';
        bar.style.width = Math.min(100, pages / maxPages * 100) + '%';
        bar.classList.toggle('bg-danger', pages > maxPages); bar.classList.toggle('bg-primary', pages <= maxPages);
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
        var bar = document.querySelector('[data-range]').firstElementChild, ok = total >= min && total <= max;
        bar.style.width = Math.min(100, total / max * 100) + '%';
        bar.classList.toggle('bg-danger', total > 0 && !ok); bar.classList.toggle('bg-primary', !(total > 0 && !ok));
        var note = document.querySelector('[data-range-note]');
        note.textContent = total === 0 ? '' : ok ? 'Within the allowed range.' : (total < min ? 'Below the minimum award.' : 'Above the maximum award.');
        tone(note, !ok, 'text-blue-700');
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
    var key = form.dataset.draftKey, savedAt = +form.dataset.savedAt, dirty = false, state = document.querySelector('[data-save-state]');
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
            if (changed) { dirty = true; document.querySelector('[data-restored]').classList.remove('hidden'); state.textContent = 'Unsaved changes'; }
        } else if (draft) { localStorage.removeItem(key); }
    } catch (e) {}
    form.addEventListener('input', function () { dirty = true; state.textContent = 'Unsaved changes'; store(); });
    form.addEventListener('submit', function () { dirty = false; try { localStorage.removeItem(key); } catch (e) {} });
    window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
    var discard = document.querySelector('[data-discard]');
    if (discard) discard.addEventListener('click', function () { try { localStorage.removeItem(key); } catch (e) {} dirty = false; location.reload(); });

    updateWords();
    updateBudget();
})();
</script>
@endpush
