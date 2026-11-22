{{--
    Choose which call a per-call page shows. Expects $call (shown now) and $calls (the options).
    Hidden when there is only one call.
--}}
@if($calls->count() > 1)
    <form method="GET" class="call-picker">
        <label for="call-picker" class="small muted">Call</label>
        <select id="call-picker" name="call" class="select select-sm" onchange="this.form.submit()">
            @foreach($calls as $option)
                <option value="{{ $option->id }}" @selected($option->id === $call->id)>{{ $option->title }} ({{ $option->code }}){{ $option->status === 'draft' ? ' · draft' : '' }}</option>
            @endforeach
        </select>
        <noscript><button type="submit" class="btn btn-sm">Show</button></noscript>
    </form>
@endif
