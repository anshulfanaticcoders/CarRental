<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:191'],
            'platform' => ['nullable', 'string', 'in:ios,android,web'],
        ]);

        $user = $request->user();
        $user->forceFill([
            'expo_push_token' => $data['token'],
            'expo_push_platform' => $data['platform'] ?? null,
            'expo_push_registered_at' => now(),
        ])->save();

        return response()->json(['success' => true]);
    }

    public function unregister(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->forceFill([
            'expo_push_token' => null,
            'expo_push_platform' => null,
            'expo_push_registered_at' => null,
        ])->save();

        return response()->json(['success' => true]);
    }
}
