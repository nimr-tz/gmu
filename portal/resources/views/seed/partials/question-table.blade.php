{{--
    The questions of one stage on the call settings page: use, wording, word limit.
    Expects $stage ('concept' or 'proposal') and from the page: $f, $isOn, $locked.
--}}
<div class="overflow-x-auto">
    <table class="data-table min-w-[640px]">
        <thead><tr><th class="w-14">Use</th><th>Question</th><th class="w-32 text-right">Word limit</th></tr></thead>
        <tbody>
            @foreach(data_get($form->config(), "questions.$stage") as $key => $q)
                @php $base = "features[questions][$stage][$key]"; @endphp
                <tr class="hover:bg-transparent">
                    <td class="align-top">
                        <input type="hidden" name="{{ $base }}[on]" value="0">
                        <input type="checkbox" class="switch mt-2" name="{{ $base }}[on]" value="1" aria-label="Ask {{ $q['heading'] }}" @checked($isOn("questions.$stage.$key.on")) @disabled($locked)>
                    </td>
                    <td class="space-y-2 align-top">
                        <input class="input font-medium" name="{{ $base }}[heading]" value="{{ $f("questions.$stage.$key.heading") }}" aria-label="Heading" @disabled($locked)>
                        <textarea class="textarea text-[13px]" rows="2" name="{{ $base }}[question]" aria-label="Question for {{ $q['heading'] }}" @disabled($locked)>{{ $f("questions.$stage.$key.question") }}</textarea>
                    </td>
                    <td class="align-top">
                        <input type="number" class="input text-right font-mono" name="{{ $base }}[limit]" value="{{ $f("questions.$stage.$key.limit") }}"
                               min="{{ $locked ? $q['limit'] : 10 }}" max="5000" aria-label="Word limit for {{ $q['heading'] }}">
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
