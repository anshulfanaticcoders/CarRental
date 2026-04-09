<?php

namespace App\Http\Controllers\Skyscanner;

use App\Http\Controllers\Controller;
use App\Services\Skyscanner\CarHireAuditLogService;
use App\Services\Skyscanner\CarHireQuoteLifecycleService;
use App\Services\Skyscanner\CarHireQuoteStoreService;
use App\Services\Skyscanner\CarHireSecurityService;
use App\Services\Skyscanner\CarHireTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarHireRedirectController extends Controller
{
    public function __construct(
        private readonly CarHireQuoteStoreService $quoteStoreService,
        private readonly CarHireQuoteLifecycleService $quoteLifecycleService,
        private readonly CarHireSecurityService $securityService,
        private readonly CarHireTrackingService $trackingService,
        private readonly CarHireAuditLogService $auditLogService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $quoteId = (string) $request->query('quote_id', '');
        $signature = (string) $request->query('signature', '');

        if ($quoteId === '') {
            return response()->json([
                'error' => 'quote_id_required',
            ], 400);
        }

        if ($signature !== '' && !$this->securityService->hasValidSignature($request->query())) {
            return response()->json([
                'error' => 'invalid_signature',
            ], 403);
        }

        $quote = $this->quoteStoreService->get($quoteId);

        if ($quote === null) {
            return response()->json([
                'error' => 'quote_not_found',
            ], 404);
        }

        $validation = $this->quoteLifecycleService->revalidate($quote);

        if (($validation['valid'] ?? false) !== true) {
            return response()->json([
                'error' => 'quote_expired',
            ], 410);
        }

        $redirectId = (string) $request->query('skyscanner_redirectid', '');
        $tracking = null;

        if ($redirectId !== '') {
            $this->trackingService->rememberRedirectCorrelation($redirectId, $quoteId, [
                'case_id' => $quote['case_id'] ?? config('skyscanner.case_id'),
                'provider_vehicle_id' => $quote['vehicle']['provider_vehicle_id'] ?? null,
            ]);
            $this->auditLogService->append('quote', $quoteId, 'quote_redirected', [
                'redirect_id' => $redirectId,
                'provider_vehicle_id' => $quote['vehicle']['provider_vehicle_id'] ?? null,
            ]);

            $tracking = [
                'redirect_id' => $redirectId,
                'quote_id' => $quoteId,
            ];
        }

        return response()->json([
            'quote' => $quote,
            'tracking' => $tracking,
        ]);
    }
}
