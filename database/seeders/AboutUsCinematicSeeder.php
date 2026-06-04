<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AboutUsCinematicSeeder extends Seeder
{
    public function run(): void
    {
        $page = DB::table('pages')
            ->where('template', 'about-us')
            ->orWhere('slug', 'about-us')
            ->orderBy('id')
            ->first();

        if (! $page) {
            $this->command?->warn('No about-us page found.');

            return;
        }

        $locales = DB::table('page_translations')
            ->where('page_id', $page->id)
            ->pluck('locale')
            ->filter()
            ->merge($this->supportedLocales())
            ->unique()
            ->values()
            ->all();

        if ($locales === []) {
            $locales = ['en'];
        }

        $sections = $this->sections();
        $now = now();

        DB::transaction(function () use ($page, $locales, $sections, $now) {
            foreach ($sections as $sortOrder => $section) {
                $sectionId = DB::table('page_sections')
                    ->where('page_id', $page->id)
                    ->where('section_type', $section['type'])
                    ->orderBy('id')
                    ->value('id');

                if ($sectionId) {
                    DB::table('page_sections')
                        ->where('id', $sectionId)
                        ->update([
                            'sort_order' => $sortOrder,
                            'is_visible' => true,
                            'updated_at' => $now,
                        ]);
                } else {
                    $sectionId = DB::table('page_sections')->insertGetId([
                        'page_id' => $page->id,
                        'section_type' => $section['type'],
                        'sort_order' => $sortOrder,
                        'is_visible' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                foreach ($locales as $locale) {
                    $this->upsertTranslation($sectionId, $locale, $this->localizedSection($section, $locale), $now);
                }
            }
        });

        $this->command?->info('Seeded cinematic About Us sections for page ID '.$page->id.'.');
    }

    private function upsertTranslation(int $sectionId, string $locale, array $section, mixed $now): void
    {
        $payload = [
            'title' => $section['title'],
            'content' => $section['content'],
            'settings' => $section['settings'] === null ? null : json_encode($section['settings'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'updated_at' => $now,
        ];

        $exists = DB::table('page_section_translations')
            ->where('page_section_id', $sectionId)
            ->where('locale', $locale)
            ->exists();

        if ($exists) {
            DB::table('page_section_translations')
                ->where('page_section_id', $sectionId)
                ->where('locale', $locale)
                ->update($payload);

            return;
        }

        DB::table('page_section_translations')->insert($payload + [
            'page_section_id' => $sectionId,
            'locale' => $locale,
            'created_at' => $now,
        ]);
    }

    private function localizedSection(array $section, string $locale): array
    {
        $translation = $this->sectionTranslations()[$locale][$section['type']] ?? null;

        if (! $translation) {
            return $section;
        }

        return array_replace_recursive($section, $translation);
    }

    private function supportedLocales(): array
    {
        return ['en', 'fr', 'nl', 'es', 'ar'];
    }

    private function sectionTranslations(): array
    {
        return [
            'fr' => [
                'hero' => [
                    'title' => 'Conçu pour chaque kilomètre entre la réservation et le retour.',
                    'content' => '<p>Nous relions les voyageurs à des partenaires de location fiables, à des prix clairs, à des protections utiles et à une assistance présente du comptoir de retrait au retour final.</p>',
                    'settings' => [
                        'badge' => 'Locations de voitures Vrooem',
                        'image_alt' => 'Voiture sportive sur une route ouverte au crépuscule',
                        'primary_button_text' => 'Découvrir notre histoire',
                        'secondary_button_text' => 'Voir ce que nous gérons',
                        'panel_image_alt' => 'Voiture sur une route de montagne au crépuscule',
                        'panel_title' => 'Retrait protégé',
                        'panel_text' => 'Assistance en direct et conditions de location claires.',
                        'side_image_alt' => 'Détail de voiture sportive rouge sous les lumières de la ville',
                    ],
                ],
                'stats' => [
                    'title' => 'Repères de la plateforme Vrooem',
                    'settings' => [
                        'items' => [
                            ['label' => 'Fournisseurs dans le monde'],
                            ['label' => 'Pays couverts'],
                            ['label' => 'Assistance voyage'],
                            ['label' => 'Trajets réalisés'],
                        ],
                    ],
                ],
                'content' => [
                    'title' => 'Le comptoir de location ne devrait pas être le premier endroit où un conducteur découvre les règles.',
                    'content' => '<p>Vrooem comble l’écart entre trouver une voiture et se sentir prêt à la récupérer. Nous rendons les détails importants de la location plus faciles à comparer avant le départ.</p>',
                    'settings' => [
                        'kicker' => 'Pourquoi nous existons',
                        'proof_items' => [
                            ['title' => 'Qualité fournisseur', 'description' => 'Lieux de retrait réels et conditions lisibles.'],
                            ['title' => 'Adapté au trajet', 'description' => 'Catégories de véhicules adaptées à l’itinéraire et aux bagages.'],
                            ['title' => 'Support adapté', 'description' => 'Aide pour les dépôts, les retards et les retours.'],
                        ],
                        'mission_lines' => [
                            ['label' => 'Avant le retrait', 'title' => 'Les conducteurs ont besoin du tableau complet tôt.', 'description' => 'Carburant, dépôt, kilométrage, protection, documents et notes de retrait doivent être visibles avant que la réservation semble finale.'],
                            ['label' => 'Au comptoir', 'title' => 'La confiance vient de moins de surprises.', 'description' => 'Des confirmations claires aident les clients à savoir quoi apporter, ce qui est inclus et quels extras peuvent être proposés.'],
                            ['label' => 'Sur la route', 'title' => 'Les plans changent, le support doit suivre.', 'description' => 'Quand les vols bougent, les créneaux de retrait changent ou des questions de retour apparaissent, l’expérience doit rester prise en main.'],
                        ],
                    ],
                ],
                'split' => [
                    'title' => 'Un parcours de location plus clair, pensé pour les moments qui peuvent mal tourner.',
                    'content' => '<p>Vrooem n’est pas seulement une grille d’offres. C’est une couche de confiance autour du choix du fournisseur, des détails de réservation, des attentes de paiement et du support le jour du voyage.</p>',
                    'settings' => [
                        'kicker' => 'Comment ça marche',
                        'image_alt' => 'Phares de voiture sur une route sinueuse de nuit',
                        'route_note_title' => 'De la recherche au retour, chaque étape a son contexte.',
                        'route_note_text' => 'Montrez au conducteur ce qui compte: carburant, dépôt, kilométrage inclus, protections, consignes au comptoir et chemins de support.',
                        'items' => [
                            ['title' => 'Comparer de vraies options', 'description' => 'Catégorie du véhicule, lieu de retrait, carburant, kilométrage, protection et conditions fournisseur sont présentés avant la réservation.'],
                            ['title' => 'Réserver avec moins de surprises', 'description' => 'Les détails de confirmation restent lisibles, pour savoir quoi apporter et à quoi s’attendre au comptoir.'],
                            ['title' => 'Obtenir de l’aide quand les plans changent', 'description' => 'Retards, changements de vol, prolongations et questions de retour sont orientés vers le support au lieu de laisser les voyageurs deviner.'],
                        ],
                    ],
                ],
                'ribbon' => [
                    'title' => 'Une marque de location avec des réflexes de voyage, pas seulement un inventaire de véhicules.',
                    'content' => '<p>Les bonnes expériences de location dépendent des détails: où se trouve le comptoir, ce que signifie le dépôt, comment fonctionne un retrait tardif et qui répond quand l’itinéraire change.</p>',
                    'settings' => [
                        'background_image_alt' => 'Voiture avançant sur une route de montagne',
                    ],
                ],
                'features' => [
                    'title' => 'Les promesses qui méritent une place sur une page À propos.',
                    'content' => '<p>Cette page explique clairement ce qu’une société de location doit dire: confiance, couverture, support, prix, qualité fournisseur, sécurité et retrait simple.</p>',
                    'settings' => [
                        'kicker' => 'Ce que les clients doivent savoir',
                        'items' => [
                            ['kicker' => 'Offre fiable', 'title' => 'Des partenaires avec de vraies opérations de retrait', 'description' => 'La qualité fournisseur compte quand un client attend au comptoir d’un aéroport avec ses bagages et peu de temps.'],
                            ['kicker' => 'Paiement clair', 'title' => 'Des prix qui s’expliquent eux-mêmes', 'description' => 'Affichez inclusions, extras, protection, dépôts et conditions clés avant l’engagement du client.'],
                            ['kicker' => 'Confiance trajet', 'title' => 'Un support pour les changements d’horaire', 'description' => 'Les vols bougent, les files existent et les fenêtres de retrait comptent. La marque doit être prête pour cette réalité.'],
                            ['kicker' => 'Soin conducteur', 'title' => 'Des protections rendues lisibles', 'description' => 'Les conducteurs doivent comprendre la couverture, la protection dommages, les franchises et le remplacement si une voiture pose problème.'],
                        ],
                    ],
                ],
                'coverage' => [
                    'title' => 'Conçu pour les aéroports, les agences urbaines, les trajets familiaux et les détours de dernière minute.',
                    'content' => '<p>Une bonne page À propos doit aider chaque visiteur à comprendre la même chose: Vrooem réunit options, conditions et support dans une couche de voyage plus calme.</p>',
                    'settings' => [
                        'kicker' => 'Où Vrooem s’intègre',
                        'items' => [
                            ['image_alt' => 'Voiture premium garée dans une rue de ville la nuit', 'title' => 'Locations en ville', 'description' => 'Rendez-vous courts, voyages d’affaires et week-ends ont besoin de comparaison rapide et de notes de retrait claires.'],
                            ['image_alt' => 'Voiture roulant sur une route de montagne panoramique', 'title' => 'Longs trajets', 'description' => 'Kilométrage, bagages, confort et carburant deviennent une partie du plan de voyage.'],
                            ['image_alt' => 'Voiture classique sur une route ouverte avec un ciel spectaculaire', 'title' => 'Réservations grand air', 'description' => 'Les voyageurs choisissent avec confiance quand les conditions sont visibles avant le comptoir.'],
                        ],
                    ],
                ],
                'cta' => [
                    'title' => 'Prêt à choisir la voiture adaptée à votre prochain voyage ?',
                    'content' => '<p>Commencez avec des options claires, des détails de protection utiles et un support présent avant le retrait, au comptoir et sur la route.</p>',
                    'settings' => [
                        'kicker' => 'Commencer à planifier',
                        'button_text' => 'Lancer votre recherche',
                    ],
                ],
            ],
            'nl' => [
                'hero' => [
                    'title' => 'Gebouwd voor elke kilometer tussen boeken en inleveren.',
                    'content' => '<p>Wij verbinden reizigers met betrouwbare verhuurpartners, duidelijke prijzen, nuttige bescherming en support die dichtbij blijft van de balie tot de laatste inlevering.</p>',
                    'settings' => [
                        'badge' => 'Vrooem autoverhuur',
                        'image_alt' => 'Sportwagen op een open weg bij schemering',
                        'primary_button_text' => 'Ontdek ons verhaal',
                        'secondary_button_text' => 'Bekijk wat wij regelen',
                        'panel_image_alt' => 'Auto op een bergweg bij schemering',
                        'panel_title' => 'Ophalen met zekerheid',
                        'panel_text' => 'Live support en duidelijke huurvoorwaarden.',
                        'side_image_alt' => 'Detail van een rode sportwagen in stadslicht',
                    ],
                ],
                'stats' => [
                    'title' => 'Vrooem platform in cijfers',
                    'settings' => [
                        'items' => [
                            ['label' => 'Aanbieders wereldwijd'],
                            ['label' => 'Landen gedekt'],
                            ['label' => 'Reissupport'],
                            ['label' => 'Afgeronde ritten'],
                        ],
                    ],
                ],
                'content' => [
                    'title' => 'De verhuurbalie mag niet de eerste plek zijn waar een bestuurder de regels leert.',
                    'content' => '<p>Vrooem is gebouwd voor de ruimte tussen een auto vinden en klaar zijn om hem op te halen. Wij maken belangrijke huurdetails makkelijker vergelijkbaar voordat de reis begint.</p>',
                    'settings' => [
                        'kicker' => 'Waarom we bestaan',
                        'proof_items' => [
                            ['title' => 'Leveranciersfit', 'description' => 'Echte ophaallocaties en leesbare voorwaarden.'],
                            ['title' => 'Reisfit', 'description' => 'Autoklassen afgestemd op route en bagage.'],
                            ['title' => 'Supportfit', 'description' => 'Hulp bij borg, vertragingen en inleveren.'],
                        ],
                        'mission_lines' => [
                            ['label' => 'Voor het ophalen', 'title' => 'Bestuurders hebben vroeg het volledige huurbeeld nodig.', 'description' => 'Brandstofbeleid, borg, kilometerstand, bescherming, documenten en ophaalnotities moeten zichtbaar zijn voordat een boeking definitief voelt.'],
                            ['label' => 'Aan de balie', 'title' => 'Vertrouwen komt door minder verrassingen.', 'description' => 'Duidelijke bevestigingen helpen klanten te weten wat ze moeten meenemen, wat is inbegrepen en welke extra opties kunnen worden aangeboden.'],
                            ['label' => 'Onderweg', 'title' => 'Reisplannen bewegen, support moet meebewegen.', 'description' => 'Wanneer vluchten schuiven, ophaaltijden veranderen of retourvragen ontstaan, moet de ervaring geregeld blijven voelen.'],
                        ],
                    ],
                ],
                'split' => [
                    'title' => 'Een duidelijkere huurreis, gebouwd rond momenten die fout kunnen gaan.',
                    'content' => '<p>Vrooem is niet alleen een lijst met auto’s. Het is een laag van vertrouwen rond leverancierskeuze, boekingsdetails, betaalverwachtingen en support op de reisdag.</p>',
                    'settings' => [
                        'kicker' => 'Hoe het werkt',
                        'image_alt' => 'Autolichten op een kronkelende nachtweg',
                        'route_note_title' => 'Van zoeken tot inleveren heeft elke stap context.',
                        'route_note_text' => 'Laat de bestuurder zien wat telt: brandstofbeleid, borg, inbegrepen kilometers, beschermingskeuzes, balie-instructies en supportroutes.',
                        'items' => [
                            ['title' => 'Vergelijk echte opties', 'description' => 'Autoklasse, ophaallocatie, brandstofbeleid, kilometers, bescherming en leveranciersvoorwaarden staan vóór het boeken klaar.'],
                            ['title' => 'Boek met minder verrassingen', 'description' => 'Bevestigingsdetails blijven leesbaar, zodat bestuurders weten wat ze meenemen en wat ze aan de balie kunnen verwachten.'],
                            ['title' => 'Krijg hulp wanneer plannen veranderen', 'description' => 'Vertragingen, vluchtwijzigingen, verlengingen en retourvragen worden naar support geleid in plaats van reizigers te laten raden.'],
                        ],
                    ],
                ],
                'ribbon' => [
                    'title' => 'Een verhuurmerk met reisgevoel, niet alleen voertuiginventaris.',
                    'content' => '<p>Goede huurervaringen hangen af van details: waar de balie is, wat de borg betekent, hoe laat ophalen werkt en wie antwoordt wanneer de planning verandert.</p>',
                    'settings' => [
                        'background_image_alt' => 'Auto rijdend over een bergweg',
                    ],
                ],
                'features' => [
                    'title' => 'De beloften die op een Over ons pagina horen.',
                    'content' => '<p>Deze pagina maakt duidelijk wat een autoverhuurbedrijf moet vertellen: vertrouwen, dekking, support, prijzen, leverancierskwaliteit, veiligheid en makkelijk ophalen.</p>',
                    'settings' => [
                        'kicker' => 'Wat klanten moeten weten',
                        'items' => [
                            ['kicker' => 'Betrouwbaar aanbod', 'title' => 'Partners met echte ophaaloperaties', 'description' => 'Leverancierskwaliteit telt wanneer een klant met bagage en weinig tijd aan een luchthavenbalie staat.'],
                            ['kicker' => 'Heldere checkout', 'title' => 'Prijzen die zichzelf uitleggen', 'description' => 'Toon inbegrepen zaken, optionele extra’s, bescherming, borg en kernvoorwaarden voordat de klant boekt.'],
                            ['kicker' => 'Reisvertrouwen', 'title' => 'Support voor timingwijzigingen', 'description' => 'Vluchten veranderen, wachtrijen ontstaan en ophaalvensters zijn belangrijk. Het merk moet klaar zijn voor die werkelijkheid.'],
                            ['kicker' => 'Zorg voor bestuurders', 'title' => 'Beschermingskeuzes leesbaar gemaakt', 'description' => 'Bestuurders moeten dekking, schadebescherming, eigen risico en vervanging begrijpen als een auto problemen heeft.'],
                        ],
                    ],
                ],
                'coverage' => [
                    'title' => 'Gebouwd voor luchthavens, stadsbalies, familieroutes en last-minute omwegen.',
                    'content' => '<p>Een sterke Over ons pagina moet elke bezoeker hetzelfde laten begrijpen: Vrooem brengt huuropties, voorwaarden en support samen in een rustigere reislaag.</p>',
                    'settings' => [
                        'kicker' => 'Waar Vrooem past',
                        'items' => [
                            ['image_alt' => 'Premium auto geparkeerd in een stadsstraat bij nacht', 'title' => 'Stadsverhuur', 'description' => 'Korte afspraken, zakenreizen en weekendplannen vragen om snelle vergelijking en duidelijke ophaalnotities.'],
                            ['image_alt' => 'Auto rijdend over een panoramische bergweg', 'title' => 'Langere routes', 'description' => 'Kilometers, bagageruimte, comfort en brandstofbeleid worden onderdeel van het reisplan.'],
                            ['image_alt' => 'Klassieke auto op een open weg met dramatische lucht', 'title' => 'Open-road boekingen', 'description' => 'Reizigers kiezen met vertrouwen wanneer voorwaarden zichtbaar zijn vóór de balie.'],
                        ],
                    ],
                ],
                'cta' => [
                    'title' => 'Klaar om de auto te kiezen die bij je volgende reis past?',
                    'content' => '<p>Begin met duidelijke huuropties, nuttige beschermingsdetails en support die helpt vóór het ophalen, aan de balie en onderweg.</p>',
                    'settings' => [
                        'kicker' => 'Begin met plannen',
                        'button_text' => 'Start je zoekopdracht',
                    ],
                ],
            ],
            'es' => [
                'hero' => [
                    'title' => 'Pensado para cada kilómetro entre la reserva y la devolución.',
                    'content' => '<p>Conectamos a viajeros con socios de alquiler fiables, precios claros, protección útil y soporte cercano desde el mostrador hasta la entrega final.</p>',
                    'settings' => [
                        'badge' => 'Alquiler de coches Vrooem',
                        'image_alt' => 'Coche deportivo en una carretera abierta al atardecer',
                        'primary_button_text' => 'Descubre la historia',
                        'secondary_button_text' => 'Ver lo que gestionamos',
                        'panel_image_alt' => 'Coche avanzando por una carretera de montaña al atardecer',
                        'panel_title' => 'Recogida protegida',
                        'panel_text' => 'Soporte en directo y condiciones de alquiler claras.',
                        'side_image_alt' => 'Detalle de un coche deportivo rojo bajo luz urbana',
                    ],
                ],
                'stats' => [
                    'title' => 'Datos clave de la plataforma Vrooem',
                    'settings' => [
                        'items' => [
                            ['label' => 'Proveedores en todo el mundo'],
                            ['label' => 'Países cubiertos'],
                            ['label' => 'Soporte de viaje'],
                            ['label' => 'Viajes completados'],
                        ],
                    ],
                ],
                'content' => [
                    'title' => 'El mostrador de alquiler no debería ser el primer lugar donde un conductor aprende las reglas.',
                    'content' => '<p>Vrooem está creado para cubrir el espacio entre encontrar un coche y sentirse listo para recogerlo. Hacemos que los detalles importantes del alquiler sean más fáciles de comparar antes de que empiece el viaje.</p>',
                    'settings' => [
                        'kicker' => 'Por qué existimos',
                        'proof_items' => [
                            ['title' => 'Ajuste de proveedor', 'description' => 'Ubicaciones reales de recogida y condiciones legibles.'],
                            ['title' => 'Ajuste de viaje', 'description' => 'Clases de vehículo alineadas con ruta y equipaje.'],
                            ['title' => 'Ajuste de soporte', 'description' => 'Ayuda para depósitos, retrasos y devoluciones.'],
                        ],
                        'mission_lines' => [
                            ['label' => 'Antes de recoger', 'title' => 'Los conductores necesitan la imagen completa desde el principio.', 'description' => 'Política de combustible, depósito, kilometraje, protección, documentos y notas de recogida deben verse antes de que la reserva parezca final.'],
                            ['label' => 'En el mostrador', 'title' => 'La confianza nace de tener menos sorpresas.', 'description' => 'Las confirmaciones claras ayudan a saber qué llevar, qué está incluido y qué extras opcionales pueden ofrecerse.'],
                            ['label' => 'En carretera', 'title' => 'Los planes cambian, el soporte debe cambiar con ellos.', 'description' => 'Cuando cambian vuelos, ventanas de recogida o dudas de devolución, la experiencia debe seguir sintiéndose atendida.'],
                        ],
                    ],
                ],
                'split' => [
                    'title' => 'Un viaje de alquiler más claro, creado alrededor de los momentos que pueden fallar.',
                    'content' => '<p>Vrooem no es solo una lista de coches. Es una capa de confianza alrededor de la elección del proveedor, los detalles de reserva, las expectativas de pago y el soporte del día de viaje.</p>',
                    'settings' => [
                        'kicker' => 'Cómo funciona',
                        'image_alt' => 'Faros de un coche en una carretera nocturna con curvas',
                        'route_note_title' => 'Desde la búsqueda hasta la devolución, cada paso tiene contexto.',
                        'route_note_text' => 'Muestra al conductor lo que importa: combustible, depósito, kilometraje incluido, protección, instrucciones del mostrador y rutas de soporte.',
                        'items' => [
                            ['title' => 'Compara opciones reales', 'description' => 'Clase de vehículo, ubicación de recogida, combustible, kilometraje, protección y condiciones del proveedor se muestran antes de reservar.'],
                            ['title' => 'Reserva con menos sorpresas', 'description' => 'Los detalles de confirmación siguen siendo claros, para entender qué llevar y qué esperar en el mostrador.'],
                            ['title' => 'Recibe ayuda cuando cambian los planes', 'description' => 'Retrasos, cambios de vuelo, extensiones y preguntas de devolución se dirigen al soporte en lugar de dejar al viajero adivinando.'],
                        ],
                    ],
                ],
                'ribbon' => [
                    'title' => 'Una marca de alquiler con instinto de viaje, no solo inventario de vehículos.',
                    'content' => '<p>Las grandes experiencias de alquiler dependen de detalles: dónde está el mostrador, qué significa el depósito, cómo funciona la recogida tarde y quién responde cuando cambia el itinerario.</p>',
                    'settings' => [
                        'background_image_alt' => 'Coche avanzando por una carretera de montaña',
                    ],
                ],
                'features' => [
                    'title' => 'Las promesas que merecen estar en una página Sobre nosotros.',
                    'content' => '<p>Esta página explica con claridad lo que una empresa de alquiler debe decir: confianza, cobertura, soporte, precios, calidad del proveedor, seguridad y recogida sencilla.</p>',
                    'settings' => [
                        'kicker' => 'Lo que los clientes deben saber',
                        'items' => [
                            ['kicker' => 'Suministro fiable', 'title' => 'Socios con operaciones reales de recogida', 'description' => 'La calidad del proveedor importa cuando un cliente está en un mostrador de aeropuerto con equipaje y poco tiempo.'],
                            ['kicker' => 'Pago claro', 'title' => 'Precios que se explican solos', 'description' => 'Muestra inclusiones, extras opcionales, protección, depósitos y condiciones clave antes de que el cliente reserve.'],
                            ['kicker' => 'Confianza de viaje', 'title' => 'Soporte para cambios de horario', 'description' => 'Los vuelos cambian, hay colas y las ventanas de recogida importan. La marca debe estar preparada para esa realidad.'],
                            ['kicker' => 'Cuidado del conductor', 'title' => 'Opciones de protección fáciles de entender', 'description' => 'Los conductores deben entender cobertura, protección de daños, franquicias y qué ocurre si un coche necesita reemplazo.'],
                        ],
                    ],
                ],
                'coverage' => [
                    'title' => 'Creado para aeropuertos, oficinas urbanas, rutas familiares y desvíos de última hora.',
                    'content' => '<p>Una página Sobre nosotros sólida debe ayudar a todo visitante a entender lo mismo: Vrooem reúne opciones, condiciones y soporte en una capa de viaje más tranquila.</p>',
                    'settings' => [
                        'kicker' => 'Dónde encaja Vrooem',
                        'items' => [
                            ['image_alt' => 'Coche premium aparcado en una calle urbana de noche', 'title' => 'Alquileres urbanos', 'description' => 'Citas cortas, viajes de negocios y escapadas necesitan comparación rápida y notas claras de recogida.'],
                            ['image_alt' => 'Coche circulando por una carretera de montaña panorámica', 'title' => 'Rutas largas', 'description' => 'Kilometraje, espacio de equipaje, confort y combustible forman parte del plan de viaje.'],
                            ['image_alt' => 'Coche clásico en carretera abierta con un cielo dramático', 'title' => 'Reservas de carretera abierta', 'description' => 'Los viajeros eligen con confianza cuando las condiciones se muestran antes del mostrador.'],
                        ],
                    ],
                ],
                'cta' => [
                    'title' => '¿Listo para elegir el coche que encaja con tu próximo viaje?',
                    'content' => '<p>Empieza con opciones claras, detalles útiles de protección y soporte que ayuda antes de la recogida, en el mostrador y en carretera.</p>',
                    'settings' => [
                        'kicker' => 'Empieza a planificar',
                        'button_text' => 'Inicia tu búsqueda',
                    ],
                ],
            ],
            'ar' => [
                'hero' => [
                    'title' => 'مصمم لكل كيلومتر بين الحجز وإرجاع السيارة.',
                    'content' => '<p>نربط المسافرين بشركاء تأجير موثوقين، وأسعار واضحة، وخيارات حماية مفيدة، ودعم يبقى قريبا من مكتب الاستلام حتى التسليم النهائي.</p>',
                    'settings' => [
                        'badge' => 'تأجير سيارات Vrooem',
                        'image_alt' => 'سيارة رياضية على طريق مفتوح وقت الغروب',
                        'primary_button_text' => 'اكتشف القصة',
                        'secondary_button_text' => 'شاهد ما نتولى ترتيبه',
                        'panel_image_alt' => 'سيارة تسير على طريق جبلي وقت الغروب',
                        'panel_title' => 'استلام مع حماية',
                        'panel_text' => 'دعم مباشر وشروط تأجير واضحة.',
                        'side_image_alt' => 'تفاصيل سيارة رياضية حمراء تحت أضواء المدينة',
                    ],
                ],
                'stats' => [
                    'title' => 'لمحات عن منصة Vrooem',
                    'settings' => [
                        'items' => [
                            ['label' => 'مزودون حول العالم'],
                            ['label' => 'دول مغطاة'],
                            ['label' => 'دعم السفر'],
                            ['label' => 'رحلات مكتملة'],
                        ],
                    ],
                ],
                'content' => [
                    'title' => 'لا يجب أن يكون مكتب التأجير أول مكان يتعرف فيه السائق على القواعد.',
                    'content' => '<p>تم بناء Vrooem لسد المسافة بين العثور على سيارة والشعور بالجاهزية لاستلامها. نجعل تفاصيل التأجير المهمة أسهل في المقارنة قبل بدء الرحلة.</p>',
                    'settings' => [
                        'kicker' => 'لماذا نوجد',
                        'proof_items' => [
                            ['title' => 'ملاءمة المورد', 'description' => 'مواقع استلام حقيقية وشروط سهلة القراءة.'],
                            ['title' => 'ملاءمة الرحلة', 'description' => 'فئات سيارات تناسب المسار والأمتعة.'],
                            ['title' => 'ملاءمة الدعم', 'description' => 'مساعدة في الودائع والتأخير والإرجاع.'],
                        ],
                        'mission_lines' => [
                            ['label' => 'قبل الاستلام', 'title' => 'يحتاج السائقون إلى الصورة الكاملة مبكرا.', 'description' => 'سياسة الوقود، الوديعة، الكيلومترات، الحماية، المستندات وملاحظات الاستلام يجب أن تكون واضحة قبل أن يبدو الحجز نهائيا.'],
                            ['label' => 'عند المكتب', 'title' => 'الثقة تأتي من مفاجآت أقل.', 'description' => 'التأكيدات الواضحة تساعد العملاء على معرفة ما يجب إحضاره، وما هو مشمول، وما هي الإضافات الاختيارية التي قد تعرض.'],
                            ['label' => 'على الطريق', 'title' => 'خطط السفر تتغير، والدعم يجب أن يتحرك معها.', 'description' => 'عندما تتغير الرحلات أو نوافذ الاستلام أو تظهر أسئلة الإرجاع، يجب أن تبقى التجربة تحت السيطرة.'],
                        ],
                    ],
                ],
                'split' => [
                    'title' => 'رحلة تأجير أوضح، مبنية حول اللحظات التي قد تسبب المشكلات.',
                    'content' => '<p>Vrooem ليس مجرد قائمة سيارات. إنه طبقة من الثقة حول اختيار المورد، وتفاصيل الحجز، وتوقعات الدفع، ودعم يوم السفر.</p>',
                    'settings' => [
                        'kicker' => 'كيف يعمل',
                        'image_alt' => 'أضواء سيارة على طريق ليلي متعرج',
                        'route_note_title' => 'من البحث إلى الإرجاع، لكل خطوة سياق واضح.',
                        'route_note_text' => 'اعرض للسائق ما يهم: سياسة الوقود، الوديعة، الكيلومترات المشمولة، خيارات الحماية، تعليمات المكتب ومسارات الدعم.',
                        'items' => [
                            ['title' => 'قارن خيارات حقيقية', 'description' => 'فئة السيارة، موقع الاستلام، سياسة الوقود، الكيلومترات، الحماية وشروط المورد تظهر قبل الحجز.'],
                            ['title' => 'احجز بمفاجآت أقل', 'description' => 'تبقى تفاصيل التأكيد سهلة القراءة حتى يعرف السائق ما يحضره وما يتوقعه عند المكتب.'],
                            ['title' => 'احصل على المساعدة عند تغير الخطط', 'description' => 'التأخير، تغييرات الرحلات، التمديدات وأسئلة الإرجاع توجه إلى الدعم بدلا من ترك المسافرين للتخمين.'],
                        ],
                    ],
                ],
                'ribbon' => [
                    'title' => 'علامة تأجير تفهم السفر، وليست مجرد مخزون سيارات.',
                    'content' => '<p>تجارب التأجير الجيدة تعتمد على التفاصيل: أين يوجد المكتب، ماذا تعني الوديعة، كيف يعمل الاستلام المتأخر، ومن يجيب عندما يتغير خط السير.</p>',
                    'settings' => [
                        'background_image_alt' => 'سيارة تسير عبر طريق جبلي',
                    ],
                ],
                'features' => [
                    'title' => 'الوعود التي تستحق الظهور في صفحة من نحن.',
                    'content' => '<p>توضح هذه الصفحة ما يجب أن تقوله شركة تأجير السيارات بوضوح: الثقة، التغطية، الدعم، الأسعار، جودة المورد، السلامة وسهولة الاستلام.</p>',
                    'settings' => [
                        'kicker' => 'ما يجب أن يعرفه العملاء',
                        'items' => [
                            ['kicker' => 'توريد موثوق', 'title' => 'شركاء لديهم عمليات استلام حقيقية', 'description' => 'جودة المورد مهمة عندما يقف العميل عند مكتب المطار ومعه أمتعة ووقت محدود.'],
                            ['kicker' => 'دفع واضح', 'title' => 'أسعار تشرح نفسها', 'description' => 'اعرض ما هو مشمول، والإضافات الاختيارية، والحماية، والودائع، والشروط الأساسية قبل تأكيد العميل.'],
                            ['kicker' => 'ثقة الرحلة', 'title' => 'دعم لتغييرات التوقيت', 'description' => 'الرحلات تتغير، والطوابير تحدث، ونوافذ الاستلام مهمة. يجب أن تكون العلامة جاهزة لهذا الواقع.'],
                            ['kicker' => 'رعاية السائق', 'title' => 'خيارات حماية سهلة الفهم', 'description' => 'يجب أن يفهم السائقون التغطية، وحماية الأضرار، ومبالغ التحمل، وما يحدث إذا احتاجت السيارة إلى استبدال.'],
                        ],
                    ],
                ],
                'coverage' => [
                    'title' => 'مصمم للمطارات، ومكاتب المدن، ورحلات العائلة، والالتفافات في آخر لحظة.',
                    'content' => '<p>صفحة من نحن القوية يجب أن تساعد كل زائر على فهم شيء واحد: Vrooem يجمع خيارات التأجير والشروط والدعم في طبقة سفر أكثر هدوءا.</p>',
                    'settings' => [
                        'kicker' => 'أين يناسب Vrooem',
                        'items' => [
                            ['image_alt' => 'سيارة فاخرة متوقفة في شارع مدينة ليلا', 'title' => 'تأجير داخل المدينة', 'description' => 'المواعيد القصيرة، ورحلات العمل، وخطط نهاية الأسبوع تحتاج إلى مقارنة سريعة وملاحظات استلام واضحة.'],
                            ['image_alt' => 'سيارة تسير على طريق جبلي جميل', 'title' => 'مسارات أطول', 'description' => 'الكيلومترات، ومساحة الأمتعة، والراحة وسياسة الوقود تصبح جزءا من خطة الرحلة.'],
                            ['image_alt' => 'سيارة كلاسيكية على طريق مفتوح مع سماء درامية', 'title' => 'حجوزات الطريق المفتوح', 'description' => 'يمكن للمسافرين الاختيار بثقة عندما تظهر الشروط قبل الوصول إلى المكتب.'],
                        ],
                    ],
                ],
                'cta' => [
                    'title' => 'هل أنت جاهز لاختيار السيارة المناسبة لرحلتك القادمة؟',
                    'content' => '<p>ابدأ بخيارات تأجير واضحة، وتفاصيل حماية مفيدة، ودعم يساعد قبل الاستلام، وعند المكتب، وعلى الطريق.</p>',
                    'settings' => [
                        'kicker' => 'ابدأ التخطيط',
                        'button_text' => 'ابدأ البحث',
                    ],
                ],
            ],
        ];
    }

    private function sections(): array
    {
        $images = [
            'hero' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=2200&q=82',
            'panel' => 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=1200&q=82',
            'side' => 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&w=800&q=82',
            'journey' => 'https://images.unsplash.com/photo-1525609004556-c46c7d6cf023?auto=format&fit=crop&w=1500&q=82',
            'ribbon' => 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=1800&q=82',
            'city' => 'https://images.unsplash.com/photo-1511919884226-fd3cad34687c?auto=format&fit=crop&w=1400&q=82',
            'route' => 'https://images.unsplash.com/photo-1519003722824-194d4455a60c?auto=format&fit=crop&w=900&q=82',
            'openRoad' => 'https://images.unsplash.com/photo-1489824904134-891ab64532f1?auto=format&fit=crop&w=900&q=82',
        ];

        return [
            [
                'type' => 'hero',
                'title' => 'Built for every mile between booking and return.',
                'content' => '<p>We connect travelers with trusted rental partners, clear prices, useful protection, and support that stays close from airport counter to final drop-off.</p>',
                'settings' => [
                    'badge' => 'Vrooem car rentals',
                    'image_url' => $images['hero'],
                    'image_alt' => 'Sports car driving on an open road at dusk',
                    'primary_button_text' => 'Explore the story',
                    'primary_button_url' => '#story',
                    'secondary_button_text' => 'See what we handle',
                    'secondary_button_url' => '#promise',
                    'panel_image_url' => $images['panel'],
                    'panel_image_alt' => 'Car moving along a mountain road at dusk',
                    'panel_title' => 'Pickup, protected',
                    'panel_text' => 'Live support and clear rental terms.',
                    'side_image_url' => $images['side'],
                    'side_image_alt' => 'Red sports car detail under city light',
                ],
            ],
            [
                'type' => 'stats',
                'title' => 'Vrooem platform highlights',
                'content' => null,
                'settings' => [
                    'items' => [
                        ['number' => '800', 'suffix' => '+', 'label' => 'Providers worldwide'],
                        ['number' => '180', 'suffix' => '+', 'label' => 'Countries covered'],
                        ['number' => '24', 'suffix' => '/7', 'label' => 'Travel support'],
                        ['number' => '250', 'suffix' => 'K+', 'label' => 'Trips completed'],
                    ],
                ],
            ],
            [
                'type' => 'content',
                'title' => 'The rental counter should not be the first place a driver learns the rules.',
                'content' => '<p>Vrooem is built for the gap between finding a car and feeling ready to collect it. We make the important rental details easier to compare before the trip starts.</p>',
                'settings' => [
                    'kicker' => 'Why we exist',
                    'proof_items' => [
                        ['icon' => 'building', 'title' => 'Supplier fit', 'description' => 'Real pickup locations and readable terms.'],
                        ['icon' => 'car', 'title' => 'Trip fit', 'description' => 'Vehicle classes matched to route and luggage.'],
                        ['icon' => 'message', 'title' => 'Support fit', 'description' => 'Help for deposits, delays, and returns.'],
                    ],
                    'mission_lines' => [
                        ['label' => 'Before pickup', 'title' => 'Drivers need the full rental picture early.', 'description' => 'Fuel policy, deposit, mileage, protection, documents, and pickup notes should be visible before a booking feels final.'],
                        ['label' => 'At the counter', 'title' => 'Confidence comes from fewer surprises.', 'description' => 'Clear confirmations help customers know what to bring, what is included, and what optional extras may be offered.'],
                        ['label' => 'On the road', 'title' => 'Travel plans move, support should move too.', 'description' => 'When flights shift, pickup windows change, or return questions appear, the experience should still feel handled.'],
                    ],
                ],
            ],
            [
                'type' => 'split',
                'title' => 'A cleaner rental journey, built around the moments that can go wrong.',
                'content' => '<p>The page should tell customers that Vrooem is not only a listing grid. It is a layer of confidence around supplier choice, booking details, payment expectations, and travel-day support.</p>',
                'settings' => [
                    'kicker' => 'How it works',
                    'image_url' => $images['journey'],
                    'image_alt' => 'Car headlights on a winding night road',
                    'route_note_title' => 'From search to return, each step has context.',
                    'route_note_text' => 'Show the driver what matters: fuel policy, deposit, included mileage, protection choices, counter instructions, and support routes.',
                    'items' => [
                        ['title' => 'Compare real options', 'description' => 'Vehicle class, pickup location, fuel policy, mileage, protection, and supplier terms are presented before booking.'],
                        ['title' => 'Book with fewer surprises', 'description' => 'Confirmation details stay readable, so drivers understand what to bring and what to expect at the counter.'],
                        ['title' => 'Get help when plans move', 'description' => 'Delays, flight changes, extensions, and return questions get routed to support instead of leaving travelers guessing.'],
                    ],
                ],
            ],
            [
                'type' => 'ribbon',
                'title' => 'A rental brand with travel instincts, not only vehicle inventory.',
                'content' => '<p>Great rental experiences depend on details: where the desk is, what the deposit means, how late pickup works, and who answers when the itinerary changes.</p>',
                'settings' => [
                    'background_image_url' => $images['ribbon'],
                    'background_image_alt' => 'Car moving through a mountain road',
                ],
            ],
            [
                'type' => 'features',
                'title' => 'The promises worth putting on an About page.',
                'content' => '<p>This concept focuses on the information a car rental company should say clearly: trust, coverage, support, pricing, supplier quality, safety, and easy pickup.</p>',
                'settings' => [
                    'kicker' => 'What customers should know',
                    'items' => [
                        ['icon' => 'building', 'kicker' => 'Trusted supply', 'title' => 'Partners with real pickup operations', 'description' => 'Supplier quality matters when a customer is standing at an airport desk with luggage and limited time.'],
                        ['icon' => 'receipt', 'kicker' => 'Clear checkout', 'title' => 'Pricing that explains itself', 'description' => 'Display inclusions, optional extras, protection, deposits, and key terms before the customer commits.'],
                        ['icon' => 'clock', 'kicker' => 'Trip confidence', 'title' => 'Support for timing changes', 'description' => 'Flights move, queues happen, and pickup windows matter. The brand should feel ready for that reality.'],
                        ['icon' => 'shield', 'kicker' => 'Driver care', 'title' => 'Protection choices made readable', 'description' => 'Drivers should understand coverage, damage protection, excess amounts, and what happens if a car needs replacement.'],
                    ],
                ],
            ],
            [
                'type' => 'coverage',
                'title' => 'Built for airports, city desks, family routes, and last-minute detours.',
                'content' => '<p>A strong About page should help every visitor understand the same thing: Vrooem brings rental options, terms, and support into one calmer travel layer.</p>',
                'settings' => [
                    'kicker' => 'Where Vrooem fits',
                    'items' => [
                        ['image_url' => $images['city'], 'image_alt' => 'Premium car parked on a city street at night', 'title' => 'City rentals', 'description' => 'Short appointments, business trips, and weekend plans need quick comparison and clear pickup notes.'],
                        ['image_url' => $images['route'], 'image_alt' => 'Car driving through a scenic mountain road', 'title' => 'Longer routes', 'description' => 'Mileage, luggage space, comfort, and fuel policy become part of the trip plan.'],
                        ['image_url' => $images['openRoad'], 'image_alt' => 'Classic car on an open road with a dramatic sky', 'title' => 'Open-road bookings', 'description' => 'Travelers can choose with confidence when terms are shown before the counter.'],
                    ],
                ],
            ],
            [
                'type' => 'cta',
                'title' => 'Ready to choose the car that fits your next trip?',
                'content' => '<p>Start with clear rental options, useful protection details, and support that helps before pickup, at the counter, and on the road.</p>',
                'settings' => [
                    'kicker' => 'Start planning',
                    'button_text' => 'Start your search',
                    'button_url' => '/',
                ],
            ],
        ];
    }
}
