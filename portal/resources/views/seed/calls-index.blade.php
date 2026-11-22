@extends('layouts.gmu')

{{-- Every call: GMU sees all of them (drafts included) and creates new ones; staff see open and closed calls. --}}

@section('title', 'Calls')

@php use App\Support\SeedGrant; @endphp

@section('content')
<x-ui.page-head title="Calls"
    :lede="$manage ? 'Every call NIMR runs. A new call starts from the last one’s settings, and stays hidden until you open it.' : 'Calls you can apply to, and earlier ones.'">
    @if($manage)
        <x-slot:actions>
            <a href="{{ route('seed.calls.create') }}" class="btn btn-primary"><i class="fas fa-plus" aria-hidden="true"></i> New call</a>
        </x-slot:actions>
    @endif
</x-ui.page-head>

<div class="card">
    @if($calls->isEmpty())
        <x-ui.empty icon="fa-bullhorn" :title="$manage ? 'No calls yet' : 'No calls are open right now'">
            {{ $manage ? 'Create the first call. Researchers see it once you set it to Open.' : 'When NIMR opens a call, it will appear here.' }}
            @if($manage)
                <x-slot:action><a href="{{ route('seed.calls.create') }}" class="btn btn-primary">New call</a></x-slot:action>
            @endif
        </x-ui.empty>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Call</th>
                        <th>Status</th>
                        <th>Concept notes close</th>
                        <th class="num">Award (TZS)</th>
                        @if($manage)<th class="num">Applications</th>@endif
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($calls as $call)
                        <tr>
                            <td>
                                <a href="{{ route('seed.calls.show', $call) }}" class="strong">{{ $call->title }}</a>
                                <div class="tiny muted-3"><span class="mono">{{ $call->code }}</span> · FY {{ $call->fiscal_year }}</div>
                            </td>
                            <td>
                                <x-ui.pill :tone="['open' => 'ok', 'draft' => null, 'closed' => 'info'][$call->status] ?? null">
                                    {{ $call->status === 'open' && ! $call->acceptingConceptNotes() ? 'Open · concept notes closed' : \App\Models\Seed\SeedCall::STATUSES[$call->status] }}
                                </x-ui.pill>
                            </td>
                            <td class="small">{{ $call->concept_deadline?->format('j M Y') ?? 'To be announced' }}</td>
                            <td class="num mono">{{ number_format($call->min_amount) }} – {{ number_format($call->max_amount) }}</td>
                            @if($manage)<td class="num mono">{{ $call->applications_count }}</td>@endif
                            <td class="num">
                                @if($manage)
                                    <a href="{{ route('seed.calls.edit', $call) }}" class="btn btn-sm" aria-label="Settings for {{ $call->title }}"><i class="fas fa-cog" aria-hidden="true"></i></a>
                                @elseif(isset($mine[$call->id]))
                                    <a href="{{ route('seed.applications.show', $mine[$call->id]) }}" class="btn btn-sm">My application</a>
                                @elseif($call->acceptingConceptNotes())
                                    <a href="{{ route('seed.calls.show', $call) }}" class="btn btn-sm btn-primary">Read and apply</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
