@extends('layouts.app')

@php use App\Support\SeedGrant as S; $canManage = auth()->user()->can('manage-seed-grants'); $lineDrawn = false; $form = $call->form(); $minKey = array_key_first($form->minimums()); @endphp

@section('title', 'Ranking')
@section('context', 'Grants · Ranking · ' . $call->code)

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

    <dl class="mb-5 grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-border bg-border lg:grid-cols-4">
        <div class="bg-card px-5 py-4"><dt class="text-xs text-muted-foreground">Above the threshold</dt><dd class="mt-1 text-2xl font-semibold tabular-nums">{{ $recommendedCount }} <span class="text-base font-normal text-muted-foreground">of {{ $ranked->count() }}</span></dd></div>
        <div class="bg-card px-5 py-4"><dt class="text-xs text-muted-foreground">Their budgets</dt><dd class="mt-1 text-2xl font-semibold tabular-nums">TZS {{ number_format($recommendedTotal / 1e6, 1) }}M</dd></div>
        <div class="bg-card px-5 py-4"><dt class="text-xs text-muted-foreground">Funds available</dt>
            <dd class="mt-1 text-2xl font-semibold tabular-nums {{ $call->envelope ? '' : 'text-muted-foreground' }}">{{ $call->envelope ? 'TZS ' . number_format($call->envelope / 1e6, 1) . 'M' : '—' }}</dd>
            <dd class="mt-0.5 text-xs {{ $call->envelope && $recommendedTotal > $call->envelope ? 'font-medium text-danger-ink' : 'text-muted-foreground' }}">{{ $call->envelope ? ($recommendedTotal > $call->envelope ? 'Recommended exceeds the funds' : 'For this call') : 'Set it in call settings' }}</dd></div>
        <div class="bg-card px-5 py-4"><dt class="text-xs text-muted-foreground">Waiting for GMU sign-off</dt><dd class="mt-1 text-2xl font-semibold tabular-nums {{ $awaitingCount ? 'text-warn-ink' : '' }}">{{ $awaitingCount }}</dd></div>
    </dl>

    <x-card :title="$call->title" :description="'Pass mark ' . S::trimNumber($form->passMark()) . ' overall' . $form->minimumsPhrase()" class="{{ $call->tintClass() }} !border-tint-line !bg-tint" flush>
        <div class="overflow-x-auto">
            <table class="data-table min-w-[980px]">
                <thead><tr>
                    @if($canManage)<th class="w-10"><span class="sr-only">Select</span></th>@endif
                    <th class="w-16">Rank</th><th>Proposal</th><th>Mean score</th>
                    @if($minKey)<th class="text-right">{{ $form->criteria()[$minKey][0] }} /{{ S::trimNumber($form->criteria()[$minKey][2]) }}</th>@endif
                    <th class="text-right">Budget, TZS</th><th>Status</th>
                </tr></thead>
                <tbody>
                    @forelse($ranked as $i => $row)
                        @php $a = $row['application']; $m = $row['means']; @endphp
                        @if(! $lineDrawn && ! $row['passes'])
                            @php $lineDrawn = true; @endphp
                            <tr class="hover:bg-transparent"><td colspan="{{ $canManage ? 7 : 6 }}" class="!bg-muted !py-2">
                                <div class="flex items-center gap-3 text-[12.5px] text-gray-700">
                                    <span class="shrink-0 font-semibold text-foreground">Funding line · {{ S::trimNumber($form->passMark()) }} overall{{ $form->minimumsPhrase() }}</span>
                                    <span class="flex-1 border-t-2 border-dashed border-gray-300"></span>
                                    <span class="shrink-0">Below this line nothing is funded</span>
                                </div></td></tr>
                        @endif
                        <tr class="{{ $row['passes'] ? '' : 'bg-muted/40 text-muted-foreground' }}">
                            @if($canManage)
                                <td>@if($a->status === S::RECOMMENDED)<input type="checkbox" class="checkbox" name="ids[]" value="{{ $a->id }}" checked aria-label="Send {{ $a->reference }}">@endif</td>
                            @endif
                            <td>@if($m)<span class="grid size-7 place-items-center rounded-lg text-[13px] font-semibold tabular-nums {{ $row['passes'] ? 'bg-ok-soft text-ok-ink' : 'bg-gray-100 text-gray-600' }}">{{ $i + 1 }}</span>@else<span class="text-subtle">—</span>@endif</td>
                            <td class="max-w-md">
                                <a href="{{ $canManage ? route('seed.submissions.show', $a) : route('seed.approvals.show', $a) }}" class="font-medium text-foreground hover:text-primary">{{ $a->title }}</a>
                                <div class="mt-0.5 text-[13px] text-muted-foreground"><span class="font-mono text-xs">{{ $a->reference }}</span> · {{ $a->pi->name }}{{ $a->pi->research_center ? ' · ' . $a->pi->research_center : '' }}</div>
                            </td>
                            <td>
                                @if($m)
                                    <div class="flex items-center gap-3">
                                        <span class="w-10 text-[15px] font-semibold tabular-nums text-foreground">{{ $m['total'] }}</span>
                                        <span class="relative h-2 w-36 rounded-full bg-gray-100">
                                            <span class="absolute inset-y-0 left-0 rounded-full {{ $row['passes'] ? 'bg-primary' : 'bg-gray-400' }}" style="width: {{ min(100, $m['total'] / max(1, $form->maxTotal()) * 100) }}%"></span>
                                            <span class="absolute -inset-y-1 w-0.5 rounded-full bg-foreground" style="left: {{ $form->passMark() / max(1, $form->maxTotal()) * 100 }}%" title="Pass mark"></span>
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
        <x-slot:footer>Ties go to the higher science score, then feasibility, then relevance. Recommendations are made on each proposal’s page; ticked proposals here are those already recommended.</x-slot:footer>
    </x-card>
</form>
@endsection
