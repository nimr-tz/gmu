@extends('layouts.gmu')

@section('title', 'My reviews')
@section('context', 'Seed Grant review panel')

@php
    $tone = ['accept' => 'ok', 'revise' => 'warn', 'reject' => 'danger'];
@endphp

@section('content')
<x-ui.page-head title="Your reviews" lede="You review anonymously. Applicants read your comments, never your name." />

<div class="grid grid-wide">
    <div class="stack">
        <section class="card">
            <div class="card-head"><div class="card-title">To do <span class="mono muted">· {{ $todo->count() }}</span></div></div>
            <div class="list">
                @forelse($todo as $a)
                    @php
                        $app = $a->application;
                        $round = $app->openRound($a->stage);
                        $due = $round?->due_at;
                        $draft = $round?->reviews->firstWhere('seed_reviewer_assignment_id', $a->id);
                    @endphp
                    <div class="list-row" style="flex-wrap:wrap;">
                        <div style="flex:0 0 64px; text-align:center; border-radius:12px; padding:8px 0; background:{{ $due && $due->diffInDays(now(), false) > -3 ? 'var(--danger-bg)' : 'var(--canvas)' }};">
                            <div class="mono" style="font-size:22px; line-height:1; {{ $due && $due->diffInDays(now(), false) > -3 ? 'color:var(--danger-ink);' : '' }}">{{ $due?->format('j') ?? '—' }}</div>
                            <div class="tiny muted" style="text-transform:uppercase;">{{ $due?->format('M') }}</div>
                        </div>
                        <div class="list-main" style="flex:1 1 300px;">
                            <div class="row" style="gap:8px;">
                                <span class="mono small muted">{{ $app->reference }}</span>
                                <span class="pill">{{ $a->stage === 'concept' ? 'Concept note' : 'Full proposal' }}{{ $round ? ' · round ' . $round->number : '' }}</span>
                                @if($a->coi_status === 'pending')<span class="pill pill-danger">Declare interests first</span>
                                @elseif($draft)<span class="pill pill-warn">Draft saved</span>
                                @elseif($round && $round->number > 1)<span class="pill pill-info">Applicant responded</span>@endif
                            </div>
                            <span class="list-title" style="font-size:15px; margin-top:4px;">{{ $a->coi_status === 'pending' ? 'Title hidden until you declare' : $app->title }}</span>
                            @if($due)<span class="list-sub">Due {{ $due->format('j M Y') }} ({{ $due->diffForHumans() }})</span>@endif
                        </div>
                        <a href="{{ route('seed.reviews.show', $a) }}" class="btn {{ $a->coi_status === 'pending' ? '' : 'btn-primary' }}">
                            {{ $a->coi_status === 'pending' ? 'Declare' : ($draft ? 'Continue review' : 'Start review') }}
                        </a>
                    </div>
                @empty
                    <div class="empty"><i class="fas fa-check-circle" aria-hidden="true"></i><p>Nothing to review right now.</p></div>
                @endforelse
            </div>
        </section>

        @if($done->isNotEmpty())
            <section class="card">
                <div class="card-head"><div class="card-title">Earlier reviews</div></div>
                <div class="list">
                    @foreach($done as $a)
                        @php $last = $a->application->rounds->where('stage', $a->stage)->flatMap->reviews->where('seed_reviewer_assignment_id', $a->id)->whereNotNull('submitted_at')->sortByDesc('submitted_at')->first(); @endphp
                        <a href="{{ route('seed.reviews.show', $a) }}" class="list-row">
                            <span class="mono small muted" style="width:100px;">{{ $a->application->reference }}</span>
                            <span class="list-main"><span class="list-title">{{ $a->coi_status === 'conflict' ? 'Conflict declared' : $a->application->title }}</span></span>
                            @if($last)<span class="pill pill-{{ $tone[$last->recommendation] ?? '' }}">{{ \App\Models\Seed\SeedReview::RECOMMENDATIONS[$last->recommendation] }}</span>
                                <span class="small muted" style="width:60px; text-align:right;">{{ $last->submitted_at->format('j M') }}</span>@endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <aside>
        <section class="card">
            <div class="card-body">
                <h2 class="card-title">How full proposals are scored</h2>
                <p class="small muted" style="margin-top:4px;">Concept notes get accept, revise or reject only. Weighted scoring starts at stage 2.</p>
                <ul style="list-style:none; margin:16px 0 0; padding:0; display:flex; flex-direction:column; gap:12px; font-size:13px;">
                    @foreach($form->criteria() as $key => [$name, , $max])
                        @php $hasMin = isset($form->minimums()[$key]); $top = max(array_column($form->criteria(), 2)) ?: 1; @endphp
                        <li><div class="row-between"><span class="{{ $hasMin ? 'strong' : '' }}">{{ $name }}</span><span class="mono">{{ \App\Support\SeedGrant::trimNumber($max) }}</span></div>
                            <div class="meter" style="margin-top:5px;"><span style="width:{{ $max / $top * 100 }}%; {{ $hasMin ? '' : 'background:var(--blue-300);' }}"></span></div></li>
                    @endforeach
                </ul>
                <div style="margin-top:18px; padding:14px; border-radius:12px; background:var(--blue-50); font-size:13px; color:var(--blue-900);">
                    Fundable at <strong class="mono">{{ \App\Support\SeedGrant::trimNumber($form->passMark()) }}/{{ \App\Support\SeedGrant::trimNumber($form->maxTotal()) }}</strong> or more{{ $form->minimumsPhrase(', and never below ', ' on ') }}. Don’t mark down a strong study for having no innovation.
                </div>
            </div>
        </section>
    </aside>
</div>
@endsection
