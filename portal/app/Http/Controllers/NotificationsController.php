<?php

namespace App\Http\Controllers;

use App\Models\GmuNotification;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $unreadOnly = $request->boolean('unread');

        return view('notifications.index', [
            'notifications' => $user->notifications()->with('related')->when($unreadOnly, fn ($q) => $q->whereNull('read_at'))->latest()->paginate(20)->withQueryString(),
            'unread'        => $user->unreadNotificationsCount(),
            'total'         => $user->notifications()->count(),
            'unreadOnly'    => $unreadOnly,
        ]);
    }

    /** Go to the page the notification is about; having opened it, it is read. */
    public function open(GmuNotification $notification)
    {
        $this->authorize('view', $notification);

        if (! $notification->read_at) {
            $notification->markAsRead();
        }

        return redirect()->to($notification->actionUrl() ?? route('notifications.index'));
    }

    public function markRead(GmuNotification $notification)
    {
        $this->authorize('update', $notification);

        $notification->markAsRead();

        return back();
    }

    public function markAllRead(Request $request)
    {
        $marked = $request->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', $marked === 1 ? '1 notification marked as read.' : $marked . ' notifications marked as read.');
    }
}
