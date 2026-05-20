<?php

namespace App\Support;

use App\Models\CurrencyRate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CurrencyRegistry
{
    private const CACHE_KEY = 'currency_registry.v1';

    public function all(): array
    {
        $path = resource_path('data/currencies.json');

        if (! File::exists($path)) {
            return [];
        }

        return Cache::rememberForever(self::CACHE_KEY.':'.File::lastModified($path), function () use ($path) {
            $entries = json_decode(File::get($path), true) ?: [];

            return collect($entries)
                ->mapWithKeys(function (array $currency) {
                    $code = strtoupper((string) ($currency['code'] ?? ''));

                    return $code ? [$code => array_merge($currency, ['code' => $code])] : [];
                })
                ->sortKeys()
                ->all();
        });
    }

    public function baseCurrency(): string
    {
        return $this->normalize(config('currency.base_currency', 'EUR'), 'EUR');
    }

    public function defaultCurrency(): string
    {
        return $this->normalize(config('currency.default', $this->baseCurrency()), $this->baseCurrency());
    }

    public function knownCodes(): array
    {
        return array_keys($this->all());
    }

    public function selectableCandidates(): array
    {
        return collect($this->all())
            ->filter(fn (array $currency) => (bool) ($currency['selectable'] ?? false)
                && (bool) ($currency['checkout_enabled'] ?? false))
            ->keys()
            ->values()
            ->all();
    }

    public function selectableCodes(?string $baseCurrency = null): array
    {
        $base = $this->normalize($baseCurrency ?: $this->baseCurrency(), 'EUR');
        $candidates = $this->selectableCandidates();

        $rateReady = CurrencyRate::query()
            ->where('base_currency', $base)
            ->whereIn('target_currency', $candidates)
            ->pluck('target_currency')
            ->map(fn ($code) => strtoupper((string) $code))
            ->all();

        if (empty($rateReady) && ! app()->isProduction()) {
            $rateReady = $candidates;
        }

        return collect(array_merge([$base], $rateReady))
            ->filter(fn ($code) => $this->isKnown($code))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function rateSyncCodes(): array
    {
        return collect($this->all())
            ->filter(fn (array $currency) => (bool) ($currency['rate_sync'] ?? false))
            ->keys()
            ->values()
            ->all();
    }

    public function popularCodes(?array $within = null): array
    {
        $allowed = $within ? array_flip(array_map('strtoupper', $within)) : null;

        return collect($this->all())
            ->filter(fn (array $currency, string $code) => (bool) ($currency['popular'] ?? false)
                && ($allowed === null || isset($allowed[$code])))
            ->keys()
            ->values()
            ->all();
    }

    public function publicPayload(?array $codes = null): array
    {
        $allowed = $codes ? array_flip(array_map('strtoupper', $codes)) : null;

        return collect($this->all())
            ->filter(fn (array $currency, string $code) => $allowed === null || isset($allowed[$code]))
            ->map(fn (array $currency) => [
                'code' => $currency['code'],
                'name' => $currency['name'] ?? $currency['code'],
                'symbol' => $currency['symbol'] ?? $currency['code'],
                'flag_country' => $currency['flag_country'] ?? null,
                'minor_unit' => (int) ($currency['minor_unit'] ?? 2),
                'popular' => (bool) ($currency['popular'] ?? false),
                'selectable' => (bool) ($currency['selectable'] ?? false),
                'checkout_enabled' => (bool) ($currency['checkout_enabled'] ?? false),
            ])
            ->values()
            ->all();
    }

    public function metadata(?string $currency): ?array
    {
        $code = $this->normalize($currency, '');

        return $code ? ($this->all()[$code] ?? null) : null;
    }

    public function symbol(?string $currency): string
    {
        $code = $this->normalize($currency, '');

        return (string) ($this->all()[$code]['symbol'] ?? ($code ?: $this->baseCurrency()));
    }

    public function minorUnit(?string $currency): int
    {
        $code = $this->normalize($currency, $this->baseCurrency());

        return (int) ($this->all()[$code]['minor_unit'] ?? 2);
    }

    public function normalize(?string $currency, string $fallback = 'EUR'): string
    {
        $raw = trim((string) $currency);
        if ($raw === '') {
            return strtoupper($fallback);
        }

        $upper = strtoupper($raw);
        if ($this->isKnown($upper)) {
            return $upper;
        }

        $preferredAliases = [
            '€' => 'EUR',
            '$' => 'USD',
            '£' => 'GBP',
            '¥' => 'JPY',
            '₹' => 'INR',
            'د.إ' => 'AED',
            'EURO' => 'EUR',
            'TL' => 'TRY',
            'US$' => 'USD',
            'USD$' => 'USD',
            'RMB' => 'CNY',
        ];

        if (isset($preferredAliases[$raw])) {
            return $preferredAliases[$raw];
        }

        if (isset($preferredAliases[$upper])) {
            return $preferredAliases[$upper];
        }

        foreach ($this->all() as $code => $meta) {
            $aliases = array_merge(
                [(string) ($meta['symbol'] ?? '')],
                $meta['aliases'] ?? [],
            );

            foreach ($aliases as $alias) {
                if ($alias !== '' && (Str::upper($alias) === $upper || $alias === $raw)) {
                    return $code;
                }
            }
        }

        return strtoupper($fallback);
    }

    public function isKnown(?string $currency): bool
    {
        $code = strtoupper(trim((string) $currency));

        return $code !== '' && isset($this->all()[$code]);
    }

    public function isSelectable(?string $currency): bool
    {
        return in_array($this->normalize($currency, ''), $this->selectableCodes(), true);
    }

    public function currencyForCountry(?string $countryCode): string
    {
        $country = strtoupper(trim((string) $countryCode));

        if ($country === '') {
            return $this->defaultCurrency();
        }

        return $this->countryCurrencyMap()[$country] ?? $this->defaultCurrency();
    }

    private function countryCurrencyMap(): array
    {
        return [
            'US' => 'USD', 'CA' => 'CAD', 'MX' => 'MXN', 'GB' => 'GBP',
            'AE' => 'AED', 'IN' => 'INR', 'JP' => 'JPY', 'AU' => 'AUD',
            'NZ' => 'NZD', 'CH' => 'CHF', 'SE' => 'SEK', 'NO' => 'NOK',
            'DK' => 'DKK', 'CN' => 'CNY', 'HK' => 'HKD', 'SG' => 'SGD',
            'KR' => 'KRW', 'BR' => 'BRL', 'ZA' => 'ZAR', 'TR' => 'TRY',
            'MA' => 'MAD', 'OM' => 'OMR', 'JO' => 'JOD', 'IS' => 'ISK',
            'AZ' => 'AZN', 'MY' => 'MYR', 'TH' => 'THB', 'PH' => 'PHP',
            'ID' => 'IDR', 'VN' => 'VND', 'BD' => 'BDT', 'PK' => 'PKR',
            'LK' => 'LKR', 'SA' => 'SAR', 'IL' => 'ILS', 'QA' => 'QAR',
            'KW' => 'KWD', 'BH' => 'BHD', 'EG' => 'EGP', 'NG' => 'NGN',
            'KE' => 'KES', 'PL' => 'PLN', 'CZ' => 'CZK', 'HU' => 'HUF',
            'RO' => 'RON', 'BG' => 'BGN', 'RU' => 'RUB', 'UA' => 'UAH',
            'DE' => 'EUR', 'FR' => 'EUR', 'IT' => 'EUR', 'ES' => 'EUR',
            'NL' => 'EUR', 'BE' => 'EUR', 'AT' => 'EUR', 'PT' => 'EUR',
            'FI' => 'EUR', 'IE' => 'EUR', 'GR' => 'EUR', 'CY' => 'EUR',
            'MT' => 'EUR', 'SK' => 'EUR', 'SI' => 'EUR', 'EE' => 'EUR',
            'LV' => 'EUR', 'LT' => 'EUR', 'LU' => 'EUR', 'HR' => 'EUR',
        ];
    }
}
