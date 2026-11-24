@extends('layouts.app')

{{--
    The grant agreement, from the owner's "Staff Pages" design: the agreement laid out as
    paper (in the serif face) beside where it stands, and for the PI the three statements to
    accept it. GMU sees the same page in its ink band, with the way to the signed-copy upload.
--}}

@php
    use App\Support\SeedGrant as S;
    $pi = $application->pi;
    $call = $application->call;
    $isPi = auth()->id() === $application->pi_id;
    $gmu = auth()->user()->can('manage-seed-grants') && ! $isPi;
    $dg = $application->approvalAt('dg');
    $drcp = $application->approvalAt('drcp');
    $s = $application->status;
    $accepted = (bool) $application->agreement_accepted_at;
    $band = $gmu ? 'bg-foreground' : 'bg-blue-700'; // the Staff Pages design's blue
    $muted = $gmu ? 'text-gray-400' : 'text-blue-200';
    $soft = $gmu ? 'text-gray-300' : 'text-blue-100';
    $h = 'mb-1.5 mt-[26px] font-sans text-[15px] font-semibold text-foreground';
    $chip = $accepted ? ['text-ok-ink', 'bg-primary'] : ['text-warn-ink', 'bg-warn'];
    $steps = [
        ['Approved by the Director General', (bool) $dg, $dg?->created_at?->format('j M Y')],
        ['Agreement issued', (bool) $application->agreement_issued_at, $application->agreement_issued_at?->format('j M Y')],
        ['Accepted online by the PI', $accepted, $application->agreement_accepted_at?->format('j M Y H:i')],
        ['Signed copy on file', (bool) $application->signed_agreement_uploaded_at, $application->signed_agreement_uploaded_at?->format('j M Y')],
    ];
    $firstOpen = collect($steps)->search(fn ($step) => ! $step[1]);
    $ticks = ['read' => 'I have read the whole agreement, including the approved proposal and budget.', 'ethics' => 'No data collection will start before ethics approval.', 'reporting' => 'I will submit quarterly progress and financial reports.'];
@endphp

@section('title', 'Grant agreement')
@section('context', ($gmu ? 'Submissions' : 'My application') . ' · Grant agreement')
@section('topbar-colour', $band)
@section('own-flash', '1')

@section('content')
<div class="-mx-4 -mt-5 flex flex-wrap items-start justify-between gap-5 px-7 pb-[100px] pt-[18px] text-white lg:-mx-6 lg:-mt-6 print:hidden {{ $band }}">
    <div class="flex min-w-0 flex-col gap-2.5">
        <a href="{{ $gmu ? route('seed.submissions.show', $application) : route('seed.applications.show', $application) }}" class="inline-flex items-center gap-1.5 self-start text-[13px] font-medium hover:text-white {{ $muted }}"><x-icon name="arrow-left" class="size-3.5" />{{ $gmu ? 'Submission record' : 'The application' }}</a>
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex h-[22px] items-center gap-1.5 whitespace-nowrap rounded-md bg-white px-2 text-xs font-semibold {{ $s === S::AWARDED ? 'text-award-ink' : $chip[0] }}"><span class="size-1.5 rounded-full {{ $s === S::AWARDED ? 'bg-award' : $chip[1] }}"></span>{{ $application->statusLabel() }}</span>
            <span class="whitespace-nowrap font-mono text-[12.5px] {{ $muted }}">{{ $application->reference }}</span>
        </div>
        <h1 class="text-[30px] font-semibold leading-tight tracking-[-0.025em] text-white">Grant agreement</h1>
        <p class="max-w-[760px] text-[15px] {{ $soft }}">{{ $application->title }} · {{ $pi->name }}</p>
    </div>
    <div class="flex shrink-0 gap-2">
        @if($application->signed_agreement_path)
            <a href="{{ route('seed.applications.agreement.signed', $application) }}" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-white/30 px-3.5 text-[13px] font-medium text-white hover:bg-white/10"><x-icon name="paperclip" class="size-3.5" />Signed copy</a>
        @endif
        <button type="button" onclick="window.print()" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3.5 text-[13px] font-semibold text-foreground hover:bg-gray-100"><x-icon name="download" class="size-3.5" />Print or save as PDF</button>
    </div>
</div>

<div class="-mt-[72px] flex flex-wrap items-start gap-6 print:mt-0">
    {{-- The agreement, laid out as paper --}}
    <article class="min-w-0 flex-[1_1_640px] rounded-2xl border border-border bg-card px-6 py-10 font-serif text-[15.5px] leading-[1.7] text-gray-800 shadow-pop sm:px-14 sm:pb-14 sm:pt-[52px] print:border-0 print:p-0 print:shadow-none">
        <div class="flex items-start justify-between gap-6 border-b-2 border-foreground pb-5 font-sans">
            <div class="flex items-center gap-3.5">
                <img src="{{ asset('images/nimr-logo.png') }}" alt="" class="size-14 object-contain">
                <div>
                    <p class="text-[12.5px] font-semibold uppercase tracking-[0.06em] text-foreground">National Institute for Medical Research</p>
                    <p class="mt-0.5 text-xs text-muted-foreground">Directorate of Research Coordination and Promotion · Grants Management Unit</p>
                </div>
            </div>
            <p class="shrink-0 text-right font-mono text-xs leading-relaxed text-gray-700">Ref. {{ $application->reference }}<br>Issued {{ $application->agreement_issued_at?->format('j M Y') }}</p>
        </div>

        <h2 class="mt-8 text-[26px] font-semibold leading-tight tracking-[-0.01em] text-foreground">{{ $call->title }}: Grant Agreement</h2>
        <p class="mt-1 font-sans text-[13.5px] text-muted-foreground">FY {{ $call->fiscal_year }}</p>

        <h3 class="{{ $h }} mt-[30px]">1. Parties</h3>
        <p>This agreement is between the National Institute for Medical Research (“NIMR”), acting through the Grants Management Unit, and <b class="font-semibold text-foreground">{{ $pi->name }}</b>{{ $pi->designation ? ', ' . $pi->designation : '' }}{{ $pi->research_center ? ', ' . $pi->research_center : '' }} (“the Principal Investigator”).</p>

        <h3 class="{{ $h }}">2. Project</h3>
        <p><b class="font-semibold text-foreground">{{ $application->title }}</b>, as described in the full proposal approved by the Director General, which forms part of this agreement.@if($application->mentor_name) Scientific mentor: {{ $application->mentor_name }}.@endif</p>

        <h3 class="{{ $h }}">3. Award</h3>
        <p>NIMR awards <b class="font-mono text-[14.5px] font-semibold text-foreground">{{ S::tzs($application->approved_amount) }}</b> against the approved budget below. Funds are released in instalments linked to the approved workplan and milestones, in line with NIMR financial procedures.</p>
        <div class="mt-4 overflow-x-auto font-sans">
            <table class="w-full min-w-[520px] border-collapse text-[13px]">
                <thead><tr class="text-left">
                    <th class="border-b-[1.5px] border-foreground py-2 pr-3 font-semibold">Budget category</th>
                    @foreach(['Q1', 'Q2', 'Q3', 'Q4'] as $q)<th class="border-b-[1.5px] border-foreground px-3 py-2 text-right font-semibold">{{ $q }}</th>@endforeach
                    <th class="border-b-[1.5px] border-foreground py-2 pl-3 text-right font-semibold">Total (TZS)</th>
                </tr></thead>
                <tbody>
                    @foreach(S::BUDGET_CATEGORIES as $key => $label)
                        @php $lines = $application->budgetLines('proposal')->where('category', $key); @endphp
                        @if($lines->isNotEmpty())
                            <tr>
                                <td class="border-b border-border py-2 pr-3">{{ $label }}</td>
                                @foreach(['q1', 'q2', 'q3', 'q4'] as $q)@php $v = $lines->sum($q); @endphp<td class="border-b border-border px-3 py-2 text-right font-mono {{ $v ? 'text-gray-800' : 'text-subtle' }}">{{ $v ? number_format($v) : '—' }}</td>@endforeach
                                <td class="border-b border-border py-2 pl-3 text-right font-mono">{{ number_format($lines->sum(fn ($l) => $l->total())) }}</td>
                            </tr>
                        @endif
                    @endforeach
                    <tr class="font-semibold">
                        <td class="py-[9px] pr-3">Total</td>
                        @foreach($application->budgetByQuarter() as $v)<td class="px-3 py-[9px] text-right font-mono">{{ number_format($v) }}</td>@endforeach
                        <td class="py-[9px] pl-3 text-right font-mono">{{ number_format($application->budgetTotal('proposal')) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h3 class="{{ $h }}">4. Period and milestones</h3>
        <p>{{ $call->duration_months }} months from the date funds are first released. Milestones are those in the approved workplan:</p>
        <p class="mt-2 whitespace-pre-line">{{ $application->sectionText('proposal', 'workplan') }}</p>

        <h3 class="{{ $h }}">5. Reporting</h3>
        <p>The Principal Investigator submits a progress report and a financial report every quarter, and at the end a final technical report, a dissemination output and a manuscript prepared for publication where feasible.</p>

        <h3 class="{{ $h }}">6. Ethics and approvals</h3>
        <p>Required ethical approvals, regulatory permissions and site authorisations must be in place before any activity that needs them. No human-participant or animal research may begin without them.</p>

        @if($application->approval_conditions)
            <div class="mt-[26px] rounded-[10px] border border-indigo-100 bg-indigo-50/60 px-5 py-4">
                <h3 class="mb-1 font-sans text-[15px] font-semibold text-indigo-800">7. Conditions set at approval</h3>
                <p class="whitespace-pre-line">{{ $application->approval_conditions }}</p>
            </div>
        @endif

        <h3 class="{{ $h }}">{{ $application->approval_conditions ? 8 : 7 }}. Changes, unspent funds and termination</h3>
        <p>Moving money between budget lines, or changing scope, personnel, sites or timelines, needs prior written approval from GMU and the relevant authorised NIMR office. Unspent or ineligible funds are handled under NIMR financial regulations. Missing milestones or reports without satisfactory justification may lead to suspension of funding or termination. The award is not renewable.</p>

        <div class="mt-12 grid grid-cols-1 gap-x-10 gap-y-8 font-sans sm:grid-cols-2">
            @foreach([
                ['Principal Investigator', $pi->name, $accepted ? 'Accepted online ' . $application->agreement_accepted_at->format('j M Y H:i') : 'awaiting acceptance', $accepted ? 'text-ok-ink' : 'text-warn-ink'],
                ['GMU Coordinator', 'For NIMR', '', ''],
                ['Director, DRCP', $drcp?->decider?->name ?? '', '', ''],
                ['Director General', $dg ? 'Approved ' . $dg->created_at->format('j M Y') : '', '', ''],
            ] as [$role, $sub, $mark, $ink])
                <div>
                    <div class="flex h-10 items-end border-b-[1.5px] border-foreground pb-1.5 font-mono text-xs {{ $ink }}">{{ $mark }}</div>
                    <p class="mt-1.5 text-[13px] leading-snug"><b class="font-semibold">{{ $role }}</b>@if($sub)<br><span class="text-muted-foreground">{{ $sub }}</span>@endif</p>
                </div>
            @endforeach
        </div>
    </article>

    <aside class="flex min-w-[300px] flex-[0_1_360px] flex-col gap-4 xl:sticky xl:top-5 print:hidden">
        @if($isPi && $s === S::AGREEMENT_ISSUED)
            <form method="POST" action="{{ route('seed.applications.agreement.accept', $application) }}" class="overflow-hidden rounded-2xl border border-warn-line bg-card shadow-lift"
                  x-data="{ t: { read: @js((bool) old('read')), ethics: @js((bool) old('ethics')), reporting: @js((bool) old('reporting')) }, tried: @js($errors->any()), get n() { return Object.values(this.t).filter(Boolean).length } }"
                  @submit="tried = true; if (n < 3) $event.preventDefault()">
                @csrf
                <header class="flex items-start gap-3 border-b border-warn-line bg-warn-soft px-[18px] py-3.5">
                    <span class="grid size-[30px] shrink-0 place-items-center rounded-lg bg-warn text-amber-950"><x-icon name="pen-line" class="size-4" /></span>
                    <div><h2 class="text-[15px] font-semibold text-amber-900">Accept the agreement</h2><p class="mt-0.5 text-[13px] leading-normal text-amber-800">Accepting here is your signature as PI. GMU then collects the other signatures.</p></div>
                </header>
                <div class="flex flex-col gap-3 px-[18px] py-4">
                    @foreach($ticks as $name => $text)
                        <label class="flex cursor-pointer items-start gap-2.5 rounded-[10px] border px-3 py-2.5 text-[13.5px] leading-snug"
                               :class="t.{{ $name }} ? 'border-blue-100 bg-blue-50/60' : (tried ? 'border-danger-line bg-card' : 'border-border bg-card')">
                            <input type="checkbox" class="checkbox mt-px" name="{{ $name }}" value="1" x-model="t.{{ $name }}">{{ $text }}
                        </label>
                    @endforeach
                    <p class="text-[12.5px] text-danger-ink" x-show="tried && n < 3" x-cloak>Tick all three to accept.</p>
                    <button type="submit" class="h-11 rounded-[9px] bg-blue-700 text-sm font-medium text-white hover:bg-blue-800">Accept as Principal Investigator</button>
                    <p class="text-center text-xs text-muted-foreground"><span x-text="n">0</span> of 3 ticked</p>
                </div>
            </form>
        @endif

        @if($accepted)
            <div role="status" class="flex gap-3 rounded-[14px] border border-ok-line bg-ok-soft px-4 py-3.5">
                <span class="grid size-6 shrink-0 place-items-center rounded-full bg-primary text-white"><x-icon name="check" class="size-3" /></span>
                <div>
                    <p class="text-sm font-semibold text-ok-ink">{{ $isPi ? 'You accepted the agreement' : 'Accepted by the PI' }}</p>
                    <p class="mt-0.5 text-[13px] leading-normal text-gray-700">
                        @if($isPi)
                            On {{ $application->agreement_accepted_at->format('j M Y') }} at {{ $application->agreement_accepted_at->format('H:i') }}. {{ $s === S::AWARDED ? 'The signed copy is on file and the grant is awarded.' : 'GMU now collects the other signatures and will tell you when the grant is awarded.' }}
                        @else
                            {{ $pi->name }} accepted online on {{ $application->agreement_accepted_at->format('j M Y') }} at {{ $application->agreement_accepted_at->format('H:i') }}. {{ $s === S::AWARDED ? 'The signed copy is on file.' : 'Collect the other three signatures, then upload the signed copy.' }}
                        @endif
                    </p>
                </div>
            </div>
        @endif

        @if($gmu && in_array($s, [S::AGREEMENT_ACCEPTED, S::AWARDED], true))
            <a href="{{ route('seed.submissions.show', $application) }}" class="flex h-11 items-center justify-center gap-2 rounded-[9px] bg-foreground text-sm font-medium text-white hover:bg-gray-800"><x-icon name="upload" class="size-4" />{{ $s === S::AWARDED ? 'Manage the signed copy' : 'Upload the signed copy' }}</a>
        @endif

        <section class="overflow-hidden rounded-[14px] border border-border bg-card">
            <header class="border-b border-border bg-muted px-4 py-3"><h3 class="text-sm font-semibold">Where it stands</h3></header>
            <ol class="p-4">
                @foreach($steps as $i => [$label, $done, $when])
                    @php $now = $i === $firstOpen; @endphp
                    <li class="flex gap-3">
                        <span class="flex flex-col items-center">
                            <span class="grid size-5 shrink-0 place-items-center rounded-full {{ $done ? 'bg-primary text-white' : ($now ? 'bg-card ring-2 ring-inset ring-warn' : 'bg-card ring-2 ring-inset ring-gray-300') }}">@if($done)<x-icon name="check" class="size-2.5" />@endif</span>
                            <span class="my-[3px] min-h-3.5 w-0.5 flex-1 rounded-sm {{ $loop->last ? 'bg-transparent' : ($done ? 'bg-blue-200' : 'bg-border') }}"></span>
                        </span>
                        <span class="pb-3.5 leading-snug">
                            <span class="block text-[13.5px] {{ $now ? 'font-semibold' : 'font-medium' }} {{ $done || $now ? 'text-foreground' : 'text-muted-foreground' }}">{{ $label }}</span>
                            @if($done && $when)<span class="block text-xs text-muted-foreground">{{ $when }}</span>@endif
                        </span>
                    </li>
                @endforeach
            </ol>
        </section>
    </aside>
</div>
@endsection
