<?php

namespace App\Http\Controllers;

use App\Services\GreenMotionService;
use Illuminate\Http\Request;

class GreenMotionController extends Controller
{
    public function __construct(private GreenMotionService $greenMotionService)
    {
    }

    public function getGreenMotionVehicles()
    {
        $vehicles = $this->greenMotionService->getVehicles(61627, '2032-01-06', '09:00', '2032-01-08', '09:00', 1, 35);

        return $vehicles;
    }
}
