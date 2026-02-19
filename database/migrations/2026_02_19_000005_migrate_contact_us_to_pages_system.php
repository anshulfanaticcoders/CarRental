<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if old table exists and has data
        if (!DB::getSchemaBuilder()->hasTable('contact_us_page')) return;

        $contactUsPage = DB::table('contact_us_page')->first();
        if (!$contactUsPage) return;

        // Check if a contact-us page already exists in the new system
        $existing = DB::table('pages')->where('slug', 'contact-us')->first();
        if ($existing) return;

        // Create the page
        $pageId = DB::table('pages')->insertGetId([
            'slug' => 'contact-us',
            'template' => 'contact-us',
            'custom_slug' => 'contact-us',
            'status' => 'published',
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Migrate non-translatable meta
        $metaFields = [
            'phone_number' => $contactUsPage->phone_number ?? null,
            'email' => $contactUsPage->email ?? null,
            'hero_image_url' => $contactUsPage->hero_image_url ?? null,
        ];
        foreach ($metaFields as $key => $value) {
            if ($value) {
                DB::table('page_meta')->insert([
                    'page_id' => $pageId, 'locale' => 'en',
                    'meta_key' => $key, 'meta_value' => $value,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        // Address meta (non-translatable in old system)
        if (!empty($contactUsPage->address)) {
            DB::table('page_meta')->insert([
                'page_id' => $pageId, 'locale' => 'en',
                'meta_key' => 'address', 'meta_value' => $contactUsPage->address,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Migrate translations
        $translations = DB::table('contact_us_page_translation')
            ->where('contact_us_page_id', $contactUsPage->id)->get();

        // Track if hero section was created (only need one)
        $heroSectionId = null;

        foreach ($translations as $trans) {
            // Page translation
            DB::table('page_translations')->insert([
                'page_id' => $pageId,
                'locale' => $trans->locale,
                'title' => $trans->hero_title ?? 'Contact Us',
                'slug' => 'contact-us',
                'content' => $trans->intro_text ?? '',
                'created_at' => now(), 'updated_at' => now(),
            ]);

            // Contact points meta (translatable)
            if (!empty($trans->contact_points)) {
                DB::table('page_meta')->insert([
                    'page_id' => $pageId, 'locale' => $trans->locale,
                    'meta_key' => 'contact_points', 'meta_value' => $trans->contact_points,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            // Create hero section (once)
            if (!$heroSectionId) {
                $heroSectionId = DB::table('page_sections')->insertGetId([
                    'page_id' => $pageId, 'section_type' => 'hero',
                    'sort_order' => 0, 'is_visible' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            // Hero section translation
            DB::table('page_section_translations')->insert([
                'page_section_id' => $heroSectionId, 'locale' => $trans->locale,
                'title' => $trans->hero_title ?? 'Contact Us',
                'content' => $trans->hero_description ?? '',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Remove the migrated contact-us page
        $page = DB::table('pages')->where('slug', 'contact-us')->where('template', 'contact-us')->first();
        if ($page) {
            DB::table('page_section_translations')
                ->whereIn('page_section_id', function ($q) use ($page) {
                    $q->select('id')->from('page_sections')->where('page_id', $page->id);
                })->delete();
            DB::table('page_sections')->where('page_id', $page->id)->delete();
            DB::table('page_meta')->where('page_id', $page->id)->delete();
            DB::table('page_translations')->where('page_id', $page->id)->delete();
            DB::table('pages')->where('id', $page->id)->delete();
        }
    }
};
