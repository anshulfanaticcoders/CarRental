<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AdobeCarService
{
    protected $baseUrl;
    protected $username;
    protected $password;

    public function __construct()
    {
        $this->baseUrl = env('ADOBE_URL');
        $this->username = env('ADOBE_USERNAME');
        $this->password = env('ADOBE_PASSWORD');
    }

    /**
     * Authenticates with the Adobe API and returns an access token.
     * The token is cached to avoid repeated login requests.
     *
     * @return string|null The access token, or null on failure.
     */
    protected function getAccessToken(): ?string
    {
        return Cache::remember('adobe_api_token', 55, function () { // Cache for 55 minutes
            $response = Http::post("{$this->baseUrl}/Auth/Login", [
                'userName' => $this->username,
                'password' => $this->password,
            ]);

            if ($response->successful() && $response->json('token')) {
                return $response->json('token');
            }

            logger()->error('Failed to get Adobe API access token.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        });
    }

    /**
     * Fetches the list of all offices (locations) from the Adobe API.
     *
     * @return array A list of offices, or an empty array on failure.
     */
    public function getOfficeList(): array
    {
        $token = $this->getAccessToken();

        if (!$token) {
            return [];
        }

        $response = Http::withToken($token)->get("{$this->baseUrl}/Offices");

        if ($response->successful()) {
            return $response->json();
        }

        logger()->error('Failed to fetch Adobe API office list.', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return [];
    }
}
