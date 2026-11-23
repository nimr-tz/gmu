{{--
    Choose which call a per-call page shows. Expects $call (shown now, or null for all calls)
    and $calls (the options). Pass allowAll => true to offer "All calls", and dark => true
    on an ink band (the GMU pages), where each call is named by its code and financial year.
    Hidden when there is only one call.
--}}
@if($calls->count() > 1)
    <form method="GET" class="flex items-center gap-2">
        @foreach(request()->except('call', 'page') as $k => $v)
            @if(is_string($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
        @endforeach
        <label for="call-picker" class="{{ ($dark ?? false) ? 'sr-only' : 'text-[13px] text-muted-foreground' }}">Call</label>
        <select id="call-picker" name="call" onchange="this.form.submit()"
                class="{{ ($dark ?? false) ? 'h-9 w-auto max-w-[22rem] rounded-lg border border-gray-700 bg-gray-800 pl-2.5 pr-8 text-[13px] text-white focus:border-primary focus:ring-primary/20' : 'select select-sm w-auto max-w-[22rem]' }}">
            @if($allowAll ?? false)
                <option value="all" @selected(! $call)>All calls</option>
            @endif
            @foreach($calls as $option)
                <option value="{{ $option->id }}" @selected($call && $option->id === $call->id)>{{ $option->code }} · {{ ($dark ?? false) ? 'FY ' . $option->fiscal_year : $option->title }}{{ $option->status === 'draft' ? ' (draft)' : '' }}</option>
            @endforeach
        </select>
        <noscript><x-button type="submit" size="sm">Show</x-button></noscript>
    </form>
@endif
