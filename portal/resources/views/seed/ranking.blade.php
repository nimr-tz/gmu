@extends('layouts.app')

{{--
    GMU's ranking, from the owner's "GMU" design (ink band): full proposals by the reviewers'
    mean score, the funding line drawn across, and the recommended ones ticked to sign off
    and send on together. (The DRCP and the DG read theirs: seed.ranking-readonly.)
--}}

@php
    use App\Support\SeedGrant as S;
    $form     = $call->form();
    $minKey   = array_key_first($form->minimums());
    $maxTotal = max(1, $form->maxTotal());
    $passPct  = $form->passMark() / $maxTotal * 100;
    $m        = fn ($n) => 'TZS ' . number_format($n / 1e6, 1) . 'M';
    $sendTo   = ['drcp' => 'Send ticked to DRCP', 'dg' => 'Send ticked to the DG'][$nextAfterGmu] ?? 'Approve ticked';
    $lineDrawn = false;
    $tile = 'rounded-[14px] px-[18px] py-4 shadow-lift';
@endphp

@section('title', 'Ranking')
@section('context', 'Grants · Ranking · ' . $call->code)
@section('topbar-colour', 'bg-foreground')
@section('own-flash', '1')

@section('content')
<form method="POST" action="{{ route('seed.ranking.send') }}" x-data="{ ticked: {{ $awaitingCount }} }" @change="ticked = $root.querySelectorAll('input[name=\'ids[]\']:checked').length">
    @csrf
    <div class="-mx-4 -mt-5 flex flex-wrap items-start justify-between gap-5 bg-foreground px-7 pb-[100px] pt-5 text-white lg:-mx-6 lg:-mt-6">
        <div class="flex min-w-0 max-w-[820px] flex-col gap-2">
            <h1 class="text-[30px] font-semibold leading-tight tracking-[-0.025em] text-white">Rank and recommend</h1>
            <p class="text-[15px] text-gray-300">Full proposals ranked by the reviewers’ mean score. The panel can change its recommendation, never the scores.</p>
        </div>
        <div class="flex flex-wrap justify-end gap-2">
            @include('seed.partials.call-picker', ['dark' => true])
            @if($awaitingCount)
                <button type="submit" :disabled="! ticked" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3.5 text-[13px] font-semibold text-foreground hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-60"><span>{{ $sendTo }} (<span x-text="ticked">{{ $awaitingCount }}</span>)</span><x-icon name="arrow-right" class="size-3.5" /></button>
            @endif
        </div>
    </div>

    <div class="-mt-[72px] flex flex-col gap-5">
        <dl class="grid grid-cols-2 gap-3.5 lg:grid-cols-4">
            <div class="{{ $tile }} border border-border bg-card"><dt class="text-[12.5px] text-muted-foreground">Above the threshold</dt><dd class="mt-1 text-[26px] font-semibold tracking-[-0.02em]">{{ $recommendedCount }} <span class="text-[15px] font-normal text-muted-foreground">of {{ $ranked->count() }}</span></dd></div>
            <div class="{{ $tile }} border border-border bg-card"><dt class="text-[12.5px] text-muted-foreground">Their budgets</dt><dd class="mt-1 text-[26px] font-semibold tracking-[-0.02em] text-ok-ink">{{ $m($recommendedTotal) }}</dd></div>
            <div class="{{ $tile }} border border-border bg-card"><dt class="text-[12.5px] text-muted-foreground">Funds available</dt><dd class="mt-1 text-[26px] font-semibold tracking-[-0.02em] {{ $call->envelope ? '' : 'text-muted-foreground' }}">{{ $call->envelope ? $m($call->envelope) : '—' }}</dd>
                <dd class="mt-0.5 text-[12.5px] {{ $call->envelope && $recommendedTotal > $call->envelope ? 'font-medium text-danger-ink' : 'text-muted-foreground' }}">{{ $call->envelope ? ($recommendedTotal > $call->envelope ? 'Recommended exceeds the funds' : 'For this call') : 'Set it in call settings' }}</dd></div>
            <div class="{{ $tile }} border {{ $awaitingCount ? 'border-warn bg-warn text-amber-950' : 'border-border bg-card' }}"><dt class="text-[12.5px] font-medium">Waiting for GMU sign-off</dt><dd class="mt-1 text-[26px] font-semibold tracking-[-0.02em]">{{ $awaitingCount }}</dd><dd class="mt-0.5 text-[12.5px]">{{ $awaitingCount ? 'Tick and send them on' : 'Nothing to sign off' }}</dd></div>
        </dl>

        @if(session('success'))
            <div role="status" x-data="{ shown: true }" x-show="shown" class="flex items-center gap-3 rounded-xl border border-ok-line bg-ok-soft px-4 py-3.5 text-ok-ink">
                <span class="grid size-[22px] shrink-0 place-items-center rounded-full bg-primary text-white"><x-icon name="check" class="size-3" /></span>
                <span class="flex-1 text-[13.5px] font-medium">{{ session('success') }} The PIs are not told until the final decision.</span>
                <button type="button" @click="shown = false" class="text-[13px] font-medium hover:underline">Dismiss</button>
            </div>
        @endif

        <section class="{{ $call->tintClass() }} overflow-hidden rounded-[14px] border border-tint-line">
            <header class="flex flex-wrap items-center gap-2.5 border-b border-tint-line bg-tint px-[18px] py-3.5">
                <span class="rounded-md bg-tint-chip px-1.5 py-0.5 font-mono text-xs font-medium text-tint-ink">{{ $call->code }}</span>
                <div class="min-w-0 flex-1"><h2 class="text-sm font-semibold">{{ $call->title }}</h2><p class="text-[13px] text-muted-foreground">Pass mark {{ S::trimNumber($form->passMark()) }} overall{{ $form->minimumsPhrase() }}</p></div>
            </header>
            @if($ranked->isEmpty())
                <div class="bg-card px-5 py-12 text-center">
                    <p class="text-[14.5px] font-semibold">No full proposals yet</p>
                    <p class="mt-1 text-[13px] text-muted-foreground">Proposals appear here, ranked, once reviewers have scored them.{{ $call->proposal_deadline ? ' Full proposals for ' . $call->code . ' are due ' . $call->proposal_deadline->format('j M Y') . '.' : '' }}</p>
                </div>
            @else
                <div class="overflow-x-auto bg-card">
                    <table class="w-full min-w-[980px] border-collapse text-left text-sm">
                        <thead><tr>
                            <th class="h-10 w-10 border-b border-border pl-[18px]"><span class="sr-only">Select</span></th>
                            <th class="w-14 border-b border-border px-3 text-[12.5px] font-medium text-muted-foreground">Rank</th>
                            <th class="border-b border-border px-3 text-[12.5px] font-medium text-muted-foreground">Proposal</th>
                            <th class="border-b border-border px-3 text-[12.5px] font-medium text-muted-foreground">Mean score</th>
                            @if($minKey)<th class="border-b border-border px-3 text-right text-[12.5px] font-medium text-muted-foreground">{{ \Illuminate\Support\Str::before($form->criteria()[$minKey][0], ' and') }} /{{ S::trimNumber($form->criteria()[$minKey][2]) }}</th>@endif
                            <th class="border-b border-border px-3 text-right text-[12.5px] font-medium text-muted-foreground">Budget, TZS</th>
                            <th class="border-b border-border pl-3 pr-[18px] text-[12.5px] font-medium text-muted-foreground">Status</th>
                        </tr></thead>
                        <tbody>
                            @foreach($ranked as $i => $row)
                                @php $a = $row['application']; $means = $row['means']; $passes = $row['passes']; @endphp
                                @if(! $lineDrawn && ! $passes)
                                    @php $lineDrawn = true; @endphp
                                    <tr><td colspan="7" class="bg-gray-100 px-[18px] py-2">
                                        <div class="flex items-center gap-3 text-[12.5px] text-gray-700">
                                            <span class="shrink-0 font-semibold text-foreground">Funding line · {{ S::trimNumber($form->passMark()) }} overall{{ $form->minimumsPhrase() }}</span>
                                            <span class="flex-1 border-t-2 border-dashed border-gray-300"></span>
                                            <span class="shrink-0">Below this line nothing is funded</span>
                                        </div>
                                    </td></tr>
                                @endif
                                <tr class="{{ $passes ? 'bg-card' : 'bg-gray-50' }}">
                                    <td class="border-b border-gray-100 py-3.5 pl-[18px]">@if($a->status === S::RECOMMENDED)<input type="checkbox" class="checkbox text-foreground" name="ids[]" value="{{ $a->id }}" checked aria-label="Send {{ $a->reference }}">@endif</td>
                                    <td class="border-b border-gray-100 px-3 py-3.5">
                                        @if($means)<span class="grid size-7 place-items-center rounded-lg text-[13px] font-semibold {{ $passes ? 'bg-blue-50 text-ok-ink' : 'bg-gray-100 text-muted-foreground' }}">{{ $i + 1 }}</span>@else<span class="text-subtle">—</span>@endif
                                    </td>
                                    <td class="max-w-[420px] border-b border-gray-100 px-3 py-3.5">
                                        <a href="{{ route('seed.submissions.show', $a) }}" class="text-sm font-medium leading-snug hover:text-ok-ink {{ $passes ? 'text-foreground' : 'text-muted-foreground' }}">{{ $a->title }}</a>
                                        <p class="mt-0.5 text-[13px] text-muted-foreground"><span class="font-mono text-xs">{{ $a->reference }}</span> · {{ $a->pi->name }}{{ $a->pi->research_center ? ' · ' . $a->pi->research_center : '' }}</p>
                                    </td>
                                    <td class="border-b border-gray-100 px-3 py-3.5">
                                        @if($means)
                                            <div class="flex items-center gap-3">
                                                <span class="w-10 text-[15px] font-semibold tabular-nums">{{ $means['total'] }}</span>
                                                <span class="relative h-2 w-[140px] rounded-full bg-gray-100" role="img" aria-label="Score {{ $means['total'] }}; pass mark {{ S::trimNumber($form->passMark()) }}">
                                                    <span class="absolute inset-y-0 left-0 rounded-full {{ $passes ? 'bg-primary' : 'bg-gray-400' }}" style="width: {{ min(100, $means['total'] / $maxTotal * 100) }}%"></span>
                                                    <span class="absolute -bottom-1 -top-1 w-0.5 rounded-sm bg-foreground" style="left: {{ $passPct }}%"></span>
                                                </span>
                                                <span class="text-xs text-muted-foreground">{{ $means['count'] }} {{ str('review')->plural($means['count']) }}</span>
                                            </div>
                                        @else
                                            <span class="text-[13px] text-muted-foreground">Not scored yet</span>
                                        @endif
                                    </td>
                                    @if($minKey)
                                        <td class="border-b border-gray-100 px-3 py-3.5 text-right font-mono">
                                            @if($means && ($means[$minKey] ?? 0) < $form->minimums()[$minKey])<span class="inline-flex h-[22px] items-center whitespace-nowrap rounded-md bg-danger-soft px-2 font-sans text-xs font-medium text-danger-ink">{{ $means[$minKey] }} · below minimum</span>@else{{ $means[$minKey] ?? '—' }}@endif
                                        </td>
                                    @endif
                                    <td class="border-b border-gray-100 px-3 py-3.5 text-right font-mono">{{ number_format($a->budgetTotal('proposal')) }}</td>
                                    <td class="border-b border-gray-100 py-3.5 pl-3 pr-[18px]">@include('seed.partials.status', ['application' => $a])</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            <footer class="border-t border-border bg-gray-50 px-[18px] py-3 text-[13px] text-muted-foreground">Ties go to the higher science score, then feasibility, then relevance. Recommendations are made on each proposal’s page; ticked proposals here are those already recommended.</footer>
        </section>
    </div>
</form>
@endsection
