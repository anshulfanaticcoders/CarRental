<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use Illuminate\Http\Request;

class AdvertisementController extends Controller
{
    /**
     * Fetch the active advertisement for the homepage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $advertisements = Advertisement::active()->latest()->get();

        return response()->json($advertisements);
    }
}
