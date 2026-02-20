<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContactUsPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Clean up existing contact page data if any (check both 'contact' and 'contact-us')
        $existingPage = DB::table('pages')->whereIn('custom_slug', ['contact', 'contact-us'])->first();
        if ($existingPage) {
            DB::table('page_section_translations')->whereIn('page_section_id',
                DB::table('page_sections')->where('page_id', $existingPage->id)->pluck('id')
            )->delete();
            DB::table('page_sections')->where('page_id', $existingPage->id)->delete();
            DB::table('page_meta')->where('page_id', $existingPage->id)->delete();
            DB::table('page_translations')->where('page_id', $existingPage->id)->delete();
            DB::table('pages')->where('id', $existingPage->id)->delete();
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $locales = ['en', 'fr', 'nl', 'es', 'ar'];
        $now = now();

        // 1. Create the page record
        $pageId = DB::table('pages')->insertGetId([
            'slug' => 'contact-' . $now->format('YmdHis'),
            'template' => 'contact-us',
            'custom_slug' => 'contact-us',
            'status' => 'published',
            'sort_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 2. Create page translations
        $translations = [
            'en' => [
                'title' => 'Contact Us',
                'slug' => 'contact',
                'content' => 'Get in touch with the Vrooem team for any questions or assistance.',
            ],
            'fr' => [
                'title' => 'Contactez-nous',
                'slug' => 'contact',
                'content' => 'Contactez l\'équipe Vrooem pour toute question ou assistance.',
            ],
            'nl' => [
                'title' => 'Neem contact op',
                'slug' => 'contact',
                'content' => 'Neem contact op met het Vrooem-team voor vragen of assistentie.',
            ],
            'es' => [
                'title' => 'Contáctanos',
                'slug' => 'contacto',
                'content' => 'Ponte en contacto con el equipo de Vrooem para cualquier pregunta o asistencia.',
            ],
            'ar' => [
                'title' => 'اتصل بنا',
                'slug' => 'اتصل',
                'content' => 'تواصل مع فريق Vrooem لأي أسئلة أو مساعدة.',
            ],
        ];

        foreach ($locales as $locale) {
            DB::table('page_translations')->insert([
                'page_id' => $pageId,
                'locale' => $locale,
                'title' => $translations[$locale]['title'],
                'slug' => $translations[$locale]['slug'],
                'content' => $translations[$locale]['content'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 3. Create non-translatable meta fields (single record for all locales)
        $nonTranslatableMeta = [
            'phone_number' => '+32493000000',
            'email' => 'info@vrooem.com',
            'hero_image_url' => 'https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?w=1920&q=80',
            'map_link' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3239.597544517678!2d4.4651!3d51.2185!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47c3f7e1e1e1e1e1%3A0x6f5e1e1e1e1e1e1e!2sNijverheidsstraat%2070%2C%202160%20Wommelgem%2C%20Belgium!5e0!3m2!1sen!2sbe!4v1620000000000!5m2!1sen!2sbe',
        ];

        foreach ($nonTranslatableMeta as $key => $value) {
            DB::table('page_meta')->insert([
                'page_id' => $pageId,
                'locale' => 'en', // Default locale for non-translatable
                'meta_key' => $key,
                'meta_value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 4. Create translatable meta fields (per locale)
        $translatableMeta = [
            'address' => [
                'en' => "Nijverheidsstraat 70\n2160 Wommelgem\nBelgium",
                'fr' => "Nijverheidsstraat 70\n2160 Wommelgem\nBelgique",
                'nl' => "Nijverheidsstraat 70\n2160 Wommelgem\nBelgië",
                'es' => "Nijverheidsstraat 70\n2160 Wommelgem\nBélgica",
                'ar' => "Nijverheidsstraat 70\n2160 فوميلخيم\nبلجيكا",
            ],
            'contact_points' => [
                'en' => json_encode([
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>',
                        'title' => 'Call Us',
                        'description' => '+32493000000',
                    ],
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>',
                        'title' => 'Email Us',
                        'description' => 'info@vrooem.com',
                    ],
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>',
                        'title' => 'Visit Us',
                        'description' => 'Nijverheidsstraat 70, 2160 Wommelgem, Belgium',
                    ],
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                        'title' => 'Working Hours',
                        'description' => 'Mon - Fri: 8:00 AM - 6:00 PM',
                    ],
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>',
                        'title' => 'WhatsApp',
                        'description' => '+32493000000',
                    ],
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>',
                        'title' => '24/7 Support',
                        'description' => 'Always here to help you',
                    ],
                ]),
                'fr' => json_encode([
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>',
                        'title' => 'Appelez-nous',
                        'description' => '+32493000000',
                    ],
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>',
                        'title' => 'Envoyez-nous un email',
                        'description' => 'info@vrooem.com',
                    ],
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>',
                        'title' => 'Visitez-nous',
                        'description' => 'Nijverheidsstraat 70, 2160 Wommelgem, Belgium',
                    ],
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                        'title' => 'Heures d\'ouverture',
                        'description' => 'Lun - Ven: 8h00 - 18h00',
                    ],
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>',
                        'title' => 'WhatsApp',
                        'description' => '+32493000000',
                    ],
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>',
                        'title' => 'Support 24/7',
                        'description' => 'Toujours là pour vous aider',
                    ],
                ]),
                'nl' => json_encode([
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>',
                        'title' => 'Bel ons',
                        'description' => '+32493000000',
                    ],
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>',
                        'title' => 'E-mail ons',
                        'description' => 'info@vrooem.com',
                    ],
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>',
                        'title' => 'Bezoek ons',
                        'description' => 'Nijverheidsstraat 70, 2160 Wommelgem, Belgium',
                    ],
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                        'title' => 'Openingstijden',
                        'description' => 'Ma - Vr: 08:00 - 18:00',
                    ],
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>',
                        'title' => 'WhatsApp',
                        'description' => '+32493000000',
                    ],
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>',
                        'title' => '24/7 Support',
                        'description' => 'Altijd klaar om te helpen',
                    ],
                ]),
                'es' => json_encode([
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>',
                        'title' => 'Llámanos',
                        'description' => '+32493000000',
                    ],
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>',
                        'title' => 'Envíanos un correo',
                        'description' => 'info@vrooem.com',
                    ],
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>',
                        'title' => 'Visítanos',
                        'description' => 'Nijverheidsstraat 70, 2160 Wommelgem, Belgium',
                    ],
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                        'title' => 'Horario de atención',
                        'description' => 'Lun - Vie: 8:00 AM - 6:00 PM',
                    ],
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>',
                        'title' => 'WhatsApp',
                        'description' => '+32493000000',
                    ],
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>',
                        'title' => 'Soporte 24/7',
                        'description' => 'Siempre aquí para ayudarle',
                    ],
                ]),
                'ar' => json_encode([
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>',
                        'title' => 'اتصل بنا',
                        'description' => '+32493000000',
                    ],
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>',
                        'title' => 'راسلنا عبر البريد',
                        'description' => 'info@vrooem.com',
                    ],
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>',
                        'title' => 'زرنا',
                        'description' => 'Nijverheidsstraat 70, 2160 Wommelgem, Belgium',
                    ],
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                        'title' => 'ساعات العمل',
                        'description' => 'الاثنين - الجمعة: 8:00 صباحاً - 6:00 مساءً',
                    ],
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>',
                        'title' => 'واتساب',
                        'description' => '+32493000000',
                    ],
                    [
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>',
                        'title' => 'دعم 24/7',
                        'description' => 'دائما هنا لمساعدتك',
                    ],
                ]),
            ],
        ];

        foreach ($translatableMeta as $metaKey => $valuesByLocale) {
            foreach ($locales as $locale) {
                DB::table('page_meta')->insert([
                    'page_id' => $pageId,
                    'locale' => $locale,
                    'meta_key' => $metaKey,
                    'meta_value' => $valuesByLocale[$locale],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // 5. Create sections
        $sectionsData = [
            'hero' => [
                'en' => [
                    'title' => 'Get In Touch',
                    'content' => 'Have questions about our car rental services? We\'re here to help. Reach out to us and we\'ll get back to you as soon as possible.',
                ],
                'fr' => [
                    'title' => 'Contactez-nous',
                    'content' => 'Des questions sur nos services de location de voitures? Nous sommes là pour vous aider. Contactez-nous et nous vous répondrons dès que possible.',
                ],
                'nl' => [
                    'title' => 'Neem contact op',
                    'content' => 'Heeft u vragen over onze autoverhuurdiensten? Wij staan klaar om te helpen. Neem contact met ons op en wij nemen zo snel mogelijk contact met u op.',
                ],
                'es' => [
                    'title' => 'Ponte en contacto',
                    'content' => '¿Tienes preguntas sobre nuestros servicios de alquiler de coches? Estamos aquí para ayudar. Ponte en contacto con nosotros y te responderemos lo antes posible.',
                ],
                'ar' => [
                    'title' => 'تواصل معنا',
                    'content' => 'لديك أسئلة حول خدمات تأجير السيارات لدينا؟ نحن هنا للمساعدة. تواصل معنا وسنرد عليك في أقرب وقت ممكن.',
                ],
            ],
            'content' => [
                'en' => [
                    'title' => 'About Vrooem',
                    'content' => 'Vrooem is your trusted partner for car rentals, offering a wide range of vehicles to suit your needs. Whether you need a compact car for city driving or a spacious SUV for a family trip, we have the perfect vehicle for you. Our team is dedicated to providing exceptional service and ensuring your rental experience is smooth and hassle-free.',
                ],
                'fr' => [
                    'title' => 'À propos de Vrooem',
                    'content' => 'Vrooem est votre partenaire de confiance pour la location de voitures, offrant une large gamme de véhicules pour répondre à vos besoins. Que vous ayez besoin d\'une voiture compacte pour la ville ou d\'un SUV spacieux pour un voyage en famille, nous avons le véhicule parfait pour vous. Notre équipe est dédiée à fournir un service exceptionnel et à garantir que votre expérience de location soit fluide et sans tracas.',
                ],
                'nl' => [
                    'title' => 'Over Vrooem',
                    'content' => 'Vrooem is uw betrouwbare partner voor autoverhuur, met een ruim aanbod van voertuigen die aan uw behoeften voldoen. Of u nu een compacte auto nodig heeft voor stadsritten of een ruime SUV voor een familietrip, wij hebben het perfecte voertuig voor u. Ons team is toegewijd aan het leveren van uitstekende service en het zorgen voor een naadloze en zorgeloze verhuurervaring.',
                ],
                'es' => [
                    'title' => 'Sobre Vrooem',
                    'content' => 'Vrooem es su socio de confianza para el alquiler de coches, ofreciendo una amplia gama de vehículos para satisfacer sus necesidades. Ya sea que necesite un coche compacto para conducir por la ciudad o un SUV espacioso para un viaje familiar, tenemos el vehículo perfecto para usted. Nuestro equipo está dedicado a brindar un servicio excepcional y garantizar que su experiencia de alquiler sea fluida y sin complicaciones.',
                ],
                'ar' => [
                    'title' => 'حول Vrooem',
                    'content' => 'Vrooem هي شريكك الموثوق لتأجير السيارات، وتقدم مجموعة واسعة من المركبات التي تناسب احتياجاتك. سواء كنت تحتاج إلى سيارة صغيرة للقيادة في المدينة أو سيارة دفع رباعي فسيحة لرحلة عائلية، فلدينا السيارة المثالية لك. يكرس فريقنا لتقديم خدمة استثنائية وضمان تجربة تأجير سلسة وخالية من المتاعب.',
                ],
            ],
        ];

        $sortOrder = 0;
        foreach ($sectionsData as $sectionType => $translationsByLocale) {
            $sectionId = DB::table('page_sections')->insertGetId([
                'page_id' => $pageId,
                'section_type' => $sectionType,
                'sort_order' => $sortOrder++,
                'is_visible' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Create translations for each section
            foreach ($locales as $locale) {
                DB::table('page_section_translations')->insert([
                    'page_section_id' => $sectionId,
                    'locale' => $locale,
                    'title' => $translationsByLocale[$locale]['title'],
                    'content' => $translationsByLocale[$locale]['content'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $this->command->info('Contact Us page seeded successfully!');
        $this->command->info("Page ID: {$pageId}");
        $this->command->info('Locales seeded: ' . implode(', ', $locales));
    }
}
