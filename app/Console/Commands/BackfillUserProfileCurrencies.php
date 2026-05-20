<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\UserProfile;
use App\Services\CountryCodeResolver;
use App\Support\CurrencyRegistry;
use Illuminate\Console\Command;

class BackfillUserProfileCurrencies extends Command
{
    protected $signature = 'profiles:backfill-currencies {--commit : Persist profile currency updates instead of running a dry run}';

    protected $description = 'Backfill empty user profile currencies from latest booking currency, profile country, or the default currency.';

    public function handle(CurrencyRegistry $registry): int
    {
        $commit = (bool) $this->option('commit');
        $selectableCodes = $registry->selectableCodes();

        $profiles = UserProfile::query()
            ->where(fn ($query) => $query->whereNull('currency')->orWhere('currency', ''))
            ->orderBy('id')
            ->get();

        if ($profiles->isEmpty()) {
            $this->info('No empty profile currencies found.');

            return self::SUCCESS;
        }

        $matched = 0;
        foreach ($profiles as $profile) {
            $currency = $this->resolveProfileCurrency($profile, $registry, $selectableCodes);

            $this->line(sprintf(
                '%s profile #%d user #%d currency => %s',
                $commit ? 'Updating' : 'Would update',
                $profile->id,
                $profile->user_id,
                $currency
            ));

            if ($commit) {
                $profile->forceFill(['currency' => $currency])->save();
            }

            $matched++;
        }

        $this->info(sprintf(
            '%s %d profile currency value(s).',
            $commit ? 'Updated' : 'Dry run matched',
            $matched
        ));

        if (! $commit) {
            $this->comment('Dry run only. Re-run with --commit during a maintenance window to persist these values.');
        }

        return self::SUCCESS;
    }

    private function resolveProfileCurrency(UserProfile $profile, CurrencyRegistry $registry, array $selectableCodes): string
    {
        $bookingCurrency = Booking::query()
            ->join('customers', 'customers.id', '=', 'bookings.customer_id')
            ->where('customers.user_id', $profile->user_id)
            ->whereNotNull('bookings.booking_currency')
            ->where('bookings.booking_currency', '<>', '')
            ->orderByDesc('bookings.created_at')
            ->orderByDesc('bookings.id')
            ->value('bookings.booking_currency');

        $normalizedBookingCurrency = $registry->normalize($bookingCurrency, '');
        if ($this->isSelectable($normalizedBookingCurrency, $selectableCodes)) {
            return $normalizedBookingCurrency;
        }

        $countryCurrency = $registry->currencyForCountry($this->countryCodeForProfile($profile->country));
        if ($this->isSelectable($countryCurrency, $selectableCodes)) {
            return $countryCurrency;
        }

        return $registry->defaultCurrency();
    }

    private function countryCodeForProfile(?string $country): ?string
    {
        $country = trim((string) $country);
        if ($country === '') {
            return null;
        }

        $upperCountry = strtoupper($country);
        if (preg_match('/^[A-Z]{2}$/', $upperCountry)) {
            return $upperCountry;
        }

        $resolved = CountryCodeResolver::resolve($country);

        return $resolved !== '' ? $resolved : null;
    }

    private function isSelectable(?string $currency, array $selectableCodes): bool
    {
        return $currency !== null && $currency !== '' && in_array($currency, $selectableCodes, true);
    }
}
