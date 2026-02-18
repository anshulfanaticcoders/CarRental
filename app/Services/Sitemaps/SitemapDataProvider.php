<?php

namespace App\Services\Sitemaps;

interface SitemapDataProvider
{
    /**
     * @return string[]
     */
    public function getLocales(): array;

    /**
     * @return array<int, array{
     *     page_id: int,
     *     locale: string,
     *     slug: string,
     *     updated_at: mixed,
     *     page_updated_at: mixed
     * }>
     */
    public function getPageTranslations(): array;

    /**
     * @return array<int, array{
     *     id: int,
     *     image: string|null,
     *     countries: array|null,
     *     updated_at: mixed,
     *     translations: array<int, array{
     *         locale: string,
     *         slug: string,
     *         title: string|null,
     *         updated_at: mixed
     *     }>
     * }>
     */
    public function getPublishedBlogs(): array;
}
