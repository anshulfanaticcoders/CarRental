<?php

namespace App\Services\MerchantFeeds;

use App\Models\MerchantFeedItem;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use XMLWriter;

class GoogleMerchantXmlWriter
{
    public function render(iterable $items, string $feedName = 'awin'): string
    {
        $writer = new XMLWriter;
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->setIndent(true);

        $writer->startElement('rss');
        $writer->writeAttribute('version', '2.0');
        $writer->writeAttribute('xmlns:g', 'http://base.google.com/ns/1.0');

        $writer->startElement('channel');
        $writer->writeElement('title', $this->text(config("merchant_feeds.{$feedName}.title", 'Vrooem Vehicle Rental Feed')));
        $writer->writeElement('link', URL::to('/'));
        $writer->writeElement('description', $this->text(config("merchant_feeds.{$feedName}.description", 'Bookable Vrooem vehicle rental offers.')));

        foreach ($items as $item) {
            $this->writeItem($writer, $item);
        }

        $writer->endElement();
        $writer->endElement();
        $writer->endDocument();

        return $writer->outputMemory();
    }

    public function write(iterable $items, string $path, string $feedName = 'awin'): void
    {
        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Failed to create merchant feed directory {$directory}");
        }

        $tempPath = $path.'.tmp';
        $xml = $this->render($items, $feedName);

        if (file_put_contents($tempPath, $xml, LOCK_EX) === false) {
            throw new RuntimeException("Failed to write merchant feed temp file {$tempPath}");
        }

        if (@rename($tempPath, $path)) {
            return;
        }

        $copied = @copy($tempPath, $path);
        $deleted = @unlink($tempPath);

        if (! $copied || ! $deleted) {
            throw new RuntimeException("Failed to publish merchant feed {$path}");
        }
    }

    private function writeItem(XMLWriter $writer, MerchantFeedItem $item): void
    {
        $writer->startElement('item');

        $this->googleElement($writer, 'id', $item->feed_key);
        $this->googleElement($writer, 'title', $item->title);
        $this->googleElement($writer, 'description', $item->description);
        $this->googleElement($writer, 'link', $item->link);
        $this->googleElement($writer, 'image_link', $item->image_link);
        $this->googleElement($writer, 'price', $this->formatPrice((float) $item->price, $item->currency));
        $this->googleElement($writer, 'availability', $item->availability);
        $this->googleElement($writer, 'brand', $item->brand ?: 'Vrooem');
        $this->googleElement($writer, 'condition', $item->condition ?: 'used');
        $this->googleElement($writer, 'product_type', $item->product_type ?: 'Car Rental');
        $this->googleElement($writer, 'identifier_exists', 'no');

        $writer->endElement();
    }

    private function googleElement(XMLWriter $writer, string $name, mixed $value): void
    {
        $writer->startElement('g:'.$name);
        $writer->text($this->text($value));
        $writer->endElement();
    }

    private function formatPrice(float $price, string $currency): string
    {
        return number_format($price, 2, '.', '').' '.strtoupper($currency);
    }

    private function text(mixed $value): string
    {
        $text = (string) ($value ?? '');
        $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
        if ($converted !== false) {
            $text = $converted;
        }

        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $text) ?? $text;

        return trim($text);
    }
}
