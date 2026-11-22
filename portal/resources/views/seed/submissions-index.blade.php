@extends('layouts.app')

{{--
    GMU's list of submissions: one call, or every call grouped by call.
    Within a call, what needs GMU comes first, then the latest activity.
--}}

@php
    use App\Http\Controllers\SeedGrant\SubmissionsController as F;
    $scope = $all ? 'all' : $call->id;
    $lastCallId = null;
@endphp

@section('title', 'Submissions')
@section('context', $all ? 'Submissions · All calls' : 'Submissions · ' . $call->title)

@section('content')
<x-page-header title="Submissions"
    :description="$all ? 'Every concept note and full proposal, across all calls, grouped by call. Drafts stay with applicants until they submit.'
                       : 'Every concept note and full proposal under ' . $call->title . '. Drafts stay with applicants until they submit.'">
    <x-slot:actions>
        @include('seed.partials.call-picker', ['allowAll' => true])
        <x-button :href="route('seed.submissions.export', array_filter(['call' => $scope, 'filter' => $filter, 'q' => $search]))" icon="download">Export</x-button>
        @unless($all)
            <x-button :href="route('seed.ranking', ['call' => $call->id])" icon="list-ordered">Ranking</x-button>
        @endunless
    </x-slot:actions>
</x-page-header>

<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    @php
        $filterItems = [route('seed.submissions.index', array_filter(['q' => $search, 'call' => $scope])) => 'All submitted'];
        foreach (F::FILTERS as $key => [$label, $statuses]) {
            $filterItems[route('seed.submissions.index', array_filter(['filter' => $key, 'q' => $search, 'call' => $scope]))] = [$label, collect($statuses)->sum(fn ($st) => $counts[$st] ?? 0)];
        }
    @endphp
    <x-segmented :items="$filterItems" :active="route('seed.submissions.index', array_filter(['filter' => $filter, 'q' => $search, 'call' => $scope]))" />
    <form method="GET" class="relative w-full sm:w-72" role="search">
        <input type="hidden" name="call" value="{{ $scope }}">
        @if($filter)<input type="hidden" name="filter" value="{{ $filter }}">@endif
        <label for="q" class="sr-only">Search</label>
        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-subtle" />
        <input id="q" type="search" name="q" value="{{ $search }}" class="input input-sm pl-9" placeholder="Ref, title or applicant">
    </form>
</div>

<x-card flush>
    <div class="overflow-x-auto">
        <table class="data-table min-w-[860px]">
            <thead><tr><th>Ref</th><th>Application</th><th>Status</th><th class="text-right">Requested (TZS)</th><th>Last activity</th><th class="text-right"><span class="sr-only">Open</span></th></tr></thead>
            <tbody>
                @forelse($applications as $a)
                    @if($all && $a->seed_call_id !== $lastCallId)
                        @php $lastCallId = $a->seed_call_id; @endphp
                        <tr class="{{ $a->call->tintClass() }} hover:bg-transparent"><td colspan="6" class="border-y border-tint-line !bg-tint !py-2">
                            <div class="flex items-center justify-between gap-3">
                                @include('seed.partials.call-tag', ['call' => $a->call])
                                <a href="{{ route('seed.submissions.index', ['call' => $a->seed_call_id]) }}" class="shrink-0 text-xs font-medium text-primary hover:underline">Only this call</a>
                            </div>
                        </td></tr>
                    @endif
                    <tr>
                        <td class="whitespace-nowrap font-mono text-xs text-muted-foreground">{{ $a->reference }}</td>
                        <td class="max-w-md">
                            <a href="{{ route('seed.submissions.show', $a) }}" class="font-medium hover:text-primary">{{ $a->title ?: 'Untitled' }}</a>
                            <div class="mt-0.5 text-[13px] text-muted-foreground">{{ $a->pi->name }}{{ $a->pi->research_center ? ' · ' . $a->pi->research_center : '' }}</div>
                        </td>
                        <td>@include('seed.partials.status', ['application' => $a])</td>
                        <td class="num">{{ number_format($a->requestedAmount()) }}</td>
                        <td class="whitespace-nowrap text-[13px]">{{ $a->updated_at->format('j M Y') }}<div class="text-xs text-muted-foreground">{{ $a->updated_at->diffForHumans() }}</div></td>
                        <td class="text-right"><x-button size="sm" :href="route('seed.submissions.show', $a)">Open</x-button></td>
                    </tr>
                @empty
                    <tr class="hover:bg-transparent"><td colspan="6">
                        <x-empty icon="inbox" :title="$search || $filter ? 'Nothing matches' : 'Nothing submitted yet'" compact>
                            {{ $search || $filter ? 'Try another filter or search.' : 'Concept notes appear here once applicants submit them.' }}
                        </x-empty>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@if($applications->hasPages())<div class="mt-4">{{ $applications->links() }}</div>@endif
@endsection
