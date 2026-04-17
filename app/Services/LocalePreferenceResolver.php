<?php

namespace App\Services;

use Illuminate\Http\Request;

class LocalePreferenceResolver
{
    private const ARAB_COUNTRY_CODES = [
        'AE', 'BH', 'DZ', 'EG', 'IQ', 'JO', 'KW', 'LB', 'LY', 'MA',
        'MR', 'OM', 'PS', 'QA', 'SA', 'SD', 'SO', 'SY', 'TN', 'YE',
        'DJ', 'KM', 'TD', 'ER', 'EH',
    ];

    public function resolveRootLocale(Request $request): string
    {
        $preferredLocale = (string) $request->cookie('preferred_locale', '');
        if ($this->isSupportedLocale($preferredLocale)) {
            return $preferredLocale;
        }

        $sessionLocale = (string) $request->session()->get('locale', '');
        if ($this->isSupportedLocale($sessionLocale)) {
            return $sessionLocale;
        }

        $countryCode = strtoupper((string) $request->session()->get('country', ''));
        $countryLocale = $this->localeFromCountryCode($countryCode);

        return $this->isSupportedLocale($countryLocale)
            ? $countryLocale
            : $this->defaultLocale();
    }

    public function localeFromCountryCode(?string $countryCode): string
    {
        $normalizedCountryCode = strtoupper(trim((string) $countryCode));

        if ($normalizedCountryCode !== '' && in_array($normalizedCountryCode, self::ARAB_COUNTRY_CODES, true)) {
            return 'ar';
        }

        return $this->defaultLocale();
    }

    private function isSupportedLocale(?string $locale): bool
    {
        return in_array($locale, $this->availableLocales(), true);
    }

    private function availableLocales(): array
    {
        return config('app.available_locales', ['en']);
    }

    private function defaultLocale(): string
    {
        return (string) config('app.fallback_locale', 'en');
    }
}
