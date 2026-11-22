{{--
    A labelled figure inside a card or a row of facts.
--}}
@props(['label', 'value' => null, 'note' => null, 'mono' => false])
<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    <dt class="text-xs text-muted-foreground">{{ $label }}</dt>
    <dd class="mt-1 text-[15px] font-semibold {{ $mono ? 'font-mono tabular-nums' : '' }}">{{ $value ?? $slot }}</dd>
    @if($note)<dd class="mt-0.5 text-xs text-muted-foreground">{{ $note }}</dd>@endif
</div>
