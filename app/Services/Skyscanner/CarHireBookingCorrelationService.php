<?php

namespace App\Services\Skyscanner;

use Illuminate\Support\Facades\Cache;

class CarHireBookingCorrelationService
{
    private const INDEX_KEY = 'skyscanner.booking.index';

    public function __construct(
        private readonly CarHireTrackingService $trackingService,
        private readonly CarHireAuditLogService $auditLogService,
    ) {
    }

    public function correlateBooking(string $redirectId, array $booking): ?array
    {
        $redirectCorrelation = $this->trackingService->getRedirectCorrelation($redirectId);

        if ($redirectCorrelation === null) {
            return null;
        }

        $correlation = [
            'redirect_id' => $redirectId,
            'quote_id' => (string) ($redirectCorrelation['quote_id'] ?? ''),
            'case_id' => (string) ($redirectCorrelation['case_id'] ?? config('skyscanner.case_id')),
            'provider_vehicle_id' => (string) ($redirectCorrelation['provider_vehicle_id'] ?? ''),
            'booking_reference' => (string) ($booking['booking_reference'] ?? ''),
            'provider_booking_ref' => (string) ($booking['provider_booking_ref'] ?? ''),
            'booking_currency' => (string) ($booking['booking_currency'] ?? ''),
            'total_amount' => (float) ($booking['total_amount'] ?? 0),
            'booking_status' => (string) ($booking['booking_status'] ?? ''),
            'pickup_date' => (string) ($booking['pickup_date'] ?? ''),
            'return_date' => (string) ($booking['return_date'] ?? ''),
        ];

        Cache::put($this->key($redirectId), $correlation, now()->addDays(7));
        $this->rememberInIndex($redirectId);
        $this->auditLogService->append('redirect', $redirectId, 'booking_correlated', [
            'quote_id' => $correlation['quote_id'],
            'booking_reference' => $correlation['booking_reference'],
            'provider_booking_ref' => $correlation['provider_booking_ref'],
            'status' => $correlation['booking_status'],
            'amount' => $correlation['total_amount'],
            'currency' => $correlation['booking_currency'],
        ]);

        return $correlation;
    }

    public function getBookingCorrelation(string $redirectId): ?array
    {
        $correlation = Cache::get($this->key($redirectId));

        return is_array($correlation) ? $correlation : null;
    }

    public function allBookingCorrelations(): array
    {
        $redirectIds = Cache::get(self::INDEX_KEY, []);

        if (!is_array($redirectIds)) {
            return [];
        }

        $correlations = [];

        foreach (array_values(array_unique($redirectIds)) as $redirectId) {
            if (!is_string($redirectId) || $redirectId === '') {
                continue;
            }

            $correlation = $this->getBookingCorrelation($redirectId);

            if ($correlation !== null) {
                $correlations[] = $correlation;
            }
        }

        return $correlations;
    }

    public function buildReportRow(array $correlation): array
    {
        return [
            'case_id' => (string) ($correlation['case_id'] ?? config('skyscanner.case_id')),
            'redirect_id' => (string) ($correlation['redirect_id'] ?? ''),
            'quote_id' => (string) ($correlation['quote_id'] ?? ''),
            'provider_vehicle_id' => (string) ($correlation['provider_vehicle_id'] ?? ''),
            'booking_reference' => (string) ($correlation['booking_reference'] ?? ''),
            'provider_booking_ref' => (string) ($correlation['provider_booking_ref'] ?? ''),
            'status' => (string) ($correlation['booking_status'] ?? ''),
            'currency' => (string) ($correlation['booking_currency'] ?? ''),
            'amount' => (float) ($correlation['total_amount'] ?? 0),
            'pickup_date' => (string) ($correlation['pickup_date'] ?? ''),
            'return_date' => (string) ($correlation['return_date'] ?? ''),
        ];
    }

    private function key(string $redirectId): string
    {
        return 'skyscanner.booking.' . $redirectId;
    }

    private function rememberInIndex(string $redirectId): void
    {
        $redirectIds = Cache::get(self::INDEX_KEY, []);

        if (!is_array($redirectIds)) {
            $redirectIds = [];
        }

        $redirectIds[] = $redirectId;

        Cache::put(self::INDEX_KEY, array_values(array_unique($redirectIds)), now()->addDays(7));
    }
}
