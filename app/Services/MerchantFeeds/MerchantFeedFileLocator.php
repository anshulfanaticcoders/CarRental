<?php

namespace App\Services\MerchantFeeds;

class MerchantFeedFileLocator
{
    public function path(string $feedName): string
    {
        $path = (string) config("merchant_feeds.{$feedName}.output_path", "feeds/{$feedName}/google-merchant.xml");

        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        return public_path($path);
    }

    private function isAbsolutePath(string $path): bool
    {
        return preg_match('/^(?:[A-Za-z]:[\/\\\\]|\/|\\\\\\\\)/', $path) === 1;
    }
}
