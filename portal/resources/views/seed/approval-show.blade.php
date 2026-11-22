@extends('layouts.app')

@php
    use App\Support\SeedGrant as S;
    use App\Http\Controllers\SeedGrant\ApprovalsController as A;
    $form   = $application->call->form();
    $myTurn = $application->status === A::QUEUE_STATUS[$level];
    $total  = $application->budgetTotal('proposal');
    $cats   = $application->budgetByCategory();
    $shades = ['personnel' => 'bg-blue-900', 'equipment' => 'bg-blue-400', 'research' => 'bg-primary', 'indirect' => 'bg-blue-200'];
    $steps  = array_intersect_key(['gmu' => 'GMU sign-off', 'drcp' => 'DRCP clearance', 'dg' => 'Director General'], array_flip($form->approvalLevels()));
    $cap    = $form->equipmentCap();
@endphp

@section('title', 'Approve ' . $application->reference)
@section('context', 'Approvals · ' . $application->reference)

@section('content')
<x-page-header :title="$application->title" :back="route('seed.approvals.index')" back-label="Approvals"
    :description="$application->pi->name . ', Principal Investigator' . ($application->pi->research_center ? ' · ' . $application->pi->research_center : '') . ($application->mentor_name ? ' · mentor ' . $application->mentor_name : '')">
    <x-slot:meta>
        <div class="mt-3 flex flex-wrap items-center gap-2.5">
            @include('seed.partials.status')
            @if($means)<x-badge tone="outline" class="font-mono">Mean score {{ $means['total'] }}</x-badge>@endif
            @include('seed.partials.call-tag', ['call' => $application->call, 'short' => true])
            <span class="font-mono text-xs text-muted-foreground">{{ $application->reference }}</span>
        </div>
    </x-slot:meta>
    <x-slot:actions>
        <div class="text-right"><p class="text-xs text-muted-foreground">Award requested</p><p class="font-mono text-2xl font-semibold tabular-nums">{{ S::tzs($total) }}</p></div>
    </x-slot:actions>
</x-page-header>

{{-- The approval chain --}}
<ol class="mb-6 grid gap-3 {{ [1 => '', 2 => 'md:grid-cols-2', 3 => 'md:grid-cols-3'][count($steps)] }}" aria-label="Approval chain">
    @foreach($steps as $l => $label)
        @php $ap = $application->approvalAt($l); $done = $ap && $ap->decision !== 'returned'; $now = ! $done && $application->status === A::QUEUE_STATUS[$l]; @endphp
        <li class="flex gap-3 rounded-xl border p-4 shadow-xs {{ $now ? 'border-sidebar bg-sidebar text-white' : 'border-border bg-card' }}">
            <span class="grid size-7 shrink-0 place-items-center rounded-full {{ $done ? 'bg-primary text-white' : ($now ? 'bg-white text-sidebar' : 'bg-muted text-muted-foreground') }}">
                @if($done)<x-icon name="check" class="size-3.5" />@else<span class="font-mono text-xs">{{ $loop->iteration }}</span>@endif
            </span>
            <span class="min-w-0">
                <span class="block text-sm font-semibold">{{ $label }}</span>
                <span class="block text-[13px] {{ $now ? 'text-sidebar-foreground' : 'text-muted-foreground' }}">
                    @if($done){{ $ap->decider?->name }} · {{ $ap->created_at->format('j M Y') }}{{ $ap->note ? ' — “' . \Illuminate\Support\Str::limit($ap->note, 70) . '”' : '' }}
                    @elseif($now){{ $l === $level ? 'Your decision' : 'Waiting' }}
                    @else Not yet @endif
                </span>
            </span>
        </li>
    @endforeach
</ol>

<div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
    <div class="space-y-6">
        @if($means)
            <x-card title="How the panel scored it" :description="$means['count'] . ' ' . str('reviewer')->plural($means['count'])">
                <ul class="divide-y divide-border">
                    @foreach($form->criteria() as $key => [$name, , $max])
                        <li class="grid grid-cols-[minmax(0,1fr)_9rem_4.5rem] items-center gap-4 py-2.5 text-sm">
                            <span class="{{ isset($form->minimums()[$key]) ? 'font-medium' : '' }}">{{ $name }}</span>
                            <x-meter :value="$max ? $means[$key] / $max * 100 : 0" :label="$name" />
                            <span class="text-right font-mono tabular-nums">{{ $means[$key] }}/{{ S::trimNumber($max) }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-1 flex justify-between border-t border-border pt-3 text-sm font-semibold"><span>Total</span><span class="font-mono">{{ $means['total'] }} / {{ S::trimNumber($form->maxTotal()) }}</span></div>
            </x-card>
        @endif

        <x-card title="Budget" description="Detailed budget from the full proposal">
            @if($total > 0)
                <div class="flex h-3 gap-0.5 overflow-hidden rounded-full" role="img"
                     aria-label="Budget split: @foreach($cats as $k => $v){{ S::BUDGET_CATEGORIES[$k] }} {{ round($v / $total * 100) }}%@if(! $loop->last), @endif @endforeach">
                    @foreach($cats as $k => $v)@if($v > 0)<span class="{{ $shades[$k] }}" style="width: {{ $v / $total * 100 }}%"></span>@endif @endforeach
                </div>
            @endif
            <dl class="mt-5 grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach($cats as $k => $v)
                    <div>
                        <dt class="flex items-center gap-2 text-xs text-muted-foreground"><span class="size-2.5 rounded-sm {{ $shades[$k] }}"></span>{{ S::BUDGET_CATEGORIES[$k] }}</dt>
                        <dd class="mt-1 font-mono text-[15px] tabular-nums">{{ number_format($v) }}</dd>
                        @if($k === 'equipment' && $total > 0 && $cap !== null)
                            <dd class="mt-0.5 text-xs {{ $v / $total * 100 > $cap ? 'font-medium text-danger-ink' : 'text-blue-700' }}">{{ number_format($v / $total * 100, 1) }}%, limit {{ $cap }}%</dd>
                        @endif
                    </div>
                @endforeach
            </dl>
            <div class="mt-5 grid grid-cols-4 gap-2 border-t border-border pt-4 text-center">
                @foreach($application->budgetByQuarter() as $q => $v)
                    <div class="rounded-lg bg-muted px-2 py-2.5"><div class="text-xs text-muted-foreground">{{ $q }}</div><div class="font-mono text-sm tabular-nums">{{ number_format($v / 1e6, 2) }}M</div></div>
                @endforeach
            </div>
        </x-card>

        <details class="group overflow-hidden rounded-xl border border-border bg-card shadow-xs">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4 text-[15px] font-semibold hover:bg-muted/50 [&::-webkit-details-marker]:hidden">
                Read the full proposal <x-icon name="chevron-down" class="text-subtle transition-transform group-open:rotate-180" />
            </summary>
            <div class="border-t border-border bg-background p-5">@include('seed.partials.content', ['stage' => 'proposal'])</div>
        </details>
    </div>

    <aside class="space-y-4 xl:sticky xl:top-24">
        @if($myTurn)
            <form method="POST" action="{{ route('seed.approvals.decide', $application) }}" x-data="{ decision: 'approved' }">
                @csrf
                <x-card title="Your decision" icon="scale">
                    <fieldset class="space-y-2">
                        <legend class="sr-only">Decision</legend>
                        @foreach(array_filter([
                            'approved'   => [$level === 'gmu' ? ($form->nextApprovalLevel('gmu') === 'dg' ? 'Send to the Director General' : 'Send to the DRCP') : ($level === 'drcp' ? 'Clear for the Director General' : 'Approve'), null],
                            'conditions' => $level === 'dg' ? ['Approve with conditions', 'Conditions go into the grant agreement'] : null,
                            'returned'   => $level !== 'gmu' ? ['Return to GMU', 'With a reason; nothing is sent to the PI'] : null,
                        ]) as $value => [$label, $sub])
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors" :class="decision === '{{ $value }}' ? 'border-primary bg-primary-soft/60 ring-1 ring-primary' : 'border-border hover:bg-muted/50'">
                                <input type="radio" class="radio mt-0.5" name="decision" value="{{ $value }}" x-model="decision" @checked($loop->first)>
                                <span><span class="block text-sm font-medium">{{ $label }}</span>@if($sub)<span class="block text-xs text-muted-foreground">{{ $sub }}</span>@endif</span>
                            </label>
                        @endforeach
                    </fieldset>
                    <x-field class="mt-4" :label="$level === 'dg' ? 'Conditions or note' : 'Note'" name="note" optional>
                        <textarea id="note" name="note" class="textarea" rows="3">{{ old('note') }}</textarea>
                    </x-field>
                    <x-button type="submit" variant="primary" class="mt-4 w-full">{{ $level === 'dg' ? 'Record decision and notify' : 'Record decision' }}</x-button>
                    <p class="mt-2.5 text-xs text-muted-foreground">Recorded in the audit register with your name and the time.</p>
                </x-card>
            </form>
        @else
            <x-alert tone="neutral" title="No decision needed from you">This application is “{{ $application->statusLabel() }}”.</x-alert>
        @endif
        @if($application->approval_conditions)
            <x-card title="Conditions"><p class="prose-text">{{ $application->approval_conditions }}</p></x-card>
        @endif
    </aside>
</div>
@endsection
