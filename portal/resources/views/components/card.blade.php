{{--
    A surface for one piece of content.
      title, description   the header (omit both for a bare card)
      actions slot         buttons on the right of the header
      footer slot          a quiet strip at the bottom
      flush                no padding, for tables and lists that run edge to edge
      tone                 danger | warn | ok to edge the card in that status colour
--}}
@props(['title' => null, 'description' => null, 'flush' => false, 'tone' => null, 'icon' => null])
@php
    $edge = ['danger' => 'border-danger-line', 'warn' => 'border-warn-line', 'ok' => 'border-ok-line'][$tone] ?? 'border-border';
@endphp
<section {{ $attributes->merge(['class' => "overflow-hidden rounded-xl border bg-card shadow-xs {$edge}"]) }}>
    @if($title || $description || isset($actions))
        <header class="flex flex-wrap items-start justify-between gap-3 px-5 pb-4 pt-5 {{ $flush ? 'border-b border-border' : '' }}">
            <div class="flex min-w-0 items-start gap-3">
                @if($icon)
                    <span class="grid size-9 shrink-0 place-items-center rounded-lg border border-border bg-card text-gray-600 shadow-xs"><x-icon :name="$icon" class="size-[18px]" /></span>
                @endif
                <div class="min-w-0">
                    @if($title)<h2 class="text-[15px] font-semibold leading-6">{{ $title }}</h2>@endif
                    @if($description)<p class="mt-0.5 text-[13px] text-muted-foreground">{{ $description }}</p>@endif
                </div>
            </div>
            @isset($actions)<div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>@endisset
        </header>
    @endif

    @if($flush)
        {{ $slot }}
    @else
        <div class="px-5 pb-5 {{ $title || $description || isset($actions) ? '' : 'pt-5' }}">{{ $slot }}</div>
    @endif

    @isset($footer)
        <footer {{ $footer->attributes->merge(['class' => 'border-t border-border bg-muted/50 px-5 py-3 text-[13px] text-muted-foreground']) }}>{{ $footer }}</footer>
    @endisset
</section>
