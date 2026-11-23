@extends('layouts.app')

{{--
    The ranking as the DRCP and the Director General read it, from the owner's
    "Approvals" design: read only. Full proposals by the reviewers' mean score, the
    funding line, and each one's status. (GMU's own ranking, with sign-off, is seed.ranking.)
--}}

@php
    use App\Support\SeedGrant as S;
    $form     = $call->form();
    $minKey   = array_key_first($form->minimums());
    $minimum  = $minKey ? $form->minimums()[$minKey] : null;
    $maxTotal = max(1, $form->maxTotal());
    $passPct  = $form->passMark() / $maxTotal * 100;
    $m        = fn ($n) => 'TZS ' . number_format($n / 1e6, 1) . 'M';
    $lineDrawn = false;
@endphp

@section('title', 'Ranking')
@section('context', 'Grants · Ranking · ' . $call->code)
@section('topbar-colour', 'bg-indigo-700')

@section('content')
<div class="-mx-4 -mt-5 flex flex-wrap items-end justify-between gap-5 bg-indigo-700 px-7 pb-[100px] pt-5 text-white lg:-mx-6 lg:-mt-6">
    <div class="flex max-w-[820px] flex-col gap-2">
        <div class="flex flex-wrap items-center gap-2.5">
            <span class="rounded-md bg-white/15 px-1.5 py-0.5 font-mono text-xs font-medium">{{ $call->code }}</span>
            <span class="text-[13px] text-indigo-100">FY {{ $call->fiscal_year }}</span>
            <span class="inline-flex h-[22px] items-center rounded-md bg-white/15 px-2 text-xs font-medium">Read only</span>
        </div>
        <h1 class="text-[30px] font-semibold leading-tight tracking-[-0.025em] text-white">Ranking</h1>
        <p class="text-[15px] text-indigo-100">Full proposals ranked by the reviewers’ mean score. The panel can change its recommendation, never the scores.</p>
    </div>
    @if($calls->count() > 1)
        <div class="[&_label]:text-indigo-100">@include('seed.partials.call-picker')</div>
    @endif
</div>

<div class="-mt-[72px] flex flex-col gap-5">
    <dl class="grid grid-cols-2 gap-3.5 lg:grid-cols-4">
        <div class="rounded-[14px] border border-border bg-card px-[18px] py-4 shadow-lift"><dt class="text-[12.5px] text-muted-foreground">Above the threshold</dt><dd class="mt-1 text-[26px] font-semibold tracking-[-0.02em]">{{ $recommendedCount }} <span class="text-[15px] font-normal text-muted-foreground">of {{ $ranked->count() }}</span></dd></div>
        <div class="rounded-[14px] border border-border bg-card px-[18px] py-4 shadow-lift"><dt class="text-[12.5px] text-muted-foreground">Their budgets</dt><dd class="mt-1 text-[26px] font-semibold tracking-[-0.02em] text-indigo-800">{{ $m($recommendedTotal) }}</dd></div>
        <div class="rounded-[14px] bg-indigo-800 px-[18px] py-4 text-white shadow-lift"><dt class="text-[12.5px] text-indigo-100">Funds available</dt><dd class="mt-1 text-[26px] font-semibold tracking-[-0.02em]">{{ $call->envelope ? $m($call->envelope) : '—' }}</dd><dd class="mt-0.5 text-[12.5px] text-indigo-100">{{ $call->envelope ? ($recommendedTotal > $call->envelope ? 'Recommended exceeds the funds' : 'For this call') : 'Not set for this call' }}</dd></div>
        <div class="rounded-[14px] border border-border bg-card px-[18px] py-4 shadow-lift"><dt class="text-[12.5px] text-muted-foreground">Waiting for GMU sign-off</dt><dd class="mt-1 text-[26px] font-semibold tracking-[-0.02em]">{{ $awaitingCount }}</dd></div>
    </dl>

    <section class="{{ $call->tintClass() }} overflow-hidden rounded-[14px] border border-indigo-100">
        <header class="flex flex-wrap items-center gap-2.5 border-b border-indigo-100 bg-indigo-50/60 px-[18px] py-3.5">
            <span class="rounded-md bg-tint-chip px-1.5 py-0.5 font-mono text-xs font-medium text-tint-ink">{{ $call->code }}</span>
            <div class="min-w-0 flex-1"><h2 class="text-sm font-semibold">{{ $call->title }}</h2><p class="text-[13px] text-muted-foreground">Pass mark {{ S::trimNumber($form->passMark()) }} overall{{ $form->minimumsPhrase() }}</p></div>
        </header>
        <div class="overflow-x-auto bg-card">
            <table class="w-full min-w-[900px] border-collapse text-left text-sm">
                <thead><tr>
                    <th class="h-10 w-16 border-b border-border pl-[18px] pr-3 text-[12.5px] font-medium text-muted-foreground">Rank</th>
                    <th class="border-b border-border px-3 text-[12.5px] font-medium text-muted-foreground">Proposal</th>
                    <th class="border-b border-border px-3 text-[12.5px] font-medium text-muted-foreground">Mean score</th>
                    @if($minKey)<th class="border-b border-border px-3 text-right text-[12.5px] font-medium text-muted-foreground">{{ \Illuminate\Support\Str::before($form->criteria()[$minKey][0], ' and') }} /{{ S::trimNumber($form->criteria()[$minKey][2]) }}</th>@endif
                    <th class="border-b border-border px-3 text-right text-[12.5px] font-medium text-muted-foreground">Budget, TZS</th>
                    <th class="border-b border-border pl-3 pr-[18px] text-[12.5px] font-medium text-muted-foreground">Status</th>
                </tr></thead>
                <tbody>
                    @forelse($ranked as $i => $row)
                        @php $a = $row['application']; $means = $row['means']; $passes = $row['passes']; @endphp
                        @if(! $lineDrawn && ! $passes)
                            @php $lineDrawn = true; @endphp
                            <tr><td colspan="6" class="bg-gray-100 px-[18px] py-2">
                                <div class="flex items-center gap-3 text-[12.5px] text-gray-700">
                                    <span class="shrink-0 font-semibold text-foreground">Funding line · {{ S::trimNumber($form->passMark()) }} overall{{ $form->minimumsPhrase() }}</span>
                                    <span class="flex-1 border-t-2 border-dashed border-gray-300"></span>
                                    <span class="shrink-0">Below this line nothing is funded</span>
                                </div>
                            </td></tr>
                        @endif
                        <tr class="{{ $passes ? 'bg-card' : 'bg-gray-50' }}">
                            <td class="border-b border-gray-100 py-3.5 pl-[18px] pr-3">
                                @if($means)<span class="grid size-7 place-items-center rounded-lg text-[13px] font-semibold {{ $passes ? 'bg-indigo-50 text-indigo-800' : 'bg-gray-100 text-muted-foreground' }}">{{ $i + 1 }}</span>@else<span class="text-subtle">—</span>@endif
                            </td>
                            <td class="max-w-[420px] border-b border-gray-100 px-3 py-3.5">
                                <a href="{{ route('seed.approvals.show', $a) }}" class="text-sm font-medium leading-snug hover:text-indigo-700 {{ $passes ? 'text-foreground' : 'text-muted-foreground' }}">{{ $a->title }}</a>
                                <p class="mt-0.5 text-[13px] text-muted-foreground"><span class="font-mono text-xs">{{ $a->reference }}</span> · {{ $a->pi->name }}{{ $a->pi->research_center ? ' · ' . $a->pi->research_center : '' }}</p>
                            </td>
                            <td class="border-b border-gray-100 px-3 py-3.5">
                                @if($means)
                                    <div class="flex items-center gap-3">
                                        <span class="w-10 text-[15px] font-semibold tabular-nums">{{ $means['total'] }}</span>
                                        <span class="relative h-2 w-[140px] rounded-full bg-gray-100" role="img" aria-label="Score {{ $means['total'] }}; pass mark {{ S::trimNumber($form->passMark()) }}">
                                            <span class="absolute inset-y-0 left-0 rounded-full {{ $passes ? 'bg-indigo-700' : 'bg-gray-400' }}" style="width: {{ min(100, $means['total'] / $maxTotal * 100) }}%"></span>
                                            <span class="absolute -bottom-1 -top-1 w-0.5 rounded-full bg-foreground" style="left: {{ $passPct }}%"></span>
                                        </span>
                                    </div>
                                @else
                                    <span class="text-[13px] text-muted-foreground">Not scored yet</span>
                                @endif
                            </td>
                            @if($minKey)
                                <td class="border-b border-gray-100 px-3 py-3.5 text-right font-mono">
                                    @if($means && ($means[$minKey] ?? 0) < $minimum)<span class="inline-flex h-[22px] items-center whitespace-nowrap rounded-md bg-danger-soft px-2 font-sans text-xs font-medium text-danger-ink">{{ $means[$minKey] }} · below {{ S::trimNumber($minimum) }}</span>@else{{ $means[$minKey] ?? '—' }}@endif
                                </td>
                            @endif
                            <td class="border-b border-gray-100 px-3 py-3.5 text-right font-mono">{{ number_format($a->budgetTotal('proposal')) }}</td>
                            <td class="border-b border-gray-100 py-3.5 pl-3 pr-[18px]">@include('seed.partials.status', ['application' => $a])</td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty icon="list-ordered" title="No full proposals yet" compact>Proposals appear here, ranked, once reviewers have scored them.</x-empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <footer class="border-t border-border bg-gray-50 px-[18px] py-3 text-[13px] text-muted-foreground">Ties go to the higher science score, then feasibility, then relevance.</footer>
    </section>
</div>
@endsection
