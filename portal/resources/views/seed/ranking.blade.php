@extends('layouts.app')

@php use App\Support\SeedGrant as S; $canManage = auth()->user()->can('manage-seed-grants'); $lineDrawn = false; $form = $call->form(); $minKey = array_key_first($form->minimums()); @endphp

@section('title', 'Ranking')
@section('context', 'Ranking · ' . $call->title)

@section('content')
<form method="POST" action="{{ route('seed.ranking.send') }}">
    @csrf
    <x-page-header title="Rank and recommend" description="Full proposals ranked by the reviewers’ mean score. The panel can change its recommendation, never the scores.">
        <x-slot:actions>
            @include('seed.partials.call-picker')
            @if($canManage && $awaitingCount)
                <x-button type="submit" variant="primary" icon-right="arrow-right">{{ ['drcp' => 'Send ticked to DRCP', 'dg' => 'Send ticked to the DG'][$nextAfterGmu] ?? 'Approve ticked' }}</x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <dl class="mb-6 grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-border bg-border shadow-xs lg:grid-cols-4">
        <div class="bg-card px-5 py-4"><dt class="text-xs text-muted-foreground">Above the threshold</dt><dd class="mt-1 text-2xl font-semibold tabular-nums">{{ $recommendedCount }} <span class="text-base font-normal text-muted-foreground">of {{ $ranked->count() }}</span></dd></div>
        <div class="bg-card px-5 py-4"><dt class="text-xs text-muted-foreground">Their budgets</dt><dd class="mt-1 text-2xl font-semibold tabular-nums">{{ number_format($recommendedTotal / 1e6, 1) }}M <span class="text-xs font-normal text-muted-foreground">TZS</span></dd></div>
        <div class="bg-card px-5 py-4"><dt class="text-xs text-muted-foreground">Funds available</dt>
            <dd class="mt-1 text-2xl font-semibold tabular-nums {{ $call->envelope ? '' : 'text-muted-foreground' }}">{{ $call->envelope ? number_format($call->envelope / 1e6, 1) . 'M' : '—' }}</dd>
            <dd class="mt-0.5 text-xs {{ $call->envelope && $recommendedTotal > $call->envelope ? 'font-medium text-danger-ink' : 'text-muted-foreground' }}">{{ $call->envelope ? ($recommendedTotal > $call->envelope ? 'Recommended exceeds the funds' : 'TZS') : 'Set it in call settings' }}</dd></div>
        <div class="bg-card px-5 py-4"><dt class="text-xs text-muted-foreground">Waiting for GMU sign-off</dt><dd class="mt-1 text-2xl font-semibold tabular-nums">{{ $awaitingCount }}</dd></div>
    </dl>

    <x-card flush>
        <div class="overflow-x-auto">
            <table class="data-table min-w-[980px]">
                <thead><tr>
                    @if($canManage)<th class="w-10"><span class="sr-only">Select</span></th>@endif
                    <th class="w-16">Rank</th><th>Proposal</th><th>Mean score</th>
                    @if($minKey)<th class="text-right">{{ $form->criteria()[$minKey][0] }} /{{ S::trimNumber($form->criteria()[$minKey][2]) }}</th>@endif
                    <th class="text-right">Budget (TZS)</th><th>Status</th>
                </tr></thead>
                <tbody>
                    @forelse($ranked as $i => $row)
                        @php $a = $row['application']; $m = $row['means']; @endphp
                        @if(! $lineDrawn && ! $row['passes'])
                            @php $lineDrawn = true; @endphp
                            <tr class="hover:bg-transparent"><td colspan="{{ $canManage ? 7 : 6 }}" class="!bg-sidebar !py-2 text-xs font-medium uppercase tracking-wider text-white">
                                Funding threshold · {{ S::trimNumber($form->passMark()) }} overall{{ $form->minimumsPhrase() }}</td></tr>
                        @endif
                        <tr class="{{ $row['passes'] ? '' : 'bg-muted/40 text-muted-foreground' }}">
                            @if($canManage)
                                <td>@if($a->status === S::RECOMMENDED)<input type="checkbox" class="checkbox" name="ids[]" value="{{ $a->id }}" checked aria-label="Send {{ $a->reference }}">@endif</td>
                            @endif
                            <td class="font-mono text-lg tabular-nums text-foreground">{{ $m ? $i + 1 : '—' }}</td>
                            <td class="max-w-md">
                                <a href="{{ $canManage ? route('seed.submissions.show', $a) : route('seed.approvals.show', $a) }}" class="font-medium text-foreground hover:text-primary">{{ $a->title }}</a>
                                <div class="mt-0.5 text-[13px] text-muted-foreground"><span class="font-mono text-xs">{{ $a->reference }}</span> · {{ $a->pi->name }}{{ $a->pi->research_center ? ' · ' . $a->pi->research_center : '' }}</div>
                            </td>
                            <td>
                                @if($m)
                                    <div class="flex items-center gap-3">
                                        <span class="w-10 font-mono font-semibold tabular-nums text-foreground">{{ $m['total'] }}</span>
                                        <span class="relative h-1.5 w-28 rounded-full bg-muted">
                                            <span class="absolute inset-y-0 left-0 rounded-full {{ $row['passes'] ? 'bg-primary' : 'bg-gray-400' }}" style="width: {{ min(100, $m['total'] / max(1, $form->maxTotal()) * 100) }}%"></span>
                                            <span class="absolute -inset-y-1 w-px bg-foreground/60" style="left: {{ $form->passMark() / max(1, $form->maxTotal()) * 100 }}%" title="Pass mark"></span>
                                        </span>
                                        <span class="text-xs text-muted-foreground">{{ $m['count'] }} {{ str('review')->plural($m['count']) }}</span>
                                    </div>
                                @else
                                    <span class="text-[13px] text-muted-foreground">Not scored yet</span>
                                @endif
                            </td>
                            @if($minKey)
                                <td class="num">
                                    @if($m && ($m[$minKey] ?? 0) < $form->minimums()[$minKey])<x-badge tone="danger">{{ $m[$minKey] }} · below minimum</x-badge>@else{{ $m[$minKey] ?? '—' }}@endif
                                </td>
                            @endif
                            <td class="num">{{ number_format($a->budgetTotal('proposal')) }}</td>
                            <td>@include('seed.partials.status', ['application' => $a])</td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty icon="list-ordered" title="No full proposals yet" compact>Proposals appear here, ranked, once reviewers have scored them.</x-empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
    <p class="mt-4 max-w-3xl text-[13px] text-muted-foreground">Ties go to the higher science score, then feasibility, then relevance. Recommendations are made on each proposal’s page; ticked proposals here are those already recommended.</p>
</form>
@endsection
