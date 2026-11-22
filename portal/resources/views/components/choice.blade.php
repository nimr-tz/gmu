{{--
    One option in a set of radio cards. The card highlights itself when chosen (no script).
      <x-choice name="decision" value="revise" label="Ask the applicant to revise" sub="…" checked />
--}}
@props(['name', 'value', 'label', 'sub' => null, 'checked' => false, 'disabled' => false, 'tone' => 'primary'])
@php
    $on = $tone === 'danger'
        ? 'has-[:checked]:border-danger has-[:checked]:bg-danger-soft/60 has-[:checked]:ring-danger'
        : 'has-[:checked]:border-primary has-[:checked]:bg-primary-soft/60 has-[:checked]:ring-primary';
@endphp
<label {{ $attributes->merge(['class' => "flex cursor-pointer items-start gap-3 rounded-lg border border-border p-3 transition-colors hover:bg-muted/50 has-[:checked]:ring-1 has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-60 {$on}"]) }}>
    <input type="radio" class="radio mt-0.5" name="{{ $name }}" value="{{ $value }}" @checked($checked) @disabled($disabled)>
    <span class="min-w-0">
        <span class="block text-sm font-medium">{{ $label }}</span>
        @if($sub)<span class="mt-0.5 block text-xs text-muted-foreground">{{ $sub }}</span>@endif
        {{ $slot }}
    </span>
</label>
