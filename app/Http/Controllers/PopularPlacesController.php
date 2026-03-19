<?php

namespace App\Http\Controllers;

use App\Helpers\LocaleHelper;
use App\Helpers\SchemaBuilder;
use App\Models\Page;
use App\Models\PopularPlace;
use App\Services\Seo\SeoMetaResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;

class PopularPlacesController extends Controller
{
    public function index()
    {
        $places = PopularPlace::all(); // Fetch all popular places
        return response()->json($places);
    }

    public function destinations()
    {
        $places = PopularPlace::query()
            ->orderBy('place_name')
            ->get();

        $schemas = [];
        if ($places->isNotEmpty()) {
            $schemas[] = SchemaBuilder::popularPlaceList($places, __('homepage.top_destinations'));
        }

        $seo = app(SeoMetaResolver::class)->resolveForRoute(
            'destinations.index',
            [],
            App::getLocale(),
            route('destinations.index', ['locale' => App::getLocale()])
        )->toArray();

        $pages = Page::with('translations')->get()->keyBy('slug');

        return Inertia::render('Destinations/Index', [
            'popularPlaces' => LocaleHelper::sanitizeUtf8($places),
            'schema' => LocaleHelper::sanitizeUtf8($schemas),
            'seo' => LocaleHelper::sanitizeUtf8($seo),
            'pages' => LocaleHelper::sanitizeUtf8($pages),
        ]);
    }
}
