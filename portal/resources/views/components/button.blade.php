{{--
    The one button. A link when given href, otherwise a <button>.
      variant  primary (ink) | accent (brand blue) | secondary | outline | ghost | danger | link
      size     sm | md | lg | icon | icon-sm
      icon / iconRight  Lucide names
    Label it with what it does: "Submit concept note", not "Submit".
--}}
@props([
    'variant' => 'secondary', 'size' => 'md', 'href' => null, 'type' => 'button',
    'icon' => null, 'iconRight' => null,
])
@php
    $base = 'inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md font-medium transition-colors '
          . 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 '
          . 'disabled:pointer-events-none disabled:opacity-50 aria-disabled:pointer-events-none aria-disabled:opacity-50';
    $variants = [
        'primary'   => 'bg-foreground text-white shadow-xs hover:bg-gray-800',
        'accent'    => 'bg-primary text-primary-foreground shadow-xs hover:bg-blue-700',
        'secondary' => 'border border-input bg-card text-foreground shadow-xs hover:bg-muted',
        'outline'   => 'border border-input bg-transparent text-foreground hover:bg-muted',
        'ghost'     => 'text-muted-foreground hover:bg-muted hover:text-foreground',
        'danger'    => 'border border-danger-line bg-card text-danger-ink shadow-xs hover:bg-danger-soft',
        'link'      => 'text-primary underline-offset-4 hover:underline px-0 h-auto',
    ];
    $sizes = [
        'sm'      => 'h-7 px-2.5 text-xs [&_svg]:size-3.5',
        'md'      => 'h-8 px-3 text-[13px] [&_svg]:size-4',
        'lg'      => 'h-10 px-4 text-sm [&_svg]:size-4',
        'icon'    => 'size-8 [&_svg]:size-4',
        'icon-sm' => 'size-7 [&_svg]:size-3.5',
    ];
    $class = $base . ' ' . ($variants[$variant] ?? $variants['secondary']) . ' ' . ($variant === 'link' ? '' : ($sizes[$size] ?? $sizes['md']));
@endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>
        @if($icon)<x-icon :name="$icon" />@endif{{ $slot }}@if($iconRight)<x-icon :name="$iconRight" />@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $class]) }}>
        @if($icon)<x-icon :name="$icon" />@endif{{ $slot }}@if($iconRight)<x-icon :name="$iconRight" />@endif
    </button>
@endif
