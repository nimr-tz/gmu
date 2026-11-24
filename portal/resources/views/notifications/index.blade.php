@extends('layouts.app')

{{--
    Everything the portal has told this person, newest first and grouped by day, from the
    owner's "Staff Pages" design. Each one is also sent by email (Mail\SeedGrantNotice).
    The band takes the reader's colour: staff blue, GMU ink, the approvers indigo.
--}}

@php
    use App\Support\NotificationLook as Look;
    $user = auth()->user();
    $band = $user->can('manage-seed-grants') ? 'bg-foreground' : ($user->can('approve-seed-grants') ? 'bg-indigo-700' : 'bg-blue-700');
    $muted = $user->can('manage-seed-grants') ? 'text-gray-300' : ($user->can('approve-seed-grants') ? 'text-indigo-100' : 'text-blue-100');
    $tabOn = $user->can('manage-seed-grants') ? 'text-foreground' : ($user->can('approve-seed-grants') ? 'text-indigo-700' : 'text-blue-700');
    $btn = $user->can('manage-seed-grants') ? 'bg-foreground hover:bg-gray-800' : ($user->can('approve-seed-grants') ? 'bg-indigo-700 hover:bg-indigo-800' : 'bg-blue-700 hover:bg-blue-800');
    $dayOf = fn ($d) => $d->isToday() ? 'Today' : ($d->isYesterday() ? 'Yesterday' : $d->format('j M Y'));
    $lastDay = null;
@endphp

@section('title', 'Notifications')
@section('context', 'Account · Notifications')
@section('topbar-colour', $band)

@section('content')
<div class="-mx-4 -mt-5 flex flex-wrap items-start justify-between gap-5 px-7 pb-[100px] pt-5 text-white lg:-mx-6 lg:-mt-6 {{ $band }}">
    <div class="flex min-w-0 flex-col gap-3">
        <h1 class="text-[30px] font-semibold leading-tight tracking-[-0.025em] text-white">Notifications</h1>
        <p class="text-[15px] {{ $muted }}">{{ $total === 0 ? 'Nothing here yet' : ($unread ? $unread . ' unread · each one is also sent by email' : 'Everything here has been read') }}</p>
        @if($total > 0)
            <nav class="flex gap-0.5 self-start rounded-[9px] bg-white/15 p-[3px]" aria-label="Show">
                @foreach([['All', $total, false], ['Unread', $unread, true]] as [$label, $n, $only])
                    <a href="{{ $only ? route('notifications.index', ['unread' => 1]) : route('notifications.index') }}" @if($unreadOnly === $only) aria-current="page" @endif
                       class="inline-flex h-7 items-center gap-1.5 rounded-[7px] px-2.5 text-[13px] font-medium {{ $unreadOnly === $only ? 'bg-white ' . $tabOn : 'text-white hover:bg-white/10' }}">{{ $label }}<span class="text-[11.5px] font-semibold opacity-70">{{ $n }}</span></a>
                @endforeach
            </nav>
        @endif
    </div>
    @if($unread > 0)
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf @method('PATCH')
            <button type="submit" class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-lg bg-white px-3.5 text-[13px] font-semibold text-foreground hover:bg-gray-100"><x-icon name="check-check" class="size-3.5" />Mark all read</button>
        </form>
    @endif
</div>

<div class="-mt-[72px]">
    <section class="max-w-[920px] overflow-hidden rounded-2xl border border-border bg-card shadow-pop">
        @forelse($notifications as $n)
            @php
                $day = $dayOf($n->created_at);
                $new = ! $n->read_at;
                [$icon, $iconTone] = Look::icon($n->type);
                $subject = Look::subject($n);
                $tint = $subject instanceof \App\Models\Seed\SeedApplication ? $subject->call?->tintClass() : $subject?->tintClass();
                $url = $n->actionUrl();
            @endphp
            @if($day !== $lastDay)
                @php $lastDay = $day; @endphp
                <div class="border-b border-gray-100 bg-gray-50 px-5 py-[9px] text-[12.5px] font-semibold text-gray-700">{{ $day }}</div>
            @endif
            <article class="relative flex gap-4 border-b border-gray-100 px-5 py-[18px] {{ $new ? 'bg-blue-50/30' : '' }}">
                @if($new)<span class="absolute inset-y-0 left-0 w-[3px] bg-primary" aria-hidden="true"></span>@endif
                <span class="grid size-[38px] shrink-0 place-items-center rounded-[10px] {{ $iconTone }}"><x-icon :name="$icon" class="size-[17px]" /></span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                        <p class="text-[14.5px] {{ $new ? 'font-semibold' : 'font-medium' }}">{{ $n->title }}@if($new)<span class="sr-only"> (unread)</span>@endif</p>
                        <time class="whitespace-nowrap text-[12.5px] text-muted-foreground" datetime="{{ $n->created_at?->toIso8601String() }}">{{ $n->created_at?->format('H:i') }} · {{ $n->created_at?->diffForHumans() }}</time>
                    </div>
                    <p class="mt-1 max-w-[680px] text-pretty text-[13.5px] leading-relaxed text-gray-700">{{ $n->message }}</p>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        @if($subject)
                            <span class="{{ $tint }} rounded-[5px] bg-tint-chip px-1.5 py-px font-mono text-[11.5px] font-medium text-tint-ink">{{ $subject instanceof \App\Models\Seed\SeedApplication ? $subject->reference : $subject->code }}</span>
                        @endif
                        <span class="flex-1"></span>
                        @if($new)
                            <form method="POST" action="{{ route('notifications.read', $n) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="h-[30px] rounded-[7px] px-2.5 text-[12.5px] font-medium text-gray-700 hover:bg-gray-100">Mark read</button>
                            </form>
                        @endif
                        @if($url)
                            <a href="{{ route('notifications.open', $n) }}" class="inline-flex h-[30px] items-center gap-1.5 rounded-[7px] px-3 text-[12.5px] font-medium {{ $new ? 'text-white ' . $btn : 'border border-input bg-card hover:bg-gray-100' }}">{{ Look::action($n, $user) }}<x-icon name="arrow-right" class="size-3.5" /></a>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="flex flex-col items-center gap-2.5 px-6 py-14 text-center">
                <span class="grid size-12 place-items-center rounded-[14px] bg-blue-50 text-ok-ink"><x-icon name="bell" class="size-[22px]" /></span>
                <p class="text-[15px] font-semibold">{{ $total === 0 ? 'Nothing to read' : 'No unread notifications' }}</p>
                <p class="max-w-[460px] text-[13.5px] leading-normal text-muted-foreground">
                    {{ $total === 0
                        ? 'Notifications arrive when a call opens, something is waiting on you, a decision is made about your application, or a deadline is near. Each one is also sent by email.'
                        : 'You have read everything. Switch to All to see earlier ones.' }}
                </p>
            </div>
        @endforelse
    </section>
    @if($notifications->hasPages())<div class="mt-4 max-w-[920px]">{{ $notifications->links() }}</div>@endif
</div>
@endsection
