@extends('layouts.app')

{{-- An applicant with applications in more than one call. --}}

@section('title', 'My applications')

@section('content')
<x-page-header title="My applications" description="One application per call. Open one to see where it stands." />

<x-card flush>
    <ul class="divide-y divide-border">
        @foreach($applications as $application)
            <li>
                <a class="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-4 hover:bg-muted/50"
                   href="{{ route($application->isEditableByApplicant() ? 'seed.applications.edit' : 'seed.applications.show', $application) }}">
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-medium">{{ $application->title ?: 'Untitled concept note' }}</span>
                        <span class="mt-1 flex flex-wrap items-center gap-2 text-[13px] text-muted-foreground">
                            @include('seed.partials.call-tag', ['call' => $application->call, 'short' => true])
                            <span class="font-mono text-xs">{{ $application->reference }}</span> · {{ $application->call->title }}
                        </span>
                    </span>
                    @include('seed.partials.status')
                    <x-icon name="chevron-right" class="text-subtle" />
                </a>
            </li>
        @endforeach
    </ul>
</x-card>
@endsection
