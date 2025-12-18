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

    public function blogsEs()
    {
        return $this->generateBlogSitemap('es');
    }

    public function blogsAr()
    {
        return $this->generateBlogSitemap('ar');
    }

    /**
     * Generate country-specific blog sitemap
     */
    public function blogsByCountry($country)
    {
        return $this->generateBlogSitemapByCountry($country);
    }

    /**
     * Generate blog listing pages sitemap for all locales and countries
     */
    public function blogListings()
    {
        $locales = ['en', 'fr', 'nl', 'es', 'ar'];

        // Get all unique countries from blogs
        $countries = Blog::where('is_published', true)
            ->whereNotNull('countries')
            ->pluck('countries')
            ->flatten()
            ->unique()
            ->toArray();

        // Add default 'us' if no countries found
        if (empty($countries)) {
            $countries = ['us'];
        }

        $content = '<?xml version="1.0" encoding="UTF-8"?>';
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($locales as $locale) {
            foreach ($countries as $country) {
                $country = strtolower($country);
                $url = url("{$locale}/{$country}/blog");

                $content .= '<url>';
                $content .= '<loc>' . htmlspecialchars($url) . '</loc>';
                $content .= '<lastmod>' . now()->toAtomString() . '</lastmod>';
                $content .= '<changefreq>daily</changefreq>';
                $content .= '<priority>0.9</priority>';
                $content .= '</url>';
            }
        }

        $content .= '</urlset>';

        return Response::make($content, 200, [
            'Content-Type' => 'application/xml'
        ]);
    }

    /**
     * Generate sitemap for a specific country across all locales
     */
    protected function generateBlogSitemapByCountry(string $country)
    {
        $country = strtolower($country);
        $locales = ['en', 'fr', 'nl', 'es', 'ar'];

        $content = '<?xml version="1.0" encoding="UTF-8"?>';
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

        // Add blog listing page for this country
        foreach ($locales as $locale) {
            $url = url("{$locale}/{$country}/blog");
            $content .= '<url>';
            $content .= '<loc>' . htmlspecialchars($url) . '</loc>';
            $content .= '<lastmod>' . now()->toAtomString() . '</lastmod>';
            $content .= '<changefreq>daily</changefreq>';
            $content .= '<priority>0.9</priority>';
            $content .= '</url>';
        }

        // Get blogs for this specific country
        $blogs = Blog::with('translations')
            ->where('is_published', true)
            ->where(function ($query) use ($country) {
                $query->whereJsonContains('countries', $country)
                    ->orWhereNull('countries');
            })
            ->latest()
            ->get();

        foreach ($blogs as $blog) {
            foreach ($locales as $locale) {
                $translation = $blog->translations->firstWhere('locale', $locale);
                if ($translation) {
                    $url = url("{$locale}/{$country}/blog/{$translation->slug}");
                    $lastMod = $blog->updated_at->toAtomString();

                    $content .= '<url>';
                    $content .= '<loc>' . htmlspecialchars($url) . '</loc>';
                    $content .= '<lastmod>' . $lastMod . '</lastmod>';
                    $content .= '<changefreq>daily</changefreq>';
                    $content .= '<priority>0.8</priority>';

                    // Add image if blog has featured image
                    if ($blog->image) {
                        $content .= '<image:image>';
                        $content .= '<image:loc>' . htmlspecialchars($blog->image) . '</image:loc>';
                        $content .= '<image:title>' . htmlspecialchars($translation->title) . '</image:title>';
                        if ($blog->meta_description) {
                            $content .= '<image:caption>' . htmlspecialchars($blog->meta_description) . '</image:caption>';
                        }
                        $content .= '</image:image>';
                    }

                    $content .= '</url>';
                }
            }
        }

        $content .= '</urlset>';

        return Response::make($content, 200, [
            'Content-Type' => 'application/xml'
        ]);
    }

    protected function generateBlogSitemap(string $locale)
    {
        App::setLocale($locale);

        $blogs = Blog::with('translations')->where('is_published', true)->latest()->get();

        $content = '<?xml version="1.0" encoding="UTF-8"?>';
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

        foreach ($blogs as $blog) {
            // Ensure the blog has a translation for the current locale
            $translation = $blog->translations->firstWhere('locale', $locale);
            if ($translation) {
                // Get countries for this blog, default to 'us' if none specified
                $countries = !empty($blog->countries) ? $blog->countries : ['us'];

                foreach ($countries as $country) {
                    $country = strtolower($country);
                    $url = url("{$locale}/{$country}/blog/{$translation->slug}");
                    $lastMod = $blog->updated_at->toAtomString();

                    $content .= '<url>';
                    $content .= '<loc>' . htmlspecialchars($url) . '</loc>';
                    $content .= '<lastmod>' . $lastMod . '</lastmod>';
                    $content .= '<changefreq>daily</changefreq>';
                    $content .= '<priority>0.8</priority>';

                    // Add image if blog has featured image
                    if ($blog->image) {
                        $content .= '<image:image>';
                        $content .= '<image:loc>' . htmlspecialchars($blog->image) . '</image:loc>';
                        $content .= '<image:title>' . htmlspecialchars($translation->title) . '</image:title>';
                        if ($blog->meta_description) {
                            $content .= '<image:caption>' . htmlspecialchars($blog->meta_description) . '</image:caption>';
                        }
                        $content .= '</image:image>';
                    }

                    $content .= '</url>';
                }
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
