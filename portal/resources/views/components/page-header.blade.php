{{--
    The top of every page: optional back link and eyebrow, the title, one line
    that says where things stand, and the page's actions on the right.
--}}
@props(['title', 'description' => null, 'eyebrow' => null, 'back' => null, 'backLabel' => 'Back'])
<div {{ $attributes->merge(['class' => 'mb-6 flex flex-wrap items-end justify-between gap-x-6 gap-y-4']) }}>
    <div class="min-w-0">
        @if($back)
            <a href="{{ $back }}" class="mb-3 inline-flex items-center gap-1 text-[13px] font-medium text-muted-foreground hover:text-foreground">
                <x-icon name="arrow-left" class="size-3.5" /> {{ $backLabel }}
            </a>
        @endif
        @if($eyebrow)<p class="mb-1.5 text-xs font-medium uppercase tracking-wider text-muted-foreground">{{ $eyebrow }}</p>@endif
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-semibold tracking-tight sm:text-[28px] sm:leading-9">{{ $title }}</h1>
            {{ $badge ?? '' }}
        </div>
        @if($description)<p class="mt-1.5 max-w-3xl text-sm text-muted-foreground">{{ $description }}</p>@endif
        {{ $meta ?? '' }}
    </div>
    @isset($actions)<div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>@endisset
</div>
