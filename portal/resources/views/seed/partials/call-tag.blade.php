{{--
    How a call is named in any list: its code as a small tag, then its title.
    Expects $call. Pass short => true for the tag alone (the title goes in a tooltip).
--}}
<span class="call-tag {{ $call->tintClass() }}" @if($short ?? false) title="{{ $call->title }}" @endif>
    <span class="call-code">{{ $call->code }}</span>@unless($short ?? false)<span class="call-name">{{ $call->title }}</span>@endunless
</span>
