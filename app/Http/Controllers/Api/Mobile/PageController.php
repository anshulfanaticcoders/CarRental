<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    private const ALLOWED_SLUGS = [
        'privacy-policy',
        'terms-and-conditions',
        'about-us',
        'contact-us',
    ];

    public function show(Request $request, string $slug): JsonResponse
    {
        if (! in_array($slug, self::ALLOWED_SLUGS, true)) {
            return response()->json(['message' => 'Page not found.'], 404);
        }

        $locale = $request->query('locale', 'en');
        $locale = is_string($locale) ? $locale : 'en';

        $page = Page::with('translations')
            ->where('custom_slug', $slug)
            ->where('status', 'published')
            ->first();

        if (! $page) {
            return response()->json(['message' => 'Page not found.'], 404);
        }

        $translation = $page->translations->firstWhere('locale', $locale)
            ?? $page->translations->firstWhere('locale', 'en')
            ?? $page->translations->first();

        if (! $translation) {
            return response()->json(['message' => 'Page content unavailable.'], 404);
        }

        return response()->json([
            'slug' => $slug,
            'title' => $translation->title,
            'blocks' => $this->htmlToBlocks((string) $translation->content),
            'updated_at' => $page->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * Convert stored HTML into a flat list of render blocks the mobile app styles natively.
     * Avoids shipping a WebView just to display legal copy.
     */
    private function htmlToBlocks(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }

        // Normalize breaks and strip scripts/styles.
        $html = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $html = preg_replace('#<br\s*/?>#i', "\n", $html) ?? $html;

        $blocks = [];

        // Match block-level elements in document order.
        if (preg_match_all('#<(h[1-6]|p|li)[^>]*>(.*?)</\1>#is', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $tag = strtolower($m[1]);
                $text = trim(html_entity_decode(strip_tags($m[2]), ENT_QUOTES | ENT_HTML5));
                $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
                if ($text === '') {
                    continue;
                }

                if (str_starts_with($tag, 'h')) {
                    $blocks[] = ['type' => 'heading', 'level' => (int) substr($tag, 1), 'text' => $text];
                } elseif ($tag === 'li') {
                    $blocks[] = ['type' => 'list_item', 'text' => $text];
                } else {
                    $blocks[] = ['type' => 'paragraph', 'text' => $text];
                }
            }
        }

        // Fallback: no recognizable block tags — return the whole thing as one paragraph.
        if (empty($blocks)) {
            $text = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5));
            $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
            if ($text !== '') {
                $blocks[] = ['type' => 'paragraph', 'text' => $text];
            }
        }

        return $blocks;
    }
}
