<?php

namespace App\Services\Skyscanner;

class CarHireReportingExportService
{
    public function __construct(
        private readonly CarHireBookingCorrelationService $bookingCorrelationService,
    ) {
    }

    public function exportRows(): array
    {
        return array_values(array_map(
            fn (array $correlation): array => $this->bookingCorrelationService->buildReportRow($correlation),
            $this->bookingCorrelationService->allBookingCorrelations()
        ));
    }
}
