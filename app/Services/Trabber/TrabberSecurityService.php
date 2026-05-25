<?php

namespace App\Services\Trabber;

use Illuminate\Http\Request;

class TrabberSecurityService
{
    public function isAuthorized(Request $request): bool
    {
        $expected = (string) config('trabber.api_key', '');
        if ($expected === '') {
            return false;
        }

        $header = (string) config('trabber.auth_header', 'x-api-key');
        $provided = (string) $request->headers->get($header, '');

        return $provided !== '' && hash_equals($expected, $provided);
    }
}
