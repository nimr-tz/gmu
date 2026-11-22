@extends('layouts.gmu')

{{-- An applicant with applications in more than one call. --}}

@section('title', 'My applications')

@section('content')
<x-ui.page-head title="My applications" lede="One application per call. Open one to see where it stands." />

<div class="card">
    <div class="list">
        @foreach($applications as $application)
            <a class="list-row" href="{{ route($application->isEditableByApplicant() ? 'seed.applications.edit' : 'seed.applications.show', $application) }}">
                <span class="list-main">
                    <span class="list-title">{{ $application->title ?: 'Untitled concept note' }}</span>
                    <span class="list-sub"><span class="mono">{{ $application->reference }}</span> · {{ $application->call->title }}</span>
                </span>
                @include('seed.partials.status')
            </a>
        @endforeach
    </div>
</div>
@endsection
