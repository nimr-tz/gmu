{{--
    A menu that opens from a trigger (Alpine). Put <x-dropdown-item>s in the slot.
      align  right | left      direction  down | up
--}}
@props(['align' => 'right', 'width' => 'w-56', 'direction' => 'down'])
<div x-data="{ open: false }" {{ $attributes->merge(['class' => 'relative']) }} @keydown.escape.window="open = false" @click.outside="open = false">
    <div @click="open = !open">{{ $trigger }}</div>
    <div x-cloak x-show="open" x-transition.opacity.duration.100ms
         class="absolute z-50 {{ $direction === 'up' ? 'bottom-full mb-2' : 'mt-2' }} {{ $width }} {{ $align === 'left' ? 'left-0' : 'right-0' }} animate-fade-in rounded-lg border border-border bg-card p-1 shadow-pop">
        {{ $slot }}
    </div>
</div>
