@extends('layouts.gmu')

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
@endphp

@section('title', $isNew ? 'New call' : 'Call settings')

@section('content')
<p class="crumbs"><a href="{{ route('seed.calls.index') }}">Calls</a> / {{ $isNew ? 'New call' : $call->title }}</p>
<x-ui.page-head :title="$isNew ? 'New call' : 'Call settings'"
    :lede="$isNew
        ? ($copiedFrom ? 'Started from the settings of “' . $copiedFrom->title . '”. Change what differs. It stays hidden from staff until you set it to Open.' : 'Set the call up, then open it when you are ready.')
        : 'What staff see on the call page, and what this call asks for.'" />

@if($locked)
    <div class="banner banner-info call-lock" role="status">
        <span class="ico"><i class="fas fa-lock" aria-hidden="true"></i></span>
        <div><strong>This call is {{ strtolower(\App\Models\Seed\SeedCall::STATUSES[$call->status]) }}, so its form is locked.</strong>
            <p>You can still change the title, summary, deadlines, funds available and status, and raise word limits. Everything else stays as applicants found it.</p></div>
    </div>
@endif

<form method="POST" action="{{ $isNew ? route('seed.calls.store') : route('seed.calls.update', $call) }}" class="stack">
    @csrf
    @unless($isNew) @method('PUT') @endunless

    {{-- ── The call ─────────────────────────────────────────────────── --}}
    <section class="card">
        <div class="card-head"><div class="card-title">The call</div></div>
        <div class="card-body">
            <div class="form-grid">
                <x-ui.field label="Title" name="title" wide required>
                    <input id="title" name="title" class="input" value="{{ old('title', $call->title) }}" required>
                </x-ui.field>
                <x-ui.field label="Short summary" name="summary" wide help="One or two sentences shown under the title.">
                    <textarea id="summary" name="summary" class="textarea" rows="3">{{ old('summary', $call->summary) }}</textarea>
                </x-ui.field>
                <x-ui.field label="Financial year" name="fiscal_year" required>
                    <input id="fiscal_year" name="fiscal_year" class="input" value="{{ old('fiscal_year', $call->fiscal_year) }}" placeholder="2026/27" required @disabled($locked)>
                    @if($locked)<input type="hidden" name="fiscal_year" value="{{ $call->fiscal_year }}">@endif
                </x-ui.field>
                <x-ui.field label="Reference code" name="code" required help="Used in application references, e.g. SG-2627-001. Unique to this call.">
                    <input id="code" name="code" class="input mono" value="{{ old('code', $call->code) }}" placeholder="2728" required @disabled($locked)>
                    @if($locked)<input type="hidden" name="code" value="{{ $call->code }}">@endif
                </x-ui.field>
                <x-ui.field label="Status" name="status" required help="Draft is hidden from staff. Open shows it and accepts concept notes until the deadline.">
                    <select id="status" name="status" class="select">
                        @foreach(\App\Models\Seed\SeedCall::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $call->status ?? 'draft') === $value) @disabled($locked && $value === 'draft')>{{ $label }}</option>
                        @endforeach
                    </select>
                </x-ui.field>
                <x-ui.field label="Funds available (TZS)" name="envelope" help="Optional. Shown against what is recommended.">
                    <input id="envelope" type="number" min="0" name="envelope" class="input mono" value="{{ old('envelope', $call->envelope) }}">
                </x-ui.field>
                <x-ui.field label="Minimum award (TZS)" name="min_amount" required>
                    <input id="min_amount" type="number" min="0" name="min_amount" class="input mono" value="{{ old('min_amount', $call->min_amount) }}" required @disabled($locked)>
                    @if($locked)<input type="hidden" name="min_amount" value="{{ $call->min_amount }}">@endif
                </x-ui.field>
                <x-ui.field label="Maximum award (TZS)" name="max_amount" required>
                    <input id="max_amount" type="number" min="0" name="max_amount" class="input mono" value="{{ old('max_amount', $call->max_amount) }}" required @disabled($locked)>
                    @if($locked)<input type="hidden" name="max_amount" value="{{ $call->max_amount }}">@endif
                </x-ui.field>
                <x-ui.field label="Study length (months)" name="duration_months" required>
                    <input id="duration_months" type="number" min="1" max="36" name="duration_months" class="input mono" value="{{ old('duration_months', $call->duration_months) }}" required @disabled($locked)>
                    @if($locked)<input type="hidden" name="duration_months" value="{{ $call->duration_months }}">@endif
                </x-ui.field>
                <x-ui.field label="Concept note deadline" name="concept_deadline">
                    <input id="concept_deadline" type="date" name="concept_deadline" class="input" value="{{ old('concept_deadline', $call->concept_deadline?->toDateString()) }}">
                </x-ui.field>
                <x-ui.field label="Full proposal deadline" name="proposal_deadline">
                    <input id="proposal_deadline" type="date" name="proposal_deadline" class="input" value="{{ old('proposal_deadline', $call->proposal_deadline?->toDateString()) }}">
                </x-ui.field>
            </div>
        </div>
    </section>

    {{-- ── Who can apply ─────────────────────────────────────────────── --}}
    <section class="card">
        <div class="card-head"><div><div class="card-title">Who can apply</div><div class="card-note">Rules on the lead applicant, checked before they can start.</div></div></div>
        <div class="card-body feature-list">
            <div class="feature">
                @include('seed.partials.switch', ['path' => 'service_limit.on', 'label' => 'Years-at-NIMR limit', 'help' => 'Lead applicants must have served at NIMR for under this many years.'])
                <label class="feature-set">Under <input type="number" class="input input-num" min="1" max="60" name="features[service_limit][years]" value="{{ $f('service_limit.years') }}" @disabled($locked)> years</label>
            </div>
            <div class="feature">
                @include('seed.partials.switch', ['path' => 'prior_grant.on', 'label' => 'Prior-grant question', 'help' => 'Asks whether they have held a large grant as PI; anyone who has cannot apply.'])
                <span class="feature-set">
                    <label>Above USD <input type="number" class="input input-num" min="0" name="features[prior_grant][usd]" value="{{ $f('prior_grant.usd') }}" @disabled($locked)></label>
                    <label>Details <input type="number" class="input input-num" min="{{ $locked ? $f('prior_grant.words') : 10 }}" name="features[prior_grant][words]" value="{{ $f('prior_grant.words') }}"> words</label>
                </span>
            </div>
        </div>
    </section>

    {{-- ── Concept note ──────────────────────────────────────────────── --}}
    <section class="card">
        <div class="card-head"><div><div class="card-title">Concept note</div><div class="card-note">Section A details, the questions, and the declaration.</div></div></div>
        <div class="card-body feature-list">
            <div class="feature">
                <span class="feature-name"><strong>Project title</strong><span class="help">Always asked.</span></span>
                <label class="feature-set">Up to <input type="number" class="input input-num" min="{{ $locked ? $f('title_words') : 5 }}" name="features[title_words]" value="{{ $f('title_words') }}"> words</label>
            </div>
            <div class="feature">
                @include('seed.partials.switch', ['path' => 'mentor.on', 'label' => 'Scientific mentor', 'help' => 'Name, designation, centre and relevant expertise.'])
                <label class="feature-set">Expertise <input type="number" class="input input-num" min="{{ $locked ? $f('mentor.words') : 10 }}" name="features[mentor][words]" value="{{ $f('mentor.words') }}"> words</label>
            </div>
            <div class="feature">
                @include('seed.partials.switch', ['path' => 'co_investigators.on', 'label' => 'Co-investigators or collaborators', 'help' => 'Optional for the applicant to fill.'])
                <label class="feature-set">Up to <input type="number" class="input input-num" min="{{ $locked ? $f('co_investigators.words') : 10 }}" name="features[co_investigators][words]" value="{{ $f('co_investigators.words') }}"> words</label>
            </div>
            <div class="feature">
                @include('seed.partials.switch', ['path' => 'declarations.on', 'label' => 'Applicant declaration', 'help' => 'The four statements of Section E, all to be ticked.'])
            </div>
        </div>
        @include('seed.partials.question-table', ['stage' => 'concept'])
    </section>

    {{-- ── Full proposal ─────────────────────────────────────────────── --}}
    <section class="card">
        <div class="card-head"><div><div class="card-title">Full proposal</div><div class="card-note">For shortlisted applicants: the questions, the detailed budget and the documents.</div></div></div>
        @include('seed.partials.question-table', ['stage' => 'proposal'])
        <div class="card-body feature-list">
            <div class="feature">
                @include('seed.partials.switch', ['path' => 'equipment_cap.on', 'label' => 'Equipment cap', 'help' => 'Equipment may not exceed this share of the detailed budget.'])
                <label class="feature-set">Up to <input type="number" class="input input-num" min="0" max="100" name="equipment_cap_percent" value="{{ old('equipment_cap_percent', $call->equipment_cap_percent ?? 20) }}" @disabled($locked)> %</label>
                @if($locked)<input type="hidden" name="equipment_cap_percent" value="{{ $call->equipment_cap_percent }}">@endif
            </div>
            @foreach(SeedGrant::DOCUMENT_KINDS as $kind => [$label])
                <div class="feature">
                    <span class="feature-name"><strong>{{ $label }}</strong><span class="help">Upload with the full proposal.</span></span>
                    <select class="select select-sm" name="features[documents][{{ $kind }}]" aria-label="{{ $label }}" @disabled($locked)>
                        @foreach(CallForm::DOCUMENT_STATES as $state => $stateLabel)
                            <option value="{{ $state }}" @selected($f('documents.' . $kind) === $state)>{{ $stateLabel }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ── Scored review ─────────────────────────────────────────────── --}}
    <section class="card">
        <div class="card-head"><div><div class="card-title">Scored review</div><div class="card-note">How reviewers score full proposals. Switch every criterion off for a call without scoring.</div></div></div>
        <div class="table-wrap">
            <table class="table settings-table">
                <thead><tr><th>Use</th><th>Criterion</th><th class="num">Maximum score</th><th class="num">Minimum to be funded</th></tr></thead>
                <tbody>
                    @foreach(SeedGrant::CRITERIA as $key => [$name, $what])
                        <tr>
                            <td>
                                <input type="hidden" name="features[criteria][{{ $key }}][on]" value="0">
                                <input type="checkbox" class="switch-box" name="features[criteria][{{ $key }}][on]" value="1" aria-label="Use {{ $name }}" @checked($isOn("criteria.$key.on")) @disabled($locked)>
                            </td>
                            <td><strong>{{ $name }}</strong><div class="tiny muted-3">{{ $what }}</div></td>
                            <td class="num"><input type="number" class="input input-num" step="0.5" min="1" max="100" name="features[criteria][{{ $key }}][max]" value="{{ SeedGrant::trimNumber((float) $f("criteria.$key.max")) }}" aria-label="{{ $name }} maximum" @disabled($locked)></td>
                            <td class="num"><input type="number" class="input input-num" step="0.5" min="0" name="features[criteria][{{ $key }}][min]" value="{{ $f("criteria.$key.min") !== null ? SeedGrant::trimNumber((float) $f("criteria.$key.min")) : '' }}" placeholder="None" aria-label="{{ $name }} minimum" @disabled($locked)></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-body feature-list">
            <div class="feature">
                <span class="feature-name"><strong>Pass mark</strong><span class="help">The mean total a proposal needs to be recommended. Currently out of {{ SeedGrant::trimNumber($form->maxTotal()) }}.</span></span>
                <label class="feature-set">At least <input type="number" class="input input-num" step="0.5" min="0" name="features[pass_mark]" value="{{ SeedGrant::trimNumber((float) $f('pass_mark')) }}" @disabled($locked)></label>
            </div>
        </div>
    </section>

    {{-- ── Approval ──────────────────────────────────────────────────── --}}
    <section class="card">
        <div class="card-head"><div><div class="card-title">Approval</div><div class="card-note">Who approves a recommended proposal, in this order. The last one may set conditions.</div></div></div>
        <div class="card-body feature-list">
            <div class="feature">
                <label class="check"><input type="checkbox" checked disabled> <span><strong>GMU sign-off</strong></span></label>
                <span class="help">Always on.</span>
            </div>
            @include('seed.partials.switch', ['path' => 'approval.drcp', 'label' => 'DRCP clearance', 'help' => 'Director, Research Coordination and Promotion.', 'row' => true])
            @include('seed.partials.switch', ['path' => 'approval.dg', 'label' => 'Director General’s approval', 'help' => 'Final institutional approval.', 'row' => true])
        </div>
    </section>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">{{ $isNew ? 'Create the call' : 'Save settings' }}</button>
        <a href="{{ $isNew ? route('seed.calls.index') : route('seed.calls.show', $call) }}" class="btn">Cancel</a>
    </div>
</form>
@endsection
