<?php

namespace App\Services\Pricing;

/**
 * THE customer-facing price markup — the same rule checkout charges by
 * (StripeCheckoutController::grossUpProviderAmount): external supplier
 * inventory carries PROVIDER_MARKUP_PERCENT, internal fleet is sold at net.
 *
 * Partner channels (Skyscanner, Trabber, the Awin/Google merchant feed) used
 * to mark prices up by PayableSetting.payment_percentage instead — the
 * admin-editable DEPOSIT percentage. Changing the deposit silently repriced
 * every partner channel, and the feed advertised internal cars above the
 * price the landing page charges.
 */
class CustomerMarkupService
{
    public function rate(): float
    {
        $percent = (float) config('services.pricing.provider_markup_percent', 15);

        return min(max($percent, 0.0), 100.0) / 100;
    }

    public function isExternalSource(?string $source): bool
    {
        $normalized = strtolower(trim((string) $source));

        return $normalized !== '' && $normalized !== 'internal';
    }

    /** The rate that applies to THIS vehicle: markup for external, 0 for internal. */
    public function rateForSource(?string $source): float
    {
        return $this->isExternalSource($source) ? $this->rate() : 0.0;
    }

    public function grossUp(float $netAmount, ?string $source): float
    {
        $rate = $this->rateForSource($source);

        return $rate > 0 ? round($netAmount * (1 + $rate), 2) : round($netAmount, 2);
    }
}
