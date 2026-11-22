{{-- The one way to show an application's status. Label from STATUS_LABELS, colour from STATUS_TONES. --}}
@php
    // STATUS_TONES → badge tones. In-progress states ('' and 'info') are grey, not blue.
    $tone = ['ok' => 'ok', 'warn' => 'warn', 'danger' => 'danger', 'navy' => 'review', 'info' => 'neutral'][$application->statusTone()] ?? 'neutral';
@endphp
<x-badge :tone="$tone" dot>{{ $application->statusLabel() }}</x-badge>
