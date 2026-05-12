<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ProviderFavorite;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $host = rtrim($request->getSchemeAndHttpHost(), '/');
        $absolutize = function (?string $path) use ($host): ?string {
            if (! $path) return null;
            if (preg_match('#^https?://#i', $path)) return $path;
            return $host.'/'.ltrim($path, '/');
        };

        $internal = $user->favorites()
            ->with(['images', 'category', 'vendorProfileData'])
            ->orderByDesc('vehicles.created_at')
            ->limit(100)
            ->get()
            ->map(function (Vehicle $v) use ($absolutize) {
                $first = $v->images->first();
                return [
                    'kind' => 'internal',
                    'key' => (string) $v->id,
                    'vehicle_id' => $v->id,
                    'brand' => $v->brand,
                    'model' => $v->model,
                    'category' => $v->category?->name,
                    'image' => $first?->image_url ? $absolutize($first->image_url) : null,
                    'currency' => 'EUR',
                    'price' => $v->price_per_day !== null ? (float) $v->price_per_day : null,
                    'price_period' => 'day',
                    'vendor_company' => $v->vendorProfileData?->company_name,
                    'source' => 'internal',
                ];
            });

        $provider = $user->providerFavorites()
            ->latest()
            ->limit(100)
            ->get()
            ->map(function (ProviderFavorite $p) use ($absolutize) {
                $payload = is_array($p->payload) ? $p->payload : [];
                $image = $payload['image'] ?? $payload['vehicle_image'] ?? null;
                return [
                    'kind' => 'provider',
                    'key' => $p->vehicle_key,
                    'vehicle_id' => null,
                    'brand' => $payload['brand'] ?? null,
                    'model' => $payload['model'] ?? null,
                    'category' => $payload['category'] ?? null,
                    'image' => is_string($image) ? $absolutize($image) : null,
                    'currency' => $payload['currency'] ?? 'EUR',
                    'price' => isset($payload['total_price']) && is_numeric($payload['total_price'])
                        ? (float) $payload['total_price']
                        : (isset($payload['daily_rate']) && is_numeric($payload['daily_rate']) ? (float) $payload['daily_rate'] : null),
                    'price_period' => isset($payload['total_price']) ? 'total' : 'day',
                    'vendor_company' => $payload['vendor_company'] ?? $payload['vendor'] ?? null,
                    'source' => $p->source,
                ];
            });

        return response()->json([
            'favorites' => $internal->concat($provider)->values(),
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $internalIds = $user->favorites()->pluck('vehicles.id')->map(fn ($id) => (string) $id);
        $providerKeys = $user->providerFavorites()->pluck('vehicle_key');

        return response()->json([
            'keys' => $internalIds->merge($providerKeys)->values(),
        ]);
    }

    public function toggle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'vehicle_key' => ['nullable', 'string', 'max:191'],
            'source' => ['nullable', 'string', 'max:64'],
            'payload' => ['nullable', 'array'],
        ]);

        $user = $request->user();

        if (! empty($validated['vehicle_id'])) {
            $vehicleId = (int) $validated['vehicle_id'];
            $exists = $user->favorites()->where('vehicles.id', $vehicleId)->exists();
            if ($exists) {
                $user->favorites()->detach($vehicleId);
                return response()->json(['action' => 'removed', 'key' => (string) $vehicleId]);
            }
            $user->favorites()->syncWithoutDetaching([$vehicleId]);
            return response()->json(['action' => 'added', 'key' => (string) $vehicleId]);
        }

        $key = (string) ($validated['vehicle_key'] ?? '');
        $source = (string) ($validated['source'] ?? '');
        if ($key === '' || $source === '') {
            return response()->json([
                'message' => 'vehicle_id, or vehicle_key + source required.',
            ], 422);
        }

        $existing = $user->providerFavorites()->where('vehicle_key', $key)->first();
        if ($existing) {
            $existing->delete();
            return response()->json(['action' => 'removed', 'key' => $key]);
        }

        $user->providerFavorites()->create([
            'vehicle_key' => $key,
            'source' => $source,
            'payload' => $validated['payload'] ?? null,
        ]);

        return response()->json(['action' => 'added', 'key' => $key]);
    }
}
