@extends('layouts.app')

{{-- Every call: GMU sees all of them (drafts included) and creates new ones; staff see open and closed calls. --}}

@section('title', 'Calls')

@section('content')
<x-page-header title="Calls"
    :description="$manage ? 'Every call NIMR runs. A new call starts from the last one’s settings, and stays hidden until you open it.' : 'Calls you can apply to, and earlier ones.'">
    @if($manage)
        <x-slot:actions><x-button variant="primary" :href="route('seed.calls.create')" icon="plus">New call</x-button></x-slot:actions>
    @endif
</x-page-header>

<x-card flush>
    @if($calls->isEmpty())
        <x-empty icon="megaphone" :title="$manage ? 'No calls yet' : 'No calls are open right now'">
            {{ $manage ? 'Create the first call. Researchers see it once you set it to Open.' : 'When NIMR opens a call, it will appear here.' }}
            @if($manage)
                <x-slot:action><x-button variant="primary" :href="route('seed.calls.create')" icon="plus">New call</x-button></x-slot:action>
            @endif
        </x-empty>
    @else
        <div class="overflow-x-auto">
            <table class="data-table min-w-[760px]">
                <thead>
                    <tr>
                        <th>Call</th>
                        <th>Status</th>
                        <th>Concept notes close</th>
                        <th class="text-right">Award (TZS)</th>
                        @if($manage)<th class="text-right">Applications</th>@endif
                        <th class="text-right"><span class="sr-only">Action</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($calls as $call)
                        <tr>
                            <td>
                                <a href="{{ route('seed.calls.show', $call) }}" class="font-medium hover:text-primary">{{ $call->title }}</a>
                                <div class="mt-1 flex items-center gap-2 text-xs text-muted-foreground">
                                    @include('seed.partials.call-tag', ['call' => $call, 'short' => true]) FY {{ $call->fiscal_year }}
                                </div>
                            </td>
                            <td>
                                <x-badge :tone="['open' => 'ok', 'closed' => 'neutral'][$call->status] ?? 'outline'" dot>
                                    {{ $call->status === 'open' && ! $call->acceptingConceptNotes() ? 'Open · concept notes closed' : \App\Models\Seed\SeedCall::STATUSES[$call->status] }}
                                </x-badge>
                            </td>
                            <td class="whitespace-nowrap text-[13px]">{{ $call->concept_deadline?->format('j M Y') ?? 'To be announced' }}</td>
                            <td class="num">{{ number_format($call->min_amount) }} – {{ number_format($call->max_amount) }}</td>
                            @if($manage)<td class="num">{{ $call->applications_count }}</td>@endif
                            <td class="text-right">
                                @if($manage)
                                    <x-button size="sm" :href="route('seed.calls.edit', $call)" icon="sliders" aria-label="Settings for {{ $call->title }}">Settings</x-button>
                                @elseif(isset($mine[$call->id]))
                                    <x-button size="sm" :href="route('seed.applications.show', $mine[$call->id])">My application</x-button>
                                @elseif($call->acceptingConceptNotes())
                                    <x-button size="sm" variant="primary" :href="route('seed.calls.show', $call)">Read and apply</x-button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-card>
@endsection
