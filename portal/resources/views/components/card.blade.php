{{--
    A card: a light grey head carries the title, description and actions;
    a white body holds the content; an optional footer strip sits below.
      title, description   the head (omit both for a bare card)
      icon                 a small outlined icon before the title
      actions slot         controls on the right of the head
      footer slot          a quiet strip under the body
      flush                no padding in the body, for tables and lists that run edge to edge
      tone                 danger | warn | ok to edge the card in that status colour
--}}
@props(['title' => null, 'description' => null, 'flush' => false, 'tone' => null, 'icon' => null])
@php
    $edge = ['danger' => 'border-danger-line bg-danger-soft', 'warn' => 'border-warn-line bg-warn-soft', 'ok' => 'border-ok-line bg-ok-soft'][$tone] ?? 'border-border bg-muted';
    $line = ['danger' => 'border-danger-line', 'warn' => 'border-warn-line', 'ok' => 'border-ok-line'][$tone] ?? 'border-border';
    $head = $title || $description || isset($actions);
@endphp
<section {{ $attributes->merge(['class' => "flex flex-col overflow-hidden rounded-xl border {$edge}"]) }}>
    @if($head)
        <header class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2 px-4 py-3 sm:flex-nowrap">
            <div class="flex min-w-0 flex-1 items-center gap-2.5">
                @if($icon)
                    <span class="grid size-8 shrink-0 place-items-center rounded-lg border border-border bg-card text-gray-600"><x-icon :name="$icon" class="size-4" /></span>
                @endif
                <div class="min-w-0">
                    @if($title)<h2 class="text-sm font-semibold leading-5">{{ $title }}</h2>@endif
                    @if($description)<p class="text-[13px] leading-5 text-muted-foreground">{{ $description }}</p>@endif
                </div>
            </div>
            @isset($actions)<div class="ml-auto flex shrink-0 flex-wrap items-center gap-1.5">{{ $actions }}</div>@endisset
        </header>
    @endif

    <div class="flex-1 bg-card {{ $head ? 'border-t ' . $line : '' }} {{ $flush ? '' : 'p-5' }}">{{ $slot }}</div>

    @isset($footer)
        <footer {{ $footer->attributes->merge(['class' => 'border-t border-border bg-muted px-4 py-2.5 text-[13px] text-muted-foreground']) }}>{{ $footer }}</footer>
    @endisset
</section>
