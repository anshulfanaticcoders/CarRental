<?php

namespace App\Services\Skyscanner;

class CarHireSecurityService
{
    public function hasValidApiKey(?string $apiKey): bool
    {
        $expected = trim((string) config('skyscanner.api_key', ''));
        $provided = trim((string) ($apiKey ?? ''));

        if ($expected === '' || $provided === '') {
            return false;
        }

        return hash_equals($expected, $provided);
    }

    public function buildSignedRedirectParams(string $quoteId, string $redirectId): array
    {
        return [
            'quote_id' => $quoteId,
            'skyscanner_redirectid' => $redirectId,
            'signature' => $this->signatureFor($quoteId, $redirectId),
        ];
    }

    public function hasValidSignature(array $params): bool
    {
        $quoteId = trim((string) ($params['quote_id'] ?? ''));
        $redirectId = trim((string) ($params['skyscanner_redirectid'] ?? ''));
        $signature = trim((string) ($params['signature'] ?? ''));

        if ($quoteId === '' || $redirectId === '' || $signature === '') {
            return false;
        }

        return hash_equals($this->signatureFor($quoteId, $redirectId), $signature);
    }

    public function allowlistedIps(): array
    {
        return array_values(array_filter(config('skyscanner.allowlisted_ips', [])));
    }

    public function isIpAllowlisted(?string $ip): bool
    {
        if ($ip === null || $ip === '') {
            return false;
        }

        return in_array($ip, $this->allowlistedIps(), true);
    }

    public function surveyReadinessSummary(): array
    {
        return [
            'api_auth_configured' => trim((string) config('skyscanner.api_key', '')) !== '',
            'ip_allowlist_required' => (bool) config('skyscanner.testing_access.ip_allowlist_required', true),
            'allowlisted_ip_count' => count($this->allowlistedIps()),
            'auth_header' => (string) config('skyscanner.testing_access.auth_header', 'x-api-key'),
            'auth_scheme' => (string) config('skyscanner.testing_access.auth_scheme', 'header'),
        ];
    }

    private function signatureFor(string $quoteId, string $redirectId): string
    {
        $secret = (string) config('skyscanner.signing_secret', '');

        return hash_hmac('sha256', $quoteId . '|' . $redirectId, $secret);
    }
}
