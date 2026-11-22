{{--
    The top of a page: optional back link and eyebrow, the title, one line that
    says where things stand, and the page's actions on the right. Kept small:
    the cards below carry the detail.
--}}
@props(['title', 'description' => null, 'eyebrow' => null, 'back' => null, 'backLabel' => 'Back'])
<div {{ $attributes->merge(['class' => 'mb-5 flex flex-wrap items-end justify-between gap-x-6 gap-y-3']) }}>
    <div class="min-w-0">
        @if($back)
            <a href="{{ $back }}" class="mb-2 inline-flex items-center gap-1 text-[13px] text-muted-foreground hover:text-foreground">
                <x-icon name="arrow-left" class="size-3.5" /> {{ $backLabel }}
            </a>
        @endif
        @if($eyebrow)<p class="mb-1 text-[13px] text-muted-foreground">{{ $eyebrow }}</p>@endif
        <div class="flex flex-wrap items-center gap-2.5">
            <h1 class="text-[22px] font-semibold leading-[30px] tracking-tight">{{ $title }}</h1>
            {{ $badge ?? '' }}
        </div>
        @if($description)<p class="mt-0.5 max-w-3xl text-[13.5px] text-muted-foreground">{{ $description }}</p>@endif
        {{ $meta ?? '' }}
    </div>
    @isset($actions)<div class="flex flex-wrap items-center gap-1.5">{{ $actions }}</div>@endisset
</div>
