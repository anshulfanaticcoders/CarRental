<?php

namespace App\Helpers;

use App\Models\Blog; // Assuming you have a Blog model

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
     * Generate ItemList schema for a collection of blog posts.
     *
     * @param \Illuminate\Database\Eloquent\Collection $blogs Collection of Blog models.
     * @param string $pageTitle The title of the page listing the blogs.
     * @return array
     */
    public static function blogList(\Illuminate\Database\Eloquent\Collection $blogs, string $pageTitle = 'Blog Posts'): array
    {
        $items = [];
        foreach ($blogs as $index => $blog) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => [
                    '@type' => 'BlogPosting',
                    '@id' => route('blog.show', $blog->slug), // Corrected route name
                    'headline' => $blog->title,
                    'url' => route('blog.show', $blog->slug), // Corrected route name
                    'image' => $blog->image_url ?? asset('default-blog-image.jpg'),
                    'datePublished' => $blog->published_at ? $blog->published_at->toIso8601String() : ($blog->created_at ? $blog->created_at->toIso8601String() : now()->toIso8601String()),
                    'author' => [
                        '@type' => 'Person', // Or 'Organization'
                        'name' => $blog->author->name ?? config('app.name', 'Your Site Name'),
                    ],
                    // Add a short description if available
                    'description' => $blog->meta_description ?? substr(strip_tags($blog->content), 0, 100) . '...',
                ]
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $pageTitle,
            'itemListElement' => $items,
            // Optionally, describe what the list is about
            // 'description' => 'A list of the latest blog posts.',
        ];
    }
}
