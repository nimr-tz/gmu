{{--
    Choose which call a per-call page shows. Expects $call (shown now, or null for all calls)
    and $calls (the options). Pass allowAll => true to offer "All calls".
    Hidden when there is only one call.
--}}
@if($calls->count() > 1)
    <form method="GET" class="flex items-center gap-2">
        @foreach(request()->except('call', 'page') as $k => $v)
            @if(is_string($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
        @endforeach
        <label for="call-picker" class="text-[13px] text-muted-foreground">Call</label>
        <select id="call-picker" name="call" class="select select-sm w-auto max-w-[22rem]" onchange="this.form.submit()">
            @if($allowAll ?? false)
                <option value="all" @selected(! $call)>All calls</option>
            @endif
            @foreach($calls as $option)
                <option value="{{ $option->id }}" @selected($call && $option->id === $call->id)>{{ $option->code }} · {{ $option->title }}{{ $option->status === 'draft' ? ' (draft)' : '' }}</option>
            @endforeach
        </select>
        <noscript><x-button type="submit" size="sm">Show</x-button></noscript>
    </form>
@endif
