{{--
    A key figure: a white card inset in a soft frame, with two supporting figures beneath.
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
<section {{ $attributes->merge(['class' => 'flex flex-col rounded-xl border border-border bg-muted/70 p-1 shadow-xs']) }}>
    <div class="flex flex-1 flex-col gap-4 rounded-lg border border-border bg-card p-4 shadow-xs">
        <div class="flex items-center gap-3">
            <span class="grid size-9 shrink-0 place-items-center rounded-lg border border-border text-gray-600 shadow-xs"><x-icon :name="$icon" class="size-[18px]" /></span>
            <div class="min-w-0 flex-1 leading-tight">
                <div class="text-sm font-semibold">{{ $title }}</div>
                @if($caption)<div class="truncate text-xs text-muted-foreground">{{ $caption }}</div>@endif
            </div>
            @if($href)
                <a href="{{ $href }}" class="grid size-7 place-items-center rounded-md text-subtle hover:bg-muted hover:text-foreground" aria-label="Open {{ $title }}"><x-icon name="chevron-right" /></a>
            @endif
        </div>
        <div>
            <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1.5">
                <span class="text-[28px] font-semibold leading-none tracking-tight tabular-nums">{{ $value }}</span>
                @if($badge !== null)<x-badge :tone="$badgeTone" :icon="$badgeIcon">{{ $badge }}</x-badge>@endif
            </div>
            @if($note)<p class="mt-2 text-xs text-muted-foreground">{{ $note }}</p>@endif
        </div>
    </div>
    @if($foot)
        <dl class="grid grid-cols-2 divide-x divide-border px-3 pb-1.5 pt-2.5">
            @foreach($foot as $label => $figure)
                <div class="{{ $loop->first ? 'pr-3' : 'pl-3' }}">
                    <dt class="text-xs text-muted-foreground">{{ $label }}</dt>
                    <dd class="mt-0.5 font-mono text-[13px] font-medium tabular-nums">{{ $figure }}</dd>
                </div>
            @endforeach
        </dl>
    @endif
</section>
