{{--
    A small label with a meaning.
      tone  ok (blue, done) | warn (amber, waiting) | danger (red, stopped or late)
            | review (violet, under review) | neutral (grey, in progress) | primary | outline
      dot   true for a leading dot
--}}
@props(['tone' => 'neutral', 'dot' => false, 'icon' => null])
@php
    $tones = [
        'ok'      => 'bg-ok-soft text-ok-ink',
        'warn'    => 'bg-warn-soft text-warn-ink',
        'danger'  => 'bg-danger-soft text-danger-ink',
        'review'  => 'bg-review-soft text-review-ink',
        'neutral' => 'bg-neutral-soft text-neutral-ink',
        'primary' => 'bg-primary text-primary-foreground',
        'outline' => 'bg-card text-foreground ring-1 ring-inset ring-input',
    ];
    $dots = ['ok' => 'bg-ok', 'warn' => 'bg-warn', 'danger' => 'bg-danger', 'review' => 'bg-review', 'neutral' => 'bg-neutral', 'primary' => 'bg-white', 'outline' => 'bg-subtle'];
    $tone = array_key_exists($tone, $tones) ? $tone : 'neutral';
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex h-[22px] items-center gap-1.5 whitespace-nowrap rounded-md px-2 text-xs font-medium [&_svg]:size-3.5 ' . $tones[$tone]]) }}>
    @if($dot)<span class="size-1.5 rounded-full {{ $dots[$tone] }}" aria-hidden="true"></span>@endif
    @if($icon)<x-icon :name="$icon" />@endif
    {{ $slot }}
</span>
