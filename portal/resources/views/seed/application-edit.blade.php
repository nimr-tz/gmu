@extends('layouts.gmu')

@php
    use App\Support\SeedGrant;
    $call      = $application->call;
    $isConcept = $stage === 'concept';
    $maxPages  = $isConcept ? SeedGrant::CONCEPT_MAX_PAGES : SeedGrant::PROPOSAL_MAX_PAGES;
    $pages     = $application->pageEstimate($stage);
    $lines     = $application->budgetLines($stage);
    $blankRows = max(3, 6 - $lines->count());
@endphp

@section('title', $isConcept ? 'Concept note' : 'Full proposal')
@section('context', $application->reference . ' · ' . ($isConcept ? 'Concept note' : 'Full proposal'))

@section('content')
<form id="app-form" method="POST" action="{{ route('seed.applications.update', $application) }}">
    @csrf @method('PUT')

    <div class="card" style="margin-bottom:20px;">
        <div class="card-body row-between" style="gap:16px;">
            <div style="flex:1 1 440px; min-width:0;">
                <div class="row" style="gap:8px;">
                    <span class="mono small muted">{{ $application->reference }}</span>
                    @include('seed.partials.status')
                    <span class="small muted">Last saved {{ $application->updated_at->diffForHumans() }}</span>
                </div>
                <h1 style="margin-top:6px; font-size:20px; line-height:1.3;">{{ $application->title ?: ($isConcept ? 'Untitled concept note' : 'Full proposal') }}</h1>
            </div>
            <div class="row" style="gap:16px;">
                <div style="min-width:150px;">
                    <div class="meter-label"><span>Length</span><b class="mono" data-pages>{{ $pages }} / {{ $maxPages }} pages</b></div>
                    <div class="meter {{ $pages > $maxPages ? 'danger' : '' }}"><span data-pages-bar style="width:{{ min(100, $pages / $maxPages * 100) }}%"></span></div>
                </div>
                <button type="submit" class="btn">Save</button>
                <button type="submit" class="btn btn-primary" formaction="{{ route('seed.applications.submit', $application) }}"
                        onclick="return confirm('{{ $application->isRevising() ? 'Send your revised version and response to the reviewers?' : ($isConcept ? 'Submit your concept note? You cannot change it after it is submitted.' : 'Submit your full proposal? You cannot change it after it is submitted.') }}')">
                    {{ $application->isRevising() ? 'Send response to reviewers' : ($isConcept ? 'Submit concept note' : 'Submit full proposal') }}
                </button>
            </div>
        </div>
    </div>

    @if($isConcept && $application->status === SeedGrant::DRAFT && $application->screened_at && $application->screening_note)
        <div class="banner banner-warn" style="margin-bottom:20px; padding:16px 18px;" role="status">
            <span class="ico" style="width:32px; height:32px; flex-basis:32px; font-size:14px;"><i class="fas fa-undo" aria-hidden="true"></i></span>
            <div><strong>GMU returned your concept note for correction</strong>
                <p style="font-size:14px; margin-top:2px;">{{ $application->screening_note }}</p>
                <p class="small muted" style="margin-top:4px;">Make the change, then submit again.</p></div>
        </div>
    @endif

    <nav class="section-nav" aria-label="Sections">
        @if($isConcept)
            @php $aDone = $application->priorLargeGrant() === 'no' && $application->mentor_name && $application->mentor_suitability; @endphp
            <a href="#project"><span class="tick {{ $aDone ? 'ok' : 'no' }}"><i class="fas fa-check" aria-hidden="true"></i></span>A. Applicant</a>
            <a href="#s-title"><span class="tick {{ $application->title ? 'ok' : 'no' }}"><i class="fas fa-check" aria-hidden="true"></i></span>Title</a>
        @endif
        @foreach($sections as $key => [$heading, , $limit])
            @php
                $w = SeedGrant::wordCount($application->sectionText($stage, $key));
                $needed = ! $isConcept || $form->sectionRequired($key, $application->choice($key));
                $answered = ! $isConcept || ! isset($form->choices()[$key]) || $application->choice($key) !== null;
                $state = $w > $limit ? 'over' : (($w === 0 && $needed) || ! $answered ? 'no' : 'ok');
                $short = $isConcept ? preg_replace('/^(\d+|[A-Z])\. /', '$1. ', \Illuminate\Support\Str::before($heading, ' and ')) : $heading;
            @endphp
            <a href="#s-{{ $key }}"><span class="tick {{ $state }}"><i class="fas {{ $state === 'over' ? 'fa-exclamation' : 'fa-check' }}" aria-hidden="true"></i></span>{{ $short }}</a>
        @endforeach
        <a href="#budget"><span class="tick {{ $lines->isEmpty() ? 'no' : 'ok' }}"><i class="fas fa-check" aria-hidden="true"></i></span>{{ $isConcept ? 'D. Budget' : 'Budget' }}</a>
        @if($isConcept && $form->declarations())
            <a href="#declaration"><span class="tick {{ count(array_intersect(array_keys($form->declarations()), $application->declarations())) === count($form->declarations()) ? 'ok' : 'no' }}"><i class="fas fa-check" aria-hidden="true"></i></span>E. Declaration</a>
        @endif
        @unless($isConcept)
            <a href="#documents"><span class="tick {{ $application->documents->isEmpty() ? 'no' : 'ok' }}"><i class="fas fa-check" aria-hidden="true"></i></span>Documents</a>
        @endunless
    </nav>

    <div class="grid grid-wide" style="margin-top:20px;">
        <div class="stack" style="gap:16px;">

            @if($round)
                <section class="card accent-warn" id="response">
                    <div class="card-body">
                        <h2 class="card-title">Your response to the reviewers</h2>
                        <p class="small muted" style="margin-top:4px;">Say what you changed for each comment, or why you did not. Due {{ $round->response_due_at?->format('j M Y') }}.</p>
                        <textarea name="response" class="textarea" rows="6" style="margin-top:12px;" aria-label="Your response to the reviewers">{{ old('response') }}</textarea>
                    </div>
                </section>
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
                <section class="card" id="project">
                    <div class="card-body">
                        <h2 class="card-title">A. Applicant and project information</h2>
                        <p class="small muted" style="margin-top:4px;">Your details come from your profile. If any is wrong, ask GMU to correct it.</p>

                        <div class="form-grid" style="margin-top:16px;">
                            <div class="kv"><span class="k">Lead applicant (PI)</span><span class="v">{{ $pi->name }}</span></div>
                            <div class="kv"><span class="k">Current designation</span><span class="v">{{ $pi->designation ?: '—' }}</span></div>
                            <div class="kv"><span class="k">NIMR centre</span><span class="v">{{ $pi->research_center ?: '—' }}</span></div>
                            <div class="kv"><span class="k">Department or section</span><span class="v">{{ $pi->department ?: '—' }}</span></div>
                            <div class="kv"><span class="k">Email</span><span class="v">{{ $pi->email }}</span></div>
                            <div class="kv"><span class="k">Telephone</span><span class="v">{{ $pi->phone ?: '—' }}</span></div>
                            <div class="kv"><span class="k">Employment</span><span class="v">{{ \App\Models\User::EMPLOYMENT_TYPES[$pi->employment_type] ?? '—' }}</span></div>
                            <div class="kv"><span class="k">Date of first appointment</span>
                                <span class="v">{{ $pi->first_appointment_date?->format('j M Y') ?? '—' }}
                                    @if($service)<span class="small muted"> · {{ $service['years'] }} y {{ $service['months'] }} m{{ $form->serviceYears() && $pi->withinServiceLimit($form->serviceYears()) ? ', under ' . $form->serviceYears() . ' years' : '' }}</span>@endif
                                </span></div>
                        </div>

                        @if($priorRule = $form->priorGrant())
                        <fieldset class="choice-group" style="margin-top:20px;">
                            <legend class="label">Have you previously received a research grant exceeding USD {{ number_format($priorRule['usd']) }} as Principal Investigator?</legend>
                            <div class="row" style="gap:20px; margin-top:8px;">
                                @foreach(['no' => 'No', 'yes' => 'Yes'] as $value => $label)
                                    <label class="check"><input type="radio" name="prior_grant" value="{{ $value }}" @checked($prior === $value) data-reveal="prior-details" data-reveal-on="yes"> <span>{{ $label }}</span></label>
                                @endforeach
                            </div>
                            <div id="prior-details" @if($prior !== 'yes') hidden @endif style="margin-top:12px;">
                                <label for="prior_grant_details" class="label">Grant title, funding organisation and amount awarded</label>
                                <textarea id="prior_grant_details" name="prior_grant_details" class="textarea" rows="2" data-words data-limit="{{ $priorRule['words'] }}">{{ $priorDetails }}</textarea>
                                <div class="wordcount" data-count-for="prior_grant_details">{{ SeedGrant::wordCount($priorDetails) }} / {{ $priorRule['words'] }} words</div>
                                <p class="small" style="margin-top:6px; color:var(--danger-ink);">The call is open only to researchers who have not held a grant above USD {{ number_format($priorRule['usd']) }} as PI.</p>
                            </div>
                        </fieldset>
                        @endif

                        <div class="form-grid" style="margin-top:20px;">
                            @if($form->mentorWords() !== null)
                            <x-ui.field label="Proposed scientific mentor’s full name" name="mentor_name">
                                <input id="mentor_name" name="mentor_name" class="input" value="{{ old('mentor_name', $application->mentor_name) }}">
                            </x-ui.field>
                            <x-ui.field label="Mentor’s designation" name="mentor_designation">
                                <input id="mentor_designation" name="mentor_designation" class="input" value="{{ old('mentor_designation', $application->mentor_designation) }}">
                            </x-ui.field>
                            <x-ui.field label="Mentor’s NIMR centre or section" name="mentor_centre" wide>
                                <input id="mentor_centre" name="mentor_centre" class="input" value="{{ old('mentor_centre', $application->mentor_centre) }}">
                            </x-ui.field>
                            <div class="field wide">
                                <label for="mentor_suitability" class="label">The mentor’s expertise relevant to the proposed study</label>
                                <textarea id="mentor_suitability" name="mentor_suitability" class="textarea" rows="2" data-words data-limit="{{ $form->mentorWords() }}">{{ $expertise }}</textarea>
                                <div class="wordcount" data-count-for="mentor_suitability">{{ SeedGrant::wordCount($expertise) }} / {{ $form->mentorWords() }} words</div>
                            </div>
                            @endif
                            @if($form->coInvestigatorWords() !== null)
                            <div class="field wide">
                                <label for="co_investigators" class="label">Proposed co-investigators or collaborators, if any</label>
                                <p class="small muted">Names, designations and institutional affiliations.</p>
                                <textarea id="co_investigators" name="co_investigators" class="textarea" rows="2" data-words data-limit="{{ $form->coInvestigatorWords() }}">{{ $coInv }}</textarea>
                                <div class="wordcount" data-count-for="co_investigators">{{ SeedGrant::wordCount($coInv) }} / {{ $form->coInvestigatorWords() }} words</div>
                            </div>
                            @endif
                        </div>
                        <p class="small muted" style="margin-top:12px;">Total funding requested is taken from your budget below: TZS {{ number_format($call->min_amount) }} to {{ number_format($call->max_amount) }}.</p>
                    </div>
                </section>

                <section class="card" id="s-title">
                    <div class="card-body">
                        <h2 class="card-title">B. Concept note technical questions</h2>
                        <label for="title" class="label" style="margin-top:16px; display:block;">Proposed project title</label>
                        @php $titleText = old('title', $application->title); @endphp
                        <input id="title" name="title" class="input" maxlength="255" value="{{ $titleText }}" data-words data-limit="{{ $form->titleWords() }}">
                        <div class="wordcount" data-count-for="title">{{ SeedGrant::wordCount($titleText) }} / {{ $form->titleWords() }} words</div>
                    </div>
                </section>
            @endif

            @foreach($sections as $key => [$heading, $guidance, $limit])
                @php
                    $text = old("sections.$key", $application->sectionText($stage, $key));
                    $choice = $isConcept ? ($form->choices()[$key] ?? null) : null;
                    $answer = $choice ? old("choices.$key", $application->choice($key)) : null;
                    $showText = ! $choice || in_array($answer, $choice['describe'], true);
                @endphp
                <section class="card" id="s-{{ $key }}">
                    <div class="card-body">
                        @if($choice)
                            <fieldset class="choice-group">
                                <legend class="card-title">{{ $heading }}</legend>
                                <p class="small" style="margin-top:4px;">{{ $choice['question'] }}</p>
                                <div class="row" style="gap:20px; margin-top:8px;">
                                    @foreach($choice['options'] as $value => $label)
                                        <label class="check"><input type="radio" name="choices[{{ $key }}]" value="{{ $value }}" @checked($answer === $value)
                                               data-reveal="describe-{{ $key }}" data-reveal-on="{{ implode(',', $choice['describe']) }}"> <span>{{ $label }}</span></label>
                                    @endforeach
                                </div>
                            </fieldset>
                            <div id="describe-{{ $key }}" @unless($showText) hidden @endunless style="margin-top:12px;">
                                <label for="sec-{{ $key }}" class="label">{{ $guidance }}</label>
                        @else
                            <div>
                                <label for="sec-{{ $key }}" class="card-title" style="display:block;">{{ $heading }}</label>
                                @if($guidance)<p class="small muted" style="margin-top:4px;">{{ $guidance }}</p>@endif
                        @endif
                                <textarea id="sec-{{ $key }}" name="sections[{{ $key }}]" class="textarea" rows="{{ $limit <= 150 ? 4 : 7 }}" style="margin-top:12px;"
                                          data-words data-page data-limit="{{ $limit }}">{{ $text }}</textarea>
                                <div class="wordcount" data-count-for="sec-{{ $key }}">{{ SeedGrant::wordCount($text) }} / {{ $limit }} words</div>
                            </div>
                    </div>
                </section>
            @endforeach

            <section class="card" id="budget">
                <div class="card-body">
                    <div class="row-between">
                        <h2 class="card-title">{{ $isConcept ? 'D. Estimated budget' : 'Detailed budget' }}</h2>
                        <span class="small muted">{{ $isConcept ? 'A detailed quarterly budget comes at stage 2.' : 'From the NIMR finance template: four categories by quarter.' }}</span>
                    </div>
                    <div class="table-wrap" style="margin-top:12px;">
                        <table class="budget-table" style="min-width:{{ $isConcept ? 460 : 860 }}px;" data-budget>
                            <thead>
                                <tr>
                                    @unless($isConcept)<th style="width:150px;">Category</th>@endunless
                                    <th>Item</th>
                                    @if($isConcept)
                                        <th style="width:200px; text-align:right;">Amount (TZS)</th>
                                    @else
                                        @foreach(['q1', 'q2', 'q3', 'q4'] as $q)<th style="width:120px; text-align:right;">{{ strtoupper($q) }}</th>@endforeach
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lines->concat(array_fill(0, $blankRows, null)) as $i => $line)
                                    <tr data-row>
                                        @unless($isConcept)
                                            <td>
                                                <select name="budget[{{ $i }}][category]" class="cell" aria-label="Category" data-category>
                                                    <option value="">—</option>
                                                    @foreach(SeedGrant::BUDGET_CATEGORIES as $value => $label)
                                                        <option value="{{ $value }}" @selected($line?->category === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        @endunless
                                        <td><input name="budget[{{ $i }}][item]" class="cell" aria-label="Item" value="{{ $line?->item }}" maxlength="255"></td>
                                        @if($isConcept)
                                            <td><input name="budget[{{ $i }}][amount]" class="cell num" aria-label="Amount" inputmode="numeric" value="{{ $line ? number_format($line->q1) : '' }}" data-amount></td>
                                        @else
                                            @foreach(['q1', 'q2', 'q3', 'q4'] as $q)
                                                <td><input name="budget[{{ $i }}][{{ $q }}]" class="cell num" aria-label="{{ strtoupper($q) }}" inputmode="numeric" value="{{ $line && $line->$q ? number_format($line->$q) : '' }}" data-amount></td>
                                            @endforeach
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm" style="margin-top:12px;" data-add-row><i class="fas fa-plus" aria-hidden="true"></i> Add line</button>

                    <div style="margin-top:20px; padding:18px; border-radius:12px; background:var(--canvas);">
                        <div class="row-between"><strong>Total</strong><span class="mono" style="font-size:22px;" data-total>{{ SeedGrant::tzs($application->budgetTotal($stage)) }}</span></div>
                        <div class="range-bar" style="margin-top:14px;" data-range><span style="width:0%"></span></div>
                        <div class="row-between mono tiny muted" style="margin-top:6px;"><span>{{ number_format($call->min_amount) }} min</span><span>{{ number_format($call->max_amount) }} max</span></div>
                        <p class="small" style="margin-top:10px;" data-range-note></p>
                        @unless($isConcept)
                            <p class="small" style="margin-top:4px;" data-equipment-note></p>
                        @endunless
                    </div>
                </div>
            </section>

            @if($isConcept && $form->declarations())
                @php $ticked = old('declarations', $application->declarations()); @endphp
                <section class="card" id="declaration">
                    <div class="card-body">
                        <h2 class="card-title">E. Applicant declaration</h2>
                        <p class="small muted" style="margin-top:4px;">Tick every statement. Submitting from your account stands as your signature and date.</p>
                        <div class="stack-sm" style="margin-top:14px;">
                            @foreach($form->declarations() as $value => $statement)
                                <label class="check" style="align-items:flex-start;">
                                    <input type="checkbox" name="declarations[]" value="{{ $value }}" style="margin-top:3px;" @checked(in_array($value, (array) $ticked, true))>
                                    <span>{{ $statement }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="small muted" style="margin-top:14px;">Applicant: <strong>{{ $application->pi->name }}</strong></p>
                    </div>
                </section>
            @endif
        </div>

        <aside class="stack-sm" style="gap:16px;">
            @if($problems)
                <section class="card">
                    <div class="card-body">
                        <h2 class="card-title">Before you can submit</h2>
                        <p class="small muted" style="margin-top:4px;">As of your last save.</p>
                        <ul style="margin:12px 0 0; padding-left:18px; display:flex; flex-direction:column; gap:6px; font-size:14px;">
                            @foreach($problems as $p)<li>{{ $p }}</li>@endforeach
                        </ul>
                    </div>
                </section>
            @else
                <div class="banner banner-ok" style="padding:16px 18px;">
                    <span class="ico" style="width:32px; height:32px; flex-basis:32px; font-size:14px;"><i class="fas fa-check" aria-hidden="true"></i></span>
                    <div><strong>Ready to submit</strong><p style="font-size:14px; margin-top:2px;">Everything required is in place.</p></div>
                </div>
            @endif

            @if($application->rounds->whereNotNull('decision')->isNotEmpty())
                <h2 class="card-title" style="margin-top:8px;">Reviewer feedback</h2>
                @include('seed.partials.feedback', ['compact' => true])
            @endif
        </aside>
    </div>
</form>

@unless($isConcept)
    <section class="card" id="documents" style="margin-top:24px; max-width:860px;">
        <div class="card-body">
            <h2 class="card-title">Supporting documents</h2>
            <p class="small muted" style="margin-top:4px;">PDF or Word, up to 10 MB each. The mentor letter and CV are required.</p>
            <div class="list" style="margin-top:12px; border:1px solid var(--line); border-radius:12px;">
                @forelse($application->documents as $doc)
                    <div class="list-row">
                        <span class="list-main"><span class="list-title">{{ $doc->kindLabel() }}</span>
                            <a href="{{ route('seed.applications.documents.download', [$application, $doc]) }}" class="list-sub">{{ $doc->file_name }}</a></span>
                        <form method="POST" action="{{ route('seed.applications.documents.destroy', [$application, $doc]) }}" onsubmit="return confirm('Remove this document?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                        </form>
                    </div>
                @empty
                    <div class="table-empty">Nothing uploaded yet.</div>
                @endforelse
            </div>
            <form method="POST" action="{{ route('seed.applications.documents.store', $application) }}" enctype="multipart/form-data" class="row" style="margin-top:16px; align-items:flex-end;">
                @csrf
                <x-ui.field label="Document" name="kind">
                    <select id="kind" name="kind" class="select">
                        @foreach($form->documents() as $value => [$label, $required])
                            <option value="{{ $value }}">{{ $label }}{{ $required ? '' : ' (optional)' }}</option>
                        @endforeach
                    </select>
                </x-ui.field>
                <x-ui.field label="File" name="file">
                    <input id="file" type="file" name="file" class="input" accept=".pdf,.doc,.docx" required>
                </x-ui.field>
                <button type="submit" class="btn">Upload</button>
            </form>
        </div>
    </section>
@endunless
@endsection

@push('scripts')
<script>
(function () {
    var min = {{ (int) $call->min_amount }}, max = {{ (int) $call->max_amount }};
    var cap = {{ (int) ($form->equipmentCap() ?? 100) }}, perPage = {{ SeedGrant::WORDS_PER_PAGE }}, maxPages = {{ $maxPages }};
    function words(t) { t = t.trim(); return t ? t.split(/\s+/).length : 0; }
    function num(v) { return parseInt(String(v).replace(/[^0-9]/g, ''), 10) || 0; }

    var areas = document.querySelectorAll('[data-words]');
    function updateWords() {
        var all = 0;
        areas.forEach(function (a) {
            var w = words(a.value), limit = +a.dataset.limit, out = document.querySelector('[data-count-for="' + a.id + '"]');
            if (a.hasAttribute('data-page')) all += w;
            out.textContent = w + ' / ' + limit + ' words';
            out.classList.toggle('over', w > limit);
        });
        var pages = Math.round(all / perPage * 10) / 10;
        document.querySelector('[data-pages]').textContent = pages + ' / ' + maxPages + ' pages';
        document.querySelector('[data-pages-bar]').style.width = Math.min(100, pages / maxPages * 100) + '%';
    }
    areas.forEach(function (a) { a.addEventListener('input', updateWords); });

    var table = document.querySelector('[data-budget]');
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
        var range = document.querySelector('[data-range]'), ok = total >= min && total <= max;
        range.firstElementChild.style.width = Math.min(100, total / max * 100) + '%';
        range.classList.toggle('is-bad', total > 0 && !ok);
        var note = document.querySelector('[data-range-note]');
        note.textContent = total === 0 ? '' : ok ? 'Within the allowed range.' : (total < min ? 'Below the minimum award.' : 'Above the maximum award.');
        note.style.color = ok ? 'var(--blue-700)' : 'var(--danger-ink)';
        var eq = document.querySelector('[data-equipment-note]');
        if (eq && total > 0) {
            var pct = equipment / total * 100;
            eq.textContent = 'Equipment ' + pct.toFixed(1) + '% of the budget (limit ' + cap + '%).';
            eq.style.color = pct > cap ? 'var(--danger-ink)' : 'var(--muted-2)';
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

    // Questions that ask for a description only for some answers (prior grant, 7 and 8).
    document.querySelectorAll('[data-reveal]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            var target = document.getElementById(radio.dataset.reveal);
            if (target && radio.checked) target.hidden = radio.dataset.revealOn.split(',').indexOf(radio.value) === -1;
        });
    });

    updateWords();
    updateBudget();
})();
</script>
@endpush
