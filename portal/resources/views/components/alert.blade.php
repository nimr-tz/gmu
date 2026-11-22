{{--
    A banner that says what happened or what happens next.
      tone  info (blue) | ok | warn | danger | neutral
--}}
@props(['tone' => 'info', 'title' => null, 'icon' => null])
@php
    $tones = [
        'info'    => ['border-ok-line bg-ok-soft text-foreground', 'text-primary', 'info'],
        'ok'      => ['border-ok-line bg-ok-soft text-foreground', 'text-ok', 'check-circle'],
        'warn'    => ['border-warn-line bg-warn-soft text-warn-ink', 'text-warn', 'clock'],
        'danger'  => ['border-danger-line bg-danger-soft text-danger-ink', 'text-danger', 'alert-triangle'],
        'neutral' => ['border-border bg-card text-foreground', 'text-subtle', 'info'],
    ];
    [$box, $iconTone, $defaultIcon] = $tones[$tone] ?? $tones['info'];
@endphp
<div role="{{ in_array($tone, ['danger', 'warn']) ? 'alert' : 'status' }}" {{ $attributes->merge(['class' => "flex gap-3 rounded-lg border px-4 py-3 text-sm {$box}"]) }}>
    <x-icon :name="$icon ?? $defaultIcon" class="mt-0.5 size-4 {{ $iconTone }}" />
    <div class="min-w-0 flex-1">
        @if($title)<p class="font-semibold">{{ $title }}</p>@endif
        <div class="{{ $title ? 'mt-0.5' : '' }}">{{ $slot }}</div>
    </div>
    @isset($actions)<div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>@endisset
</div>
