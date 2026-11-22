{{--
    How a call is named in any list: its code in the call's tint, then its title.
    Expects $call. Pass short => true for the code alone (the title goes in a tooltip).
--}}
<span class="{{ $call->tintClass() }} inline-flex min-w-0 max-w-full items-center gap-2" @if($short ?? false) title="{{ $call->title }}" @endif>
    <span class="shrink-0 rounded-md bg-tint-chip px-1.5 py-0.5 font-mono text-xs font-medium text-tint-ink">{{ $call->code }}</span>
    @unless($short ?? false)<span class="truncate font-medium text-foreground">{{ $call->title }}</span>@endunless
</span>
