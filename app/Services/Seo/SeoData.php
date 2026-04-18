<?php

namespace App\Services\Seo;

class SeoData
{
    public function __construct(
        public string $title,
        public ?string $description,
        public string $canonical,
        public ?string $image,
        public ?string $robots,
        public string $ogType = 'website',
        public ?string $imageAlt = null,
        public string $siteName = 'Vrooem',
        public string $twitterSite = '@vrooem',
    ) {}

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'canonical' => $this->canonical,
            'image' => $this->image,
            'image_alt' => $this->imageAlt,
            'robots' => $this->robots,
            'og_type' => $this->ogType,
            'site_name' => $this->siteName,
            'twitter_site' => $this->twitterSite,
        ];
    }
}
