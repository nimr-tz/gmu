{{--
    The questions of one stage on the call settings page: use, wording, word limit.
    Expects $stage ('concept' or 'proposal') and from the page: $f, $isOn, $locked.
--}}
<div class="table-wrap">
    <table class="table settings-table">
        <thead><tr><th>Use</th><th>Question</th><th class="num">Word limit</th></tr></thead>
        <tbody>
            @foreach(data_get($form->config(), "questions.$stage") as $key => $q)
                @php $base = "features[questions][$stage][$key]"; @endphp
                <tr>
                    <td>
                        <input type="hidden" name="{{ $base }}[on]" value="0">
                        <input type="checkbox" class="switch-box" name="{{ $base }}[on]" value="1" aria-label="Ask {{ $q['heading'] }}" @checked($isOn("questions.$stage.$key.on")) @disabled($locked)>
                    </td>
                    <td class="settings-question">
                        <input class="input" name="{{ $base }}[heading]" value="{{ $f("questions.$stage.$key.heading") }}" aria-label="Heading" @disabled($locked)>
                        <textarea class="textarea" rows="2" name="{{ $base }}[question]" aria-label="Question for {{ $q['heading'] }}" @disabled($locked)>{{ $f("questions.$stage.$key.question") }}</textarea>
                    </td>
                    <td class="num">
                        <input type="number" class="input input-num" name="{{ $base }}[limit]" value="{{ $f("questions.$stage.$key.limit") }}"
                               min="{{ $locked ? $q['limit'] : 10 }}" max="5000" aria-label="Word limit for {{ $q['heading'] }}">
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
