<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Per-consumer authentication for the partner API. The admin UI has issued
 * hashed, scoped, revocable ApiKeys since day one — and nothing ever verified
 * them: one shared gateway token was the whole auth layer and the partner
 * TOLD US who they were via a plain api_consumer_id field. Anyone holding the
 * shared token could book, read or cancel as any consumer, and revoking or
 * suspending a partner did nothing.
 *
 * With an X-Api-Key header: the key is verified (hash, active, not expired,
 * not revoked), the consumer must be active, the scope must cover the
 * endpoint, and the consumer identity is DERIVED FROM THE KEY — any
 * client-supplied api_consumer_id is overwritten.
 *
 * Without the header: the request still passes on the gateway token alone
 * (existing integrations keep working) but is logged as a legacy identity
 * risk until every partner is migrated to keys.
 */
class ResolveApiConsumer
{
    public function handle(Request $request, Closure $next)
    {
        $rawKey = trim((string) ($request->header('X-Api-Key') ?: $request->header('X-Partner-Key') ?: ''));

        if ($rawKey === '') {
            Log::warning('Partner API request without X-Api-Key — consumer identity is client-asserted', [
                'path' => $request->path(),
                'claimed_consumer_id' => $request->input('api_consumer_id') ?? $request->query('api_consumer_id'),
            ]);

            return $next($request);
        }

        $apiKey = ApiKey::with('consumer')->where('key_hash', hash('sha256', $rawKey))->first();

        if (! $apiKey || $apiKey->revoked_at !== null || ! $apiKey->isActive()) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_API_KEY',
                    'message' => 'The API key is invalid, expired, or revoked.',
                    'status' => 401,
                ],
            ], 401);
        }

        $consumer = $apiKey->consumer;
        if (! $consumer || ! $consumer->isActive()) {
            return response()->json([
                'error' => [
                    'code' => 'CONSUMER_SUSPENDED',
                    'message' => 'This API consumer is suspended.',
                    'status' => 403,
                ],
            ], 403);
        }

        $requiredScope = $this->requiredScope($request);
        if ($requiredScope !== null && ! $apiKey->hasScope($requiredScope)) {
            return response()->json([
                'error' => [
                    'code' => 'INSUFFICIENT_SCOPE',
                    'message' => "This API key does not have the '{$requiredScope}' scope.",
                    'status' => 403,
                ],
            ], 403);
        }

        $apiKey->forceFill(['last_used_at' => now()])->saveQuietly();

        // Identity comes from the key, never from the request body/query.
        $request->merge(['api_consumer_id' => $consumer->id]);
        $request->query->set('api_consumer_id', (string) $consumer->id);
        $request->attributes->set('api_consumer', $consumer);

        return $next($request);
    }

    private function requiredScope(Request $request): ?string
    {
        $path = $request->path();

        return match (true) {
            str_ends_with($path, '/cancel') => 'bookings:cancel',
            $request->isMethod('POST') && str_ends_with($path, '/bookings') => 'bookings:create',
            str_contains($path, '/bookings') => 'bookings:read',
            str_ends_with($path, '/extras') => 'vehicles:extras',
            str_contains($path, '/vehicles') => 'vehicles:search',
            default => null,
        };
    }
}
