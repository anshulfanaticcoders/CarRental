<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Find the about-us page by its English translation slug
        $aboutUsTranslation = DB::table('page_translations')
            ->where('locale', 'en')
            ->where('slug', 'about-us')
            ->first();

        if (! $aboutUsTranslation) {
            return;
        }

        $pageId = $aboutUsTranslation->page_id;

        $frRow = DB::table('page_translations')
            ->where('page_id', $pageId)
            ->where('locale', 'fr')
            ->first();

        $nlRow = DB::table('page_translations')
            ->where('page_id', $pageId)
            ->where('locale', 'nl')
            ->first();

        if (! $frRow || ! $nlRow) {
            return;
        }

        // The FR row currently has Dutch content ("over-ons") and NL has French ("a-propos-de-nous").
        // Swap all translatable columns between the two rows.
        $swapColumns = ['title', 'slug', 'content'];

        $frValues = [];
        $nlValues = [];
        foreach ($swapColumns as $col) {
            $frValues[$col] = $nlRow->$col ?? null;
            $nlValues[$col] = $frRow->$col ?? null;
        }

        DB::table('page_translations')->where('id', $frRow->id)->update($frValues);
        DB::table('page_translations')->where('id', $nlRow->id)->update($nlValues);
    }

    public function down(): void
    {
        // Running up() again will swap them back
        $this->up();
    }
};
