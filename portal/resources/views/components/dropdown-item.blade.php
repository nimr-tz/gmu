@props(['href' => null, 'icon' => null])
@php $class = 'flex w-full items-center gap-2.5 rounded-md px-2.5 py-2 text-left text-sm text-foreground hover:bg-muted [&_svg]:size-4 [&_svg]:text-subtle'; @endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>@if($icon)<x-icon :name="$icon" />@endif{{ $slot }}</a>
@else
    <button type="submit" {{ $attributes->merge(['class' => $class]) }}>@if($icon)<x-icon :name="$icon" />@endif{{ $slot }}</button>
@endif
