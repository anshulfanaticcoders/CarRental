<?php

namespace App\Helpers;

use App\Models\Blog;
use App\Models\Testimonial;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\PopularPlace; // Added PopularPlace model
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route as LaravelRoute; // To avoid conflict with Inertia's Route

class SchemaBuilder
{
    /**
     * Generate BlogPosting schema.
     *
     * @param Blog $blog The blog post model instance.
     * @return array
     */
    public static function blog(Blog $blog): array
    {
        // Basic BlogPosting schema
        // Ensure your Blog model has relevant attributes like title, content, author, image, published_at, updated_at
        // You might need to adjust property names based on your Blog model's actual attributes.

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => url()->current(), // URL of the blog post page
            ],
            'headline' => $blog->title, // Assuming 'title' attribute
            'description' => $blog->meta_description ?? substr(strip_tags($blog->content), 0, 160), // Assuming 'content' and 'meta_description'
            'image' => [
                '@type' => 'ImageObject',
                'url' => $blog->image_url ?? asset('default-blog-image.jpg'), // Assuming 'image_url' or a default
                // 'width' => 1200, // Optional: specify image width
                // 'height' => 630, // Optional: specify image height
            ],
            'author' => [
                '@type' => 'Person', // Or 'Organization' if applicable
                'name' => $blog->author->name ?? 'Your Site Name', // Assuming Blog has an author relationship with a name attribute
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'Your Site Name'), // Your website/organization name
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('logo.png'), // Path to your site logo
                    // 'width' => 600, // Optional
                    // 'height' => 60,  // Optional
                ],
            ],
            'datePublished' => $blog->published_at ? $blog->published_at->toIso8601String() : now()->toIso8601String(), // Assuming 'published_at' Carbon instance
            'dateModified' => $blog->updated_at ? $blog->updated_at->toIso8601String() : now()->toIso8601String(), // Assuming 'updated_at' Carbon instance
        ];

        // Add articleBody if content is available
        if (!empty($blog->content)) {
            $schema['articleBody'] = strip_tags($blog->content); // Plain text version of the content
        }

        // You can add more properties like keywords, wordCount, etc.
        // 'keywords' => $blog->tags->pluck('name')->implode(', '), // If you have tags

        return $schema;
    }

    // We can add other schema types like product, car, place later
    // public static function product($product) { ... }
    // public static function car($vehicle) { ... }
    // public static function place($place) { ... }

    /**
     * Generate Review schema for a single testimonial.
     *
     * @param Testimonial $testimonial The testimonial model instance.
     * @return array
     */
    public static function testimonial(Testimonial $testimonial): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Review',
            'itemReviewed' => [
                '@type' => 'Organization', // Or 'Service', 'Product' depending on what is reviewed
                'name' => config('app.name', 'Your Company Name'),
                // 'url' => url('/'), // URL of the company/service being reviewed
            ],
            'author' => [
                '@type' => 'Person',
                'name' => $testimonial->name,
                'jobTitle' => $testimonial->designation, // Designation can be jobTitle
            ],
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => (string) $testimonial->ratings, // Ensure it's a string or number
                'bestRating' => '5', // Assuming 5 is the highest rating
                'worstRating' => '1', // Assuming 1 is the lowest rating
            ],
            'reviewBody' => strip_tags($testimonial->review),
            'publisher' => [ // The entity that publishes the review, often the same as itemReviewed or your site
                '@type' => 'Organization',
                'name' => config('app.name', 'Your Company Name'),
                // 'logo' => [
                //     '@type' => 'ImageObject',
                //     'url' => asset('logo.png'), // Path to your site logo
                // ],
            ],
            // 'datePublished' => $testimonial->created_at ? $testimonial->created_at->toIso8601String() : now()->toIso8601String(),
        ];

        if (!empty($testimonial->avatar)) {
            $schema['image'] = [ // Image associated with the review, e.g., author's avatar
                '@type' => 'ImageObject',
                'url' => $testimonial->avatar,
                'caption' => $testimonial->name . ' - Avatar',
            ];
        }

        // If the testimonial has a specific date it was given
        if ($testimonial->created_at) {
            $schema['datePublished'] = $testimonial->created_at->toIso8601String();
        }


        return $schema;
    }

    /**
     * Generate ItemList schema for a collection of testimonials.
     *
     * @param Collection $testimonials Collection of Testimonial models.
     * @param string $pageTitle The title of the page listing the testimonials.
     * @return array
     */
    public static function testimonialList(Collection $testimonials, string $pageTitle = 'Customer Testimonials'): array
    {
        $items = [];
        foreach ($testimonials as $index => $testimonial) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => static::testimonial($testimonial), // Reuse the single testimonial schema
            ];
        }

        if (empty($items)) {
            return [];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $pageTitle,
            'itemListElement' => $items,
            'description' => 'A list of customer testimonials and reviews.',
        ];
    }

    /**
     * Generate FAQPage schema.
     *
    /**
     * Generate ItemList schema for a collection of blog posts.
     *
     * @param \Illuminate\Database\Eloquent\Collection $blogs Collection of Blog models.
     * @param string $pageTitle The title of the page listing the blogs.
     * @return array
     */
    // public static function blogList(\Illuminate\Database\Eloquent\Collection $blogs, string $pageTitle = 'Blog Posts'): array
    // {
    //     $items = [];
    //     foreach ($blogs as $index => $blog) {
    //         $items[] = [
    //             '@type' => 'ListItem',
    //             'position' => $index + 1,
    //             'item' => [
    //                 '@type' => 'BlogPosting',
    //                 '@id' => route('blog.show', ['locale' => app()->getLocale(), 'blog' => $blog->getTranslation(app()->getLocale())?->slug]),
    //                 'headline' => $blog->title,
    //                 'url' => route('blog.show', ['locale' => app()->getLocale(), 'blog' => $blog->getTranslation(app()->getLocale())?->slug]),
    //                 'image' => $blog->image_url ?? asset('default-blog-image.jpg'),
    //                 'datePublished' => $blog->published_at ? $blog->published_at->toIso8601String() : ($blog->created_at ? $blog->created_at->toIso8601String() : now()->toIso8601String()),
    //                 'author' => [
    //                     '@type' => 'Person', // Or 'Organization'
    //                     'name' => $blog->author->name ?? config('app.name', 'Your Site Name'),
    //                 ],
    //                 // Add a short description if available
    //                 'description' => $blog->meta_description ?? substr(strip_tags($blog->content), 0, 100) . '...',
    //             ]
    //         ];
    //     }

    //     return [
    //         '@context' => 'https://schema.org',
    //         '@type' => 'ItemList',
    //         'name' => $pageTitle,
    //         'itemListElement' => $items,
    //         // Optionally, describe what the list is about
    //         // 'description' => 'A list of the latest blog posts.',
    //     ];
    // }
    public static function blogList(\Illuminate\Database\Eloquent\Collection $blogs, string $pageTitle = 'Blog Posts', ?string $country = null): array
    {
        $locale = app()->getLocale();
        $items = [];

        foreach ($blogs as $index => $blog) {
            // Take first country from blog or fallback
            $blogCountry = $country ?? strtolower($blog->countries[0] ?? 'us');
            $slug = $blog->getTranslation($locale)?->slug ?? $blog->slug;

            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => [
                    '@type' => 'BlogPosting',
                    '@id' => route('blog.show', [
                        'locale' => $locale,
                        'country' => $blogCountry,
                        'blog' => $slug,
                    ]),
                    'headline' => $blog->title,
                    'url' => route('blog.show', [
                        'locale' => $locale,
                        'country' => $blogCountry,
                        'blog' => $slug,
                    ]),
                    'image' => $blog->image_url ?? asset('default-blog-image.jpg'),
                    'datePublished' => $blog->published_at
                        ? $blog->published_at->toIso8601String()
                        : ($blog->created_at
                            ? $blog->created_at->toIso8601String()
                            : now()->toIso8601String()),
                    'author' => [
                        '@type' => 'Person',
                        'name' => $blog->author->name ?? config('app.name', 'Your Site Name'),
                    ],
                    'description' => $blog->meta_description ?? substr(strip_tags($blog->content), 0, 100) . '...',
                ],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $pageTitle,
            'itemListElement' => $items,
        ];
    }

    /**
     * Generate ItemList schema for a collection of vehicles.
     *
     * @param \Illuminate\Database\Eloquent\Collection $vehicles Collection of Vehicle models.
     * @param string $pageTitle The title of the page listing the vehicles.
     * @param array $filters The current search filters, to determine price display.
     * @return array
     */
    public static function vehicleList(\Illuminate\Database\Eloquent\Collection $vehicles, string $pageTitle = 'Vehicle Search Results', array $filters = []): array
    {
        $items = [];
        foreach ($vehicles as $index => $vehicle) {
            $primaryImage = null;
            if ($vehicle->images && is_array($vehicle->images)) {
                foreach ($vehicle->images as $image) {
                    if (isset($image['image_type']) && $image['image_type'] === 'primary' && isset($image['image_url'])) {
                        $primaryImage = $image['image_url'];
                        break;
                    }
                }
            }
            if (!$primaryImage && $vehicle->images && is_array($vehicle->images) && count($vehicle->images) > 0 && isset($vehicle->images[0]['image_url'])) {
                $primaryImage = $vehicle->images[0]['image_url']; // Fallback to the first image
            }

            $price = 'N/A';
            $currency = $vehicle->vendor_profile->currency ?? 'USD'; // Default currency
            $availability = 'https://schema.org/InStock'; // Default availability

            // Determine price based on filters or default to daily
            $packageType = $filters['package_type'] ?? 'day';
            if ($packageType === 'day' && isset($vehicle->price_per_day)) {
                $price = $vehicle->price_per_day;
            } elseif ($packageType === 'week' && isset($vehicle->price_per_week)) {
                $price = $vehicle->price_per_week;
            } elseif ($packageType === 'month' && isset($vehicle->price_per_month)) {
                $price = $vehicle->price_per_month;
            } elseif (isset($vehicle->price_per_day)) { // Fallback to daily if specific package price not found
                $price = $vehicle->price_per_day;
            }

            // If vehicle is not available based on booking dates, mark as OutOfStock
            // This logic might need to be more sophisticated based on actual availability checks
            if (isset($filters['date_from']) && isset($filters['date_to'])) {
                // Assuming a simple check, actual implementation would query bookings
                // For now, we'll keep it InStock unless specific logic is added
            }

            $item = [
                '@type' => 'Product', // Using Product schema, could be Vehicle if more specific fields are needed
                '@id' => route('vehicle.show', ['locale' => app()->getLocale(), 'id' => $vehicle->id]),
                'name' => ($vehicle->brand ?? '') . ' ' . ($vehicle->model ?? '') . ' (' . ($vehicle->year ?? 'N/A') . ')',
                'description' => $vehicle->description ?? 'High-quality rental vehicle.',
                'image' => $primaryImage ?? asset('default-vehicle-image.jpg'),
                'brand' => [
                    '@type' => 'Brand',
                    'name' => $vehicle->brand ?? 'Unknown Brand',
                ],
                'model' => $vehicle->model ?? 'Unknown Model',
                // 'vehicleSeatingCapacity' => $vehicle->seating_capacity ?? null, // Example of a vehicle-specific property
                // 'fuelType' => $vehicle->fuel ?? null,
                // 'vehicleTransmission' => $vehicle->transmission ?? null,
            ];

            if ($price !== 'N/A' && is_numeric($price)) {
                $item['offers'] = [
                    '@type' => 'Offer',
                    'priceCurrency' => $currency,
                    'price' => (string) round($price, 2),
                    'availability' => $availability,
                    'url' => route('vehicle.show', ['locale' => app()->getLocale(), 'id' => $vehicle->id]), // Link to the product page
                ];
            }

            // Add aggregateRating if available
            if (isset($vehicle->average_rating) && isset($vehicle->review_count) && $vehicle->review_count > 0) {
                $item['aggregateRating'] = [
                    '@type' => 'AggregateRating',
                    'ratingValue' => round($vehicle->average_rating, 1),
                    'reviewCount' => $vehicle->review_count,
                ];
            }

            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => $item,
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $pageTitle,
            'itemListElement' => $items,
            'description' => 'List of available rental vehicles based on your search criteria.',
        ];
    }

    /**
     * Generate FAQPage schema.
     *
     * @param array $faqs An array of FAQ items, where each item is an associative array with 'question' and 'answer' keys.
     * @return array
     */
    public static function faqPage(array $faqs): array
    {
        $mainEntity = [];
        foreach ($faqs as $faq) {
            if (isset($faq['question']) && isset($faq['answer'])) {
                $mainEntity[] = [
                    '@type' => 'Question',
                    'name' => $faq['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => strip_tags($faq['answer']), // Ensure answer is plain text
                    ],
                ];
            }
        }

        if (empty($mainEntity)) {
            return []; // Return empty if no valid FAQs to prevent schema errors
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $mainEntity,
        ];
    }

    /**
     * Generate Organization schema.
     *
     * @param string $name
     * @param string $url
     * @param string $logoUrl
     * @param string $telephone
     * @param string $email
     * @param array $address Assoc array with keys: streetAddress, addressLocality, addressRegion, postalCode, addressCountry
     * @return array
     */
    public static function organization(string $name, string $url, string $logoUrl, string $telephone, string $email, array $address): array
    {
        // Ensure URL has a scheme
        $parsedUrl = parse_url($url);
        if (empty($parsedUrl['scheme'])) {
            $url = 'https://' . $url; // Default to https
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $name,
            'url' => $url,
            'logo' => $logoUrl,
            'telephone' => $telephone,
            'email' => $email,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $address['streetAddress'] ?? '',
                'addressLocality' => $address['addressLocality'] ?? '',
                'addressRegion' => $address['addressRegion'] ?? '', // Optional, e.g., state or province
                'postalCode' => $address['postalCode'] ?? '',
                'addressCountry' => $address['addressCountry'] ?? '',
            ],
            // Optionally, add sameAs for social media profiles if available
            // 'sameAs' => [
            //    'https://www.facebook.com/yourpage',
            //    'https://www.twitter.com/yourhandle',
            // ]
        ];
    }

    /**
     * Generate Product/Car schema for a single vehicle.
     *
     * @param Vehicle $vehicle The vehicle model instance.
     * @return array
     */
    public static function singleVehicle(Vehicle $vehicle): array
    {
        $primaryImage = $vehicle->images()->where('image_type', 'primary')->first();
        $galleryImages = $vehicle->images()->where('image_type', 'gallery')->get();
        $allImages = collect([$primaryImage])->concat($galleryImages)->filter()->map(fn($img) => $img->image_url)->all();

        $offers = [];
        $currency = $vehicle->vendorProfile->currency ?? 'USD';

        if (isset($vehicle->price_per_day) && $vehicle->price_per_day > 0) {
            $offers[] = [
                '@type' => 'Offer',
                'name' => 'Daily Rental',
                'price' => (string) round($vehicle->price_per_day, 2),
                'priceCurrency' => $currency,
                'availability' => 'https://schema.org/InStock', // Assuming available if listed
                'url' => route('vehicle.show', ['locale' => app()->getLocale(), 'id' => $vehicle->id]),
            ];
        }
        if (isset($vehicle->price_per_week) && $vehicle->price_per_week > 0) {
            $offers[] = [
                '@type' => 'Offer',
                'name' => 'Weekly Rental',
                'price' => (string) round($vehicle->price_per_week, 2),
                'priceCurrency' => $currency,
                'availability' => 'https://schema.org/InStock',
                'url' => route('vehicle.show', ['locale' => app()->getLocale(), 'id' => $vehicle->id]),
            ];
        }
        if (isset($vehicle->price_per_month) && $vehicle->price_per_month > 0) {
            $offers[] = [
                '@type' => 'Offer',
                'name' => 'Monthly Rental',
                'price' => (string) round($vehicle->price_per_month, 2),
                'priceCurrency' => $currency,
                'availability' => 'https://schema.org/InStock',
                'url' => route('vehicle.show', ['locale' => app()->getLocale(), 'id' => $vehicle->id]),
            ];
        }

        $description = "Rent the " . $vehicle->brand . " " . $vehicle->model . ". Features: " .
            $vehicle->seating_capacity . " seats, " . $vehicle->transmission . " transmission, " .
            $vehicle->fuel . " fuel type. Located at " . $vehicle->full_vehicle_address . ".";
        if ($vehicle->description) { // If a more detailed description exists
            $description = $vehicle->description;
        }


        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Car', // Using 'Car' type as it's more specific than 'Product'
            'name' => ($vehicle->brand ?? '') . ' ' . ($vehicle->model ?? '') . ' (' . ($vehicle->year ?? 'N/A') . ')',
            'description' => $description,
            'image' => $allImages, // Array of image URLs
            'sku' => 'VEHICLE-' . $vehicle->id, // Unique SKU
            'mpn' => $vehicle->registration_number ?? $vehicle->id, // Manufacturer Part Number (e.g., VIN or registration)
            'brand' => [
                '@type' => 'Brand',
                'name' => $vehicle->brand ?? 'Unknown Brand',
            ],
            'model' => $vehicle->model ?? 'Unknown Model',
            'vehicleSeatingCapacity' => (string) $vehicle->seating_capacity,
            'fuelType' => $vehicle->fuel,
            'vehicleTransmission' => $vehicle->transmission,
            'color' => $vehicle->color,
            'mileageFromOdometer' => [ // If mileage is total mileage
                '@type' => 'QuantitativeValue',
                'value' => (float) $vehicle->mileage, // Assuming this is total mileage
                'unitCode' => 'KMT' // KMT for kilometer
            ],
            // 'vehicleEngine' => [
            //     '@type' => 'EngineSpecification',
            //     'engineDisplacement' => $vehicle->specifications->engine_displacement ?? null, // e.g., "2.0 L"
            //     'enginePower' => $vehicle->horsepower ? ['@type' => 'QuantitativeValue', 'value' => (float)$vehicle->horsepower, 'unitText' => 'hp'] : null,
            // ],
            'offers' => count($offers) > 1 ? $offers : ($offers[0] ?? null), // If multiple offers, use array, else single offer
            // If you have review data readily available on $vehicle model (e.g., from a withAvg relation)
            // 'aggregateRating' => $vehicle->average_rating ? [
            //     '@type' => 'AggregateRating',
            //     'ratingValue' => round($vehicle->average_rating, 1),
            //     'reviewCount' => $vehicle->review_count ?? 0,
            // ] : null,
            'itemCondition' => 'https://schema.org/UsedCondition', // Assuming most rentals are used
            // Add provider (the vendor)
            'provider' => $vehicle->vendorProfileData ? [
                '@type' => 'Organization',
                'name' => $vehicle->vendorProfileData->company_name ?? $vehicle->vendorProfile->company_name ?? 'Rental Provider',
                'address' => $vehicle->full_vehicle_address, // Or more structured address if available
                // 'telephone' => $vehicle->vendorProfile->phone_number ?? null,
            ] : null,
        ];

        // Remove null values to keep schema clean
        return array_filter($schema, fn($value) => !is_null($value));
    }

    /**
     * Generate ItemList schema for a collection of vehicle categories.
     *
     * @param Collection $categories Collection of VehicleCategory models.
     * @param string $pageTitle The title of the page listing the categories.
     * @return array
     */
    public static function vehicleCategoryList(Collection $categories, string $pageTitle = 'Vehicle Categories'): array
    {
        $items = [];
        foreach ($categories as $index => $category) {
            $item = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => [
                    '@type' => 'ProductCollection', // Using ProductCollection or a more generic Thing
                    'name' => $category->name,
                    'description' => $category->description ?? 'A category of vehicles available for rent.',
                    // Generate a URL to the search page for this category
                    // Ensure you have a route like 'search.category' that accepts a category slug or ID
                    'url' => route('search', ['locale' => app()->getLocale(), 'category_id' => $category->id]),
                ],
            ];
            if (!empty($category->image)) {
                $item['item']['image'] = $category->image;
            }
            $items[] = $item;
        }

        if (empty($items)) {
            return [];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $pageTitle,
            'itemListElement' => $items,
            'description' => 'Browse our diverse range of vehicle categories.',
        ];
    }

    /**
     * Generate ItemList schema for a collection of popular places.
     *
     * @param Collection $places Collection of PopularPlace models.
     * @param string $pageTitle The title for the ItemList.
     * @return array
     */
    public static function popularPlaceList(Collection $places, string $pageTitle = 'Popular Destinations'): array
    {
        $items = [];
        foreach ($places as $index => $place) {
            $placeSchema = [
                '@type' => 'Place',
                'name' => $place->place_name,
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $place->city,
                    'addressCountry' => $place->country,
                ],
                // Construct a URL to a page showing vehicles in/near this place
                // This assumes you have a search route that can filter by location name or coordinates
                'url' => url('/s?where=' . urlencode($place->place_name . ', ' . $place->city . ', ' . $place->country) . '&latitude=' . $place->latitude . '&longitude=' . $place->longitude),
            ];

            if (!empty($place->image)) {
                $placeSchema['image'] = $place->image;
            }
            if (!empty($place->latitude) && !empty($place->longitude)) {
                $placeSchema['geo'] = [
                    '@type' => 'GeoCoordinates',
                    'latitude' => $place->latitude,
                    'longitude' => $place->longitude,
                ];
            }

            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => $placeSchema,
            ];
        }

        if (empty($items)) {
            return [];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $pageTitle,
            'itemListElement' => $items,
            'description' => 'Discover popular destinations for your next trip.',
        ];
    }
}
