<?php

namespace App\Http\Controllers;

use App\Services\EsimAccessService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EsimController extends Controller
{
    protected $esimAccessService;

    public function __construct(EsimAccessService $esimAccessService)
    {
        $this->esimAccessService = $esimAccessService;
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'country' => 'nullable|string',
        ]);

        $countries = $this->esimAccessService->getCountries();

        $packages = [];
        if (!empty($validated['country'])) {
            $packages = $this->esimAccessService->getPackages($validated['country']);
        }

        return Inertia::render('Esim/Search', [
            'countries' => $countries,
            'packages' => $packages,
            'filters' => $validated,
        ]);
    }
}
