{{--
    A key figure: the card frame with the title in it, a white panel with the
    figure, and two supporting figures in the frame below.
      icon, title, caption   the head
      value, badge           the figure and a small badge beside it (badgeTone: ok | warn | danger | neutral)
      note                   one line of context
      foot                   [label => figure, label => figure]
      href                   makes the head a link
--}}
@props([
    'icon', 'title', 'caption' => null, 'value',
    'badge' => null, 'badgeTone' => 'neutral', 'badgeIcon' => null,
    'note' => null, 'foot' => [], 'href' => null,
])
<section {{ $attributes->merge(['class' => 'flex flex-col rounded-xl border border-border bg-muted p-1']) }}>
    <div class="flex items-center gap-2.5 px-2.5 pb-2 pt-1.5">
        <span class="grid size-8 shrink-0 place-items-center rounded-lg border border-border bg-card text-gray-600 shadow-xs"><x-icon :name="$icon" class="size-4" /></span>
        <div class="min-w-0 flex-1 leading-tight">
            <div class="text-sm font-semibold">{{ $title }}</div>
            @if($caption)<div class="truncate text-xs text-muted-foreground">{{ $caption }}</div>@endif
        </div>
        @if($href)
            <a href="{{ $href }}" class="grid size-7 place-items-center rounded-md border border-border bg-card text-subtle shadow-xs hover:text-foreground" aria-label="Open {{ $title }}"><x-icon name="chevron-right" class="size-3.5" /></a>
        @endif
    </div>
    <div class="flex-1 rounded-lg border border-border bg-card px-4 py-4 shadow-xs">
        <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1.5">
            <span class="text-[26px] font-semibold leading-none tracking-tight tabular-nums">{{ $value }}</span>
            @if($badge !== null)<x-badge :tone="$badgeTone" :icon="$badgeIcon">{{ $badge }}</x-badge>@endif
        </div>
        @if($note)<p class="mt-2.5 text-[13px] text-muted-foreground">{{ $note }}</p>@endif
    </div>
    @if($foot)
        <dl class="flex flex-wrap justify-between gap-x-4 px-3 pb-1 pt-2 text-[13px]">
            @foreach($foot as $label => $figure)
                <div class="flex items-baseline gap-1.5">
                    <dt class="text-muted-foreground">{{ $label }}</dt>
                    <dd class="font-mono font-medium tabular-nums">{{ $figure }}</dd>
                </div>
            @endforeach
        </dl>
    @endif
</section>
