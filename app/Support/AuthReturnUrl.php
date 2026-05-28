<?php

namespace App\Support;

use Illuminate\Http\Request;

final class AuthReturnUrl
{
    public const SESSION_KEY = 'auth.return_to';

    private const LOCALES = ['en', 'fr', 'nl', 'es', 'ar'];
    private const RETURNABLE_SECTIONS = ['s', 'booking'];

    public static function capture(Request $request, ?string $candidate): ?string
    {
        $returnTo = self::normalize($request, $candidate);

        if ($returnTo) {
            $request->session()->put(self::SESSION_KEY, $returnTo);
        } else {
            $request->session()->forget(self::SESSION_KEY);
        }

        return $returnTo;
    }

    public static function pull(Request $request, ?string $candidate = null): ?string
    {
        $returnTo = self::normalize($request, $candidate)
            ?? self::normalize($request, $request->session()->pull(self::SESSION_KEY));

        $request->session()->forget(self::SESSION_KEY);

        return $returnTo;
    }

    public static function normalize(Request $request, ?string $candidate): ?string
    {
        $candidate = trim((string) $candidate);

        if ($candidate === '' || str_contains($candidate, "\r") || str_contains($candidate, "\n")) {
            return null;
        }

        if (str_starts_with($candidate, '//')) {
            return null;
        }

        $baseUrl = $request->getSchemeAndHttpHost();
        $url = str_starts_with($candidate, '/') ? $baseUrl.$candidate : $candidate;
        $parts = parse_url($url);
        $baseParts = parse_url($baseUrl);

        if (! is_array($parts) || ! is_array($baseParts)) {
            return null;
        }

        $scheme = $parts['scheme'] ?? $baseParts['scheme'] ?? null;
        $host = $parts['host'] ?? $baseParts['host'] ?? null;
        $port = $parts['port'] ?? null;
        $basePort = $baseParts['port'] ?? null;
        $path = $parts['path'] ?? null;

        if ($scheme !== ($baseParts['scheme'] ?? null) || $host !== ($baseParts['host'] ?? null) || $port !== $basePort || ! $path) {
            return null;
        }

        if (! self::isAllowedPath($path)) {
            return null;
        }

        return $path.(isset($parts['query']) ? '?'.$parts['query'] : '');
    }

    private static function isAllowedPath(string $path): bool
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));

        if (count($segments) < 2 || ! in_array($segments[0], self::LOCALES, true)) {
            return false;
        }

        return in_array($segments[1], self::RETURNABLE_SECTIONS, true);
    }
}
