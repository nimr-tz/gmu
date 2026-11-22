{{--
    A form field: label, the control (slot), help, and the error for `name`.
--}}
@props(['label' => null, 'name' => null, 'for' => null, 'help' => null, 'required' => false, 'optional' => false])
@php $for ??= $name ? str_replace(['[', ']'], ['_', ''], $name) : null; $key = $name ? str_replace(['[', ']'], ['.', ''], $name) : null; @endphp
<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if($label)
        <label class="label" @if($for) for="{{ $for }}" @endif>
            {{ $label }}@if($required)<span class="text-danger" aria-hidden="true"> *</span>@endif
            @if($optional)<span class="font-normal text-muted-foreground"> (optional)</span>@endif
        </label>
    @endif
    {{ $slot }}
    @if($key)@error($key)<span class="error-text">{{ $message }}</span>@enderror @endif
    @if($help)<span class="help">{{ $help }}</span>@endif
</div>
