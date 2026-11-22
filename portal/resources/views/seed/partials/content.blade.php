{{-- Read-only concept note or full proposal. Expects $application and $stage. --}}
@php use App\Support\SeedGrant; $form = $application->call->form(); @endphp

<div class="space-y-6">
@if($stage === 'concept')
    @php
        $prior = $application->priorLargeGrant();
        $coInv = $application->conceptField('co_investigators');
    @endphp
    <x-card title="A. Applicant and project information">
        <dl class="grid gap-x-6 gap-y-5 sm:grid-cols-2">
            <div class="sm:col-span-2"><dt class="text-xs text-muted-foreground">Project title</dt><dd class="mt-1 text-[15px] font-medium">{{ $application->title ?: '—' }}</dd></div>
            @if($form->mentorWords() !== null)
                <div><dt class="text-xs text-muted-foreground">Proposed mentor</dt>
                    <dd class="mt-1 font-medium">{{ $application->mentor_name ?: '—' }}</dd>
                    <dd class="text-[13px] text-muted-foreground">{{ collect([$application->mentor_designation, $application->mentor_centre])->filter()->implode(' · ') }}</dd></div>
            @endif
            @if($priorRule = $form->priorGrant())
                <div><dt class="text-xs text-muted-foreground">Prior grant above USD {{ number_format($priorRule['usd']) }} as PI</dt>
                    <dd class="mt-1 font-medium">{{ ['no' => 'No', 'yes' => 'Yes'][$prior] ?? 'Not answered' }}</dd>
                    @if($prior === 'yes' && $application->conceptField('prior_grant_details'))<dd class="text-[13px] text-muted-foreground">{{ $application->conceptField('prior_grant_details') }}</dd>@endif</div>
            @endif
            <div><dt class="text-xs text-muted-foreground">Total funding requested</dt><dd class="mt-1 font-mono font-medium tabular-nums">{{ SeedGrant::tzs($application->budgetTotal('concept')) }}</dd></div>
            @if($form->mentorWords() !== null && $application->mentor_suitability)
                <div class="sm:col-span-2"><dt class="text-xs text-muted-foreground">Mentor’s relevant expertise</dt><dd class="prose-text mt-1">{{ $application->mentor_suitability }}</dd></div>
            @endif
            @if($form->coInvestigatorWords() !== null)
                <div class="sm:col-span-2"><dt class="text-xs text-muted-foreground">Co-investigators or collaborators</dt><dd class="prose-text mt-1">{{ filled($coInv) ? $coInv : 'None named.' }}</dd></div>
            @endif
        </dl>
    </x-card>
@endif

<x-card :title="$stage === 'concept' ? 'B. Technical questions and C. workplan' : 'The full proposal'">
    <div class="divide-y divide-border">
        @foreach($form->sections($stage) as $key => [$heading])
            @php
                $text = $application->sectionText($stage, $key);
                $choice = $stage === 'concept' ? ($form->choices()[$key] ?? null) : null;
                $answer = $choice ? $application->choice($key) : null;
            @endphp
            <div class="scroll-mt-24 py-5 first:pt-1 last:pb-0" id="{{ $stage }}-{{ $key }}">
                <h3 class="text-sm font-semibold">{{ $heading }}</h3>
                @if($choice)
                    <p class="mt-1.5 text-[13px] text-muted-foreground">{{ $choice['question'] }}
                        <span class="font-medium text-foreground">{{ $choice['options'][$answer] ?? 'Not answered' }}</span></p>
                @endif
                @if($text !== '')
                    <p class="prose-text mt-2">{{ $text }}</p>
                @elseif(! $choice || $form->sectionRequired($key, $answer))
                    <p class="mt-2 text-sm italic text-muted-foreground">Not written yet.</p>
                @endif
            </div>
        @endforeach
    </div>
</x-card>

<x-card :title="$stage === 'concept' ? 'Estimated budget' : 'Detailed budget'" flush>
    <x-slot:actions><span class="font-mono text-lg font-semibold tabular-nums">{{ SeedGrant::tzs($application->budgetTotal($stage)) }}</span></x-slot:actions>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    @if($stage === 'proposal')<th>Category</th>@endif
                    <th>Item</th>
                    @if($stage === 'proposal')<th class="text-right">Q1</th><th class="text-right">Q2</th><th class="text-right">Q3</th><th class="text-right">Q4</th>@endif
                    <th class="text-right">Amount (TZS)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($application->budgetLines($stage) as $line)
                    <tr>
                        @if($stage === 'proposal')<td class="text-muted-foreground">{{ SeedGrant::BUDGET_CATEGORIES[$line->category] ?? '—' }}</td>@endif
                        <td>{{ $line->item }}</td>
                        @if($stage === 'proposal')
                            <td class="num">{{ number_format($line->q1) }}</td><td class="num">{{ number_format($line->q2) }}</td>
                            <td class="num">{{ number_format($line->q3) }}</td><td class="num">{{ number_format($line->q4) }}</td>
                        @endif
                        <td class="num font-medium">{{ number_format($line->total()) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-muted-foreground">No budget yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

@if($stage === 'concept' && $form->declarations())
    @php $ticked = $application->declarations(); @endphp
    <x-card title="E. Applicant declaration">
        <ul class="space-y-2.5">
            @foreach($form->declarations() as $value => $statement)
                @php $on = in_array($value, $ticked, true); @endphp
                <li class="flex items-start gap-2.5 text-sm">
                    <span class="mt-0.5 grid size-4 shrink-0 place-items-center rounded {{ $on ? 'bg-primary text-white' : 'border border-input' }}">@if($on)<x-icon name="check" class="size-3" />@endif</span>
                    <span>{{ $statement }}</span>
                </li>
            @endforeach
        </ul>
        @if($application->concept_submitted_at)
            <p class="mt-4 text-[13px] text-muted-foreground">Submitted by {{ $application->pi->name }} on {{ $application->concept_submitted_at->format('j M Y') }}.</p>
        @endif
    </x-card>
@endif

@if($stage === 'proposal')
    <x-card title="Supporting documents" flush>
        <ul class="divide-y divide-border">
            @forelse($application->documents as $doc)
                <li>
                    <a class="flex items-center gap-3 px-5 py-3 hover:bg-muted/50" href="{{ route('seed.applications.documents.download', [$application, $doc]) }}">
                        <span class="grid size-9 shrink-0 place-items-center rounded-lg border border-border bg-muted font-mono text-[10px] font-medium text-muted-foreground">{{ strtoupper(pathinfo($doc->file_name, PATHINFO_EXTENSION)) }}</span>
                        <span class="min-w-0 flex-1"><span class="block text-sm font-medium">{{ $doc->kindLabel() }}</span><span class="block truncate text-[13px] text-muted-foreground">{{ $doc->file_name }}</span></span>
                        <x-icon name="download" class="text-subtle" />
                    </a>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-muted-foreground">No documents uploaded.</li>
            @endforelse
        </ul>
    </x-card>
@endif
</div>
