{{--
    A key figure as a tinted tile: a dot and a title, a small chip on the right,
    the figure in the tile's colour, and one plain line of context.
      title           what the figure is ("Needs you")
      value           the figure
      note            one line that says what it means
      badge           a short chip on the right ("2 to screen")
      tone            blue | violet | sky | pink | amber | rose | neutral
                      Amber only when the figure is something waiting on someone;
                      rose only when something is stopped, late or blocked.
      meter           optional percentage, drawn as a thin bar under the figure
      href            makes the whole tile a link
    Keep it to what people need: no ratios or medians that need explaining.
--}}
@props([
    'title', 'value', 'note' => null, 'badge' => null,
    'tone' => 'blue', 'meter' => null, 'href' => null,
])
@php
    $tones = [
        'blue'    => ['tile' => 'border-blue-100 bg-blue-50',     'dot' => 'bg-primary',   'chip' => 'bg-blue-100 text-blue-700',     'value' => 'text-blue-700',   'track' => 'bg-blue-100',   'bar' => 'bg-primary'],
        'violet'  => ['tile' => 'border-violet-100 bg-violet-50', 'dot' => 'bg-violet-500', 'chip' => 'bg-violet-100 text-violet-700', 'value' => 'text-violet-700', 'track' => 'bg-violet-100', 'bar' => 'bg-violet-500'],
        'sky'     => ['tile' => 'border-sky-100 bg-sky-50',       'dot' => 'bg-sky-500',    'chip' => 'bg-sky-100 text-sky-700',       'value' => 'text-sky-700',    'track' => 'bg-sky-100',    'bar' => 'bg-sky-500'],
        'pink'    => ['tile' => 'border-pink-100 bg-pink-50',     'dot' => 'bg-pink-500',   'chip' => 'bg-pink-100 text-pink-700',     'value' => 'text-pink-700',   'track' => 'bg-pink-100',   'bar' => 'bg-pink-500'],
        'amber'   => ['tile' => 'border-amber-200 bg-amber-50',   'dot' => 'bg-amber-500',  'chip' => 'bg-amber-100 text-amber-800',   'value' => 'text-amber-800',  'track' => 'bg-amber-100',  'bar' => 'bg-amber-500'],
        'rose'    => ['tile' => 'border-danger-line bg-danger-soft', 'dot' => 'bg-danger',  'chip' => 'bg-red-100 text-danger-ink',    'value' => 'text-danger-ink', 'track' => 'bg-red-100',    'bar' => 'bg-danger'],
        'neutral' => ['tile' => 'border-border bg-muted',         'dot' => 'bg-gray-400',   'chip' => 'bg-gray-100 text-gray-700',     'value' => 'text-foreground', 'track' => 'bg-gray-200',   'bar' => 'bg-gray-500'],
    ];
    $t = $tones[$tone] ?? $tones['blue'];
    // Figures (with digits) are large; words ("Revision requested") a size smaller, so they fit one line.
    $size = preg_match('/\d/', (string) $value) ? 'text-[28px] leading-9' : 'text-[22px] leading-8';
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => "flex flex-col gap-1.5 rounded-xl border px-4 py-3.5 {$t['tile']}" . ($href ? ' transition-shadow hover:shadow-sm' : '')]) }}>
    <div class="flex items-center gap-2">
        <span class="size-2 shrink-0 rounded-full {{ $t['dot'] }}" aria-hidden="true"></span>
        <span class="min-w-0 flex-1 text-[13.5px] font-medium leading-snug text-foreground">{{ $title }}</span>
        @if($badge !== null)<span class="inline-flex h-[22px] shrink-0 items-center rounded-md px-2 text-xs font-medium {{ $t['chip'] }}">{{ $badge }}</span>@endif
    </div>
    <span class="break-words {{ $size }} font-semibold tracking-tight tabular-nums {{ $t['value'] }}">{{ $value }}</span>
    @if($meter !== null)
        <div class="h-1.5 w-full overflow-hidden rounded-full {{ $t['track'] }}" role="progressbar" aria-valuenow="{{ round($meter) }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $title }}">
            <div class="h-full rounded-full {{ $t['bar'] }}" style="width: {{ max(0, min(100, $meter)) }}%"></div>
        </div>
    @endif
    @if($note)<span class="text-[13px] text-muted-foreground">{{ $note }}</span>@endif
</{{ $tag }}>
