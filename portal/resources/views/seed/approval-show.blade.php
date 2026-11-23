@extends('layouts.app')

{{--
    The decision page, from the owner's "Approvals" design: an indigo band with the
    status, reference, title and PI; three figures (your decision, the award, the
    panel score); the approval chain; how the panel scored it, the budget and the
    full proposal; and the decision last, with the consequence stated before you press.
--}}

@php
    use App\Support\SeedGrant as S;
    use App\Http\Controllers\SeedGrant\ApprovalsController as A;
    $form     = $application->call->form();
    $myTurn   = $application->status === A::QUEUE_STATUS[$level];
    $total    = $application->budgetTotal('proposal');
    $cats     = $application->budgetByCategory();
    $catTotal = max(1, array_sum($cats));
    $catColour = ['personnel' => 'bg-indigo-700', 'equipment' => 'bg-violet-500', 'research' => 'bg-sky-500', 'indirect' => 'bg-indigo-400'];
    $steps    = array_intersect_key(['gmu' => 'GMU sign-off', 'drcp' => 'DRCP clearance', 'dg' => 'Director General'], array_flip($form->approvalLevels()));
    $cap      = $form->equipmentCap();
    $maxTotal = max(1, $form->maxTotal());
    $passPct  = $form->passMark() / $maxTotal * 100;
    $pill     = ['ok' => ['text-blue-700', 'bg-primary'], 'award' => ['text-award-ink', 'bg-award'], 'warn' => ['text-warn-ink', 'bg-warn'], 'danger' => ['text-danger-ink', 'bg-danger'], 'navy' => ['text-review-ink', 'bg-review']][$application->statusTone()] ?? ['text-gray-700', 'bg-gray-400'];

    // The decision: [value, label, sub], and what happens after each.
    $next = $form->nextApprovalLevel($level);
    $options = array_values(array_filter([
        ['approved', match ($level) { 'gmu' => $next === 'dg' ? 'Send to the Director General' : 'Send to the DRCP', 'drcp' => 'Clear for the Director General', default => 'Approve' }, null],
        $level === 'dg' ? ['conditions', 'Approve with conditions', 'Conditions go into the grant agreement'] : null,
        $level !== 'gmu' ? ['returned', 'Return to GMU', 'With a reason; nothing is sent to the PI'] : null,
    ]));
    $after = [
        'approved'   => $next ? 'It moves to ' . ($next === 'dg' ? 'the Director General' : 'the DRCP') . '. The PI is not told until the final decision.' : 'The PI is told in the portal, and GMU issues the grant agreement.',
        'conditions' => 'The PI is told in the portal, and your conditions go into the grant agreement.',
        'returned'   => 'It goes back to GMU with your reason. Nothing is sent to the PI.',
    ];
@endphp

@section('title', 'Decide ' . $application->reference)
@section('context', 'Grants · Approvals · ' . $application->reference)
@section('topbar-colour', 'bg-indigo-700')

@section('content')
{{-- The band --}}
<div class="-mx-4 -mt-5 flex flex-col gap-2.5 bg-indigo-700 px-7 pb-[100px] pt-[18px] text-white lg:-mx-6 lg:-mt-6">
    <a href="{{ route('seed.approvals.index', ['call' => $application->seed_call_id]) }}" class="inline-flex items-center gap-1.5 self-start text-[13px] font-medium text-indigo-100 hover:text-white"><x-icon name="arrow-left" class="size-3.5" />Approvals</a>
    <div class="flex flex-wrap items-center gap-2.5">
        <span class="inline-flex h-[22px] shrink-0 items-center gap-1.5 whitespace-nowrap rounded-md bg-white px-2 text-xs font-semibold {{ $pill[0] }}"><span class="size-1.5 rounded-full {{ $pill[1] }}"></span>{{ $application->statusLabel() }}</span>
        <span class="rounded-md bg-white/15 px-1.5 py-0.5 font-mono text-xs font-medium">{{ $application->call->code }}</span>
        <span class="font-mono text-[12.5px] text-indigo-100">{{ $application->reference }}</span>
    </div>
    <h1 class="max-w-[900px] text-balance text-[28px] font-semibold leading-tight tracking-[-0.025em] text-white">{{ $application->title }}</h1>
    <p class="text-[15px] text-indigo-100">{{ $application->pi->name }}, Principal Investigator{{ $application->pi->research_center ? ' · ' . $application->pi->research_center : '' }}{{ $application->mentor_name ? ' · mentor ' . $application->mentor_name : '' }}</p>
</div>

<div class="-mt-[72px] flex flex-col gap-5">
    {{-- Three figures --}}
    <div class="grid gap-4 md:grid-cols-3">
        <div class="flex flex-col gap-2.5 rounded-[14px] border p-5 shadow-lift {{ $myTurn ? 'border-warn bg-warn text-amber-950' : 'border-border bg-card' }}">
            <span class="text-[13.5px] font-semibold">Your decision</span>
            <span class="text-[26px] font-semibold leading-tight tracking-[-0.02em]">{{ $myTurn ? 'Waiting for you' : 'Not needed' }}</span>
            <span class="text-[13px] {{ $myTurn ? 'text-amber-900' : 'text-muted-foreground' }}">{{ $myTurn ? 'You are the ' . ['gmu' => 'GMU sign-off', 'drcp' => 'DRCP clearance', 'dg' => 'final decision'][$level] : 'This application is “' . $application->statusLabel() . '”' }}</span>
        </div>
        <div class="flex flex-col gap-2.5 rounded-[14px] border border-border bg-card p-5 shadow-lift">
            <span class="text-[13.5px] font-medium">Award requested</span>
            <span class="text-[26px] font-semibold leading-tight tracking-[-0.02em] tabular-nums text-indigo-800">TZS {{ number_format($total / 1e6, 1) }}M</span>
            <span class="text-[13px] text-muted-foreground">{{ S::tzs($total) }} in the detailed budget</span>
        </div>
        <div class="flex flex-col gap-2.5 rounded-[14px] border border-border bg-card p-5 shadow-lift">
            <span class="text-[13.5px] font-medium">Panel score</span>
            <span class="text-[26px] font-semibold leading-tight tracking-[-0.02em] tabular-nums">{{ $means ? $means['total'] . ' / ' . S::trimNumber($form->maxTotal()) : 'Not scored' }}</span>
            @if($means)
                <div class="relative h-1.5 rounded-full bg-gray-100" role="img" aria-label="Panel score {{ $means['total'] }}; pass mark {{ S::trimNumber($form->passMark()) }}">
                    <div class="absolute inset-y-0 left-0 rounded-full bg-indigo-700" style="width: {{ min(100, $means['total'] / $maxTotal * 100) }}%"></div>
                    <span class="absolute -bottom-1 -top-1 w-0.5 rounded-full bg-foreground" style="left: {{ $passPct }}%"></span>
                </div>
            @endif
            <span class="text-[13px] text-muted-foreground">{{ $means ? 'Mean of ' . $means['count'] . ' ' . str('reviewer')->plural($means['count']) . ' · fundable at ' . S::trimNumber($form->passMark()) : 'No scores yet' }}</span>
        </div>
    </div>

    {{-- The approval chain --}}
    <ol class="grid gap-3 md:grid-cols-3" aria-label="Approval chain">
        @foreach($steps as $l => $label)
            @php $ap = $application->approvalAt($l); $done = $ap && $ap->decision !== 'returned'; $now = ! $done && $application->status === A::QUEUE_STATUS[$l]; @endphp
            <li class="flex items-start gap-3 rounded-xl border px-4 py-3.5 {{ $now ? 'border-warn-line bg-warn-soft' : 'border-border bg-card' }}">
                <span class="grid size-7 shrink-0 place-items-center rounded-full font-mono text-xs font-semibold {{ $done ? 'bg-indigo-700 text-white' : ($now ? 'bg-warn text-white' : 'bg-gray-100 text-muted-foreground') }}">
                    @if($done)<x-icon name="check" class="size-3.5" />@else{{ $loop->iteration }}@endif
                </span>
                <span class="min-w-0 leading-snug">
                    <span class="block text-sm font-semibold">{{ $label }}</span>
                    <span class="block text-[13px] {{ $now ? 'text-warn-ink' : 'text-muted-foreground' }}">
                        @if($done){{ $ap->decider?->name }} · {{ $ap->created_at->format('j M Y') }}{{ $ap->note ? ' — “' . \Illuminate\Support\Str::limit($ap->note, 70) . '”' : '' }}
                        @elseif($now){{ $l === $level ? 'Your decision' : 'Waiting' }}
                        @elseif($ap && $ap->decision === 'returned')Returned to GMU · {{ $ap->created_at->format('j M Y') }}{{ $ap->note ? ' — “' . \Illuminate\Support\Str::limit($ap->note, 70) . '”' : '' }}
                        @else Not yet @endif
                    </span>
                </span>
            </li>
        @endforeach
    </ol>

    <div class="flex flex-wrap items-start gap-5">
        <div class="flex min-w-0 flex-[1_1_560px] flex-col gap-5">
            @if($means)
                <section class="overflow-hidden rounded-[14px] border border-border">
                    <header class="flex items-center justify-between gap-3 border-b border-border bg-muted px-[18px] py-3">
                        <div><h2 class="text-sm font-semibold">How the panel scored it</h2><p class="text-[13px] text-muted-foreground">{{ $means['count'] }} {{ str('reviewer')->plural($means['count']) }}</p></div>
                        <span class="font-mono text-[15px] font-semibold">{{ $means['total'] }} / {{ S::trimNumber($form->maxTotal()) }}</span>
                    </header>
                    <ul class="bg-card px-[18px] pb-2.5 pt-1.5">
                        @foreach($form->criteria() as $key => [$name, , $max])
                            @php $v = $means[$key] ?? 0; $min = $form->minimums()[$key] ?? null; $low = $min !== null && $v < $min; @endphp
                            <li class="grid grid-cols-[minmax(0,1fr)_150px_70px] items-center gap-4 border-b border-gray-100 py-[11px] text-[13.5px] last:border-b-0">
                                <span class="{{ $min !== null ? 'font-semibold' : '' }}">{{ $name }}</span>
                                <span class="relative h-2 rounded-full bg-gray-100" role="img" aria-label="{{ $name }}: {{ $v }} of {{ S::trimNumber($max) }}">
                                    <span class="absolute inset-y-0 left-0 rounded-full {{ $low ? 'bg-danger' : 'bg-indigo-700' }}" style="width: {{ $max ? min(100, $v / $max * 100) : 0 }}%"></span>
                                    @if($min !== null)<span class="absolute -bottom-[3px] -top-[3px] w-0.5 rounded-full bg-foreground" style="left: {{ $min / $max * 100 }}%"></span>@endif
                                </span>
                                <span class="text-right font-mono {{ $low ? 'text-danger-ink' : '' }}">{{ $v }}/{{ S::trimNumber($max) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if($form->minimums())
                        <p class="bg-card px-[18px] pb-3.5 text-[12.5px] text-muted-foreground">{{ collect($form->minimums())->map(fn ($min, $k) => $form->criteria()[$k][0] . ' must reach ' . S::trimNumber($min))->implode('; ') }}; the marker shows that minimum.</p>
                    @endif
                </section>
            @endif

            <section class="overflow-hidden rounded-[14px] border border-border">
                <header class="border-b border-border bg-muted px-[18px] py-3"><h2 class="text-sm font-semibold">Budget</h2><p class="text-[13px] text-muted-foreground">Detailed budget from the full proposal</p></header>
                <div class="flex flex-col gap-[18px] bg-card px-5 py-[18px]">
                    @if($total > 0)
                        <div class="flex h-3.5 gap-0.5 overflow-hidden rounded-[5px]" role="img"
                             aria-label="Budget split: @foreach($cats as $k => $v){{ S::BUDGET_CATEGORIES[$k] }} {{ round($v / $catTotal * 100) }}%@if(! $loop->last), @endif @endforeach">
                            @foreach($cats as $k => $v)@if($v > 0)<span class="{{ $catColour[$k] ?? 'bg-gray-400' }}" style="width: {{ $v / $catTotal * 100 }}%"></span>@endif @endforeach
                        </div>
                    @endif
                    <dl class="grid grid-cols-2 gap-3.5 md:grid-cols-4">
                        @foreach($cats as $k => $v)
                            <div>
                                <dt class="flex items-center gap-1.5 text-[12.5px] text-muted-foreground"><span class="size-[9px] rounded-sm {{ $catColour[$k] ?? 'bg-gray-400' }}"></span>{{ S::BUDGET_CATEGORIES[$k] }}</dt>
                                <dd class="mt-1 font-mono text-[15px]">{{ number_format($v) }}</dd>
                                @if($k === 'equipment' && $total > 0 && $cap !== null)
                                    <dd class="mt-0.5 text-xs {{ $v / $total * 100 > $cap ? 'font-medium text-danger-ink' : 'text-muted-foreground' }}">{{ number_format($v / $total * 100, 1) }}%, limit {{ $cap }}%</dd>
                                @endif
                            </div>
                        @endforeach
                    </dl>
                    <div class="grid grid-cols-4 gap-2 border-t border-border pt-3.5 text-center">
                        @foreach($application->budgetByQuarter() as $q => $v)
                            <div class="rounded-[10px] bg-muted px-1.5 py-2.5"><p class="text-xs text-muted-foreground">{{ $q }}</p><p class="mt-0.5 font-mono text-sm">{{ number_format($v / 1e6, 2) }}M</p></div>
                        @endforeach
                    </div>
                </div>
            </section>

            <details class="group overflow-hidden rounded-[14px] border border-border">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 bg-card px-5 py-4 text-[15px] font-semibold hover:bg-gray-50 [&::-webkit-details-marker]:hidden">
                    Read the full proposal <x-icon name="chevron-down" class="text-subtle transition-transform group-open:rotate-180" />
                </summary>
                <div class="border-t border-border bg-gray-50 p-5">@include('seed.partials.content', ['stage' => 'proposal'])</div>
            </details>
        </div>

        {{-- The decision, last --}}
        <aside class="flex min-w-[300px] flex-[0_1_380px] flex-col gap-4 xl:sticky xl:top-20">
            @if($myTurn)
                @php $firstChoice = old('decision', $options[0][0]); @endphp
                <form method="POST" action="{{ route('seed.approvals.decide', $application) }}" novalidate
                      x-data="{ decision: @js($firstChoice), note: @js(old('note', '')), tried: false, after: @js($after),
                                get needNote() { return this.decision !== 'approved' },
                                get noteError() { return this.tried && this.needNote && ! this.note.trim() ? (this.decision === 'conditions' ? 'Write the conditions.' : 'Give GMU your reason.') : '' } }"
                      @submit="tried = true; if (needNote && ! note.trim()) $event.preventDefault()">
                    @csrf
                    <section class="overflow-hidden rounded-2xl border border-warn-line bg-card shadow-lift">
                        <header class="border-b border-warn-line bg-warn-soft px-[18px] py-3.5"><h2 class="text-[15px] font-semibold text-amber-900">Your decision</h2><p class="text-[13px] text-warn-ink">The consequence is stated before you press</p></header>
                        <div class="flex flex-col gap-3.5 px-[18px] py-4">
                            <fieldset class="flex flex-col gap-2">
                                <legend class="sr-only">Decision</legend>
                                @foreach($options as [$value, $label, $sub])
                                    <label class="flex cursor-pointer items-start gap-3 rounded-[10px] border-[1.5px] px-3.5 py-3 transition-colors"
                                           :class="decision === '{{ $value }}' ? 'border-indigo-700 bg-indigo-50/60' : 'border-input hover:bg-gray-50'">
                                        <input type="radio" class="radio mt-0.5 text-indigo-700" name="decision" value="{{ $value }}" x-model="decision" @checked($firstChoice === $value)>
                                        <span class="leading-snug"><span class="block text-sm font-semibold">{{ $label }}</span>@if($sub)<span class="block text-[12.5px] text-muted-foreground">{{ $sub }}</span>@endif</span>
                                    </label>
                                @endforeach
                            </fieldset>
                            <p class="rounded-[10px] bg-muted px-3.5 py-3 text-[13px] leading-relaxed text-gray-700"><b class="font-semibold text-foreground">What happens next:</b> <span x-text="after[decision]">{{ $after[$firstChoice] }}</span></p>
                            <label class="flex flex-col gap-1.5">
                                <span class="text-[13px] font-medium">{{ $level === 'dg' ? 'Conditions or note' : 'Note' }} <span class="font-normal text-muted-foreground" x-show="! needNote">(optional)</span></span>
                                <textarea name="note" rows="3" class="textarea" x-model="note" :aria-invalid="noteError || @js($errors->has('note')) ? 'true' : 'false'"
                                          :placeholder="decision === 'conditions' ? 'The conditions the PI must meet' : (decision === 'returned' ? 'Why it goes back to GMU' : '')"></textarea>
                                <span class="text-[12.5px] text-danger-ink" x-show="noteError" x-text="noteError"></span>
                                @error('note')<span class="text-[12.5px] text-danger-ink" x-show="! noteError">{{ $message }}</span>@enderror
                            </label>
                            <button type="submit" class="h-[46px] rounded-[9px] bg-foreground text-[14.5px] font-medium text-white hover:bg-gray-800">{{ $level === 'dg' ? 'Record decision and notify' : 'Record decision' }}</button>
                            <p class="text-center text-xs text-muted-foreground">Recorded in the audit register with your name and the time.</p>
                        </div>
                    </section>
                </form>
            @else
                <div class="rounded-[14px] border border-border bg-muted px-[18px] py-4"><p class="text-sm font-semibold">No decision needed from you</p><p class="mt-1 text-[13.5px] text-gray-700">This application is “{{ $application->statusLabel() }}”.</p></div>
            @endif
            @if($application->approval_conditions)
                <section class="rounded-[14px] border border-border px-[18px] py-4"><h3 class="text-sm font-semibold">Conditions</h3><p class="prose-text mt-1.5 text-[13.5px]">{{ $application->approval_conditions }}</p></section>
            @endif
        </aside>
    </div>
</div>
@endsection
