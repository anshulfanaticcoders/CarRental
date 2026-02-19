<?php

namespace App\Services\Seo;

class SeoData
{
    public function __construct(
        public string $title,
        public ?string $description,
        public string $canonical,
        public ?string $image,
        public ?string $robots
    ) {
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'canonical' => $this->canonical,
            'image' => $this->image,
            'robots' => $this->robots,
        ];
    }
}
