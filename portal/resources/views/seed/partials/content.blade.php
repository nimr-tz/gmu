{{-- Read-only concept note or full proposal. Expects $application and $stage. --}}
@php use App\Support\SeedGrant; $form = $application->call->form(); @endphp

@if($stage === 'concept')
    @php
        $prior = $application->priorLargeGrant();
        $coInv = $application->conceptField('co_investigators');
    @endphp
    <section class="card">
        <div class="card-body">
            <div class="card-title" style="margin-bottom:12px;">A. Applicant and project information</div>
            <div class="form-grid">
                <div class="kv"><span class="k">Project title</span><span class="v">{{ $application->title ?: '—' }}</span></div>
                @if($form->mentorWords() !== null)
                <div class="kv"><span class="k">Proposed mentor</span><span class="v">{{ $application->mentor_name ?: '—' }}</span>
                    <span class="small muted">{{ collect([$application->mentor_designation, $application->mentor_centre])->filter()->implode(' · ') }}</span></div>
                @endif
                @if($priorRule = $form->priorGrant())
                <div class="kv"><span class="k">Prior grant above USD {{ number_format($priorRule['usd']) }} as PI</span>
                    <span class="v">{{ ['no' => 'No', 'yes' => 'Yes'][$prior] ?? 'Not answered' }}</span>
                    @if($prior === 'yes' && $application->conceptField('prior_grant_details'))<span class="small muted">{{ $application->conceptField('prior_grant_details') }}</span>@endif</div>
                @endif
                <div class="kv"><span class="k">Total funding requested</span><span class="v mono">{{ SeedGrant::tzs($application->budgetTotal('concept')) }}</span></div>
            </div>
            @if($form->mentorWords() !== null && $application->mentor_suitability)
                <div class="kv" style="margin-top:16px;"><span class="k">Mentor’s relevant expertise</span><p class="doc-text" style="font-size:14px;">{{ $application->mentor_suitability }}</p></div>
            @endif
            @if($form->coInvestigatorWords() !== null)
            <div class="kv" style="margin-top:16px;"><span class="k">Co-investigators or collaborators</span>
                <p class="doc-text" style="font-size:14px;">{{ filled($coInv) ? $coInv : 'None named.' }}</p></div>
            @endif
        </div>
    </section>
@endif

<section class="card">
    <div class="card-body">
        @if($stage === 'concept')<div class="card-title" style="margin-bottom:4px;">B. Technical questions and C. workplan</div>@endif
        @foreach($form->sections($stage) as $key => [$heading])
            @php
                $text = $application->sectionText($stage, $key);
                $choice = $stage === 'concept' ? ($form->choices()[$key] ?? null) : null;
                $answer = $choice ? $application->choice($key) : null;
            @endphp
            <div class="prose-section" id="{{ $stage }}-{{ $key }}">
                <h3>{{ $heading }}</h3>
                @if($choice)
                    <p class="small" style="margin-top:6px;">{{ $choice['question'] }}
                        <strong>{{ $choice['options'][$answer] ?? 'Not answered' }}</strong></p>
                @endif
                @if($text !== '')
                    <p class="doc-text" style="margin-top:8px;">{{ $text }}</p>
                @elseif(! $choice || $form->sectionRequired($key, $answer))
                    <p class="muted" style="margin-top:8px;">Not written yet.</p>
                @endif
            </div>
        @endforeach
    </div>
</section>

<section class="card">
    <div class="card-head"><div><div class="card-title">{{ $stage === 'concept' ? 'Estimated budget' : 'Detailed budget' }}</div></div>
        <div class="mono" style="font-size:18px;">{{ SeedGrant::tzs($application->budgetTotal($stage)) }}</div></div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    @if($stage === 'proposal')<th>Category</th>@endif
                    <th>Item</th>
                    @if($stage === 'proposal')<th class="num">Q1</th><th class="num">Q2</th><th class="num">Q3</th><th class="num">Q4</th>@endif
                    <th class="num">Amount (TZS)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($application->budgetLines($stage) as $line)
                    <tr>
                        @if($stage === 'proposal')<td>{{ SeedGrant::BUDGET_CATEGORIES[$line->category] ?? '—' }}</td>@endif
                        <td>{{ $line->item }}</td>
                        @if($stage === 'proposal')
                            <td class="num">{{ number_format($line->q1) }}</td><td class="num">{{ number_format($line->q2) }}</td>
                            <td class="num">{{ number_format($line->q3) }}</td><td class="num">{{ number_format($line->q4) }}</td>
                        @endif
                        <td class="num strong">{{ number_format($line->total()) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="table-empty">No budget yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@if($stage === 'concept' && $form->declarations())
    @php $ticked = $application->declarations(); @endphp
    <section class="card">
        <div class="card-body">
            <div class="card-title">E. Applicant declaration</div>
            <ul style="margin:10px 0 0; padding:0; list-style:none; display:flex; flex-direction:column; gap:6px; font-size:14px;">
                @foreach($form->declarations() as $value => $statement)
                    <li class="row" style="gap:8px; align-items:flex-start; flex-wrap:nowrap;">
                        <i class="fas {{ in_array($value, $ticked, true) ? 'fa-check-square' : 'fa-square' }}" style="margin-top:3px; color:{{ in_array($value, $ticked, true) ? 'var(--blue-600)' : 'var(--muted-3)' }};" aria-hidden="true"></i>
                        <span>{{ $statement }}</span>
                    </li>
                @endforeach
            </ul>
            @if($application->concept_submitted_at)
                <p class="small muted" style="margin-top:10px;">Submitted by {{ $application->pi->name }} on {{ $application->concept_submitted_at->format('j M Y') }}.</p>
            @endif
        </div>
    </section>
@endif

@if($stage === 'proposal')
    <section class="card">
        <div class="card-head"><div class="card-title">Supporting documents</div></div>
        <div class="list">
            @forelse($application->documents as $doc)
                <a class="list-row" href="{{ route('seed.applications.documents.download', [$application, $doc]) }}">
                    <span class="pill pill-danger mono">{{ strtoupper(pathinfo($doc->file_name, PATHINFO_EXTENSION)) }}</span>
                    <span class="list-main"><span class="list-title">{{ $doc->kindLabel() }}</span><span class="list-sub">{{ $doc->file_name }}</span></span>
                    <i class="fas fa-download muted" aria-hidden="true"></i>
                </a>
            @empty
                <div class="table-empty">No documents uploaded.</div>
            @endforelse
        </div>
    </section>
@endif
