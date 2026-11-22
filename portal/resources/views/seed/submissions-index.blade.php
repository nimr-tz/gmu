@extends('layouts.gmu')

@php use App\Support\SeedGrant; use App\Http\Controllers\SeedGrant\SubmissionsController as F; @endphp

@section('title', 'Submissions')
@section('context', 'Seed Grant · FY ' . $call->fiscal_year)

@section('content')
<x-ui.page-head title="Submissions" :lede="'Every concept note and full proposal under ' . $call->title . '. Drafts stay with applicants until they submit.'">
    <x-slot:actions>
        @include('seed.partials.call-picker')
        <a href="{{ route('seed.ranking', ['call' => $call->id]) }}" class="btn"><i class="fas fa-list-ol" aria-hidden="true"></i> Ranking</a>
    </x-slot:actions>
</x-ui.page-head>

<div class="row-between" style="margin-bottom:16px;">
    <nav class="chips" aria-label="Filter">
        <a href="{{ route('seed.submissions.index', array_filter(['q' => $search, 'call' => $call->id])) }}" class="chip {{ ! $filter ? 'is-active' : '' }}">All submitted</a>
        @foreach(F::FILTERS as $key => [$label, $statuses])
            @php $n = collect($statuses)->sum(fn ($s) => $counts[$s] ?? 0); @endphp
            <a href="{{ route('seed.submissions.index', array_filter(['filter' => $key, 'q' => $search, 'call' => $call->id])) }}" class="chip {{ $filter === $key ? 'is-active' : '' }}">{{ $label }} <span class="mono" style="margin-left:6px;">{{ $n }}</span></a>
        @endforeach
    </nav>
    <form method="GET" class="row" role="search">
        @if($filter)<input type="hidden" name="filter" value="{{ $filter }}">@endif
        <label for="q" class="sr-only">Search</label>
        <input id="q" type="search" name="q" value="{{ $search }}" class="input" placeholder="Ref, title or applicant" style="width:260px;">
        <button class="btn" type="submit" aria-label="Search"><i class="fas fa-search" aria-hidden="true"></i></button>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table" style="min-width:820px;">
            <thead><tr><th>Ref</th><th>Application</th><th>Status</th><th class="num">Requested (TZS)</th><th>Updated</th><th><span class="sr-only">Open</span></th></tr></thead>
            <tbody>
                @forelse($applications as $a)
                    <tr>
                        <td class="mono sub">{{ $a->reference }}</td>
                        <td>
                            <div class="strong">{{ $a->title ?: 'Untitled' }}</div>
                            <div class="sub">{{ $a->pi->name }}{{ $a->pi->research_center ? ' · ' . $a->pi->research_center : '' }}</div>
                        </td>
                        <td>@include('seed.partials.status', ['application' => $a])</td>
                        <td class="num">{{ number_format($a->requestedAmount()) }}</td>
                        <td class="sub" style="white-space:nowrap;">{{ $a->updated_at->diffForHumans() }}</td>
                        <td style="text-align:right;"><a href="{{ route('seed.submissions.show', $a) }}" class="btn btn-sm">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-empty"><i class="fas fa-inbox" aria-hidden="true"></i>Nothing here yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination-wrap">{{ $applications->links() }}</div>
@endsection
