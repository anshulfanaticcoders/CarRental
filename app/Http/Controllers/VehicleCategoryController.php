<?php

namespace App\Http\Controllers;

use App\Models\VehicleCategory;
use Illuminate\Http\Request;

class VehicleCategoryController extends Controller
{
    public function index()
    {
        // Fetch all vehicle categories
        $categories = VehicleCategory::all(['id', 'name','image']); // Adjust fields as necessary

        return response()->json($categories);
    }
} 