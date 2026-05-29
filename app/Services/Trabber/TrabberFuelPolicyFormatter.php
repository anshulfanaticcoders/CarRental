<?php

namespace App\Services\Trabber;

class TrabberFuelPolicyFormatter
{
    private const LABELS = [
        'ff' => 'Full to Full',
        'full_to_full' => 'Full to Full',
        'sl' => 'Same Level',
        'same_level' => 'Same Level',
        'same_to_same' => 'Same to Same',
        'fe' => 'Full to Empty',
        'full_to_empty' => 'Full to Empty',
        'fp' => 'Free Tank',
        'free_tank' => 'Free Tank',
        'pre_purchase' => 'Pre-purchase',
    ];

    public function label(mixed ...$candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if ($candidate === null) {
                continue;
            }

            $value = trim((string) $candidate);

            if ($value === '') {
                continue;
            }

            return self::LABELS[strtolower($value)] ?? $value;
        }

        return null;
    }
}
