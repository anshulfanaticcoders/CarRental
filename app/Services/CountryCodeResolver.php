<?php

namespace App\Services;

/**
 * Resolves country names to ISO 3166-1 alpha-2 codes using public/countries.json.
 * Single source of truth for country data across the entire app.
 */
class CountryCodeResolver
{
    private static ?array $nameToCode = null;
    private static ?array $countries = null;

    /**
     * Get ISO country code from country name.
     * Returns empty string if not found.
     */
    public static function resolve(?string $country): string
    {
        if (!$country || trim($country) === '') return '';

        self::loadOnce();

        return self::$nameToCode[strtolower(trim($country))] ?? '';
    }

    /**
     * Get all countries as an array of {name, code, phone_code}.
     */
    public static function all(): array
    {
        self::loadOnce();
        return self::$countries;
    }

    private static function loadOnce(): void
    {
        if (self::$nameToCode !== null) return;

        $path = public_path('countries.json');
        $data = json_decode(file_get_contents($path), true) ?: [];

        self::$countries = $data;
        self::$nameToCode = [];

        foreach ($data as $entry) {
            $name = strtolower(trim($entry['name'] ?? ''));
            $code = strtoupper(trim($entry['code'] ?? ''));
            if ($name && $code) {
                self::$nameToCode[$name] = $code;
            }
        }

        // Common non-English / alternative country names
        $aliases = [
            'españa' => 'ES', 'espana' => 'ES', 'spanien' => 'ES', 'spanje' => 'ES',
            'maroc' => 'MA', 'marokko' => 'MA', 'marruecos' => 'MA',
            'belgique' => 'BE', 'belgien' => 'BE', 'belgië' => 'BE', 'belgie' => 'BE',
            'deutschland' => 'DE', 'allemagne' => 'DE', 'duitsland' => 'DE',
            'france' => 'FR', 'frankreich' => 'FR', 'frankrijk' => 'FR',
            'italia' => 'IT', 'italien' => 'IT', 'italië' => 'IT', 'italie' => 'IT',
            'nederland' => 'NL', 'pays-bas' => 'NL', 'niederlande' => 'NL',
            'türkiye' => 'TR', 'turkije' => 'TR', 'turquie' => 'TR', 'türkei' => 'TR',
            'grèce' => 'GR', 'griechenland' => 'GR', 'griekenland' => 'GR',
            'portugal' => 'PT', 'schweiz' => 'CH', 'suisse' => 'CH', 'zwitserland' => 'CH',
            'österreich' => 'AT', 'oostenrijk' => 'AT', 'autriche' => 'AT',
            'royaume-uni' => 'GB', 'vereinigtes königreich' => 'GB',
            'états-unis' => 'US', 'vereinigte staaten' => 'US', 'verenigde staten' => 'US',
            'émirats arabes unis' => 'AE', 'vereinigte arabische emirate' => 'AE',
            'verenigde arabische emiraten' => 'AE', 'emiratos árabes unidos' => 'AE',
            'croatie' => 'HR', 'kroatien' => 'HR', 'kroatië' => 'HR',
            'chypre' => 'CY', 'zypern' => 'CY',
        ];
        foreach ($aliases as $alias => $code) {
            self::$nameToCode[$alias] = $code;
        }
    }
}
