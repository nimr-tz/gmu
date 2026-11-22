{{--
    A small segmented control for switching views or filters ("All | Flagged").
      items   [url => label] or [url => [label, count]]
      active  the url that is current
--}}
@props(['items', 'active' => null, 'label' => 'Filter'])
<nav {{ $attributes->merge(['class' => 'inline-flex max-w-full overflow-x-auto rounded-lg border border-border bg-card p-0.5 shadow-xs']) }} aria-label="{{ $label }}">
    @foreach($items as $url => $item)
        @php [$text, $count] = is_array($item) ? $item + [1 => null] : [$item, null]; $on = $url === $active; @endphp
        <a href="{{ $url }}" @if($on) aria-current="page" @endif
           class="inline-flex h-7 items-center gap-1.5 whitespace-nowrap rounded-md px-2.5 text-[13px] transition-colors {{ $on ? 'bg-muted font-medium text-foreground ring-1 ring-inset ring-border' : 'text-muted-foreground hover:text-foreground' }}">
            {{ $text }}@if($count !== null)<span class="font-mono text-xs {{ $on ? 'text-foreground' : 'text-subtle' }}">{{ $count }}</span>@endif
        </a>
    @endforeach
</nav>
