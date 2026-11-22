@extends('layouts.gmu')

@php $isNew = ! $call->exists; @endphp

@section('title', $isNew ? 'Set up the call' : 'Call settings')

@section('content')
@if($isNew)
    <p class="crumbs"><a href="{{ route('dashboard') }}">Dashboard</a> / Set up the call</p>
    <x-ui.page-head title="Set up the call"
                    lede="What staff see on the call page, and the limits applications are checked against. Keep the status at Draft until you are ready to open it." />
@else
    <p class="crumbs"><a href="{{ route('seed.call') }}">The call</a> / Settings</p>
    <x-ui.page-head title="Call settings" lede="What staff see on the call page, and the limits applications are checked against." />
@endif

<form method="POST" action="{{ route('seed.call.update') }}" class="card">
    @csrf @method('PUT')
    <div class="card-body">
        <div class="form-grid">
            <x-ui.field label="Title" name="title" wide required>
                <input id="title" name="title" class="input" value="{{ old('title', $call->title) }}" required>
            </x-ui.field>
            <x-ui.field label="Short summary" name="summary" wide help="One or two sentences shown under the title.">
                <textarea id="summary" name="summary" class="textarea" rows="3">{{ old('summary', $call->summary) }}</textarea>
            </x-ui.field>
            <x-ui.field label="Financial year" name="fiscal_year" required>
                <input id="fiscal_year" name="fiscal_year" class="input" value="{{ old('fiscal_year', $call->fiscal_year) }}" placeholder="2026/27" required>
            </x-ui.field>
            <x-ui.field label="Reference code" name="code" required help="Used in references, e.g. SG-2627-001.">
                <input id="code" name="code" class="input mono" value="{{ old('code', $call->code) }}" placeholder="2627" required>
            </x-ui.field>
            <x-ui.field label="Status" name="status" required>
                <select id="status" name="status" class="select">
                    @foreach(\App\Models\Seed\SeedCall::STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $call->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-ui.field>
            <x-ui.field label="Minimum award (TZS)" name="min_amount" required>
                <input id="min_amount" type="number" min="0" name="min_amount" class="input mono" value="{{ old('min_amount', $call->min_amount) }}" required>
            </x-ui.field>
            <x-ui.field label="Maximum award (TZS)" name="max_amount" required>
                <input id="max_amount" type="number" min="0" name="max_amount" class="input mono" value="{{ old('max_amount', $call->max_amount) }}" required>
            </x-ui.field>
            <x-ui.field label="Funds available (TZS)" name="envelope" help="Optional. Shown against what is recommended.">
                <input id="envelope" type="number" min="0" name="envelope" class="input mono" value="{{ old('envelope', $call->envelope) }}">
            </x-ui.field>
            <x-ui.field label="Study length (months)" name="duration_months" required>
                <input id="duration_months" type="number" min="1" max="36" name="duration_months" class="input mono" value="{{ old('duration_months', $call->duration_months) }}" required>
            </x-ui.field>
            <x-ui.field label="Equipment limit (% of budget)" name="equipment_cap_percent" required>
                <input id="equipment_cap_percent" type="number" min="0" max="100" name="equipment_cap_percent" class="input mono" value="{{ old('equipment_cap_percent', $call->equipment_cap_percent) }}" required>
            </x-ui.field>
            <x-ui.field label="Concept note deadline" name="concept_deadline">
                <input id="concept_deadline" type="date" name="concept_deadline" class="input" value="{{ old('concept_deadline', $call->concept_deadline?->toDateString()) }}">
            </x-ui.field>
            <x-ui.field label="Full proposal deadline" name="proposal_deadline">
                <input id="proposal_deadline" type="date" name="proposal_deadline" class="input" value="{{ old('proposal_deadline', $call->proposal_deadline?->toDateString()) }}">
            </x-ui.field>
        </div>
    </div>
    <div class="card-foot form-actions">
        <button class="btn btn-primary" type="submit">{{ $isNew ? 'Create the call' : 'Save settings' }}</button>
        <a href="{{ $isNew ? route('dashboard') : route('seed.call') }}" class="btn">Cancel</a>
    </div>
</form>
@endsection
