@extends('layouts.app')

{{--
    A call's settings, from the owner's "GMU Calls" design (ink band): a list of the seven
    sections with what is ready, then each section with its switches and numbers, and a
    sticky bar to save. Expects $call (new or saved), $form (its CallForm) and $copiedFrom
    (the call a new one was started from, if any). Once a call has opened, only the basics,
    the call page's words and raised word limits can change. Approval is the same for every
    call: GMU, then the DRCP, then the Director General.
--}}

@php
    use App\Support\CallForm;
    use App\Support\SeedGrant;
    use App\Models\Seed\SeedCall;
    $isNew  = ! $call->exists;
    $locked = $call->settingsLocked();
    $c      = $form->config();
    $f      = fn (string $path, $default = null) => old('features.' . $path, data_get($c, $path, $default));
    $isOn   = fn (string $path) => (bool) old('features.' . $path, data_get($c, $path));
    $num    = 'input h-8 w-[84px] text-right font-mono text-[13px]';
    $row    = 'flex flex-wrap items-center justify-between gap-x-6 gap-y-3 border-t border-gray-100 px-[18px] py-4';
    $set    = 'flex flex-wrap items-center gap-2 text-[13px] text-muted-foreground';
    $card   = 'scroll-mt-5 overflow-hidden rounded-[14px] border border-border bg-card';
    $head   = 'border-b border-border bg-muted px-[18px] py-3';
    $field  = 'flex flex-col gap-1.5';
    $label  = 'text-[13px] font-medium';
    $help   = 'text-xs text-muted-foreground';
    $status = old('status', $call->status ?? 'draft');
    $critColour = ['relevance' => 'bg-primary', 'science' => 'bg-violet-500', 'feasibility' => 'bg-sky-500', 'impact' => 'bg-pink-500', 'development' => 'bg-indigo-400', 'innovation' => 'bg-gray-400'];
    $critTotal = $form->maxTotal();
    $scoringOk = $critTotal > 0 && $form->passMark() > 0 && $form->passMark() <= $critTotal;
    $sections = [['call', 'The call'], ['page', 'What the call page says'], ['who', 'Who can apply'], ['concept', 'Concept note'], ['proposal', 'Full proposal'], ['scoring', 'Scored review'], ['approval', 'Approval']];
    $lockedSections = ['who', 'proposal', 'scoring'];
    $chip = ['open' => ['text-ok-ink', 'bg-primary'], 'draft' => ['text-gray-700', 'bg-gray-400'], 'closed' => ['text-gray-700', 'bg-gray-400']][$call->status ?? 'draft'];
@endphp

@section('title', $isNew ? 'New call' : 'Call settings')
@section('context', 'Grants · Calls · ' . ($isNew ? 'New call' : 'Settings · ' . $call->code))
@section('topbar-colour', 'bg-foreground')

@section('content')
<div class="-mx-4 -mt-5 flex flex-col gap-2.5 bg-foreground px-7 pb-[100px] pt-[18px] text-white lg:-mx-6 lg:-mt-6">
    <a href="{{ route('seed.calls.index') }}" class="inline-flex items-center gap-1.5 self-start text-[13px] font-medium text-gray-300 hover:text-white"><x-icon name="arrow-left" class="size-3.5" />Calls</a>
    <div class="flex flex-wrap items-center gap-2">
        @if($call->code)<span class="{{ $call->exists ? $call->tintClass() : 'tint-3' }} rounded-md bg-tint-chip px-1.5 py-0.5 font-mono text-xs font-medium text-tint-ink">{{ $call->code }}</span>@endif
        <span class="inline-flex h-[22px] items-center gap-1.5 rounded-md bg-white px-2 text-xs font-semibold {{ $chip[0] }}"><span class="size-1.5 rounded-full {{ $chip[1] }}"></span>{{ ['draft' => 'Draft', 'open' => 'Open', 'closed' => 'Closed'][$call->status ?? 'draft'] }}</span>
    </div>
    <h1 class="text-[30px] font-semibold leading-tight tracking-[-0.025em] text-white">{{ $isNew ? 'New call' : 'Call settings' }}</h1>
    <p class="max-w-[860px] text-[15px] text-gray-300">
        {{ $isNew
            ? ($copiedFrom ? 'Started from the settings of “' . $copiedFrom->title . '”. Change what differs. It stays hidden from staff until you set it to Open.' : 'Set the call up, then open it when you are ready.')
            : 'What staff see on the call page, and what this call asks for.' }}
    </p>
</div>

<form method="POST" action="{{ $isNew ? route('seed.calls.store') : route('seed.calls.update', $call) }}"
      x-data="{ title: @js(old('title', $call->title ?? '')), code: @js(old('code', $call->code ?? '')), deadline: @js(old('concept_deadline', $call->concept_deadline?->toDateString() ?? '')),
                about: @js((string) $f('page.about')), who: @js((string) $f('page.who')), tried: false,
                get ready() { return [this.title.trim() && /^[A-Za-z0-9]{2,12}$/.test(this.code.trim()) && this.deadline, this.about.trim() && this.who.trim(), true, true, true, {{ $scoringOk ? 'true' : 'false' }}, true] },
                get readyCount() { return this.ready.filter(Boolean).length } }"
      @submit="tried = true; if (! ready[0]) { $event.preventDefault(); window.scrollTo({ top: 0, behavior: 'smooth' }) }"
      class="-mt-[72px] flex flex-wrap items-start gap-6 pb-8">
    @csrf
    @unless($isNew) @method('PUT') @endunless

    <nav aria-label="Sections" class="shrink-0 basis-[250px] rounded-[14px] border border-border bg-card p-2 shadow-pop lg:sticky lg:top-5">
        <p class="px-2.5 pb-1.5 pt-2 text-[12.5px] text-muted-foreground"><span x-text="readyCount">{{ count($sections) }}</span> of {{ count($sections) }} sections ready</p>
        <ol class="flex flex-col gap-px">
            @foreach($sections as $i => [$id, $name])
                <li><a href="#{{ $id }}" class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-[13.5px] font-medium hover:bg-gray-100">
                    <span class="grid size-5 shrink-0 place-items-center rounded-full text-[11px] font-semibold" :class="ready[{{ $i }}] ? 'bg-foreground text-white' : 'bg-card text-muted-foreground ring-[1.5px] ring-inset ring-gray-300'">
                        <x-icon name="check" class="size-2.5" x-show="ready[{{ $i }}]" /><span x-show="! ready[{{ $i }}]" x-cloak>{{ $i + 1 }}</span>
                    </span>
                    <span class="flex-1">{{ $name }}</span>
                    @if($locked && in_array($id, $lockedSections, true))<x-icon name="lock" class="size-3 text-subtle" />@endif
                </a></li>
            @endforeach
        </ol>
    </nav>

    <div class="flex min-w-0 flex-[1_1_640px] flex-col gap-5">
        @if($locked)
            <div class="flex gap-3 rounded-xl border border-ok-line bg-card px-4 py-3.5 shadow-pop">
                <span class="grid size-[30px] shrink-0 place-items-center rounded-lg bg-blue-50 text-ok-ink"><x-icon name="lock" class="size-4" /></span>
                <div><p class="text-sm font-semibold">This call is {{ strtolower(['open' => 'Open', 'closed' => 'Closed'][$call->status] ?? $call->status) }}, so its form is locked.</p><p class="mt-0.5 text-[13.5px] leading-normal text-gray-700">You can still change the title, summary, deadlines, funds available and status, and raise word limits. Everything else stays as applicants found it.</p></div>
            </div>
        @endif

        {{-- The call --}}
        <section id="call" class="{{ $card }}">
            <header class="{{ $head }}"><h2 class="text-sm font-semibold">The call</h2></header>
            <div class="grid gap-x-4 gap-y-[18px] p-[18px] sm:grid-cols-2">
                <label class="{{ $field }} sm:col-span-2"><span class="{{ $label }}">Title</span>
                    <input name="title" class="input" x-model="title" placeholder="The call’s name, as researchers will see it" :class="tried && ! title.trim() && 'border-danger'" required>
                    <span class="text-[12.5px] text-danger-ink" x-show="tried && ! title.trim()" x-cloak>Give the call a title.</span>
                    @error('title')<span class="text-[12.5px] text-danger-ink">{{ $message }}</span>@enderror
                </label>
                <label class="{{ $field }} sm:col-span-2"><span class="{{ $label }}">Short summary <span class="font-normal text-muted-foreground">(optional)</span></span>
                    <textarea name="summary" class="textarea" rows="2">{{ old('summary', $call->summary) }}</textarea>
                    <span class="{{ $help }}">One or two sentences shown under the title.</span>
                </label>
                <label class="{{ $field }}"><span class="{{ $label }}">Financial year</span>
                    <input name="fiscal_year" class="input" value="{{ old('fiscal_year', $call->fiscal_year) }}" placeholder="2027/28" required @disabled($locked)>
                    @if($locked)<input type="hidden" name="fiscal_year" value="{{ $call->fiscal_year }}">@endif
                </label>
                <label class="{{ $field }}"><span class="{{ $label }}">Reference code</span>
                    <input name="code" class="input font-mono" x-model="code" placeholder="2728" required @disabled($locked) :class="tried && ! /^[A-Za-z0-9]{2,12}$/.test(code.trim()) && 'border-danger'">
                    @if($locked)<input type="hidden" name="code" value="{{ $call->code }}">@endif
                    <span class="{{ $help }}">Used in application references, e.g. SG-<span x-text="code || '2728'">{{ $call->code ?: '2728' }}</span>-001. Unique to this call.</span>
                    @error('code')<span class="text-[12.5px] text-danger-ink">{{ $message }}</span>@enderror
                </label>
                <label class="{{ $field }}"><span class="{{ $label }}">Status</span>
                    <select name="status" class="select">
                        @foreach(['draft' => 'Draft', 'open' => 'Open', 'closed' => 'Closed'] as $value => $name)
                            <option value="{{ $value }}" @selected($status === $value) @disabled($locked && $value === 'draft')>{{ $name }}</option>
                        @endforeach
                    </select>
                    <span class="{{ $help }}">Draft is hidden from staff. Open shows it and accepts concept notes until the deadline.</span>
                </label>
                <label class="{{ $field }}"><span class="{{ $label }}">Funds available (TZS) <span class="font-normal text-muted-foreground">(optional)</span></span>
                    <input type="number" min="0" name="envelope" class="input font-mono" value="{{ old('envelope', $call->envelope) }}">
                    <span class="{{ $help }}">Shown against what is recommended.</span>
                </label>
                <label class="{{ $field }}"><span class="{{ $label }}">Minimum award (TZS)</span>
                    <input type="number" min="0" name="min_amount" class="input font-mono" value="{{ old('min_amount', $call->min_amount) }}" required @disabled($locked)>
                    @if($locked)<input type="hidden" name="min_amount" value="{{ $call->min_amount }}">@endif
                </label>
                <label class="{{ $field }}"><span class="{{ $label }}">Maximum award (TZS)</span>
                    <input type="number" min="0" name="max_amount" class="input font-mono" value="{{ old('max_amount', $call->max_amount) }}" required @disabled($locked)>
                    @if($locked)<input type="hidden" name="max_amount" value="{{ $call->max_amount }}">@endif
                    @error('max_amount')<span class="text-[12.5px] text-danger-ink">{{ $message }}</span>@enderror
                </label>
                <label class="{{ $field }}"><span class="{{ $label }}">Study length (months)</span>
                    <input type="number" min="1" max="36" name="duration_months" class="input font-mono" value="{{ old('duration_months', $call->duration_months) }}" required @disabled($locked)>
                    @if($locked)<input type="hidden" name="duration_months" value="{{ $call->duration_months }}">@endif
                </label>
                <label class="{{ $field }}"><span class="{{ $label }}">Concept note deadline</span>
                    <input type="date" name="concept_deadline" class="input" x-model="deadline" :class="tried && ! deadline && 'border-danger'">
                    <span class="{{ $help }}">Accepted until the end of this day, East Africa Time.</span>
                </label>
                <label class="{{ $field }}"><span class="{{ $label }}">Full proposal deadline</span>
                    <input type="date" name="proposal_deadline" class="input" value="{{ old('proposal_deadline', $call->proposal_deadline?->toDateString()) }}">
                    @error('proposal_deadline')<span class="text-[12.5px] text-danger-ink">{{ $message }}</span>@enderror
                </label>
            </div>
        </section>

        {{-- The call page's words --}}
        <section id="page" class="{{ $card }}">
            <header class="{{ $head }}"><h2 class="text-sm font-semibold">What the call page says</h2><p class="text-[13px] text-muted-foreground">Staff read this before applying. You can change it at any time.</p></header>
            <div class="flex flex-col gap-[18px] p-[18px]">
                <label class="{{ $field }}"><span class="{{ $label }}">Why this call exists</span>
                    <textarea name="features[page][about]" class="textarea" rows="4" x-model="about"></textarea>
                    <span class="{{ $help }}">Leave a blank line between paragraphs.</span>
                </label>
                <label class="{{ $field }}"><span class="{{ $label }}">Who can lead an application</span>
                    <input name="features[page][who]" class="input" x-model="who">
                    <span class="{{ $help }}">The rest (years at NIMR, prior grants, mentor) is added from the switches below.</span>
                </label>
                <label class="{{ $field }}"><span class="{{ $label }}">Priority areas: introduction</span>
                    <input name="features[page][priorities_intro]" class="input" value="{{ $f('page.priorities_intro') }}">
                </label>
                <label class="{{ $field }}"><span class="{{ $label }}">Priority areas</span>
                    <textarea name="features[page][priorities]" class="textarea" rows="9">{{ is_array($f('page.priorities')) ? implode("\n", $f('page.priorities')) : $f('page.priorities') }}</textarea>
                    <span class="{{ $help }}">One per line. Leave empty for a call without priority areas.</span>
                </label>
            </div>
        </section>

        {{-- Who can apply --}}
        <section id="who" class="{{ $card }}">
            <header class="{{ $head }}"><h2 class="text-sm font-semibold">Who can apply</h2><p class="text-[13px] text-muted-foreground">Rules on the lead applicant, checked before they can start</p></header>
            <div class="{{ $row }} border-t-0">
                @include('seed.partials.switch', ['path' => 'service_limit.on', 'label' => 'Years-at-NIMR limit', 'help' => 'Lead applicants must have served at NIMR for under this many years.'])
                <label class="{{ $set }}">Under <input type="number" class="{{ $num }}" min="1" max="60" name="features[service_limit][years]" value="{{ $f('service_limit.years') }}" aria-label="Years limit" @disabled($locked)> years</label>
            </div>
            <div class="{{ $row }}">
                @include('seed.partials.switch', ['path' => 'prior_grant.on', 'label' => 'Prior-grant question', 'help' => 'Asks whether they have held a large grant as PI, and for its details if so. The answer goes to GMU; it does not stop anyone applying.'])
                <span class="{{ $set }}">
                    <label class="flex items-center gap-2">Above USD <input type="number" class="{{ $num }}" min="0" name="features[prior_grant][usd]" value="{{ $f('prior_grant.usd') }}" aria-label="Prior grant threshold" @disabled($locked)></label>
                    <label class="flex items-center gap-2">Details <input type="number" class="{{ $num }}" min="{{ $locked ? $f('prior_grant.words') : 10 }}" name="features[prior_grant][words]" value="{{ $f('prior_grant.words') }}" aria-label="Prior grant words"> words</label>
                </span>
            </div>
        </section>

        {{-- Concept note --}}
        <section id="concept" class="{{ $card }}">
            <header class="{{ $head }}"><h2 class="text-sm font-semibold">Concept note</h2><p class="text-[13px] text-muted-foreground">Section A details, the questions, and the declaration</p></header>
            <div class="{{ $row }} border-t-0">
                <span><span class="block text-sm font-medium">Project title</span><span class="mt-0.5 block text-[13px] text-muted-foreground">Always asked.</span></span>
                <label class="{{ $set }}">Up to <input type="number" class="{{ $num }}" min="{{ $locked ? $f('title_words') : 5 }}" name="features[title_words]" value="{{ $f('title_words') }}" aria-label="Title words"> words</label>
            </div>
            <div class="{{ $row }}">
                @include('seed.partials.switch', ['path' => 'mentor.on', 'label' => 'Scientific mentor', 'help' => 'Name, designation, centre and relevant expertise.'])
                <label class="{{ $set }}">Expertise <input type="number" class="{{ $num }}" min="{{ $locked ? $f('mentor.words') : 10 }}" name="features[mentor][words]" value="{{ $f('mentor.words') }}" aria-label="Mentor words"> words</label>
            </div>
            <div class="{{ $row }}">
                @include('seed.partials.switch', ['path' => 'co_investigators.on', 'label' => 'Co-investigators or collaborators', 'help' => 'Optional for the applicant to fill.'])
                <label class="{{ $set }}">Up to <input type="number" class="{{ $num }}" min="{{ $locked ? $f('co_investigators.words') : 10 }}" name="features[co_investigators][words]" value="{{ $f('co_investigators.words') }}" aria-label="Co-investigator words"> words</label>
            </div>
            <div class="{{ $row }}">
                @include('seed.partials.switch', ['path' => 'declarations.on', 'label' => 'Applicant declaration', 'help' => 'The four statements of Section E, all to be ticked.'])
            </div>
            <div class="border-t border-border">@include('seed.partials.question-table', ['stage' => 'concept'])</div>
        </section>

        {{-- Full proposal --}}
        <section id="proposal" class="{{ $card }}">
            <header class="{{ $head }}"><h2 class="text-sm font-semibold">Full proposal</h2><p class="text-[13px] text-muted-foreground">For shortlisted applicants: the questions, the detailed budget and the documents</p></header>
            @include('seed.partials.question-table', ['stage' => 'proposal'])
            <div class="{{ $row }} border-t border-border">
                @include('seed.partials.switch', ['path' => 'equipment_cap.on', 'label' => 'Equipment cap', 'help' => 'Equipment may not exceed this share of the detailed budget.'])
                <label class="{{ $set }}">Up to <input type="number" class="{{ $num }}" min="0" max="100" name="equipment_cap_percent" value="{{ old('equipment_cap_percent', $call->equipment_cap_percent ?? 20) }}" aria-label="Equipment cap percent" @disabled($locked)> %</label>
                @if($locked)<input type="hidden" name="equipment_cap_percent" value="{{ $call->equipment_cap_percent }}">@endif
            </div>
            @foreach(SeedGrant::DOCUMENT_KINDS as $kind => [$docLabel])
                <div class="{{ $row }}">
                    <span><span class="block text-sm font-medium">{{ $docLabel }}</span><span class="mt-0.5 block text-[13px] text-muted-foreground">Upload with the full proposal.</span></span>
                    <fieldset class="flex gap-0.5 rounded-[9px] bg-gray-200/70 p-[3px] {{ $locked ? 'opacity-60' : '' }}" x-data="{ v: @js($f('documents.' . $kind)) }">
                        <legend class="sr-only">{{ $docLabel }}</legend>
                        @foreach(CallForm::DOCUMENT_STATES as $state => $stateLabel)
                            <label class="h-[26px] rounded-md px-2.5 text-[12.5px] font-medium leading-[26px] {{ $locked ? 'cursor-not-allowed' : 'cursor-pointer' }}" :class="v === '{{ $state }}' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground'">
                                <input type="radio" class="sr-only" name="features[documents][{{ $kind }}]" value="{{ $state }}" x-model="v" @disabled($locked)>{{ $stateLabel }}
                            </label>
                        @endforeach
                    </fieldset>
                </div>
            @endforeach
        </section>

        {{-- Scored review --}}
        <section id="scoring" class="{{ $card }}">
            <header class="flex items-center justify-between gap-3 {{ $head }}">
                <div><h2 class="text-sm font-semibold">Scored review</h2><p class="text-[13px] text-muted-foreground">How reviewers score full proposals. Switch every criterion off for a call without scoring.</p></div>
                <span class="shrink-0 font-mono text-sm font-semibold">{{ SeedGrant::trimNumber($critTotal) }} pts</span>
            </header>
            <div class="px-[18px] pb-1 pt-3.5">
                <div class="flex h-2.5 gap-0.5 overflow-hidden rounded" role="img" aria-label="Weight of each criterion">
                    @foreach($form->criteria() as $key => [, , $max])<span class="{{ $critColour[$key] ?? 'bg-gray-400' }}" style="width: {{ $critTotal ? $max / $critTotal * 100 : 0 }}%"></span>@endforeach
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] border-collapse text-left text-[13.5px]">
                    <thead><tr>
                        <th class="h-[38px] w-14 border-b border-border pl-[18px] text-[12.5px] font-medium text-muted-foreground">Use</th>
                        <th class="border-b border-border px-3 text-[12.5px] font-medium text-muted-foreground">Criterion</th>
                        <th class="w-[130px] border-b border-border px-3 text-right text-[12.5px] font-medium text-muted-foreground">Maximum score</th>
                        <th class="w-[170px] border-b border-border pl-3 pr-[18px] text-right text-[12.5px] font-medium text-muted-foreground">Minimum to be funded</th>
                    </tr></thead>
                    <tbody>
                        @foreach(SeedGrant::CRITERIA as $key => [$name, $what])
                            @php $critOn = $isOn("criteria.$key.on"); @endphp
                            <tr class="{{ $critOn ? '' : 'opacity-45' }}">
                                <td class="border-b border-gray-100 py-3 pl-[18px]">
                                    <input type="hidden" name="features[criteria][{{ $key }}][on]" value="0">
                                    <input type="checkbox" class="switch checked:bg-foreground" name="features[criteria][{{ $key }}][on]" value="1" aria-label="Use {{ $name }}" @checked($critOn) @disabled($locked)>
                                </td>
                                <td class="border-b border-gray-100 p-3">
                                    <span class="flex items-center gap-2 font-medium"><span class="size-2 rounded-sm {{ $critColour[$key] ?? 'bg-gray-400' }}"></span>{{ $name }}</span>
                                    <span class="mt-0.5 block text-xs leading-normal text-muted-foreground">{{ $what }}</span>
                                </td>
                                <td class="border-b border-gray-100 p-3 text-right"><input type="number" class="{{ $num }} ml-auto w-[76px]" step="0.5" min="1" max="100" name="features[criteria][{{ $key }}][max]" value="{{ SeedGrant::trimNumber((float) $f("criteria.$key.max")) }}" aria-label="{{ $name }} maximum" @disabled($locked)></td>
                                <td class="border-b border-gray-100 py-3 pl-3 pr-[18px] text-right">
                                    <input type="number" class="{{ $num }} ml-auto w-[76px]" step="0.5" min="0" name="features[criteria][{{ $key }}][min]" value="{{ $f("criteria.$key.min") !== null ? SeedGrant::trimNumber((float) $f("criteria.$key.min")) : '' }}" placeholder="None" aria-label="{{ $name }} minimum" @disabled($locked)>
                                    @error("features.criteria.$key.min")<span class="mt-1 block text-xs text-danger-ink">{{ $message }}</span>@enderror
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-3 px-[18px] py-4">
                <span><span class="block text-sm font-medium">Pass mark</span>
                    <span class="mt-0.5 block text-[13px] {{ $errors->has('features.pass_mark') ? 'text-danger-ink' : 'text-muted-foreground' }}">{{ $errors->first('features.pass_mark') ?: 'The mean total a proposal needs to be recommended. Currently out of ' . SeedGrant::trimNumber($critTotal) . '.' }}</span></span>
                <label class="{{ $set }}">At least <input type="number" class="{{ $num }}" step="0.5" min="0" name="features[pass_mark]" value="{{ SeedGrant::trimNumber((float) $f('pass_mark')) }}" aria-label="Pass mark" @disabled($locked)></label>
            </div>
        </section>

        {{-- Approval: the same for every call --}}
        <section id="approval" class="{{ $card }}">
            <header class="{{ $head }}"><h2 class="text-sm font-semibold">Approval</h2><p class="text-[13px] text-muted-foreground">Who approves a recommended proposal, in this order. The last one may set conditions.</p></header>
            <div class="flex flex-wrap items-center gap-2 px-[18px] py-4">
                @foreach(['GMU', 'DRCP', 'Director General'] as $who)
                    <span class="inline-flex h-7 items-center rounded-[7px] px-2.5 text-[13px] font-semibold {{ $loop->last ? 'bg-indigo-50 text-indigo-700' : 'bg-gray-100 text-gray-700' }}">{{ $who }}</span>
                    @unless($loop->last)<x-icon name="arrow-right" class="size-3.5 text-subtle" />@endunless
                @endforeach
            </div>
            <ul class="border-t border-gray-100 px-[18px] py-3.5 text-[13px] leading-relaxed text-muted-foreground">
                <li><b class="font-medium text-foreground">GMU sign-off</b> sends the proposals selected in the ranking on.</li>
                <li><b class="font-medium text-foreground">DRCP clearance</b>: Director, Research Coordination and Promotion.</li>
                <li><b class="font-medium text-foreground">Director General’s approval</b>: final institutional approval, with or without conditions.</li>
            </ul>
        </section>

        <div class="sticky bottom-4 z-10 flex flex-wrap items-center justify-between gap-3 rounded-[14px] bg-foreground/95 py-3 pl-[18px] pr-3.5 text-white shadow-pop">
            <span class="text-[13px]" :class="tried && ! ready[0] ? 'text-rose-300' : 'text-gray-400'"
                  x-text="tried && ! ready[0] ? 'Fill in the title, a reference code and the concept note deadline.' : readyCount + ' of {{ count($sections) }} sections ready'"></span>
            <div class="flex gap-2">
                <a href="{{ $isNew ? route('seed.calls.index') : route('seed.calls.show', $call) }}" class="inline-flex h-9 items-center rounded-lg border border-gray-700 px-3.5 text-[13px] font-medium text-white hover:bg-gray-800">Cancel</a>
                <button type="submit" class="h-9 rounded-lg bg-white px-4 text-[13px] font-semibold text-foreground hover:bg-gray-100">{{ $isNew ? 'Create the call' : 'Save settings' }}</button>
            </div>
        </div>
    </div>
</form>
@endsection
