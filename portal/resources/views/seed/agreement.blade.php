@extends('layouts.app')

@php
    use App\Support\SeedGrant as S;
    $pi = $application->pi;
    $call = $application->call;
    $isPi = auth()->id() === $application->pi_id;
    $dg = $application->approvalAt('dg');
    $h2 = 'mt-8 mb-2 text-[15px] font-semibold text-foreground';
@endphp

@section('title', 'Grant agreement')
@section('context', 'My application · ' . $application->reference . ' · Grant agreement')

@section('content')
<x-page-header title="Grant agreement" class="print:hidden" :back="auth()->user()->can('manage-seed-grants') ? route('seed.submissions.show', $application) : route('seed.applications.show', $application)" back-label="The application">
    <x-slot:meta>
        <div class="mt-2 flex flex-wrap items-center gap-2.5">
            @include('seed.partials.status')
            <span class="font-mono text-xs text-muted-foreground">{{ $application->reference }}</span>
        </div>
    </x-slot:meta>
    <x-slot:actions>
        <x-button onclick="window.print()" icon="download">Print or save as PDF</x-button>
        @if($application->signed_agreement_path)
            <x-button :href="route('seed.applications.agreement.signed', $application)" icon="paperclip">Signed copy</x-button>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
    {{-- The agreement, laid out as paper --}}
    <article class="rounded-xl border border-border bg-card px-6 py-8 text-[14px] leading-relaxed text-gray-700 sm:px-12 sm:py-12 print:border-0 print:p-0">
        <div class="flex items-start justify-between gap-6 border-b border-foreground pb-5">
            <div class="flex items-center gap-4">
                <img src="{{ asset('images/nimr-logo.png') }}" alt="" class="size-14 object-contain">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-foreground">National Institute for Medical Research</p>
                    <p class="mt-0.5 text-xs text-muted-foreground">Directorate of Research Coordination and Promotion · Grants Management Unit</p>
                </div>
            </div>
            <p class="shrink-0 text-right font-mono text-xs text-gray-700">Ref. {{ $application->reference }}<br>Issued {{ $application->agreement_issued_at->format('j M Y') }}</p>
        </div>

        <h1 class="mt-8 text-2xl">{{ $call->title }}: Grant Agreement</h1>
        <p class="mt-1 text-muted-foreground">FY {{ $call->fiscal_year }}</p>

        <h2 class="{{ $h2 }}">1. Parties</h2>
        <p>This agreement is between the National Institute for Medical Research (“NIMR”), acting through the Grants Management Unit, and <strong class="text-foreground">{{ $pi->name }}</strong>{{ $pi->designation ? ', ' . $pi->designation : '' }}{{ $pi->research_center ? ', ' . $pi->research_center : '' }} (“the Principal Investigator”).</p>

        <h2 class="{{ $h2 }}">2. Project</h2>
        <p><strong class="text-foreground">{{ $application->title }}</strong>, as described in the full proposal approved by the Director General, which forms part of this agreement.@if($application->mentor_name) Scientific mentor: {{ $application->mentor_name }}.@endif</p>

        <h2 class="{{ $h2 }}">3. Award</h2>
        <p>NIMR awards <strong class="font-mono text-foreground">{{ S::tzs($application->approved_amount) }}</strong> against the approved budget below. Funds are released in instalments linked to the approved workplan and milestones, in line with NIMR financial procedures.</p>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[520px] border-collapse text-[13px]">
                <thead><tr class="border-b border-foreground/80 text-left">
                    <th class="py-2 pr-3 font-semibold text-foreground">Budget category</th>
                    @foreach(['Q1', 'Q2', 'Q3', 'Q4'] as $q)<th class="px-3 py-2 text-right font-semibold text-foreground">{{ $q }}</th>@endforeach
                    <th class="py-2 pl-3 text-right font-semibold text-foreground">Total (TZS)</th>
                </tr></thead>
                <tbody>
                    @foreach(S::BUDGET_CATEGORIES as $key => $label)
                        @php $lines = $application->budgetLines('proposal')->where('category', $key); @endphp
                        @if($lines->isNotEmpty())
                            <tr class="border-b border-border">
                                <td class="py-2 pr-3">{{ $label }}</td>
                                @foreach(['q1', 'q2', 'q3', 'q4'] as $q)<td class="px-3 py-2 text-right font-mono tabular-nums">{{ number_format($lines->sum($q)) }}</td>@endforeach
                                <td class="py-2 pl-3 text-right font-mono tabular-nums">{{ number_format($lines->sum(fn ($l) => $l->total())) }}</td>
                            </tr>
                        @endif
                    @endforeach
                    <tr class="font-semibold text-foreground">
                        <td class="py-2 pr-3">Total</td>
                        @foreach($application->budgetByQuarter() as $v)<td class="px-3 py-2 text-right font-mono tabular-nums">{{ number_format($v) }}</td>@endforeach
                        <td class="py-2 pl-3 text-right font-mono tabular-nums">{{ number_format($application->budgetTotal('proposal')) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h2 class="{{ $h2 }}">4. Period and milestones</h2>
        <p>{{ $call->duration_months }} months from the date funds are first released. Milestones are those in the approved workplan:</p>
        <p class="prose-text mt-2">{{ $application->sectionText('proposal', 'workplan') }}</p>

        <h2 class="{{ $h2 }}">5. Reporting</h2>
        <p>The Principal Investigator submits a progress report and a financial report every quarter, and at the end a final technical report, a dissemination output and a manuscript prepared for publication where feasible.</p>

        <h2 class="{{ $h2 }}">6. Ethics and approvals</h2>
        <p>Required ethical approvals, regulatory permissions and site authorisations must be in place before any activity that needs them. No human-participant or animal research may begin without them.</p>

        @if($application->approval_conditions)
            <h2 class="{{ $h2 }}">7. Conditions set at approval</h2>
            <p class="prose-text">{{ $application->approval_conditions }}</p>
        @endif

        <h2 class="{{ $h2 }}">{{ $application->approval_conditions ? 8 : 7 }}. Changes, unspent funds and termination</h2>
        <p>Moving money between budget lines, or changing scope, personnel, sites or timelines, needs prior written approval from GMU and the relevant authorised NIMR office. Unspent or ineligible funds are handled under NIMR financial regulations. Missing milestones or reports without satisfactory justification may lead to suspension of funding or termination. The award is not renewable.</p>

        <div class="mt-12 grid gap-x-10 gap-y-8 sm:grid-cols-2">
            <div>
                <div class="flex h-10 items-end border-b border-foreground pb-1.5 font-mono text-xs {{ $application->agreement_accepted_at ? 'text-ok-ink' : 'text-warn-ink' }}">
                    {{ $application->agreement_accepted_at ? 'Accepted online ' . $application->agreement_accepted_at->format('j M Y H:i') : 'awaiting acceptance' }}</div>
                <p class="mt-1.5 text-[13px]"><strong class="text-foreground">Principal Investigator</strong><br><span class="text-muted-foreground">{{ $pi->name }}</span></p>
            </div>
            <div><div class="h-10 border-b border-foreground"></div><p class="mt-1.5 text-[13px]"><strong class="text-foreground">GMU Coordinator</strong><br><span class="text-muted-foreground">For NIMR</span></p></div>
            <div><div class="h-10 border-b border-foreground"></div><p class="mt-1.5 text-[13px]"><strong class="text-foreground">Director, DRCP</strong></p></div>
            <div><div class="h-10 border-b border-foreground"></div><p class="mt-1.5 text-[13px]"><strong class="text-foreground">Director General</strong>@if($dg)<br><span class="text-muted-foreground">Approved {{ $dg->created_at->format('j M Y') }}</span>@endif</p></div>
        </div>
    </article>

    <aside class="space-y-4 xl:sticky xl:top-24 print:hidden">
        @if($isPi && $application->status === S::AGREEMENT_ISSUED)
            <form method="POST" action="{{ route('seed.applications.agreement.accept', $application) }}">
                @csrf
                <x-card tone="warn" title="Accept the agreement" description="Accepting here is your signature as PI. GMU then collects the other signatures." icon="pen-line">
                    <div class="space-y-3">
                        @foreach(['read' => 'I have read the whole agreement, including the approved proposal and budget.', 'ethics' => 'No data collection will start before ethics approval.', 'reporting' => 'I will submit quarterly progress and financial reports.'] as $name => $text)
                            <label class="flex items-start gap-2.5 text-sm"><input type="checkbox" class="checkbox mt-0.5" name="{{ $name }}" value="1">{{ $text }}</label>
                        @endforeach
                    </div>
                    <x-button type="submit" variant="primary" class="mt-4 w-full">Accept as Principal Investigator</x-button>
                </x-card>
            </form>
        @endif

        @can('manage-seed-grants')
            @if(in_array($application->status, [S::AGREEMENT_ACCEPTED, S::AWARDED], true))
                <x-button class="w-full" :href="route('seed.submissions.show', $application)" icon="upload">{{ $application->status === S::AWARDED ? 'Manage the signed copy' : 'Upload the signed copy' }}</x-button>
            @endif
        @endcan

        <x-card title="Where it stands">
            <ol class="space-y-3.5 text-[13px]">
                @foreach([
                    ['Approved by the Director General', (bool) $dg],
                    ['Agreement issued', (bool) $application->agreement_issued_at],
                    ['Accepted online by the PI', (bool) $application->agreement_accepted_at],
                    ['Signed copy on file', (bool) $application->signed_agreement_uploaded_at],
                ] as [$label, $done])
                    <li class="flex items-center gap-2.5">
                        <span class="grid size-5 shrink-0 place-items-center rounded-full {{ $done ? 'bg-ok-soft text-ok-ink' : 'border-2 border-input' }}">@if($done)<x-icon name="check" class="size-3" />@endif</span>
                        <span class="{{ $done ? '' : 'text-muted-foreground' }}">{{ $label }}</span>
                    </li>
                @endforeach
            </ol>
        </x-card>
    </aside>
</div>
@endsection
