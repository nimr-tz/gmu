@extends('layouts.app')

{{--
    GMU's list of submissions, from the owner's "GMU" design (ink band): one call, or every
    call grouped by call. Within a call, what needs GMU comes first, then the latest activity.
--}}

@php
    use App\Http\Controllers\SeedGrant\SubmissionsController as F;
    $scope = $all ? 'all' : $call->id;
    $lastCallId = null;
    $workflow = app(\App\Services\SeedGrant\Workflow::class);
    $here = fn ($filterKey) => route('seed.submissions.index', array_filter(['filter' => $filterKey, 'q' => $search, 'call' => $scope]));
    $tabs = ['' => ['All submitted', $counts->except(\App\Support\SeedGrant::DRAFT)->sum()]];
    foreach (F::FILTERS as $key => [$label, $statuses]) {
        $tabs[$key] = [$label, collect($statuses)->sum(fn ($st) => $counts[$st] ?? 0)];
    }
@endphp

@section('title', 'Submissions')
@section('context', $all ? 'Grants · Submissions' : 'Grants · Submissions · ' . $call->code)
@section('topbar-colour', 'bg-foreground')

@section('content')
<div class="-mx-4 -mt-5 flex flex-wrap items-start justify-between gap-5 bg-foreground px-7 pb-[100px] pt-5 text-white lg:-mx-6 lg:-mt-6">
    <div class="flex min-w-0 flex-col gap-2">
        <h1 class="text-[30px] font-semibold leading-tight tracking-[-0.025em] text-white">Submissions</h1>
        <p class="text-[15px] text-gray-300">{{ $all ? 'Every call, grouped by call' : $call->title }} · drafts stay with applicants until they submit</p>
    </div>
    <div class="flex flex-wrap justify-end gap-2">
        @include('seed.partials.call-picker', ['allowAll' => true, 'dark' => true])
        <a href="{{ route('seed.submissions.export', array_filter(['call' => $scope, 'filter' => $filter, 'q' => $search])) }}" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-gray-700 px-3.5 text-[13px] font-medium text-white hover:bg-gray-800"><x-icon name="download" class="size-3.5" />Export</a>
        @unless($all)
            <a href="{{ route('seed.ranking', ['call' => $call->id]) }}" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3.5 text-[13px] font-semibold text-foreground hover:bg-gray-100">Ranking</a>
        @endunless
    </div>
</div>

<div class="-mt-[72px]">
    <section class="overflow-hidden rounded-[14px] border border-border bg-card shadow-pop">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border bg-muted px-3 py-2.5">
            <nav class="flex flex-wrap gap-0.5 rounded-[9px] bg-gray-200/70 p-[3px]" aria-label="Filter">
                @foreach($tabs as $key => [$label, $n])
                    @php $on = (string) $filter === (string) $key; @endphp
                    <a href="{{ $here($key ?: null) }}" @if($on) aria-current="page" @endif
                       class="inline-flex h-7 items-center gap-1.5 whitespace-nowrap rounded-[7px] px-2.5 text-[13px] font-medium {{ $on ? 'bg-card text-foreground shadow-sm ring-1 ring-border' : 'text-gray-600 hover:text-foreground' }}">{{ $label }}<span class="text-[11.5px] font-semibold tabular-nums {{ $on ? 'text-muted-foreground' : 'text-subtle' }}">{{ $n }}</span></a>
                @endforeach
            </nav>
            <form method="GET" class="relative w-[280px] max-w-full" role="search">
                <input type="hidden" name="call" value="{{ $scope }}">
                @if($filter)<input type="hidden" name="filter" value="{{ $filter }}">@endif
                <label for="q" class="sr-only">Search</label>
                <x-icon name="search" class="pointer-events-none absolute left-2.5 top-1/2 size-3.5 -translate-y-1/2 text-subtle" />
                <input id="q" type="search" name="q" value="{{ $search }}" class="input h-8 bg-card pl-8 text-[13px]" placeholder="Ref, title or applicant">
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[880px] border-collapse text-left text-sm">
                <thead><tr>
                    <th class="h-[38px] border-b border-border pl-[18px] pr-3 text-[12.5px] font-medium text-muted-foreground">Reference</th>
                    <th class="border-b border-border px-3 text-[12.5px] font-medium text-muted-foreground">Application</th>
                    <th class="border-b border-border px-3 text-[12.5px] font-medium text-muted-foreground">Status</th>
                    <th class="border-b border-border px-3 text-right text-[12.5px] font-medium text-muted-foreground">Requested, TZS</th>
                    <th class="border-b border-border pl-3 pr-[18px] text-[12.5px] font-medium text-muted-foreground">Last activity</th>
                </tr></thead>
                <tbody>
                    @forelse($applications as $a)
                        @if($all && $a->seed_call_id !== $lastCallId)
                            @php $lastCallId = $a->seed_call_id; @endphp
                            <tr class="{{ $a->call->tintClass() }}"><td colspan="5" class="border-b border-tint-line bg-tint px-[18px] py-[9px]">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="flex min-w-0 items-center gap-2">
                                        @include('seed.partials.call-tag', ['call' => $a->call, 'short' => true])
                                        <span class="truncate text-[13px] font-semibold">{{ $a->call->title }}</span>
                                        <span class="whitespace-nowrap text-[12.5px] text-muted-foreground">FY {{ $a->call->fiscal_year }}</span>
                                    </span>
                                    <a href="{{ route('seed.submissions.index', ['call' => $a->seed_call_id]) }}" class="shrink-0 text-[12.5px] font-medium text-tint-ink hover:underline">Only this call</a>
                                </div>
                            </td></tr>
                        @endif
                        @php $next = \App\Support\GmuNext::step($a, $workflow); @endphp
                        <tr class="relative hover:bg-gray-50">
                            <td class="whitespace-nowrap border-b border-gray-100 py-[13px] pl-[18px] pr-3 font-mono text-[12.5px] text-gray-700">{{ $a->reference }}</td>
                            <td class="max-w-[460px] border-b border-gray-100 px-3 py-[13px]">
                                <a href="{{ route('seed.submissions.show', $a) }}" class="block font-medium leading-snug after:absolute after:inset-0 hover:text-ok-ink">{{ $a->title ?: 'Untitled' }}</a>
                                <span class="mt-0.5 block text-[12.5px] text-muted-foreground">{{ $a->pi->name }}{{ $a->pi->research_center ? ' · ' . $a->pi->research_center : '' }}</span>
                            </td>
                            <td class="border-b border-gray-100 px-3 py-[13px]">
                                @include('seed.partials.status', ['application' => $a])
                                @if($next)<span class="mt-1 block text-xs font-medium {{ $next[1] === 'danger' ? 'text-danger-ink' : 'text-amber-800' }}">GMU: {{ $next[0] }}</span>@endif
                            </td>
                            <td class="border-b border-gray-100 px-3 py-[13px] text-right font-mono text-[13px]">{{ number_format($a->requestedAmount()) }}</td>
                            <td class="whitespace-nowrap border-b border-gray-100 py-[13px] pl-3 pr-[18px] text-[13px]">{{ $a->updated_at->format('j M Y') }} <span class="text-muted-foreground">· {{ $a->updated_at->diffForHumans() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-11 text-center">
                            <p class="text-[14.5px] font-semibold">{{ $search || $filter ? 'Nothing matches' : 'Nothing submitted yet' }}</p>
                            <p class="mt-1 text-[13px] text-muted-foreground">{{ $search || $filter ? 'Try another filter or search.' : 'Concept notes appear here once applicants submit them.' }}</p>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <footer class="border-t border-border bg-gray-50 px-[18px] py-[11px] text-[12.5px] text-muted-foreground">Within a call, what needs GMU comes first, then the latest activity. Drafts stay with applicants until they submit.</footer>
    </section>
    @if($applications->hasPages())<div class="mt-4">{{ $applications->links() }}</div>@endif
</div>
@endsection
