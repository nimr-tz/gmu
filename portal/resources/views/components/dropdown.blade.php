{{--
    A menu that opens from a trigger (Alpine). Put <x-dropdown-item>s in the slot.
      align  right | left
--}}
@props(['align' => 'right', 'width' => 'w-56'])
<div x-data="{ open: false }" class="relative" @keydown.escape.window="open = false" @click.outside="open = false">
    <div @click="open = !open">{{ $trigger }}</div>
    <div x-cloak x-show="open" x-transition.opacity.duration.100ms
         class="absolute z-50 mt-2 {{ $width }} {{ $align === 'left' ? 'left-0' : 'right-0' }} origin-top-right animate-fade-in rounded-lg border border-border bg-card p-1 shadow-pop">
        {{ $slot }}
    </div>
</div>
