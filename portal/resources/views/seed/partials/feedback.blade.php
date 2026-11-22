{{--
    Reviewer feedback as the applicant sees it: decided rounds only, reviewers
    by letter, never by name, and never the confidential note to GMU.
    Expects $application; optional $compact for the editor side panel.
--}}
@php
    $compact = $compact ?? false;
    $decided = $application->rounds->filter(fn ($r) => $r->decision !== null)->sortByDesc(fn ($r) => [$r->stage === 'proposal', $r->number]);
    $tones = ['accept' => 'ok', 'revise' => 'warn', 'reject' => 'danger'];
@endphp

<div class="space-y-4">
@forelse($decided as $round)
    <x-card :title="ucfirst($round->stage === 'proposal' ? 'full proposal' : 'concept note') . ' · round ' . $round->number"
            :description="'Decided ' . $round->decided_at?->format('j M Y')">
        <x-slot:actions>
            <x-badge :tone="['revise' => 'warn', 'advance' => 'ok', 'decline' => 'danger'][$round->decision]" dot>
                {{ ['revise' => 'Revision requested', 'advance' => $round->stage === 'proposal' ? 'Recommended' : 'Shortlisted', 'decline' => 'Not taken forward'][$round->decision] }}
            </x-badge>
        </x-slot:actions>

        <div class="space-y-4">
            @if($round->decision_note)
                <x-alert tone="info" title="From GMU" icon="message-square"><p class="whitespace-pre-line">{{ $round->decision_note }}</p></x-alert>
            @endif
            <div class="divide-y divide-border">
                @foreach($round->reviews->filter->isSubmitted()->sortBy(fn ($r) => $r->assignment->label) as $review)
                    <div class="py-4 first:pt-0">
                        <div class="flex items-center justify-between gap-3">
                            <span class="flex items-center gap-2 text-sm font-semibold">
                                <span class="grid size-6 place-items-center rounded-full bg-muted font-mono text-xs">{{ $review->assignment->label }}</span>
                                Reviewer {{ $review->assignment->label }}
                            </span>
                            <x-badge :tone="$tones[$review->recommendation] ?? 'neutral'">{{ \App\Models\Seed\SeedReview::RECOMMENDATIONS[$review->recommendation] ?? '' }}</x-badge>
                        </div>
                        <p class="prose-text mt-2">{{ $review->comments }}</p>
                    </div>
                @endforeach
                @if($round->response)
                    <div class="py-4 last:pb-0">
                        <p class="text-sm font-semibold">Your response <span class="font-normal text-muted-foreground">· {{ $round->responded_at?->format('j M Y') }}</span></p>
                        <p class="prose-text mt-2">{{ $round->response }}</p>
                    </div>
                @endif
            </div>
        </div>
    </x-card>
@empty
    @unless($compact)
        <x-card><x-empty icon="message-square" title="No reviewer feedback yet">Feedback appears here once GMU has decided a review round.</x-empty></x-card>
    @endunless
@endforelse
</div>
