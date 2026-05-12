<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $allowedRoles = $this->allowedRoles($user->role ?? 'customer');

        $items = $user->notifications()
            ->where(function ($q) use ($allowedRoles) {
                $q->whereIn('data->role', $allowedRoles)
                  ->orWhereNull('data->role');
            })
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $unread = $user->unreadNotifications()
            ->where(function ($q) use ($allowedRoles) {
                $q->whereIn('data->role', $allowedRoles)
                  ->orWhereNull('data->role');
            })
            ->count();

        return response()->json([
            'notifications' => $items->map(fn ($n) => $this->transform($n))->values(),
            'unread_count' => $unread,
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $allowedRoles = $this->allowedRoles($user->role ?? 'customer');

        $unread = $user->unreadNotifications()
            ->where(function ($q) use ($allowedRoles) {
                $q->whereIn('data->role', $allowedRoles)
                  ->orWhereNull('data->role');
            })
            ->count();

        return response()->json(['unread_count' => $unread]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $n = $request->user()->notifications()->findOrFail($id);
        $n->markAsRead();
        return response()->json(['success' => true]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }

    public function clearAll(Request $request): JsonResponse
    {
        $request->user()->notifications()->delete();
        return response()->json(['success' => true]);
    }

    private function allowedRoles(string $role): array
    {
        return match ($role) {
            'admin' => ['admin'],
            'vendor' => ['vendor'],
            'affiliate' => ['affiliate'],
            default => ['customer'],
        };
    }

    private function transform($n): array
    {
        $data = is_array($n->data) ? $n->data : (json_decode($n->data ?? '{}', true) ?: []);
        return [
            'id' => $n->id,
            'type' => $n->type,
            'title' => $data['title'] ?? $data['subject'] ?? 'Notification',
            'message' => $data['message'] ?? $data['body'] ?? null,
            'action_url' => $data['action_url'] ?? $data['url'] ?? null,
            'booking_id' => $data['booking_id'] ?? null,
            'booking_number' => $data['booking_number'] ?? null,
            'icon' => $data['icon'] ?? null,
            'category' => $data['category'] ?? $this->categoryFromType($n->type),
            'read_at' => $n->read_at?->toIso8601String(),
            'created_at' => $n->created_at?->toIso8601String(),
        ];
    }

    private function categoryFromType(?string $type): string
    {
        if (! $type) return 'general';
        $lower = strtolower($type);
        if (str_contains($lower, 'booking')) return 'booking';
        if (str_contains($lower, 'payment')) return 'payment';
        if (str_contains($lower, 'review')) return 'review';
        if (str_contains($lower, 'affiliate')) return 'affiliate';
        if (str_contains($lower, 'message')) return 'message';
        return 'general';
    }
}
