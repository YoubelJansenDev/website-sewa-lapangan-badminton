<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = UserNotification::where('user_id', $request->user()->id);

        return response()->json([
            'unread_count' => (clone $query)->where('is_read', false)->count(),
            'notifications' => $query->latest()->paginate(20),
        ]);
    }

    public function markRead(Request $request, UserNotification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 404);

        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'Notifikasi ditandai sudah dibaca.']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        UserNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'Semua notifikasi ditandai sudah dibaca.']);
    }
}