<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(15);

        return view('notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->whereKey($id)
            ->firstOrFail();

        $notification->markAsRead();

        return redirect()
            ->route('notifications.index')
            ->with('status', __('Notification marked as read.'));
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return redirect()
            ->route('notifications.index')
            ->with('status', __('All notifications marked as read.'));
    }
}
