{{--
    A thin progress bar. value is a percentage. tone: primary | review | warn | danger | muted | tint (the call's own hue, inside a .tint-N)
--}}
@props(['value' => 0, 'tone' => 'primary', 'label' => null])
@php
    $pct = max(0, min(100, (float) $value));
    $bar = ['primary' => 'bg-primary', 'review' => 'bg-review', 'warn' => 'bg-warn', 'danger' => 'bg-danger', 'muted' => 'bg-gray-400', 'tint' => 'bg-tint-solid'][$tone] ?? 'bg-primary';
@endphp
<div {{ $attributes->merge(['class' => 'h-1.5 w-full overflow-hidden rounded-full bg-gray-100']) }}
     role="progressbar" aria-valuenow="{{ round($pct) }}" aria-valuemin="0" aria-valuemax="100" @if($label) aria-label="{{ $label }}" @endif>
    <div class="h-full rounded-full {{ $bar }}" style="width: {{ $pct }}%"></div>
</div>
