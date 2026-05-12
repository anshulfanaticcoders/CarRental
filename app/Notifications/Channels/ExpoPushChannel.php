<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushChannel
{
    private const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toExpoPush')) {
            return;
        }
        $token = $notifiable->expo_push_token ?? null;
        if (! is_string($token) || trim($token) === '') {
            return;
        }
        if (! preg_match('/^(ExponentPushToken|ExpoPushToken)\[.+\]$/', $token)) {
            return;
        }

        $payload = $notification->toExpoPush($notifiable);
        if (! is_array($payload)) return;

        $body = array_filter([
            'to' => $token,
            'title' => $payload['title'] ?? null,
            'body' => $payload['body'] ?? null,
            'data' => $payload['data'] ?? null,
            'sound' => $payload['sound'] ?? 'default',
            'badge' => $payload['badge'] ?? null,
            'channelId' => $payload['channelId'] ?? null,
            'priority' => $payload['priority'] ?? 'high',
        ], fn ($v) => $v !== null);

        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->asJson()
                ->post(self::ENDPOINT, $body);

            if (! $response->successful()) {
                Log::warning('Expo push failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'user_id' => $notifiable->id ?? null,
                ]);
                return;
            }

            $data = $response->json('data');
            if (is_array($data) && ($data['status'] ?? null) === 'error') {
                $details = $data['details'] ?? [];
                if (isset($details['error']) && in_array($details['error'], ['DeviceNotRegistered', 'InvalidCredentials'], true)) {
                    // Token is dead — clear it so we stop trying
                    if (method_exists($notifiable, 'forceFill')) {
                        $notifiable->forceFill([
                            'expo_push_token' => null,
                            'expo_push_platform' => null,
                        ])->save();
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Expo push exception', [
                'message' => $e->getMessage(),
                'user_id' => $notifiable->id ?? null,
            ]);
        }
    }
}
