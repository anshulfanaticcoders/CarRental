<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AboutUsPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Clean up existing about-us page data if any
        $existingPage = DB::table('pages')->whereIn('custom_slug', ['about-us', 'about'])->first();
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
            'slug' => 'about-us-' . $now->format('YmdHis'),
            'template' => 'about-us',
            'custom_slug' => 'about-us',
            'status' => 'published',
            'sort_order' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 2. Create page translations
        $translations = [
            'en' => [
                'title' => 'About Us',
                'slug' => 'about-us',
                'content' => 'Learn more about Vrooem and our mission to revolutionize car rental.',
            ],
            'fr' => [
                'title' => 'À propos de nous',
                'slug' => 'a-propos',
                'content' => 'En savoir plus sur Vrooem et notre mission de révolutionner la location de voitures.',
            ],
            'nl' => [
                'title' => 'Over ons',
                'slug' => 'over-ons',
                'content' => 'Meer informatie over Vrooem en onze missie om autoverhuur te revolutioneren.',
            ],
            'es' => [
                'title' => 'Sobre nosotros',
                'slug' => 'sobre-nosotros',
                'content' => 'Conoce más sobre Vrooem y nuestra misión de revolucionar el alquiler de coches.',
            ],
            'ar' => [
                'title' => 'معلومات عنا',
                'slug' => 'معلومات عنا',
                'content' => 'تعرف على المزيد عن Vrooem ومهمتنا في إحداث ثورة في تأجير السيارات.',
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

        // 3. Create non-translatable meta fields
        $nonTranslatableMeta = [
            'team_image' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=1200&q=80',
        ];

        foreach ($nonTranslatableMeta as $key => $value) {
            DB::table('page_meta')->insert([
                'page_id' => $pageId,
                'locale' => 'en',
                'meta_key' => $key,
                'meta_value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 4. Create translatable meta fields
        $translatableMeta = [
            'company_bio' => [
                'en' => '<p>Vrooem was founded with a simple yet powerful vision: to make car rental easy, transparent, and affordable for everyone. We believe that getting behind the wheel shouldn\'t come with hidden fees, confusing terms, or frustrating customer service.</p><p>Our team brings together decades of experience in travel, technology, and customer service. We\'ve built a platform that puts you first, offering a wide selection of vehicles from trusted partners, competitive pricing, and the kind of support that makes your journey smoother from start to finish.</p>',
                'fr' => '<p>Vrooem a été fondé avec une vision simple mais puissante: rendre la location de voitures facile, transparente et abordable pour tous. Nous croyons que prendre le volant ne devrait pas s\'accompagner de frais cachés, de termes confus ou d\'un service client frustrant.</p><p>Notre équipe réunit des décennies d\'expérience dans les voyages, la technologie et le service client. Nous avons construit une plateforme qui vous place en premier, offrant une large sélection de véhicules de partenaires de confiance, des prix compétitifs et un support qui rend votre voyage plus fluide du début à la fin.</p>',
                'nl' => '<p>Vrooem is opgericht met een eenvoudige maar krachtige visie: autoverhuur makkelijk, transparant en betaalbaar maken voor iedereen. Wij geloven dat achter het stuur stappen geen verborgen kosten, verwarrende voorwaarden of frustrerende klantenservice met zich mee moet brengen.</p><p>Ons team brengt decennia aan ervaring samen in reizen, technologie en klantenservice. We hebben een platform gebouwd dat u op de eerste plaats zet, met een breed aanbod aan voertuigen van vertrouwde partners, competitieve prijzen en ondersteuning die uw reis van begin tot eind soepeler maakt.</p>',
                'es' => '<p>Vrooem fue fundado con una visión simple pero poderosa: hacer que el alquiler de coches sea fácil, transparente y asequible para todos. Creemos que tomar el volante no debería implicar tarifas ocultas, términos confusos o un servicio al cliente frustrante.</p><p>Nuestro equipo reúne décadas de experiencia en viajes, tecnología y servicio al cliente. Hemos construido una plataforma que te pone primero, ofreciendo una amplia selección de vehículos de socios de confianza, precios competitivos y el tipo de soporte que hace tu viaje más fluido de principio a fin.</p>',
                'ar' => '<p>تأسس Vrooem برؤية بسيطة لكنها قوية: جعل تأجير السيارات سهلاً وشفافاً وبأسعار في متناول الجميع. نعتقد أن القيادة لا يجب أن تأتي مع رسوم خفية أو شروط مربكة أو خدمة عمل محبطة.</p><p>يجمع فريقنا عقوداً من الخبرة في السفر والتكنولوجيا وخدمة العمل. بنينا منصة تضعك أولاً، وتوفر مجموعة واسعة من المركبات من شركاء موثوقين، وأسعار تنافسية، ونوع من الدعم الذي يجعل رحلتك أكثر سلاسة من البداية إلى النهاية.</p>',
            ],
            'mission_statement' => [
                'en' => '<p>At Vrooem, our mission is to revolutionize the car rental experience by combining cutting-edge technology with genuine human care. We\'re committed to transparency, fair pricing, and exceptional service that puts your needs first. Whether you\'re traveling for business or leisure, alone or with family, we\'re here to ensure your journey is seamless, safe, and enjoyable.</p>',
                'fr' => '<p>Chez Vrooem, notre mission est de révolutionner l\'expérience de location de voitures en combinant une technologie de pointe avec un véritable soin humain. Nous nous engageons envers la transparence, des tarifs équitables et un service exceptionnel qui place vos besoins en premier. Que vous voyagiez pour affaires ou plaisir, seul ou en famille, nous sommes là pour assurer que votre voyage soit transparent, sûr et agréable.</p>',
                'nl' => '<p>Bij Vrooem is onze missie om de autoverhuurervaring te revolutioneren door geavanceerde technologie te combineren met oprechte menselijke zorg. We zetten ons in voor transparantie, eerlijke prijzen en uitstekende service die uw behoeften op de eerste plaats zet. Of u nu reist voor zakelijk of plezier, alleen of met familie, wij zijn er om te zorgen dat uw reis naadloos, veilig en plezierig verloopt.</p>',
                'es' => '<p>En Vrooem, nuestra misión es revolucionar la experiencia de alquiler de coches combinando tecnología de vanguardia con un cuidado humano genuino. Estamos comprometidos con la transparencia, precios justos y un servicio excepcional que pone tus necesidades primero. Ya sea que viajes por negocios o placer, solo o con familia, estamos aquí para asegurar que tu viaje sea fluido, seguro y disfrutable.</p>',
                'ar' => '<p>في Vrooem، مهمتنا هي إحداث ثورة في تجربة تأجير السيارات من خلال الجمع بين التكنولوجيا المتطورة والرعاية الإنسانية الحقيقية. نحن ملتزمون بالشفافية والتسعير العادل والخدمة الاستثنائية التي تضع احتياجاتك أولاً. سواء كنت تسافر للعمل أو الترفيه، بمفردك أو مع عائلتك، نحن هنا لضمان أن تكون رحلتك سلسة وآمنة وممتعة.</p>',
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

        // 5. Create sections with translations and settings
        $sectionsData = [
            'hero' => [
                'en' => [
                    'title' => 'Discover Vrooem',
                    'content' => 'Your trusted partner for hassle-free car rentals across Europe and beyond.',
                ],
                'fr' => [
                    'title' => 'Découvrez Vrooem',
                    'content' => 'Votre partenaire de confiance pour la location de voitures sans tracas en Europe et au-delà.',
                ],
                'nl' => [
                    'title' => 'Ontdek Vrooem',
                    'content' => 'Uw betrouwbare partner voor zorgeloze autoverhuur in Europa en daarbuiten.',
                ],
                'es' => [
                    'title' => 'Descubre Vrooem',
                    'content' => 'Tu socio de confianza para alquiler de coches sin complicaciones en toda Europa y más allá.',
                ],
                'ar' => [
                    'title' => 'اكتشف Vrooem',
                    'content' => 'شريكك الموثوق لتأجير السيارات الخالي من المتاعب في جميع أنحاء أوروبا وما وراءها.',
                ],
            ],
            'content' => [
                'en' => [
                    'title' => 'Our Story',
                    'content' => '<p>Vrooem started with a simple idea: car rental should be easy. No hidden fees, no complicated paperwork, no waiting in long queues. Just select your car, book it, and drive away.</p><p>Today, we\'re proud to serve thousands of customers across multiple European countries, offering a diverse fleet of vehicles to suit every need and budget.</p>',
                ],
                'fr' => [
                    'title' => 'Notre histoire',
                    'content' => '<p>Vrooem a commencé avec une idée simple: la location de voitures devrait être facile. Pas de frais cachés, pas de paperasse compliquée, pas d\'attente dans de longues files. Choisissez simplement votre voiture, réservez-la, et partez.</p><p>Aujourd\'hui, nous sommes fiers de servir des milliers de clients dans plusieurs pays européens, offrant une flotte diversifiée de véhicules pour répondre à chaque besoin et budget.</p>',
                ],
                'nl' => [
                    'title' => 'Ons verhaal',
                    'content' => '<p>Vrooem begon met een simpel idee: autoverhuur moet makkelijk zijn. Geen verborgen kosten, geen ingewikkelde papierwerk, geen wachten in lange rijen. Kies gewoon je auto, boek hem, en rijd weg.</p><p>Vandaag zijn we trots om duizenden klanten in meerdere Europese landen te bedienen, met een divers wagenpark dat aan elke behoefte en budget voldoet.</p>',
                ],
                'es' => [
                    'title' => 'Nuestra historia',
                    'content' => '<p>Vrooem comenzó con una idea simple: el alquiler de coches debería ser fácil. Sin tarifas ocultas, sin papeleo complicado, sin hacer largas colas. Simplemente elige tu coche, resérvalo y conduce.</p><p>Hoy, estamos orgullosos de servir a miles de clientes en varios países europeos, ofreciendo una flota diversa de vehículos para satisfacer cada necesidad y presupuesto.</p>',
                ],
                'ar' => [
                    'title' => 'قصتنا',
                    'content' => '<p>بدأ Vrooem بفكرة بسيطة: تأجير السيارات يجب أن يكون سهلاً. لا رسوم خفية، لا أوراق معقدة، لا انتظار في طوابير طويلة. فقط اختر سيارتك، احجزها، واقود.</p><p>اليوم، نحن فخونون بخدمة آلاف العملاء في عدة دول أوروبية، ونقدم أسطولاً متنوعاً من المركبات لتلبية كل احتياج وميزانية.</p>',
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

        // 6. Create features section with settings
        $featuresSettings = json_encode([
            'items' => [
                ['emoji' => '🚗', 'title' => 'Wide Selection', 'description' => 'Choose from economy cars to luxury vehicles'],
                ['emoji' => '💰', 'title' => 'Best Prices', 'description' => 'Competitive rates with no hidden fees'],
                ['emoji' => '⚡', 'title' => 'Fast Booking', 'description' => 'Book online in just a few clicks'],
                ['emoji' => '🔒', 'title' => 'Secure Payments', 'description' => 'Your data and payments are protected'],
                ['emoji' => '📍', 'title' => 'Convenient Locations', 'description' => 'Pick-up and drop-off across Europe'],
                ['emoji' => '🤝', 'title' => '24/7 Support', 'description' => 'We\'re always here to help you'],
            ],
        ]);

        $featuresSectionId = DB::table('page_sections')->insertGetId([
            'page_id' => $pageId,
            'section_type' => 'features',
            'sort_order' => $sortOrder++,
            'is_visible' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $featuresTranslations = [
            'en' => ['title' => 'Why Choose Vrooem'],
            'fr' => ['title' => 'Pourquoi choisir Vrooem'],
            'nl' => ['title' => 'Waarom Vrooem'],
            'es' => ['title' => 'Por qué elegir Vrooem'],
            'ar' => ['title' => 'لماذا تختار Vrooem'],
        ];

        foreach ($locales as $locale) {
            DB::table('page_section_translations')->insert([
                'page_section_id' => $featuresSectionId,
                'locale' => $locale,
                'title' => $featuresTranslations[$locale]['title'],
                'content' => null,
                'settings' => $featuresSettings,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 7. Create stats section with settings
        $statsSettings = json_encode([
            'subtitle' => 'Trusted by thousands of happy customers',
            'items' => [
                ['number' => '50,000+', 'label' => 'Happy Customers'],
                ['number' => '100+', 'label' => 'Vehicle Locations'],
                ['number' => '500+', 'label' => 'Vehicles Available'],
                ['number' => '24/7', 'label' => 'Customer Support'],
            ],
        ]);

        $statsSectionId = DB::table('page_sections')->insertGetId([
            'page_id' => $pageId,
            'section_type' => 'stats',
            'sort_order' => $sortOrder++,
            'is_visible' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $statsTranslations = [
            'en' => ['title' => 'Our Numbers Speak for Themselves'],
            'fr' => ['title' => 'Nos chiffres parlent d\'eux-mêmes'],
            'nl' => ['title' => 'Onze cijfers spreken voor zich'],
            'es' => ['title' => 'Nuestros números hablan por sí solos'],
            'ar' => ['title' => 'أرقامنا تتحدث بنفسها'],
        ];

        foreach ($locales as $locale) {
            DB::table('page_section_translations')->insert([
                'page_section_id' => $statsSectionId,
                'locale' => $locale,
                'title' => $statsTranslations[$locale]['title'],
                'content' => null,
                'settings' => $statsSettings,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 8. Create split section with settings
        $splitSettings = json_encode([
            'subtitle' => 'Driving innovation in car rental',
            'image_url' => 'https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?w=800&q=80',
        ]);

        $splitSectionId = DB::table('page_sections')->insertGetId([
            'page_id' => $pageId,
            'section_type' => 'split',
            'sort_order' => $sortOrder++,
            'is_visible' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $splitTranslations = [
            'en' => [
                'title' => 'Experience the Future of Car Rental',
                'content' => '<p>Our platform uses advanced algorithms to match you with the perfect vehicle for your journey. Smart filters, real-time availability, and instant confirmation mean you spend less time planning and more time enjoying the ride.</p>',
            ],
            'fr' => [
                'title' => 'Découvrez le futur de la location de voitures',
                'content' => '<p>Notre plateforme utilise des algorithmes avancés pour vous associer au véhicule parfait pour votre voyage. Les filtres intelligents, la disponibilité en temps réel et la confirmation instantanée signifient que vous passez moins de temps à planifier et plus de temps à profiter du trajet.</p>',
            ],
            'nl' => [
                'title' => 'Ervaar de toekomst van autoverhuur',
                'content' => '<p>Ons platform gebruikt geavanceerde algoritmen om u te koppelen aan het perfecte voertuig voor uw reis. Slimme filters, realtime beschikbaarheid en directe bevestiging betekenen dat u minder tijd besteedt aan planning en meer tijd aan het genieten van de rit.</p>',
            ],
            'es' => [
                'title' => 'Experimenta el futuro del alquiler de coches',
                'content' => '<p>Nuestra plataforma utiliza algoritmos avanzados para emparejarte con el vehículo perfecto para tu viaje. Los filtros inteligentes, la disponibilidad en tiempo real y la confirmación instantánea significan que pasas menos tiempo planificando y más tiempo disfrutando del viaje.</p>',
            ],
            'ar' => [
                'title' => 'اختبر مستقبل تأجير السيارات',
                'content' => '<p>تستخدم منصتنا خوارزميات متقدمة لمطابقتك مع السيارة المثالية لرحلتك. تعني الفلاتر الذكية والتوفر في الوقت الفعلي والتأكيد الفوري أنك تقضي وقتاً أقل في التخطيط ووقتاً أطول في الاستمتاع بالرحلة.</p>',
            ],
        ];

        foreach ($locales as $locale) {
            DB::table('page_section_translations')->insert([
                'page_section_id' => $splitSectionId,
                'locale' => $locale,
                'title' => $splitTranslations[$locale]['title'],
                'content' => $splitTranslations[$locale]['content'],
                'settings' => $splitSettings,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 9. Create CTA section with settings
        $ctaSettings = json_encode([
            'button_text' => 'Book Your Ride Today',
            'button_url' => '/en/vehicles',
        ]);

        $ctaSectionId = DB::table('page_sections')->insertGetId([
            'page_id' => $pageId,
            'section_type' => 'cta',
            'sort_order' => $sortOrder++,
            'is_visible' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $ctaTranslations = [
            'en' => [
                'title' => 'Ready to Start Your Journey?',
                'content' => '<p>Book your perfect ride today and experience the Vrooem difference. Quality vehicles, transparent pricing, and exceptional service await you.</p>',
            ],
            'fr' => [
                'title' => 'Prêt à commencer votre voyage?',
                'content' => '<p>Réservez votre véhicule idéal aujourd\'hui et découvrez la différence Vrooem. Des véhicules de qualité, une tarification transparente et un service exceptionnel vous attendent.</p>',
            ],
            'nl' => [
                'title' => 'Klaar om uw reis te beginnen?',
                'content' => '<p>Boek vandaag uw perfecte rit en ervaar het Vrooem-verschil. Kwaliteitsvoertuigen, transparante prijzen en uitstekende service staan op u te wachten.</p>',
            ],
            'es' => [
                'title' => '¿Listo para comenzar tu viaje?',
                'content' => '<p>Reserva tu vehículo perfecto hoy y experimenta la diferencia Vrooem. Vehículos de calidad, precios transparentes y un servicio excepcional te esperan.</p>',
            ],
            'ar' => [
                'title' => 'هل أنت مستعد لبدء رحلتك؟',
                'content' => '<p>احجز رحلتك المثالية اليوم وجرب فارق Vrooem. مركبات عالية الجودة، أسعار شفافة وخدمة استثنائية في انتظارك.</p>',
            ],
        ];

        foreach ($locales as $locale) {
            DB::table('page_section_translations')->insert([
                'page_section_id' => $ctaSectionId,
                'locale' => $locale,
                'title' => $ctaTranslations[$locale]['title'],
                'content' => $ctaTranslations[$locale]['content'],
                'settings' => $ctaSettings,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->info('About Us page seeded successfully!');
        $this->command->info("Page ID: {$pageId}");
        $this->command->info('Locales seeded: ' . implode(', ', $locales));
        $this->command->info('Sections created: hero, content, features, stats, split, cta');
    }
}
