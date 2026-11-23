@extends('layouts.app')

@section('title', 'Notifications')
@section('context', 'Account · Notifications')

@section('content')
@php
    $icons = [
        'seed_submitted' => 'send', 'seed_resubmitted' => 'send', 'seed_screened' => 'clipboard-check', 'seed_returned' => 'rotate-ccw',
        'seed_review' => 'message-square', 'seed_reviews_in' => 'users', 'seed_conflict' => 'alert-triangle', 'seed_decision' => 'scale',
        'seed_approval' => 'shield-check', 'seed_approved' => 'check-circle', 'seed_agreement' => 'pen-line',
        'seed_call' => 'megaphone', 'seed_reminder' => 'clock',
        'new_call' => 'megaphone', 'review_assigned' => 'clipboard-check', 'report_due' => 'calendar', 'compliance_overdue' => 'alert-triangle',
    ];
@endphp

<x-page-header title="Notifications" :description="$unread > 0 ? $unread . ' unread' : 'Everything here has been read'">
    @if($unread > 0)
        <x-slot:actions>
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf @method('PATCH')
                <x-button type="submit" size="sm" variant="secondary" icon="check">Mark all read</x-button>
            </form>
        </x-slot:actions>
    @endif
</x-page-header>

<x-card flush>
    @if($notifications->isEmpty())
        <x-empty icon="bell" title="Nothing to read">
            Notifications arrive when a call opens, something is waiting on you, a decision is made about your application, or a deadline is near. Each one is also sent by email.
        </x-empty>
    @else
        <ul class="divide-y divide-border">
            @foreach($notifications as $notification)
                @php $url = $notification->actionUrl(); $new = ! $notification->read_at; @endphp
                <li class="relative flex gap-4 px-5 py-4 {{ $new ? 'bg-primary-soft/40' : '' }}">
                    @if($new)<span class="absolute inset-y-0 left-0 w-0.5 bg-primary" aria-hidden="true"></span>@endif
                    <span class="grid size-9 shrink-0 place-items-center rounded-lg border border-border bg-card text-gray-600 shadow-xs"><x-icon :name="$icons[$notification->type] ?? 'bell'" /></span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-baseline justify-between gap-x-4">
                            <p class="text-sm {{ $new ? 'font-semibold' : 'font-medium' }}">{{ $notification->title }}@if($new)<span class="sr-only"> (unread)</span>@endif</p>
                            <time class="whitespace-nowrap text-xs text-muted-foreground" title="{{ $notification->created_at?->format('j M Y H:i') }}">{{ $notification->created_at?->format('j M Y H:i') }} · {{ $notification->created_at?->diffForHumans() }}</time>
                        </div>
                        <p class="mt-1 max-w-3xl text-[13px] leading-relaxed text-gray-700">{{ $notification->message }}</p>
                        @if($url || $new)
                            <div class="mt-3 flex flex-wrap gap-2">
                                @if($url)<x-button size="sm" :variant="$new ? 'primary' : 'secondary'" :href="route('notifications.open', $notification)" icon-right="arrow-right">Open</x-button>@endif
                                @if($new)
                                    <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                        @csrf @method('PATCH')
                                        <x-button type="submit" size="sm" variant="ghost" icon="check">Mark read</x-button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</x-card>
@if($notifications->hasPages())<div class="mt-4">{{ $notifications->links() }}</div>@endif
@endsection
