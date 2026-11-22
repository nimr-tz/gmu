@extends('layouts.gmu')

{{--
    GMU's list of submissions: one call, or every call grouped by call.
    Within a call, what needs GMU comes first, then the latest activity.
--}}

@php
    use App\Support\SeedGrant;
    use App\Http\Controllers\SeedGrant\SubmissionsController as F;
    $scope = $all ? 'all' : $call->id;
    $lastCallId = null;
@endphp

@section('title', 'Submissions')
@section('context', $all ? 'Submissions · all calls' : 'Submissions · ' . $call->title)

@section('content')
<x-ui.page-head title="Submissions"
    :lede="$all ? 'Every concept note and full proposal, across all calls, grouped by call. Drafts stay with applicants until they submit.'
                : 'Every concept note and full proposal under ' . $call->title . '. Drafts stay with applicants until they submit.'">
    <x-slot:actions>
        @include('seed.partials.call-picker', ['allowAll' => true])
        <a href="{{ route('seed.submissions.export', array_filter(['call' => $scope, 'filter' => $filter, 'q' => $search])) }}" class="btn">
            <i class="fas fa-file-csv" aria-hidden="true"></i> Export</a>
        @unless($all)
            <a href="{{ route('seed.ranking', ['call' => $call->id]) }}" class="btn"><i class="fas fa-list-ol" aria-hidden="true"></i> Ranking</a>
        @endunless
    </x-slot:actions>
</x-ui.page-head>

<div class="row-between list-tools">
    <nav class="chips" aria-label="Filter">
        <a href="{{ route('seed.submissions.index', array_filter(['q' => $search, 'call' => $scope])) }}" class="chip {{ ! $filter ? 'is-active' : '' }}">All submitted</a>
        @foreach(F::FILTERS as $key => [$label, $statuses])
            @php $n = collect($statuses)->sum(fn ($s) => $counts[$s] ?? 0); @endphp
            <a href="{{ route('seed.submissions.index', array_filter(['filter' => $key, 'q' => $search, 'call' => $scope])) }}" class="chip {{ $filter === $key ? 'is-active' : '' }}">{{ $label }} <span class="mono chip-count">{{ $n }}</span></a>
        @endforeach
    </nav>
    <form method="GET" class="row" role="search">
        <input type="hidden" name="call" value="{{ $scope }}">
        @if($filter)<input type="hidden" name="filter" value="{{ $filter }}">@endif
        <label for="q" class="sr-only">Search</label>
        <input id="q" type="search" name="q" value="{{ $search }}" class="input search-input" placeholder="Ref, title or applicant">
        <button class="btn" type="submit" aria-label="Search"><i class="fas fa-search" aria-hidden="true"></i></button>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table table-wide">
            <thead><tr><th>Ref</th><th>Application</th><th>Status</th><th class="num">Requested (TZS)</th><th>Last activity</th><th><span class="sr-only">Open</span></th></tr></thead>
            <tbody>
                @forelse($applications as $a)
                    @if($all && $a->seed_call_id !== $lastCallId)
                        @php $lastCallId = $a->seed_call_id; @endphp
                        <tr class="group-row"><td colspan="6">
                            @include('seed.partials.call-tag', ['call' => $a->call])
                            <a href="{{ route('seed.submissions.index', ['call' => $a->seed_call_id]) }}" class="group-link">Only this call</a>
                        </td></tr>
                    @endif
                    <tr>
                        <td class="mono sub nowrap">{{ $a->reference }}</td>
                        <td>
                            <div class="strong">{{ $a->title ?: 'Untitled' }}</div>
                            <div class="sub">{{ $a->pi->name }}{{ $a->pi->research_center ? ' · ' . $a->pi->research_center : '' }}</div>
                        </td>
                        <td>@include('seed.partials.status', ['application' => $a])</td>
                        <td class="num">{{ number_format($a->requestedAmount()) }}</td>
                        <td class="sub nowrap">{{ $a->updated_at->format('j M Y') }}<div class="tiny muted-3">{{ $a->updated_at->diffForHumans() }}</div></td>
                        <td class="num"><a href="{{ route('seed.submissions.show', $a) }}" class="btn btn-sm">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-empty"><i class="fas fa-inbox" aria-hidden="true"></i>
                        {{ $search || $filter ? 'Nothing matches. Try another filter or search.' : 'Nothing submitted yet. Concept notes appear here once applicants submit them.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination-wrap">{{ $applications->links() }}</div>
@endsection
