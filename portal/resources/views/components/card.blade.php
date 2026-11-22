{{--
    The one surface: a quiet frame that carries the header, around a white panel
    that holds the content. Supporting figures or notes sit in the frame below.
      title, description   the header (omit both for a bare card)
      icon                 a small outlined icon before the title
      actions slot         controls on the right of the header
      footer slot          a strip in the frame, under the panel
      flush                no padding in the panel, for tables and lists that run edge to edge
      tone                 danger | warn | ok to edge the card in that status colour
--}}
@props(['title' => null, 'description' => null, 'flush' => false, 'tone' => null, 'icon' => null])
@php
    $edge = ['danger' => 'border-danger-line bg-danger-soft/50', 'warn' => 'border-warn-line bg-warn-soft/50', 'ok' => 'border-ok-line bg-ok-soft/50'][$tone] ?? 'border-border bg-muted';
    $head = $title || $description || isset($actions);
@endphp
<section {{ $attributes->merge(['class' => "flex flex-col rounded-xl border p-1 {$edge}"]) }}>
    @if($head)
        <header class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 px-3 pb-2.5 pt-2">
            <div class="flex min-w-0 items-center gap-2.5">
                @if($icon)
                    <span class="grid size-8 shrink-0 place-items-center rounded-lg border border-border bg-card text-gray-600 shadow-xs"><x-icon :name="$icon" class="size-4" /></span>
                @endif
                <div class="min-w-0">
                    @if($title)<h2 class="text-sm font-semibold leading-5">{{ $title }}</h2>@endif
                    @if($description)<p class="text-[13px] leading-5 text-muted-foreground">{{ $description }}</p>@endif
                </div>
            </div>
            @isset($actions)<div class="flex shrink-0 flex-wrap items-center gap-1.5">{{ $actions }}</div>@endisset
        </header>
    @endif

    <div class="flex-1 overflow-hidden rounded-lg border border-border bg-card shadow-xs {{ $flush ? '' : 'p-5' }}">{{ $slot }}</div>

    @isset($footer)
        <footer {{ $footer->attributes->merge(['class' => 'px-3 pb-1.5 pt-2.5 text-[13px] text-muted-foreground']) }}>{{ $footer }}</footer>
    @endisset
</section>
