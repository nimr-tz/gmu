{{--
    One feature switch on the call settings page.
    Expects $path (dot path in the call's config, e.g. "mentor.on"), $label, $help,
    and from the page: $isOn, $locked. Pass row => true to wrap it as its own row.
--}}
@php $name = 'features[' . str_replace('.', '][', $path) . ']'; @endphp
@if($row ?? false)<div class="feature">@endif
<label class="feature-name check">
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" class="switch-box" name="{{ $name }}" value="1" @checked($isOn($path)) @disabled($locked)>
    <span><strong>{{ $label }}</strong>@if($help)<span class="help">{{ $help }}</span>@endif</span>
</label>
@if($row ?? false)</div>@endif
