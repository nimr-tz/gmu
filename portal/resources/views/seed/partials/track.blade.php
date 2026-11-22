{{-- The eight-step progress line. Expects $application. --}}
@php
    $current = $application->trackIndex();
    $stopped = in_array($application->status, \App\Support\SeedGrant::CLOSED_STATUSES, true);
    $awarded = $application->status === \App\Support\SeedGrant::AWARDED;
@endphp
<ol class="grid grid-cols-4 gap-x-2 gap-y-5 sm:grid-cols-8" aria-label="Where the application is">
    @foreach(\App\Support\SeedGrant::TRACK as $i => [$title, $sub])
        @php
            $state = $i < $current || ($i === $current && $awarded) ? 'done' : ($i === $current ? ($stopped ? 'stopped' : 'now') : 'todo');
        @endphp
        <li class="min-w-0" @if($i === $current) aria-current="step" @endif>
            <div class="flex items-center gap-1.5">
                @if($state === 'done')
                    <span class="grid size-5 shrink-0 place-items-center rounded-full bg-primary text-white"><x-icon name="check" class="size-3" /></span>
                @elseif($state === 'now')
                    <span class="grid size-5 shrink-0 place-items-center rounded-full bg-primary-soft ring-2 ring-primary"><span class="size-2 rounded-full bg-primary"></span></span>
                @elseif($state === 'stopped')
                    <span class="grid size-5 shrink-0 place-items-center rounded-full bg-danger text-white"><x-icon name="x" class="size-3" /></span>
                @else
                    <span class="grid size-5 shrink-0 place-items-center rounded-full border border-input bg-card font-mono text-[10px] text-subtle">{{ $i + 1 }}</span>
                @endif
                <span class="h-0.5 flex-1 rounded-full {{ $i < $current ? 'bg-primary' : 'bg-border' }} {{ $loop->last ? 'invisible' : '' }}"></span>
            </div>
            <p class="mt-2 text-[13px] font-medium leading-snug {{ $state === 'todo' ? 'text-muted-foreground' : 'text-foreground' }}">{{ $title }}</p>
            <p class="mt-0.5 text-xs leading-snug {{ $state === 'now' ? 'font-medium text-primary' : ($state === 'stopped' ? 'font-medium text-danger-ink' : 'text-muted-foreground') }}">
                {{ $i === $current ? $application->statusLabel() : $sub }}
            </p>
        </li>
    @endforeach
</ol>
