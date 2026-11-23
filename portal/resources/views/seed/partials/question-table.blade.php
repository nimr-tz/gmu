{{--
    The questions of one stage on the call settings page, from the owner's "GMU Calls" design:
    each question and its word limit, plus (as agreed with the owner) a switch to ask it and
    its heading and wording, which a call may change before it opens.
    Expects $stage ('concept' or 'proposal') and from the page: $f, $isOn, $locked, $form.
--}}
<div class="overflow-x-auto">
    <table class="w-full min-w-[560px] border-collapse text-left text-[13.5px]">
        <thead><tr>
            <th class="h-9 w-14 border-b border-border bg-gray-50 pl-[18px] text-[12.5px] font-medium text-muted-foreground">Use</th>
            <th class="border-b border-border bg-gray-50 px-3 text-[12.5px] font-medium text-muted-foreground">Question</th>
            <th class="w-[150px] border-b border-border bg-gray-50 pl-3 pr-[18px] text-right text-[12.5px] font-medium text-muted-foreground">Word limit</th>
        </tr></thead>
        <tbody>
            @foreach(data_get($form->config(), "questions.$stage") as $key => $q)
                @php $base = "features[questions][$stage][$key]"; @endphp
                <tr class="align-top">
                    <td class="border-b border-gray-100 py-2.5 pl-[18px]">
                        <input type="hidden" name="{{ $base }}[on]" value="0">
                        <input type="checkbox" class="switch mt-1.5 checked:bg-foreground" name="{{ $base }}[on]" value="1" aria-label="Ask {{ $q['heading'] }}" @checked($isOn("questions.$stage.$key.on")) @disabled($locked)>
                    </td>
                    <td class="border-b border-gray-100 px-3 py-2.5">
                        <div x-data="{ edit: false }">
                            <div class="flex items-start gap-2">
                                <input class="h-8 min-w-0 flex-1 rounded-md border border-transparent bg-transparent px-1.5 font-medium hover:border-input focus:border-primary disabled:hover:border-transparent" name="{{ $base }}[heading]" value="{{ $f("questions.$stage.$key.heading") }}" aria-label="Heading" @disabled($locked)>
                                @unless($locked)<button type="button" @click="edit = ! edit" class="mt-1 shrink-0 text-xs font-medium text-ok-ink hover:underline" x-text="edit ? 'Done' : 'Wording'">Wording</button>@endunless
                            </div>
                            <p class="px-1.5 text-xs leading-normal text-muted-foreground" x-show="! edit">{{ \Illuminate\Support\Str::limit($f("questions.$stage.$key.question"), 140) }}</p>
                            <textarea class="textarea mt-1 text-[13px]" rows="3" name="{{ $base }}[question]" aria-label="Question for {{ $q['heading'] }}" x-show="edit" x-cloak @disabled($locked)>{{ $f("questions.$stage.$key.question") }}</textarea>
                        </div>
                    </td>
                    <td class="border-b border-gray-100 py-2 pl-3 pr-[18px] text-right">
                        <input type="number" class="input ml-auto h-[30px] w-[84px] text-right font-mono text-[13px]" name="{{ $base }}[limit]" value="{{ $f("questions.$stage.$key.limit") }}"
                               min="{{ $locked ? $q['limit'] : 10 }}" max="5000" aria-label="Word limit for {{ $q['heading'] }}">
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
