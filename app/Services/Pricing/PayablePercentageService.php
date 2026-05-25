<?php

namespace App\Services\Pricing;

use App\Models\PayableSetting;
use Throwable;

class PayablePercentageService
{
    public function percentage(float $fallback = 15.0): float
    {
        try {
            $percentage = PayableSetting::query()->value('payment_percentage');
        } catch (Throwable) {
            return $this->sanitize($fallback);
        }

        if ($percentage === null || ! is_numeric($percentage)) {
            return $this->sanitize($fallback);
        }

        return $this->sanitize((float) $percentage);
    }

    public function rate(float $fallback = 15.0): float
    {
        return $this->percentage($fallback) / 100;
    }

    private function sanitize(float $percentage): float
    {
        return min(max($percentage, 0.0), 100.0);
    }
}
