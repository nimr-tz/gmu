{{--
    What an empty list says: what will appear here, and when.
--}}
@props(['icon' => 'inbox', 'title' => null, 'compact' => false])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center text-center ' . ($compact ? 'px-4 py-8' : 'px-6 py-14')]) }}>
    <span class="mb-4 grid size-11 place-items-center rounded-xl border border-border bg-card text-subtle shadow-xs"><x-icon :name="$icon" class="size-5" /></span>
    @if($title)<p class="text-sm font-semibold">{{ $title }}</p>@endif
    <div class="mt-1 max-w-md text-[13px] text-muted-foreground">{{ $slot }}</div>
    @isset($action)<div class="mt-5">{{ $action }}</div>@endisset
</div>
