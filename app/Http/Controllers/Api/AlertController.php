<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AlertService;

class AlertController extends Controller
{
    public function __construct(
        protected AlertService $service,
    ) {}

    public function index()
    {
        $alerts = $this->service->recentAlerts(50);

        return response()->json([
            'data' => $alerts,
            'unread_count' => $this->service->unreadCount(),
        ]);
    }

    public function unreadCount()
    {
        return response()->json([
            'count' => $this->service->unreadCount(),
        ]);
    }

    public function markRead(int $id)
    {
        $ok = $this->service->markAsRead($id);

        if (! $ok) {
            return response()->json(['message' => 'Alert not found'], 404);
        }

        return response()->json(['message' => 'Marked as read']);
    }

    public function markAllRead()
    {
        $count = $this->service->markAllAsRead();

        return response()->json([
            'message' => "{$count} alerts marked as read",
            'count' => $count,
        ]);
    }

    public function dismiss(int $id)
    {
        $ok = $this->service->dismiss($id);

        if (! $ok) {
            return response()->json(['message' => 'Alert not found'], 404);
        }

        return response()->json(['message' => 'Alert dismissed']);
    }
}
