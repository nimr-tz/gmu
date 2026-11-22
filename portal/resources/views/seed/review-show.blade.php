@extends('layouts.gmu')

@php
    use App\Support\SeedGrant as S;
    use App\Models\Seed\SeedReview;
    $stage    = $assignment->stage;
    $editable = $assignment->canReview() && $round && $round->isOpen() && ! $review?->isSubmitted();
@endphp

@section('title', 'Review ' . $application->reference)
@section('context', 'My reviews · ' . $application->reference)

@section('content')
<p class="crumbs"><a href="{{ route('seed.reviews.index') }}">My reviews</a> / <span class="mono">{{ $application->reference }}</span></p>

@if($assignment->coi_status === 'pending')
    <div style="max-width:720px;">
        <x-ui.page-head title="Declare your interests" lede="Before you see the application, tell GMU whether you have any actual, potential or perceived conflict of interest." />
        <section class="card">
            <div class="card-body">
                <dl class="form-grid" style="margin:0;">
                    <div class="kv"><dt class="k">Application</dt><dd class="v" style="margin:0;">{{ $application->title }}</dd></div>
                    <div class="kv"><dt class="k">Applicant</dt><dd class="v" style="margin:0;">{{ $application->pi->name }}{{ $application->pi->research_center ? ' · ' . $application->pi->research_center : '' }}</dd></div>
                    <div class="kv"><dt class="k">Mentor</dt><dd class="v" style="margin:0;">{{ $application->mentor_name ?: '—' }}</dd></div>
                    <div class="kv"><dt class="k">Stage</dt><dd class="v" style="margin:0;">{{ $stage === 'concept' ? 'Concept note' : 'Full proposal' }}</dd></div>
                </dl>
                <p class="small muted" style="margin-top:18px;">A conflict includes working in the same section, supervising or being supervised by the applicant or mentor, a personal or financial interest, or co-authorship in the last three years.</p>
                <form method="POST" action="{{ route('seed.reviews.declare', $assignment) }}" style="margin-top:16px;">
                    @csrf
                    <fieldset style="border:0; padding:0; margin:0; display:flex; flex-direction:column; gap:8px;">
                        <legend class="sr-only">Declaration</legend>
                        <label class="opt"><input type="radio" name="conflict" value="0" required><span><strong>I have no conflict of interest</strong><span class="opt-sub">I will review this application impartially and keep it confidential.</span></span></label>
                        <label class="opt"><input type="radio" name="conflict" value="1"><span><strong>I have a conflict of interest</strong><span class="opt-sub">GMU will give the review to someone else.</span></span></label>
                    </fieldset>
                    <x-ui.field label="Describe the conflict (if any)" name="coi_note">
                        <textarea id="coi_note" name="coi_note" class="textarea" rows="3" style="margin-top:12px;">{{ old('coi_note') }}</textarea>
                    </x-ui.field>
                    <button class="btn btn-primary" type="submit" style="margin-top:14px;">Record declaration</button>
                </form>
            </div>
        </section>
    </div>
@else
    <div class="page-head">
        <div style="flex:1 1 520px; min-width:0;">
            <div class="row" style="gap:8px;"><span class="pill">{{ $stage === 'concept' ? 'Concept note' : 'Full proposal' }}{{ $round ? ' · round ' . $round->number : '' }}</span><span class="pill pill-ok">No conflict declared</span></div>
            <h1 style="margin-top:8px; font-size:clamp(20px,2vw,26px); line-height:1.3;">{{ $application->title }}</h1>
        </div>
        <span class="small muted">
            @if($review?->isSubmitted()) Submitted {{ $review->submitted_at->format('j M Y') }}
            @elseif($round?->isOpen()) Due {{ $round->due_at?->format('j M Y') }}{{ $review ? ' · draft saved' : '' }}
            @else This round is closed @endif
        </span>
    </div>

    @if($previousRound)
        <section class="card accent-warn" style="margin-bottom:20px;">
            <div class="card-body">
                <h2 class="card-title">Since round {{ $previousRound->number }}</h2>
                @if($previousReview)
                    <p class="small muted" style="margin-top:8px;">You recommended <strong>{{ SeedReview::RECOMMENDATIONS[$previousReview->recommendation] ?? '—' }}</strong> and wrote:</p>
                    <p class="doc-text" style="margin-top:4px; font-size:14px;">{{ $previousReview->comments }}</p>
                @endif
                @if($previousRound->decision_note)
                    <p class="small muted" style="margin-top:12px;">GMU asked the applicant:</p><p class="doc-text" style="margin-top:4px; font-size:14px;">{{ $previousRound->decision_note }}</p>
                @endif
                @if($previousRound->response)
                    <p class="small muted" style="margin-top:12px;"><strong>The applicant’s response</strong></p><p class="doc-text" style="margin-top:4px; font-size:14px;">{{ $previousRound->response }}</p>
                @endif
            </div>
        </section>
    @endif

    <div class="grid grid-wide">
        <div class="stack" style="gap:16px;">
            @if($stage === 'proposal')
                <details class="card"><summary class="card-body" style="cursor:pointer; font-weight:600;">Read the concept note it grew from</summary>
                    <div style="padding:0 22px 22px;" class="stack-sm">@include('seed.partials.content', ['stage' => 'concept'])</div></details>
            @endif
            @include('seed.partials.content', ['stage' => $stage])
        </div>

        <aside>
            <form method="POST" action="{{ route('seed.reviews.save', $assignment) }}" class="stack-sm" style="gap:16px; position:sticky; top:84px;" data-review>
                @csrf @method('PUT')

                @if($stage === 'proposal')
                    @php $form = $application->call->form(); $maxTotal = $form->maxTotal() ?: 100; @endphp
                    <section class="card is-dark" style="padding:22px;">
                        <p class="eyebrow" style="color:var(--blue-300);">Your total</p>
                        <div class="row" style="gap:6px; align-items:baseline; margin-top:6px;"><span class="mono" style="font-size:48px; line-height:1; color:#fff;" data-total>{{ $review?->total ?? '—' }}</span><span class="mono" style="color:#A9B6D3;">/ {{ S::trimNumber($maxTotal) }}</span></div>
                        <div style="position:relative; margin-top:16px; height:10px; border-radius:99px; background:#1C2B57;">
                            <div data-total-bar style="position:absolute; left:0; top:0; bottom:0; border-radius:99px; width:{{ min(100, ($review?->total ?? 0) / $maxTotal * 100) }}%; background:var(--blue-400);"></div>
                            <div aria-hidden="true" style="position:absolute; left:{{ min(100, $form->passMark() / $maxTotal * 100) }}%; top:-5px; bottom:-5px; width:2px; background:#fff;"></div>
                        </div>
                        <p class="small" style="margin-top:12px; color:#C7D2EA;" data-verdict></p>
                    </section>

                    <section class="card">
                        <div class="card-body stack-sm">
                            <h2 class="card-title">Score each criterion</h2>
                            @foreach($form->criteria() as $key => [$name, $what, $max])
                                @php $value = old("scores.$key", $review?->score($key)); @endphp
                                <div>
                                    <div class="row-between" style="flex-wrap:nowrap; align-items:flex-start;">
                                        <label for="score-{{ $key }}" class="strong" style="font-size:14px;">{{ $name }}</label>
                                        <span class="mono" style="white-space:nowrap;"><output data-out-for="score-{{ $key }}">{{ $value ?? '—' }}</output><span class="muted"> / {{ $max }}</span></span>
                                    </div>
                                    <p class="tiny muted">{{ $what }}</p>
                                    <input id="score-{{ $key }}" type="range" name="scores[{{ $key }}]" min="0" max="{{ $max }}" step="0.5"
                                           value="{{ $value ?? 0 }}" data-score="{{ $key }}" data-max="{{ $max }}" @if($value === null) data-unset @endif
                                           style="width:100%; accent-color:var(--brand); height:32px;" @disabled(! $editable)>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="card">
                    <div class="card-body">
                        <fieldset style="border:0; margin:0; padding:0;">
                            <legend class="label" style="margin-bottom:8px;">Your recommendation</legend>
                            <div class="row" style="gap:8px;">
                                @foreach(SeedReview::RECOMMENDATIONS as $value => $label)
                                    <label class="opt" style="flex:1 1 90px; align-items:center;"><input type="radio" name="recommendation" value="{{ $value }}" @checked(old('recommendation', $review?->recommendation) === $value) @disabled(! $editable)><strong>{{ $label }}</strong></label>
                                @endforeach
                            </div>
                        </fieldset>
                        <x-ui.field label="Comments for the applicant" name="comments" help="Shared with the applicant without your name.">
                            <textarea id="comments" name="comments" class="textarea" rows="6" style="margin-top:12px;" @readonly(! $editable)>{{ old('comments', $review?->comments) }}</textarea>
                        </x-ui.field>
                        <x-ui.field label="Confidential note to GMU" name="confidential_note" help="Optional. The applicant never sees this.">
                            <textarea id="confidential_note" name="confidential_note" class="textarea" rows="2" style="margin-top:12px;" @readonly(! $editable)>{{ old('confidential_note', $review?->confidential_note) }}</textarea>
                        </x-ui.field>
                    </div>
                </section>

                @if($editable)
                    <div class="row">
                        <button type="submit" name="action" value="draft" class="btn">Save draft</button>
                        <button type="submit" name="action" value="submit" class="btn btn-primary" style="flex:1;" onclick="return confirm('Submit your review? You cannot change it afterwards.')">Submit review</button>
                    </div>
                @endif
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
        if (!complete) { verdict.textContent = 'Score every criterion to see the total.'; return; }
        var under = Object.keys(mins).filter(function (k) { return (scores[k] || 0) < mins[k].min; });
        var ok = total >= pass && under.length === 0;
        verdict.textContent = ok ? 'Fundable on your scores.'
            : (under.length ? mins[under[0]].name + ' below ' + mins[under[0]].min + ' of ' + mins[under[0]].max + ': not fundable.' : 'Below ' + pass + ': not fundable.');
        verdict.style.color = ok ? '#BCD0FB' : '#F8B4A3';
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
