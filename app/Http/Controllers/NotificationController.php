<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\BookingTimeAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class NotificationController extends Controller
{
    public function unread(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $isStaff = method_exists($user, 'isStaff') ? $user->isStaff() : false;

        if (! Schema::hasTable('notifications')) {
            return response()->json([
                'is_staff' => $isStaff,
                'pending_approval_count' => 0,
                'recent_pending_approvals' => [],
                'user_unread_count' => 0,
                'user_unread_notifications' => [],
                'header_notification_count' => 0,
            ]);
        }

        app(BookingTimeAlertService::class)->syncForUser($user, $isStaff);

        $unreadNotifications = $user->unreadNotifications()
            ->latest()
            ->take(3)
            ->get()
            ->map(function ($notification) use ($isStaff): array {
                $url = (string) ($notification->data['url'] ?? '#');

                return [
                    'id' => $notification->id,
                    'title' => $notification->data['title'] ?? 'Notification',
                    'message' => $notification->data['message'] ?? '',
                    'url' => $this->safeNotificationUrl($url, $isStaff),
                    'created_at_human' => optional($notification->created_at)->diffForHumans() ?? '',
                ];
            })
            ->values();

        $userUnreadCount = $user->unreadNotifications()->count();

        $pendingApprovalCount = 0;
        $recentPendingApprovals = collect();

        if ($isStaff) {
            $pendingQuery = Booking::query()
                ->with('room')
                ->whereHas('room', fn ($roomQuery) => $roomQuery->visible())
                ->where('status', 'pending')
                ->latest();

            $pendingApprovalCount = (clone $pendingQuery)->count();

            $recentPendingApprovals = $pendingQuery
                ->take(5)
                ->get()
                ->map(function (Booking $booking): array {
                    return [
                        'id' => $booking->id,
                        'room_name' => $booking->room?->name ?? 'Room',
                        'user_name' => $booking->user_name,
                        'created_at_human' => optional($booking->created_at)->diffForHumans() ?? '',
                    ];
                })
                ->values();
        }

        return response()->json([
            'is_staff' => $isStaff,
            'pending_approval_count' => $pendingApprovalCount,
            'recent_pending_approvals' => $recentPendingApprovals,
            'user_unread_count' => $userUnreadCount,
            'user_unread_notifications' => $unreadNotifications,
            'header_notification_count' => $pendingApprovalCount + $userUnreadCount,
        ]);
    }

    public function markAllRead(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if ($user) {
            if (Schema::hasTable('notifications')) {
                $user->unreadNotifications->markAsRead();
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    private function safeNotificationUrl(string $url, bool $isStaff): string
    {
        if ($url === '' || $url === '#') {
            return '#';
        }

        if ($isStaff) {
            return $url;
        }

        $staffOnlyFragments = [
            '/approvals',
            '/manage-rooms',
            '/reports',
            '/settings',
            '/api/users/search',
            '/calendar-per-room/users/search',
        ];

        foreach ($staffOnlyFragments as $fragment) {
            if (str_contains($url, $fragment)) {
                return route('dashboard');
            }
        }

        return $url;
    }
}
