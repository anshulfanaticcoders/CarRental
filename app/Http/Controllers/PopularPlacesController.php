<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PopularPlace;

class PopularPlacesController extends Controller
{
    public function index()
    {
        $places = PopularPlace::all(); // Fetch all popular places
        return response()->json($places);
    }
}
