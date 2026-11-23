@extends('layouts.app')

{{--
    A review, from the owner's "Reviews" design. First the reviewer declares their
    interests (before they see the application); then the scoring page: what changed
    since the last round, the application read only, and beside it the total, a score
    for each criterion, the recommendation, comments, and a confirmation before submitting.
--}}

@php
    use App\Support\SeedGrant as S;
    use App\Models\Seed\SeedReview;
    $stage    = $assignment->stage;
    $editable = $assignment->canReview() && $round && $round->isOpen() && ! $review?->isSubmitted();
    $stageName = $stage === 'concept' ? 'Concept note' : 'Full proposal';
    $recTone = ['accept' => 'border-primary bg-blue-50 text-blue-700', 'revise' => 'border-warn bg-warn-soft text-warn-ink', 'reject' => 'border-danger bg-danger-soft text-danger-ink'];
@endphp

@section('title', 'Review ' . $application->reference)
@section('context', 'Review panel · My reviews · ' . $application->reference)
@section('topbar-colour', 'bg-emerald-700')

@section('content')
@if($assignment->coi_status === 'pending')
    {{-- Step 1: declare interests, before seeing the application --}}
    <div class="-mx-4 -mt-5 flex justify-center bg-emerald-700 px-7 pb-[110px] pt-[18px] text-white lg:-mx-6 lg:-mt-6">
        <div class="flex w-full max-w-[680px] flex-col gap-2.5">
            <a href="{{ route('seed.reviews.index') }}" class="inline-flex items-center gap-1.5 self-start text-[13px] font-medium text-emerald-100 hover:text-white"><x-icon name="arrow-left" class="size-3.5" />My reviews</a>
            <span class="inline-flex h-[26px] items-center gap-2 self-start rounded-[7px] bg-white/15 px-2.5 text-[12.5px] font-medium">Step 1 of 2 · before you see the application</span>
            <h1 class="text-[30px] font-semibold leading-tight tracking-[-0.025em] text-white">Declare your interests</h1>
            <p class="text-pretty text-[15px] leading-relaxed text-emerald-100">Before you see the application, tell GMU whether you have any actual, potential or perceived conflict of interest.</p>
        </div>
    </div>
    <div class="-mt-[84px] flex justify-center pb-10">
        <form method="POST" action="{{ route('seed.reviews.declare', $assignment) }}" x-data="{ conflict: @js(old('conflict')), tried: false }"
              @submit="tried = true; if (conflict === null || conflict === '') $event.preventDefault()"
              class="w-full max-w-[680px] overflow-hidden rounded-[18px] border border-border bg-card shadow-pop">
            @csrf
            <dl class="grid gap-x-6 gap-y-4 border-b border-emerald-100 bg-emerald-50/50 px-[22px] py-5 sm:grid-cols-2">
                <div class="sm:col-span-2"><dt class="text-xs text-muted-foreground">Application</dt><dd class="mt-0.5 text-base font-semibold leading-snug">{{ $application->title }}</dd></div>
                <div><dt class="text-xs text-muted-foreground">Applicant</dt><dd class="mt-0.5 text-sm font-medium">{{ $application->pi->name }}{{ $application->pi->research_center ? ' · ' . $application->pi->research_center : '' }}</dd></div>
                <div><dt class="text-xs text-muted-foreground">Mentor</dt><dd class="mt-0.5 text-sm font-medium">{{ $application->mentor_name ?: '—' }}</dd></div>
                <div><dt class="text-xs text-muted-foreground">Stage</dt><dd class="mt-0.5 text-sm font-medium">{{ $stageName }}</dd></div>
            </dl>
            <div class="flex flex-col gap-4 px-[22px] py-5">
                <p class="text-pretty text-[13.5px] leading-relaxed text-gray-700">A conflict includes working in the same section, supervising or being supervised by the applicant or mentor, a personal or financial interest, or co-authorship in the last three years.</p>
                <fieldset class="flex flex-col gap-2.5">
                    <legend class="sr-only">Declaration</legend>
                    @foreach(['0' => ['I have no conflict of interest', 'I will review this application impartially and keep it confidential.'], '1' => ['I have a conflict of interest', 'GMU will give the review to someone else.']] as $value => [$label, $sub])
                        <label class="flex cursor-pointer items-start gap-3.5 rounded-xl border-[1.5px] px-4 py-3.5 transition-colors"
                               :class="conflict === '{{ $value }}' ? 'border-emerald-700 bg-emerald-50/60' : (tried && ! conflict ? 'border-danger' : 'border-input hover:bg-gray-50')">
                            <input type="radio" class="radio mt-0.5 text-emerald-700 focus:ring-emerald-700/20" name="conflict" value="{{ $value }}" x-model="conflict" required>
                            <span class="leading-snug"><span class="block text-[14.5px] font-semibold">{{ $label }}</span><span class="block text-[13px] text-muted-foreground">{{ $sub }}</span></span>
                        </label>
                    @endforeach
                </fieldset>
                <p class="text-[13px] text-danger-ink" x-show="tried && ! conflict" x-cloak>Choose one of the two statements.</p>
                <label class="flex flex-col gap-1.5" x-show="conflict === '1'" x-cloak>
                    <span class="text-[13px] font-medium">Describe the conflict</span>
                    <textarea name="coi_note" class="textarea" rows="3" :required="conflict === '1'">{{ old('coi_note') }}</textarea>
                    <span class="text-xs text-muted-foreground">GMU reads this to decide who reviews instead.</span>
                </label>
            </div>
            <footer class="flex items-center justify-between gap-3 border-t border-border bg-gray-50 px-[22px] py-3.5">
                <span class="text-[12.5px] text-muted-foreground">Recorded with your name and the time.</span>
                <button type="submit" class="h-[42px] rounded-lg bg-foreground px-[18px] text-sm font-medium text-white hover:bg-gray-800">Record declaration</button>
            </footer>
        </form>
    </div>
@else
    {{-- Step 2: the scoring page --}}
    @php $form = $application->call->form(); $maxTotal = $form->maxTotal() ?: 100; $criteriaCount = count($form->criteria()); @endphp
    <div class="-mx-4 -mt-5 flex flex-col gap-2.5 bg-emerald-700 px-7 pb-8 pt-[18px] text-white lg:-mx-6 lg:-mt-6">
        <a href="{{ route('seed.reviews.index') }}" class="inline-flex items-center gap-1.5 self-start text-[13px] font-medium text-emerald-100 hover:text-white"><x-icon name="arrow-left" class="size-3.5" />My reviews</a>
        <h1 class="max-w-[900px] text-balance text-[28px] font-semibold leading-tight tracking-[-0.025em] text-white">{{ $application->title }}</h1>
        <div class="flex flex-wrap items-center gap-2 text-[13px] text-emerald-100">
            <span class="inline-flex h-[22px] items-center whitespace-nowrap rounded-md bg-white px-2 text-xs font-semibold text-foreground">{{ $stageName }}{{ $round ? ' · round ' . $round->number : '' }}</span>
            @if($assignment->coi_status === 'none')<span class="inline-flex h-[22px] items-center gap-1 whitespace-nowrap rounded-md bg-white/15 px-2 text-xs font-medium text-white"><x-icon name="check" class="size-3" />No conflict declared</span>@endif
            <span class="font-mono text-[12.5px]">{{ $application->reference }}</span>
            <span>·</span>
            <span>
                @if($review?->isSubmitted()) Submitted {{ $review->submitted_at->format('j M Y') }}
                @elseif($round?->isOpen()) Due {{ $round->due_at?->format('j M Y') }}{{ $review ? ' · draft saved' : '' }}
                @else This round is closed @endif
            </span>
        </div>
    </div>

    <div class="flex flex-col gap-5 pt-6">
        @if($previousRound)
            <section class="overflow-hidden rounded-[14px] border border-warn-line bg-warn-soft/50">
                <header class="flex items-center gap-2.5 border-b border-warn-line bg-warn-soft px-[18px] py-3"><x-icon name="history" class="size-4 text-warn-ink" /><h2 class="text-sm font-semibold text-amber-900">Since round {{ $previousRound->number }}</h2></header>
                <div class="grid gap-[18px] px-[18px] py-4 md:grid-cols-3">
                    @if($previousReview)
                        <div><p class="text-[13px] text-muted-foreground">You recommended <strong class="text-foreground">{{ SeedReview::RECOMMENDATIONS[$previousReview->recommendation] ?? '—' }}</strong> and wrote:</p><p class="mt-1 whitespace-pre-line text-[13.5px] leading-relaxed text-gray-800">{{ $previousReview->comments }}</p></div>
                    @endif
                    @if($previousRound->decision_note)
                        <div><p class="text-[13px] text-muted-foreground">GMU asked the applicant:</p><p class="mt-1 whitespace-pre-line text-[13.5px] leading-relaxed text-gray-800">{{ $previousRound->decision_note }}</p></div>
                    @endif
                    @if($previousRound->response)
                        <div><p class="text-[13px] font-semibold">The applicant’s response</p><p class="mt-1 whitespace-pre-line text-[13.5px] leading-relaxed text-gray-800">{{ $previousRound->response }}</p></div>
                    @endif
                </div>
            </section>
        @endif

        <div class="flex flex-wrap items-start gap-6">
            {{-- The application, read only --}}
            <div class="flex min-w-0 flex-[1_1_520px] flex-col gap-4">
                @if($stage === 'proposal')
                    <details class="group overflow-hidden rounded-[14px] border border-border">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 bg-card px-[18px] py-[15px] text-[14.5px] font-semibold hover:bg-gray-50 [&::-webkit-details-marker]:hidden">
                            Read the concept note it grew from <x-icon name="chevron-down" class="text-subtle transition-transform group-open:rotate-180" />
                        </summary>
                        <div class="border-t border-border bg-gray-50 p-[18px]">@include('seed.partials.content', ['stage' => 'concept'])</div>
                    </details>
                @endif
                @include('seed.partials.content', ['stage' => $stage])
            </div>

            {{-- Scoring --}}
            <aside class="flex min-w-[320px] flex-[0_1_400px] flex-col gap-3.5 xl:sticky xl:top-20">
                @if($review?->isSubmitted())
                    <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3.5">
                        <span class="grid size-[22px] shrink-0 place-items-center rounded-full bg-emerald-700 text-white"><x-icon name="check" class="size-3" /></span>
                        <div><p class="text-sm font-semibold text-emerald-800">Review submitted</p><p class="mt-0.5 text-[13px] text-gray-700">{{ $review->submitted_at->format('j M Y') }}. It can no longer be changed.</p></div>
                    </div>
                @endif

                <form id="review-form" method="POST" action="{{ route('seed.reviews.save', $assignment) }}" class="flex flex-col gap-3.5" data-review novalidate
                      x-data="{ rec: @js(old('recommendation', $review?->recommendation)), comments: @js(old('comments', $review?->comments ?? '')), tried: false, confirm: false, total: '—', touched: 0,
                                missing() { this.touched; return this.$root.querySelectorAll('[data-score][data-unset]').length },
                                errors() {
                                    const e = [], m = this.missing();
                                    if (m) e.push('Score all {{ $criteriaCount }} criteria (' + m + ' left).');
                                    if (! this.rec) e.push('Choose your recommendation.');
                                    if (! this.comments.trim()) e.push('Write comments for the applicant.');
                                    return e;
                                } }"
                      @input="touched++" @keydown.escape.window="confirm = false">
                    @csrf @method('PUT')

                    @if($stage === 'proposal')
                        <section class="rounded-2xl bg-emerald-800 px-5 py-[18px] text-white shadow-lift">
                            <div class="flex items-center justify-between"><span class="text-[13.5px] font-medium text-emerald-100">Your total</span><span class="text-xs text-emerald-200" data-set-count></span></div>
                            <div class="mt-1.5 flex items-baseline gap-1.5"><span class="text-[46px] font-semibold leading-none tracking-[-0.03em] tabular-nums" data-total>{{ $review?->total ?? '—' }}</span><span class="text-base text-emerald-200">/ {{ S::trimNumber($maxTotal) }}</span></div>
                            <div class="relative mt-3.5 h-2 rounded-full bg-white/20">
                                <div data-total-bar class="absolute inset-y-0 left-0 rounded-full bg-white transition-[width]" style="width: {{ min(100, ($review?->total ?? 0) / $maxTotal * 100) }}%"></div>
                                <span aria-hidden="true" title="Pass mark" class="absolute -bottom-[5px] -top-[5px] w-0.5 rounded-full bg-warn" style="left: {{ min(100, $form->passMark() / $maxTotal * 100) }}%"></span>
                            </div>
                            <div class="relative mt-1.5 h-4 font-mono text-[11px] text-emerald-200">
                                <span class="absolute left-0">0</span>
                                <span class="absolute -translate-x-1/2 whitespace-nowrap text-amber-200" style="left: {{ min(100, $form->passMark() / $maxTotal * 100) }}%">Pass {{ S::trimNumber($form->passMark()) }}</span>
                                <span class="absolute right-0">{{ S::trimNumber($maxTotal) }}</span>
                            </div>
                            <p class="mt-3 rounded-[10px] bg-white/10 px-3 py-2.5 text-[13px] text-emerald-100" data-verdict></p>
                        </section>

                        <section class="overflow-hidden rounded-[14px] border border-border">
                            <header class="border-b border-border bg-muted px-4 py-3"><h3 class="text-sm font-semibold">Score each criterion</h3></header>
                            <div class="flex flex-col gap-[18px] bg-card px-4 py-3.5">
                                @foreach($form->criteria() as $key => [$name, $what, $max])
                                    @php $value = old("scores.$key", $review?->score($key)); $min = $form->minimums()[$key] ?? null; @endphp
                                    <div>
                                        <div class="flex items-start justify-between gap-3">
                                            <label for="score-{{ $key }}" class="text-[13.5px] font-semibold leading-snug">{{ $name }}</label>
                                            <span class="whitespace-nowrap font-mono text-sm"><output class="font-semibold" data-out-for="score-{{ $key }}">{{ $value ?? '—' }}</output><span class="text-muted-foreground"> / {{ S::trimNumber($max) }}</span></span>
                                        </div>
                                        <p class="mt-0.5 text-xs leading-snug text-muted-foreground">{{ $what }}</p>
                                        <input id="score-{{ $key }}" type="range" name="scores[{{ $key }}]" min="0" max="{{ $max }}" step="0.5"
                                               value="{{ $value ?? 0 }}" data-score="{{ $key }}" data-max="{{ $max }}" @if($min !== null) data-min="{{ $min }}" @endif @if($value === null) data-unset @endif
                                               class="mt-1.5 h-7 w-full cursor-pointer accent-emerald-700 transition-opacity data-[unset]:opacity-45 disabled:cursor-not-allowed" @disabled(! $editable)>
                                        @if($min !== null)<p class="text-xs text-muted-foreground" data-min-note-for="score-{{ $key }}">Must reach {{ S::trimNumber($min) }} for the proposal to be fundable.</p>@endif
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <section class="overflow-hidden rounded-[14px] border border-border">
                        <div class="flex flex-col gap-4 bg-card p-4">
                            <fieldset>
                                <legend class="mb-2 text-[13.5px] font-semibold">Your recommendation</legend>
                                <div class="grid grid-cols-3 gap-2">
                                    @foreach(SeedReview::RECOMMENDATIONS as $value => $label)
                                        <label class="flex h-[42px] cursor-pointer items-center justify-center rounded-[9px] border-[1.5px] text-sm font-semibold transition-colors has-[:disabled]:cursor-not-allowed"
                                               :class="rec === '{{ $value }}' ? '{{ $recTone[$value] ?? '' }}' : (tried && ! rec ? 'border-danger bg-card text-gray-700' : 'border-input bg-card text-gray-700 hover:bg-gray-50')">
                                            <input type="radio" class="sr-only" name="recommendation" value="{{ $value }}" x-model="rec" @checked(old('recommendation', $review?->recommendation) === $value) @disabled(! $editable)>{{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                            <label class="flex flex-col gap-1.5">
                                <span class="text-[13.5px] font-semibold">Comments for the applicant</span>
                                <textarea name="comments" class="textarea" rows="6" x-model="comments" :aria-invalid="tried && ! comments.trim() ? 'true' : 'false'" @readonly(! $editable)></textarea>
                                <span class="text-xs text-muted-foreground">Shared with the applicant without your name.</span>
                            </label>
                            <label class="flex flex-col gap-1.5">
                                <span class="text-[13.5px] font-semibold">Confidential note to GMU <span class="font-normal text-muted-foreground">(optional)</span></span>
                                <textarea name="confidential_note" class="textarea" rows="2" @readonly(! $editable)>{{ old('confidential_note', $review?->confidential_note) }}</textarea>
                                <span class="text-xs text-muted-foreground">The applicant never sees this.</span>
                            </label>
                            <div role="alert" class="rounded-[10px] border border-danger-line bg-danger-soft px-3.5 py-3 text-danger-ink" x-show="tried && errors().length" x-cloak>
                                <p class="text-[13px] font-semibold">Before you submit</p>
                                <ul class="mt-1.5 list-disc pl-4 text-[13px] leading-normal"><template x-for="e in errors()"><li x-text="e"></li></template></ul>
                            </div>
                        </div>
                        @if($editable)
                            <footer class="flex gap-2 border-t border-border bg-gray-50 px-4 py-3">
                                <button type="submit" name="action" value="draft" class="inline-flex h-10 items-center gap-1.5 rounded-lg border border-input bg-card px-3.5 text-[13.5px] font-medium text-foreground hover:bg-muted"><x-icon name="save" class="size-4" />Save draft</button>
                                <button type="button" @click="tried = true; if (! errors().length) { total = document.querySelector('[data-total]')?.textContent ?? '—'; confirm = true }" class="h-10 flex-1 rounded-lg bg-emerald-700 text-sm font-semibold text-white hover:bg-emerald-800">Submit review</button>
                            </footer>
                        @endif
                    </section>

                    {{-- Confirm before submitting --}}
                    <div x-cloak x-show="confirm" x-transition.opacity class="fixed inset-0 z-[60] grid place-items-center bg-foreground/50 p-6" @click.self="confirm = false" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
                        <div class="w-full max-w-[480px] overflow-hidden rounded-[18px] bg-card shadow-pop">
                            <div class="flex flex-col gap-3 px-6 py-[22px]">
                                <h2 id="confirm-title" class="text-[19px] font-semibold tracking-[-0.015em]">Submit your review?</h2>
                                <p class="text-sm leading-normal text-gray-700">You cannot change it afterwards.</p>
                                <dl class="mt-1 grid grid-cols-2 gap-2.5">
                                    <div class="rounded-[10px] bg-muted px-3.5 py-3"><dt class="text-xs text-muted-foreground">Recommendation</dt><dd class="mt-0.5 text-[15px] font-semibold" x-text="{{ json_encode(SeedReview::RECOMMENDATIONS) }}[rec] ?? '—'"></dd></div>
                                    <div class="rounded-[10px] bg-muted px-3.5 py-3"><dt class="text-xs text-muted-foreground">{{ $stage === 'proposal' ? 'Your total' : 'Stage' }}</dt><dd class="mt-0.5 text-[15px] font-semibold">@if($stage === 'proposal')<span x-text="total"></span> / {{ S::trimNumber($maxTotal) }}@else{{ $stageName }}@endif</dd></div>
                                </dl>
                            </div>
                            <footer class="flex justify-end gap-2 border-t border-border bg-gray-50 px-6 py-3.5">
                                <button type="button" @click="confirm = false" class="h-10 rounded-lg border border-input bg-card px-4 text-sm font-medium hover:bg-muted">Keep editing</button>
                                <button type="submit" name="action" value="submit" class="h-10 rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800">Submit review</button>
                            </footer>
                        </div>
                    </div>
                </form>
            </aside>
        </div>
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
    var verdict = document.querySelector('[data-verdict]');
    function setVerdict(text, kind) {
        verdict.textContent = text;
        verdict.classList.remove('bg-white/10', 'text-emerald-100', 'bg-white', 'text-emerald-800', 'bg-danger-soft', 'text-danger-ink', 'font-semibold');
        if (kind === 'ok') verdict.classList.add('bg-white', 'text-emerald-800', 'font-semibold');
        else if (kind === 'bad') verdict.classList.add('bg-danger-soft', 'text-danger-ink', 'font-semibold');
        else verdict.classList.add('bg-white/10', 'text-emerald-100');
    }
    function update() {
        var total = 0, set = 0, scores = {};
        inputs.forEach(function (i) {
            var out = document.querySelector('[data-out-for="' + i.id + '"]'), note = document.querySelector('[data-min-note-for="' + i.id + '"]');
            var unset = i.hasAttribute('data-unset'), v = parseFloat(i.value), low = !unset && i.dataset.min && v < +i.dataset.min;
            out.textContent = unset ? '—' : v;
            out.classList.toggle('text-danger-ink', !!low);
            if (note) {
                note.textContent = low ? 'Below the minimum of ' + i.dataset.min + '.' : 'Must reach ' + i.dataset.min + ' for the proposal to be fundable.';
                note.classList.toggle('text-danger-ink', !!low); note.classList.toggle('text-muted-foreground', !low);
            }
            if (unset) return;
            set++; total += v; scores[i.dataset.score] = v;
        });
        var complete = set === inputs.length;
        document.querySelector('[data-set-count]').textContent = set + ' of ' + inputs.length + ' scored';
        document.querySelector('[data-total]').textContent = complete ? total : '—';
        document.querySelector('[data-total-bar]').style.width = Math.min(100, (complete ? total : 0) / maxTotal * 100) + '%';
        if (!complete) { setVerdict('Score every criterion to see the total.'); return; }
        var under = Object.keys(mins).filter(function (k) { return (scores[k] || 0) < mins[k].min; });
        var ok = total >= pass && under.length === 0;
        setVerdict(ok ? 'Fundable on your scores.'
            : (under.length ? mins[under[0]].name + ' below ' + mins[under[0]].min + ' of ' + mins[under[0]].max + ': not fundable.' : 'Below ' + pass + ': not fundable.'),
            ok ? 'ok' : 'bad');
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
