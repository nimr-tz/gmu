@extends('layouts.app')

@php
    use App\Support\SeedGrant as S;
    use App\Models\Seed\SeedReview;
    $stage    = $assignment->stage;
    $editable = $assignment->canReview() && $round && $round->isOpen() && ! $review?->isSubmitted();
@endphp

@section('title', 'Review ' . $application->reference)
@section('context', 'My reviews · ' . $application->reference)

@section('content')
@if($assignment->coi_status === 'pending')
    {{-- Before anything else: declare interests --}}
    <div class="mx-auto max-w-2xl">
        <x-page-header title="Declare your interests" :back="route('seed.reviews.index')" back-label="My reviews"
            description="Before you see the application, tell GMU whether you have any actual, potential or perceived conflict of interest." />
        <form method="POST" action="{{ route('seed.reviews.declare', $assignment) }}" x-data="{ conflict: '{{ old('conflict') }}' }">
            @csrf
            <x-card>
                <dl class="grid gap-x-6 gap-y-4 pt-1 sm:grid-cols-2">
                    <div class="sm:col-span-2"><dt class="text-xs text-muted-foreground">Application</dt><dd class="mt-1 font-medium">{{ $application->title }}</dd></div>
                    <x-stat label="Applicant" :value="$application->pi->name . ($application->pi->research_center ? ' · ' . $application->pi->research_center : '')" />
                    <x-stat label="Mentor" :value="$application->mentor_name ?: '—'" />
                    <x-stat label="Stage" :value="$stage === 'concept' ? 'Concept note' : 'Full proposal'" />
                </dl>
                <p class="mt-5 border-t border-border pt-4 text-[13px] text-muted-foreground">A conflict includes working in the same section, supervising or being supervised by the applicant or mentor, a personal or financial interest, or co-authorship in the last three years.</p>
                <fieldset class="mt-4 space-y-2">
                    <legend class="sr-only">Declaration</legend>
                    @foreach(['0' => ['I have no conflict of interest', 'I will review this application impartially and keep it confidential.'], '1' => ['I have a conflict of interest', 'GMU will give the review to someone else.']] as $value => [$label, $sub])
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors" :class="conflict === '{{ $value }}' ? 'border-primary bg-primary-soft/60 ring-1 ring-primary' : 'border-border hover:bg-muted/50'">
                            <input type="radio" class="radio mt-0.5" name="conflict" value="{{ $value }}" x-model="conflict" required>
                            <span><span class="block text-sm font-medium">{{ $label }}</span><span class="block text-xs text-muted-foreground">{{ $sub }}</span></span>
                        </label>
                    @endforeach
                </fieldset>
                <x-field class="mt-4" label="Describe the conflict" name="coi_note" optional>
                    <textarea id="coi_note" name="coi_note" class="textarea" rows="3">{{ old('coi_note') }}</textarea>
                </x-field>
                <x-slot:footer class="flex justify-end"><x-button type="submit" variant="primary">Record declaration</x-button></x-slot:footer>
            </x-card>
        </form>
    </div>
@else
    <x-page-header :title="$application->title" :back="route('seed.reviews.index')" back-label="My reviews">
        <x-slot:meta>
            <div class="mt-3 flex flex-wrap items-center gap-2.5 text-[13px] text-muted-foreground">
                <x-badge>{{ $stage === 'concept' ? 'Concept note' : 'Full proposal' }}{{ $round ? ' · round ' . $round->number : '' }}</x-badge>
                <x-badge tone="ok" icon="check">No conflict declared</x-badge>
                <span class="font-mono text-xs">{{ $application->reference }}</span>
                <span>·</span>
                <span>
                    @if($review?->isSubmitted()) Submitted {{ $review->submitted_at->format('j M Y') }}
                    @elseif($round?->isOpen()) Due {{ $round->due_at?->format('j M Y') }}{{ $review ? ' · draft saved' : '' }}
                    @else This round is closed @endif
                </span>
            </div>
        </x-slot:meta>
    </x-page-header>

    @if($previousRound)
        <x-card class="mb-6" tone="warn" :title="'Since round ' . $previousRound->number" icon="history">
            <div class="space-y-4">
                @if($previousReview)
                    <div><p class="text-[13px] text-muted-foreground">You recommended <strong class="text-foreground">{{ SeedReview::RECOMMENDATIONS[$previousReview->recommendation] ?? '—' }}</strong> and wrote:</p>
                        <p class="prose-text mt-1">{{ $previousReview->comments }}</p></div>
                @endif
                @if($previousRound->decision_note)
                    <div><p class="text-[13px] text-muted-foreground">GMU asked the applicant:</p><p class="prose-text mt-1">{{ $previousRound->decision_note }}</p></div>
                @endif
                @if($previousRound->response)
                    <div><p class="text-[13px] font-medium">The applicant’s response</p><p class="prose-text mt-1">{{ $previousRound->response }}</p></div>
                @endif
            </div>
        </x-card>
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_26rem]">
        <div class="space-y-6">
            @if($stage === 'proposal')
                <details class="group overflow-hidden rounded-xl border border-border bg-card shadow-xs">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4 text-[15px] font-semibold hover:bg-muted/50 [&::-webkit-details-marker]:hidden">
                        Read the concept note it grew from <x-icon name="chevron-down" class="text-subtle transition-transform group-open:rotate-180" />
                    </summary>
                    <div class="border-t border-border bg-background p-5">@include('seed.partials.content', ['stage' => 'concept'])</div>
                </details>
            @endif
            @include('seed.partials.content', ['stage' => $stage])
        </div>

        <aside class="xl:sticky xl:top-24">
            <form method="POST" action="{{ route('seed.reviews.save', $assignment) }}" class="space-y-4" data-review>
                @csrf @method('PUT')

                @if($stage === 'proposal')
                    @php $form = $application->call->form(); $maxTotal = $form->maxTotal() ?: 100; @endphp
                    <section class="rounded-xl bg-sidebar p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wider text-blue-300">Your total</p>
                        <div class="mt-2 flex items-baseline gap-1.5"><span class="font-mono text-5xl leading-none text-white" data-total>{{ $review?->total ?? '—' }}</span><span class="font-mono text-sidebar-muted">/ {{ S::trimNumber($maxTotal) }}</span></div>
                        <div class="relative mt-4 h-2 rounded-full bg-sidebar-chip">
                            <div data-total-bar class="absolute inset-y-0 left-0 rounded-full bg-blue-400 transition-[width]" style="width: {{ min(100, ($review?->total ?? 0) / $maxTotal * 100) }}%"></div>
                            <div aria-hidden="true" class="absolute -inset-y-1.5 w-0.5 bg-white" style="left: {{ min(100, $form->passMark() / $maxTotal * 100) }}%"></div>
                        </div>
                        <p class="mt-3 text-[13px] text-sidebar-foreground" data-verdict></p>
                    </section>

                    <x-card title="Score each criterion">
                        <div class="space-y-5">
                            @foreach($form->criteria() as $key => [$name, $what, $max])
                                @php $value = old("scores.$key", $review?->score($key)); @endphp
                                <div>
                                    <div class="flex items-start justify-between gap-3">
                                        <label for="score-{{ $key }}" class="text-sm font-medium">{{ $name }}</label>
                                        <span class="whitespace-nowrap font-mono text-sm"><output data-out-for="score-{{ $key }}">{{ $value ?? '—' }}</output><span class="text-muted-foreground"> / {{ S::trimNumber($max) }}</span></span>
                                    </div>
                                    <p class="mt-0.5 text-xs text-muted-foreground">{{ $what }}</p>
                                    <input id="score-{{ $key }}" type="range" name="scores[{{ $key }}]" min="0" max="{{ $max }}" step="0.5"
                                           value="{{ $value ?? 0 }}" data-score="{{ $key }}" data-max="{{ $max }}" @if($value === null) data-unset @endif
                                           class="mt-1 h-8 w-full cursor-pointer accent-primary disabled:cursor-not-allowed" @disabled(! $editable)>
                                </div>
                            @endforeach
                        </div>
                    </x-card>
                @endif

                <x-card>
                    <fieldset x-data="{ rec: '{{ old('recommendation', $review?->recommendation) }}' }">
                        <legend class="label mb-2">Your recommendation</legend>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach(SeedReview::RECOMMENDATIONS as $value => $label)
                                <label class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border px-2 py-2.5 text-sm font-medium transition-colors has-[:disabled]:cursor-not-allowed"
                                       :class="rec === '{{ $value }}' ? 'border-primary bg-primary-soft text-ok-ink ring-1 ring-primary' : 'border-border hover:bg-muted/50'">
                                    <input type="radio" class="sr-only" name="recommendation" value="{{ $value }}" x-model="rec" @checked(old('recommendation', $review?->recommendation) === $value) @disabled(! $editable)>{{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <x-field class="mt-4" label="Comments for the applicant" name="comments" help="Shared with the applicant without your name.">
                        <textarea id="comments" name="comments" class="textarea" rows="6" @readonly(! $editable)>{{ old('comments', $review?->comments) }}</textarea>
                    </x-field>
                    <x-field class="mt-4" label="Confidential note to GMU" name="confidential_note" help="The applicant never sees this." optional>
                        <textarea id="confidential_note" name="confidential_note" class="textarea" rows="2" @readonly(! $editable)>{{ old('confidential_note', $review?->confidential_note) }}</textarea>
                    </x-field>
                    @if($editable)
                        <x-slot:footer class="flex gap-2">
                            <x-button type="submit" name="action" value="draft" icon="save">Save draft</x-button>
                            <x-button type="submit" name="action" value="submit" variant="primary" class="flex-1" onclick="return confirm('Submit your review? You cannot change it afterwards.')">Submit review</x-button>
                        </x-slot:footer>
                    @endif
                </x-card>
            </form>
        </aside>
    </div>
@endif
@endsection

@if($stage === 'proposal' && $assignment->coi_status === 'none')
@push('scripts')
@php
    $scoreForm = $application->call->form();
    $scoreMins = [];
    foreach ($scoreForm->minimums() as $k => $min) {
        $scoreMins[$k] = ['min' => $min, 'name' => $scoreForm->criteria()[$k][0], 'max' => $scoreForm->criteria()[$k][2]];
    }
@endphp
<script>
(function () {
    var pass = {{ $scoreForm->passMark() }}, maxTotal = {{ $scoreForm->maxTotal() ?: 100 }};
    var mins = {!! json_encode((object) $scoreMins) !!};
    var inputs = document.querySelectorAll('[data-score]');
    function update() {
        var total = 0, complete = true, scores = {};
        inputs.forEach(function (i) {
            var out = document.querySelector('[data-out-for="' + i.id + '"]');
            if (i.hasAttribute('data-unset')) { complete = false; out.textContent = '—'; return; }
            var v = parseFloat(i.value); total += v; out.textContent = v;
            scores[i.dataset.score] = v;
        });
        document.querySelector('[data-total]').textContent = complete ? total : '—';
        document.querySelector('[data-total-bar]').style.width = Math.min(100, (complete ? total : 0) / maxTotal * 100) + '%';
        var verdict = document.querySelector('[data-verdict]');
        verdict.classList.remove('text-blue-200', 'text-red-300');
        if (!complete) { verdict.textContent = 'Score every criterion to see the total.'; return; }
        var under = Object.keys(mins).filter(function (k) { return (scores[k] || 0) < mins[k].min; });
        var ok = total >= pass && under.length === 0;
        verdict.textContent = ok ? 'Fundable on your scores.'
            : (under.length ? mins[under[0]].name + ' below ' + mins[under[0]].min + ' of ' + mins[under[0]].max + ': not fundable.' : 'Below ' + pass + ': not fundable.');
        verdict.classList.add(ok ? 'text-blue-200' : 'text-red-300');
    }
    inputs.forEach(function (i) { i.addEventListener('input', function () { i.removeAttribute('data-unset'); update(); }); });
    // Untouched sliders are not sent, so a criterion is never scored 0 by accident.
    document.querySelector('[data-review]').addEventListener('submit', function () {
        inputs.forEach(function (i) { if (i.hasAttribute('data-unset')) i.disabled = true; });
    });
    update();
})();
</script>
@endpush
@endif
