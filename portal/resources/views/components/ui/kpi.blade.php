{{--
    A key figure, in three parts inside a soft frame:
      head   icon, title, caption
      body   the figure, a small badge, one line of context
      foot   two supporting figures
    Badge tones follow the status colours: ok (blue), warn (amber), danger (red), neutral (grey).
--}}
@props([
    'icon', 'title', 'caption' => null, 'value',
    'badge' => null, 'badgeTone' => 'neutral', 'badgeIcon' => null,
    'note' => null, 'foot' => [], 'href' => null,
])
<section {{ $attributes->merge(['class' => 'kpi']) }}>
    <div class="kpi-main">
        <div class="kpi-head">
            <span class="kpi-icon"><x-ui.icon :name="$icon" /></span>
            <span class="kpi-titles"><span class="kpi-title">{{ $title }}</span>@if($caption)<span class="kpi-caption">{{ $caption }}</span>@endif</span>
            @if($href)<a href="{{ $href }}" class="kpi-link" aria-label="Open {{ $title }}"><x-ui.icon name="chevron-right" /></a>@endif
        </div>
        <div class="kpi-figure">
            <span class="kpi-value">{{ $value }}</span>
            @if($badge !== null)
                <span class="delta delta-{{ $badgeTone }}">@if($badgeIcon)<x-ui.icon :name="$badgeIcon" />@endif{{ $badge }}</span>
            @endif
        </div>
        @if($note)<p class="kpi-note">{{ $note }}</p>@endif
    </div>
    @if($foot)
        <dl class="kpi-foot">
            @foreach($foot as $label => $figure)
                <div><dt>{{ $label }}</dt><dd>{{ $figure }}</dd></div>
            @endforeach
        </dl>
    @endif
</section>
