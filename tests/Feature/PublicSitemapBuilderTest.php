<?php

namespace Tests\Feature;

use App\Services\Sitemaps\PublicSitemapBuilder;
use App\Services\Sitemaps\SitemapDataProvider;
use App\Services\Sitemaps\SitemapUrlPolicy;
use Carbon\Carbon;
use Tests\TestCase;

class PublicSitemapBuilderTest extends TestCase
{
    public function test_public_sitemaps_include_expected_urls_and_exclude_forbidden(): void
    {
        $provider = new FakeSitemapDataProvider();
        $builder = new PublicSitemapBuilder($provider, new SitemapUrlPolicy(), 'https://example.com', 2);

        $result = $builder->build();

        $indexXml = $result->index->render();
        $this->assertStringContainsString('https://example.com/sitemaps/static-en.xml', $indexXml);
        $this->assertStringContainsString('https://example.com/sitemaps/pages-en.xml', $indexXml);
        $this->assertStringContainsString('https://example.com/sitemaps/blog-listings-en.xml', $indexXml);
        $this->assertStringContainsString('https://example.com/sitemaps/blogs-en-us-1.xml', $indexXml);

        $combined = '';
        foreach ($result->files as $file) {
            $combined .= $file->sitemap->render();
        }

        $this->assertStringContainsString('https://example.com/en', $combined);
        $this->assertStringContainsString('https://example.com/en/faq', $combined);
        $this->assertStringContainsString('https://example.com/en/contact-us', $combined);
        $this->assertStringContainsString('https://example.com/en/business/register', $combined);
        $this->assertStringContainsString('https://example.com/en/page/about-us', $combined);
        $this->assertStringContainsString('https://example.com/en/us/blog', $combined);
        $this->assertStringContainsString('https://example.com/en/us/blog/hello-world', $combined);

        $this->assertStringNotContainsString('/vehicle/', $combined);
        $this->assertStringNotContainsString('/booking', $combined);
        $this->assertStringNotContainsString('/admin', $combined);
        $this->assertStringNotContainsString('/profile', $combined);
        $this->assertStringNotContainsString('/messages', $combined);
    }
}

class FakeSitemapDataProvider implements SitemapDataProvider
{
    public function getLocales(): array
    {
        return ['en', 'fr'];
    }

    public function getPageTranslations(): array
    {
        return [
            [
                'page_id' => 1,
                'locale' => 'en',
                'slug' => 'about-us',
                'updated_at' => Carbon::parse('2025-01-02'),
                'page_updated_at' => Carbon::parse('2025-01-01'),
            ],
            [
                'page_id' => 1,
                'locale' => 'fr',
                'slug' => 'a-propos',
                'updated_at' => Carbon::parse('2025-01-03'),
                'page_updated_at' => Carbon::parse('2025-01-01'),
            ],
        ];
    }

    public function getPublishedBlogs(): array
    {
        return [
            [
                'id' => 1,
                'image' => 'https://example.com/images/blog1.jpg',
                'countries' => null,
                'canonical_country' => 'us',
                'updated_at' => Carbon::parse('2025-01-05'),
                'translations' => [
                    [
                        'locale' => 'en',
                        'slug' => 'hello-world',
                        'title' => 'Hello World',
                        'updated_at' => Carbon::parse('2025-01-06'),
                    ],
                    [
                        'locale' => 'fr',
                        'slug' => 'bonjour',
                        'title' => 'Bonjour',
                        'updated_at' => Carbon::parse('2025-01-07'),
                    ],
                ],
            ],
            [
                'id' => 2,
                'image' => null,
                'countries' => ['us', 'be'],
                'canonical_country' => 'be',
                'updated_at' => Carbon::parse('2025-01-08'),
                'translations' => [
                    [
                        'locale' => 'en',
                        'slug' => 'us-deal',
                        'title' => 'US Deal',
                        'updated_at' => Carbon::parse('2025-01-08'),
                    ],
                    [
                        'locale' => 'fr',
                        'slug' => 'offre-us',
                        'title' => 'Offre US',
                        'updated_at' => Carbon::parse('2025-01-08'),
                    ],
                ],
            ],
        ];
    }
}
