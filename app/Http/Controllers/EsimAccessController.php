<?php

namespace App\Http\Controllers;

use App\Services\EsimAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EsimAccessController extends Controller
{
    public function __construct(private EsimAccessService $esimAccessService)
    {
    }

    public function countries(): JsonResponse
    {
        $result = $this->esimAccessService->getCountries();

        return response()->json([
            'success' => $result['success'],
            'data' => $result['data'],
            'error' => $result['error'],
        ], (int) ($result['status'] ?? 500));
    }

    public function plans(string $countryCode): JsonResponse
    {
        $result = $this->esimAccessService->getPlans($countryCode);

        return response()->json([
            'success' => $result['success'],
            'data' => $result['data'],
            'error' => $result['error'],
        ], (int) ($result['status'] ?? 500));
    }

    public function order(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country_code' => 'required|string|size:2',
            'plan_id' => 'required|string',
            'email' => 'required|email',
            'customer_name' => 'required|string|max:255',
        ]);

        $result = $this->esimAccessService->createOrder($validated);

        return response()->json([
            'success' => $result['success'],
            'data' => $result['data'],
            'error' => $result['error'],
        ], (int) ($result['status'] ?? 500));
    }
}
