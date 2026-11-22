@extends('layouts.app')

{{--
    A call's settings: the basics, then each feature the call can use, with a
    switch and its own numbers. Expects $call (new or saved), $form (its
    CallForm) and $copiedFrom (the call a new one was started from, if any).
    Once a call has opened, only word limits can change, and only upwards.
--}}

@php
    use App\Support\CallForm;
    use App\Support\SeedGrant;
    $isNew  = ! $call->exists;
    $locked = $call->settingsLocked();
    $c      = $form->config();
    $f      = fn (string $path, $default = null) => old('features.' . $path, data_get($c, $path, $default));
    $isOn   = fn (string $path) => (bool) old('features.' . $path, data_get($c, $path));
    $num    = 'input h-8 w-24 text-right font-mono text-[13px]';
    $row    = 'flex flex-wrap items-center justify-between gap-x-6 gap-y-3 border-t border-border px-5 py-4 first:border-t-0';
    $set    = 'flex flex-wrap items-center gap-2 text-[13px] text-muted-foreground';
@endphp

@section('title', $isNew ? 'New call' : 'Call settings')
@section('context', 'Calls · ' . ($isNew ? 'New call' : $call->title))

@section('content')
<x-page-header :title="$isNew ? 'New call' : 'Call settings'" :back="route('seed.calls.index')" back-label="Calls"
    :description="$isNew
        ? ($copiedFrom ? 'Started from the settings of “' . $copiedFrom->title . '”. Change what differs. It stays hidden from staff until you set it to Open.' : 'Set the call up, then open it when you are ready.')
        : 'What staff see on the call page, and what this call asks for.'" />

@if($locked)
    <x-alert tone="info" icon="lock" class="mb-6" :title="'This call is ' . strtolower(\App\Models\Seed\SeedCall::STATUSES[$call->status]) . ', so its form is locked.'">
        You can still change the title, summary, deadlines, funds available and status, and raise word limits. Everything else stays as applicants found it.
    </x-alert>
@endif

<form method="POST" action="{{ $isNew ? route('seed.calls.store') : route('seed.calls.update', $call) }}" class="mx-auto max-w-5xl space-y-6">
    @csrf
    @unless($isNew) @method('PUT') @endunless

    {{-- The call --}}
    <x-card title="The call" icon="megaphone">
        <div class="grid gap-x-4 gap-y-5 sm:grid-cols-2">
            <x-field class="sm:col-span-2" label="Title" name="title" required>
                <input id="title" name="title" class="input" value="{{ old('title', $call->title) }}" required>
            </x-field>
            <x-field class="sm:col-span-2" label="Short summary" name="summary" help="One or two sentences shown under the title.">
                <textarea id="summary" name="summary" class="textarea" rows="3">{{ old('summary', $call->summary) }}</textarea>
            </x-field>
            <x-field label="Financial year" name="fiscal_year" required>
                <input id="fiscal_year" name="fiscal_year" class="input" value="{{ old('fiscal_year', $call->fiscal_year) }}" placeholder="2026/27" required @disabled($locked)>
                @if($locked)<input type="hidden" name="fiscal_year" value="{{ $call->fiscal_year }}">@endif
            </x-field>
            <x-field label="Reference code" name="code" required help="Used in application references, e.g. SG-2627-001. Unique to this call.">
                <input id="code" name="code" class="input font-mono" value="{{ old('code', $call->code) }}" placeholder="2728" required @disabled($locked)>
                @if($locked)<input type="hidden" name="code" value="{{ $call->code }}">@endif
            </x-field>
            <x-field label="Status" name="status" required help="Draft is hidden from staff. Open shows it and accepts concept notes until the deadline.">
                <select id="status" name="status" class="select">
                    @foreach(\App\Models\Seed\SeedCall::STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $call->status ?? 'draft') === $value) @disabled($locked && $value === 'draft')>{{ $label }}</option>
                    @endforeach
                </select>
            </x-field>
            <x-field label="Funds available (TZS)" name="envelope" help="Shown against what is recommended." optional>
                <input id="envelope" type="number" min="0" name="envelope" class="input font-mono" value="{{ old('envelope', $call->envelope) }}">
            </x-field>
            <x-field label="Minimum award (TZS)" name="min_amount" required>
                <input id="min_amount" type="number" min="0" name="min_amount" class="input font-mono" value="{{ old('min_amount', $call->min_amount) }}" required @disabled($locked)>
                @if($locked)<input type="hidden" name="min_amount" value="{{ $call->min_amount }}">@endif
            </x-field>
            <x-field label="Maximum award (TZS)" name="max_amount" required>
                <input id="max_amount" type="number" min="0" name="max_amount" class="input font-mono" value="{{ old('max_amount', $call->max_amount) }}" required @disabled($locked)>
                @if($locked)<input type="hidden" name="max_amount" value="{{ $call->max_amount }}">@endif
            </x-field>
            <x-field label="Study length (months)" name="duration_months" required>
                <input id="duration_months" type="number" min="1" max="36" name="duration_months" class="input font-mono" value="{{ old('duration_months', $call->duration_months) }}" required @disabled($locked)>
                @if($locked)<input type="hidden" name="duration_months" value="{{ $call->duration_months }}">@endif
            </x-field>
            <div class="hidden sm:block"></div>
            <x-field label="Concept note deadline" name="concept_deadline" help="Concept notes are accepted until the end of this day, East Africa Time.">
                <input id="concept_deadline" type="date" name="concept_deadline" class="input" value="{{ old('concept_deadline', $call->concept_deadline?->toDateString()) }}">
            </x-field>
            <x-field label="Full proposal deadline" name="proposal_deadline">
                <input id="proposal_deadline" type="date" name="proposal_deadline" class="input" value="{{ old('proposal_deadline', $call->proposal_deadline?->toDateString()) }}">
            </x-field>
        </div>
    </x-card>

    {{-- The call page's text --}}
    <x-card title="What the call page says" description="Staff read this before applying. You can change it at any time." icon="file-text">
        <div class="space-y-5">
            <x-field label="Why this call exists" name="features[page][about]" for="page-about" help="Leave a blank line between paragraphs.">
                <textarea id="page-about" name="features[page][about]" class="textarea" rows="5">{{ $f('page.about') }}</textarea>
            </x-field>
            <x-field label="Who can lead an application" name="features[page][who]" for="page-who" help="The rest (years at NIMR, prior grants, mentor) is added from the switches below.">
                <input id="page-who" name="features[page][who]" class="input" value="{{ $f('page.who') }}">
            </x-field>
            <x-field label="Priority areas: introduction" name="features[page][priorities_intro]" for="page-priorities-intro">
                <input id="page-priorities-intro" name="features[page][priorities_intro]" class="input" value="{{ $f('page.priorities_intro') }}">
            </x-field>
            <x-field label="Priority areas" name="features[page][priorities]" for="page-priorities" help="One per line. Leave empty for a call without priority areas.">
                <textarea id="page-priorities" name="features[page][priorities]" class="textarea" rows="6">{{ is_array($f('page.priorities')) ? implode("\n", $f('page.priorities')) : $f('page.priorities') }}</textarea>
            </x-field>
        </div>
    </x-card>

    {{-- Who can apply --}}
    <x-card title="Who can apply" description="Rules on the lead applicant, checked before they can start." icon="users" flush>
        <div class="{{ $row }}">
            @include('seed.partials.switch', ['path' => 'service_limit.on', 'label' => 'Years-at-NIMR limit', 'help' => 'Lead applicants must have served at NIMR for under this many years.'])
            <label class="{{ $set }}">Under <input type="number" class="{{ $num }}" min="1" max="60" name="features[service_limit][years]" value="{{ $f('service_limit.years') }}" @disabled($locked)> years</label>
        </div>
        <div class="{{ $row }}">
            @include('seed.partials.switch', ['path' => 'prior_grant.on', 'label' => 'Prior-grant question', 'help' => 'Asks whether they have held a large grant as PI; anyone who has cannot apply.'])
            <span class="{{ $set }}">
                <label class="flex items-center gap-2">Above USD <input type="number" class="{{ $num }}" min="0" name="features[prior_grant][usd]" value="{{ $f('prior_grant.usd') }}" @disabled($locked)></label>
                <label class="flex items-center gap-2">Details <input type="number" class="{{ $num }}" min="{{ $locked ? $f('prior_grant.words') : 10 }}" name="features[prior_grant][words]" value="{{ $f('prior_grant.words') }}"> words</label>
            </span>
        </div>
    </x-card>

    {{-- Concept note --}}
    <x-card title="Concept note" description="Section A details, the questions, and the declaration." icon="pencil" flush>
        <div class="{{ $row }}">
            <span><span class="block text-sm font-medium">Project title</span><span class="mt-0.5 block text-[13px] text-muted-foreground">Always asked.</span></span>
            <label class="{{ $set }}">Up to <input type="number" class="{{ $num }}" min="{{ $locked ? $f('title_words') : 5 }}" name="features[title_words]" value="{{ $f('title_words') }}"> words</label>
        </div>
        <div class="{{ $row }}">
            @include('seed.partials.switch', ['path' => 'mentor.on', 'label' => 'Scientific mentor', 'help' => 'Name, designation, centre and relevant expertise.'])
            <label class="{{ $set }}">Expertise <input type="number" class="{{ $num }}" min="{{ $locked ? $f('mentor.words') : 10 }}" name="features[mentor][words]" value="{{ $f('mentor.words') }}"> words</label>
        </div>
        <div class="{{ $row }}">
            @include('seed.partials.switch', ['path' => 'co_investigators.on', 'label' => 'Co-investigators or collaborators', 'help' => 'Optional for the applicant to fill.'])
            <label class="{{ $set }}">Up to <input type="number" class="{{ $num }}" min="{{ $locked ? $f('co_investigators.words') : 10 }}" name="features[co_investigators][words]" value="{{ $f('co_investigators.words') }}"> words</label>
        </div>
        <div class="{{ $row }}">
            @include('seed.partials.switch', ['path' => 'declarations.on', 'label' => 'Applicant declaration', 'help' => 'The four statements of Section E, all to be ticked.'])
        </div>
        <div class="border-t border-border">@include('seed.partials.question-table', ['stage' => 'concept'])</div>
    </x-card>

    {{-- Full proposal --}}
    <x-card title="Full proposal" description="For shortlisted applicants: the questions, the detailed budget and the documents." icon="file-text" flush>
        @include('seed.partials.question-table', ['stage' => 'proposal'])
        <div class="{{ $row }} border-t">
            @include('seed.partials.switch', ['path' => 'equipment_cap.on', 'label' => 'Equipment cap', 'help' => 'Equipment may not exceed this share of the detailed budget.'])
            <label class="{{ $set }}">Up to <input type="number" class="{{ $num }}" min="0" max="100" name="equipment_cap_percent" value="{{ old('equipment_cap_percent', $call->equipment_cap_percent ?? 20) }}" @disabled($locked)> %</label>
            @if($locked)<input type="hidden" name="equipment_cap_percent" value="{{ $call->equipment_cap_percent }}">@endif
        </div>
        @foreach(SeedGrant::DOCUMENT_KINDS as $kind => [$label])
            <div class="{{ $row }}">
                <span><span class="block text-sm font-medium">{{ $label }}</span><span class="mt-0.5 block text-[13px] text-muted-foreground">Upload with the full proposal.</span></span>
                <select class="select select-sm w-40" name="features[documents][{{ $kind }}]" aria-label="{{ $label }}" @disabled($locked)>
                    @foreach(CallForm::DOCUMENT_STATES as $state => $stateLabel)
                        <option value="{{ $state }}" @selected($f('documents.' . $kind) === $state)>{{ $stateLabel }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach
    </x-card>

    {{-- Scored review --}}
    <x-card title="Scored review" description="How reviewers score full proposals. Switch every criterion off for a call without scoring." icon="star" flush>
        <div class="overflow-x-auto">
            <table class="data-table min-w-[640px]">
                <thead><tr><th class="w-14">Use</th><th>Criterion</th><th class="w-32 text-right">Maximum score</th><th class="w-40 text-right">Minimum to be funded</th></tr></thead>
                <tbody>
                    @foreach(SeedGrant::CRITERIA as $key => [$name, $what])
                        <tr class="hover:bg-transparent">
                            <td>
                                <input type="hidden" name="features[criteria][{{ $key }}][on]" value="0">
                                <input type="checkbox" class="switch" name="features[criteria][{{ $key }}][on]" value="1" aria-label="Use {{ $name }}" @checked($isOn("criteria.$key.on")) @disabled($locked)>
                            </td>
                            <td><p class="font-medium">{{ $name }}</p><p class="mt-0.5 text-xs text-muted-foreground">{{ $what }}</p></td>
                            <td class="text-right"><input type="number" class="{{ $num }} ml-auto" step="0.5" min="1" max="100" name="features[criteria][{{ $key }}][max]" value="{{ SeedGrant::trimNumber((float) $f("criteria.$key.max")) }}" aria-label="{{ $name }} maximum" @disabled($locked)></td>
                            <td class="text-right"><input type="number" class="{{ $num }} ml-auto" step="0.5" min="0" name="features[criteria][{{ $key }}][min]" value="{{ $f("criteria.$key.min") !== null ? SeedGrant::trimNumber((float) $f("criteria.$key.min")) : '' }}" placeholder="None" aria-label="{{ $name }} minimum" @disabled($locked)></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="{{ $row }} border-t">
            <span><span class="block text-sm font-medium">Pass mark</span><span class="mt-0.5 block text-[13px] text-muted-foreground">The mean total a proposal needs to be recommended. Currently out of {{ SeedGrant::trimNumber($form->maxTotal()) }}.</span></span>
            <label class="{{ $set }}">At least <input type="number" class="{{ $num }}" step="0.5" min="0" name="features[pass_mark]" value="{{ SeedGrant::trimNumber((float) $f('pass_mark')) }}" @disabled($locked)></label>
        </div>
    </x-card>

    {{-- Approval --}}
    <x-card title="Approval" description="Who approves a recommended proposal, in this order. The last one may set conditions." icon="shield-check" flush>
        <div class="{{ $row }}">
            <label class="flex items-start gap-3">
                <input type="checkbox" class="switch mt-0.5" checked disabled>
                <span><span class="block text-sm font-medium">GMU sign-off</span><span class="mt-0.5 block text-[13px] text-muted-foreground">Always on.</span></span>
            </label>
        </div>
        @include('seed.partials.switch', ['path' => 'approval.drcp', 'label' => 'DRCP clearance', 'help' => 'Director, Research Coordination and Promotion.', 'row' => true])
        @include('seed.partials.switch', ['path' => 'approval.dg', 'label' => 'Director General’s approval', 'help' => 'Final institutional approval.', 'row' => true])
    </x-card>

    <div class="sticky bottom-0 z-10 -mx-4 flex justify-end gap-2 border-t border-border bg-background/90 px-4 py-3 backdrop-blur sm:mx-0 sm:rounded-xl sm:border sm:px-5">
        <x-button :href="$isNew ? route('seed.calls.index') : route('seed.calls.show', $call)">Cancel</x-button>
        <x-button type="submit" variant="primary">{{ $isNew ? 'Create the call' : 'Save settings' }}</x-button>
    </div>
</form>
@endsection
