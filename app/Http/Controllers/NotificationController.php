<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Header bell: open one notification (marks it read) or mark all as read. Only the user's own – others 404. */
class NotificationController extends Controller
{
    /** Polled by the header: unread count + the dropdown body, rendered from the same partial as on page load. */
    public function bell(Request $request): JsonResponse
    {
        $bell = $request->user()->notificationBell();

        return response()->json([
            'unread' => $bell['unreadCount'],
            'html' => view('layouts.notifications', $bell)->render(),
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notification);
        $notification->markAsRead();

        // Our notifications store a relative path; anything else falls back to the dashboard.
        $url = $notification->data['url'] ?? null;

        return is_string($url) && str_starts_with($url, '/') && ! str_starts_with($url, '//')
            ? redirect($url)
            : to_route('dashboard');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
