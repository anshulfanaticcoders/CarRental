<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\PopularPlace;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Response;

class SiteMapController extends Controller
{
    public function blogsEn()
    {
        return $this->generateBlogSitemap('en');
    }

    public function blogsFr()
    {
        return $this->generateBlogSitemap('fr');
    }

    public function blogsNl()
    {
        return $this->generateBlogSitemap('nl');
    }

    protected function generateBlogSitemap(string $locale)
    {
        App::setLocale($locale);

        $blogs = Blog::with('translations')->where('is_published', true)->latest()->get();

        $content = '<?xml version="1.0" encoding="UTF-8"?>';
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($blogs as $blog) {
            // Ensure the blog has a translation for the current locale
            $translation = $blog->translations->firstWhere('locale', $locale);
            if ($translation) {
                $url = url("/{$locale}/blog/{$translation->slug}");
                $lastMod = $blog->updated_at->toAtomString(); // Or created_at if preferred

                $content .= '<url>';
                $content .= '<loc>' . htmlspecialchars($url) . '</loc>';
                $content .= '<lastmod>' . $lastMod . '</lastmod>';
                $content .= '<changefreq>daily</changefreq>';
                $content .= '<priority>0.8</priority>';
                $content .= '</url>';
            }
        }

        $content .= '</urlset>';

        return Response::make($content, 200, [
            'Content-Type' => 'application/xml'
        ]);
    }

    public function vehiclesEn()
    {
        return $this->generateVehicleSitemap('en');
    }

    public function vehiclesFr()
    {
        return $this->generateVehicleSitemap('fr');
    }

    public function vehiclesNl()
    {
        return $this->generateVehicleSitemap('nl');
    }

    protected function generateVehicleSitemap(string $locale)
    {
        App::setLocale($locale);

        // Fetch all vehicles that are available
        $vehicles = Vehicle::where('status', 'available')->latest()->get();

        $content = '<?xml version="1.0" encoding="UTF-8"?>';
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($vehicles as $vehicle) {
            // Assuming vehicle IDs are unique and can form a URL
            $url = url("/{$locale}/vehicle/{$vehicle->id}");
            $lastMod = $vehicle->updated_at->toAtomString();

            $content .= '<url>';
            $content .= '<loc>' . htmlspecialchars($url) . '</loc>';
            $content .= '<lastmod>' . $lastMod . '</lastmod>';
            $content .= '<changefreq>daily</changefreq>';
            $content .= '<priority>0.9</priority>'; // Vehicles might be more important than blogs
            $content .= '</url>';
        }

        $content .= '</urlset>';

        return Response::make($content, 200, [
            'Content-Type' => 'application/xml'
        ]);
    }

    public function categoriesEn()
    {
        return $this->generateCategorySitemap('en');
    }

    public function categoriesFr()
    {
        return $this->generateCategorySitemap('fr');
    }

    public function categoriesNl()
    {
        return $this->generateCategorySitemap('nl');
    }

    protected function generateCategorySitemap(string $locale)
    {
        App::setLocale($locale);

        // Fetch all active vehicle categories
        $categories = VehicleCategory::where('status', true)->get(); // Assuming 'status' field indicates active

        $content = '<?xml version="1.0" encoding="UTF-8"?>';
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($categories as $category) {
            // Assuming category has a 'slug' field for URL generation
            // If not, you might need to generate one or use ID.
            // Based on VehicleController::searchByCategory, it uses category_slug.
            $url = url("/{$locale}/search/category/{$category->slug}");
            $lastMod = $category->updated_at->toAtomString();

            $content .= '<url>';
            $content .= '<loc>' . htmlspecialchars($url) . '</loc>';
            $content .= '<lastmod>' . $lastMod . '</lastmod>';
            $content .= '<changefreq>weekly</changefreq>';
            $content .= '<priority>0.7</priority>';
            $content .= '</url>';
        }

        $content .= '</urlset>';

        return Response::make($content, 200, [
            'Content-Type' => 'application/xml'
        ]);
    }

    public function placesEn()
    {
        return $this->generatePlaceSitemap('en');
    }

    public function placesFr()
    {
        return $this->generatePlaceSitemap('fr');
    }

    public function placesNl()
    {
        return $this->generatePlaceSitemap('nl');
    }

    protected function generatePlaceSitemap(string $locale)
    {
        App::setLocale($locale);

        // Fetch all popular places
        $places = PopularPlace::all();

        $content = '<?xml version="1.0" encoding="UTF-8"?>';
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($places as $place) {
            // Assuming PopularPlace has a 'slug' or 'id' that can be used to form a URL.
            // Based on the existing routes, there isn't a specific public route for individual popular places.
            // If there were, it would likely be something like /en/place/{slug} or /en/place/{id}.
            // Construct the URL based on how popular places are linked in Welcome.vue
            // It links to the search page with location parameters.
            $url = url("/{$locale}/s?where=" . urlencode("{$place->place_name}, {$place->city}, {$place->country}") . "&latitude={$place->latitude}&longitude={$place->longitude}&radius=10000");
            $lastMod = $place->updated_at->toAtomString();

            $content .= '<url>';
            $content .= '<loc>' . htmlspecialchars($url) . '</loc>';
            $content .= '<lastmod>' . $lastMod . '</lastmod>';
            $content .= '<changefreq>monthly</changefreq>';
            $content .= '<priority>0.6</priority>';
            $content .= '</url>';
        }

        $content .= '</urlset>';

        return Response::make($content, 200, [
            'Content-Type' => 'application/xml'
        ]);
    }
}
