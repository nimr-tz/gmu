{{--
    One feature switch on the call settings page (ink when on, as the "GMU Calls" design draws it).
    Expects $path (dot path in the call's config, e.g. "mentor.on"), $label, $help,
    and from the page: $isOn, $locked. Pass row => true to wrap it as its own row.
--}}
@php $name = 'features[' . str_replace('.', '][', $path) . ']'; @endphp
@if($row ?? false)<div class="border-t border-gray-100 px-[18px] py-3.5">@endif
<label class="flex flex-[1_1_360px] items-start gap-3 {{ $locked ? 'cursor-not-allowed' : 'cursor-pointer' }}">
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" class="switch mt-px checked:bg-foreground" name="{{ $name }}" value="1" @checked($isOn($path)) @disabled($locked)>
    <span class="min-w-0">
        <span class="block text-sm font-medium">{{ $label }}</span>
        @if($help)<span class="mt-0.5 block text-[13px] leading-normal text-muted-foreground">{{ $help }}</span>@endif
    </span>
</label>
@if($row ?? false)</div>@endif
