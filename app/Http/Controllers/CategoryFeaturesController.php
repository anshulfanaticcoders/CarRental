<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleCategory;
use Illuminate\Http\Request;

class CategoryFeaturesController extends Controller
{
    /**
     * Display a listing of the features for a given category.
     *
     * @param  \App\Models\VehicleCategory  $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(VehicleCategory $category)
    {
        // Eager load features and select only necessary fields
        $features = $category->features()->select(['id', 'feature_name', 'icon_url'])->get();
        
        return response()->json($features);
    }
}
