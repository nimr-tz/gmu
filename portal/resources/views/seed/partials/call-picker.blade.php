{{--
    Choose which call a per-call page shows. Expects $call (shown now, or null for all calls)
    and $calls (the options). Pass allowAll => true to offer "All calls".
    Hidden when there is only one call.
--}}
@if($calls->count() > 1)
    <form method="GET" class="call-picker">
        <label for="call-picker" class="small muted">Call</label>
        <select id="call-picker" name="call" class="select select-sm" onchange="this.form.submit()">
            @if($allowAll ?? false)
                <option value="all" @selected(! $call)>All calls</option>
            @endif
            @foreach($calls as $option)
                <option value="{{ $option->id }}" @selected($call && $option->id === $call->id)>{{ $option->code }} · {{ $option->title }}{{ $option->status === 'draft' ? ' (draft)' : '' }}</option>
            @endforeach
        </select>
        <noscript><button type="submit" class="btn btn-sm">Show</button></noscript>
    </form>
@endif
