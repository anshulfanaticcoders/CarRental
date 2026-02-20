<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TermsAndConditionsPageSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $existingPage = DB::table('pages')->where('custom_slug', 'terms-and-conditions')->first();
        if ($existingPage) {
            DB::table('page_section_translations')->whereIn('page_section_id',
                DB::table('page_sections')->where('page_id', $existingPage->id)->pluck('id')
            )->delete();
            DB::table('page_sections')->where('page_id', $existingPage->id)->delete();
            DB::table('page_meta')->where('page_id', $existingPage->id)->delete();
            DB::table('page_translations')->where('page_id', $existingPage->id)->delete();
            DB::table('pages')->where('id', $existingPage->id)->delete();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $locales = ['en', 'fr', 'nl', 'es', 'ar'];
        $now = now();

        $pageId = DB::table('pages')->insertGetId([
            'slug' => 'terms-and-conditions-' . $now->format('YmdHis'),
            'template' => 'legal',
            'custom_slug' => 'terms-and-conditions',
            'status' => 'published',
            'sort_order' => 4,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $contents = $this->getContentByLocale();

        foreach ($locales as $locale) {
            DB::table('page_translations')->insert([
                'page_id' => $pageId,
                'locale' => $locale,
                'title' => $contents[$locale]['title'],
                'slug' => $contents[$locale]['slug'],
                'content' => $contents[$locale]['content'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('page_meta')->insert([
            ['page_id' => $pageId, 'locale' => 'en', 'meta_key' => 'effective_date', 'meta_value' => '2025-01-01', 'created_at' => $now, 'updated_at' => $now],
            ['page_id' => $pageId, 'locale' => 'en', 'meta_key' => 'last_updated', 'meta_value' => '2025-01-20', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $sectionId = DB::table('page_sections')->insertGetId([
            'page_id' => $pageId,
            'section_type' => 'content',
            'sort_order' => 0,
            'is_visible' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($locales as $locale) {
            DB::table('page_section_translations')->insert([
                'page_section_id' => $sectionId,
                'locale' => $locale,
                'title' => null,
                'content' => $contents[$locale]['content'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->info('Terms & Conditions page seeded successfully!');
        $this->command->info("Page ID: {$pageId}");
    }

    private function getContentByLocale(): array
    {
        return [
            'en' => ['title' => 'Terms & Conditions', 'slug' => 'terms-and-conditions', 'content' => $this->getEnglishContent()],
            'fr' => ['title' => 'Conditions générales', 'slug' => 'conditions-generales', 'content' => $this->getFrenchContent()],
            'nl' => ['title' => 'Algemene voorwaarden', 'slug' => 'algemene-voorwaarden', 'content' => $this->getDutchContent()],
            'es' => ['title' => 'Términos y condiciones', 'slug' => 'terminos-y-condiciones', 'content' => $this->getSpanishContent()],
            'ar' => ['title' => 'الشروط والأحكام', 'slug' => 'الشروط-والأحكام', 'content' => $this->getArabicContent()],
        ];
    }

    private function getEnglishContent(): string
    {
        return '<h2>1. Introduction</h2>
<p>Welcome to Vrooem.com, a digital platform connecting car rental providers with users seeking vehicle rentals. By using our website, app, or services, you agree to comply with these Terms & Conditions. If you do not agree, please refrain from using our platform.</p>

<h2>2. Definitions</h2>
<p><strong>Vrooem.com</strong> – Refers to the website, application, and services offered by Vrooem.</p>
<p><strong>User</strong> – Any individual or entity using Vrooem.com.</p>
<p><strong>Rental Provider</strong> – A professional business listing vehicles for rent on our platform.</p>
<p><strong>Vehicle</strong> – Any car, van, camper, or other rental option available through Vrooem.com.</p>
<p><strong>Booking</strong> – A confirmed vehicle rental transaction made via Vrooem.com.</p>

<h2>3. Eligibility</h2>
<p>To use Vrooem.com, you must:</p>
<ul>
<li>Be at least 18 years old.</li>
<li>Have a valid driver\'s license (specific conditions may vary by rental provider).</li>
<li>Agree to provide accurate and up-to-date information.</li>
<li>Not be previously banned or restricted from the platform.</li>
</ul>

<h2>4. Account Registration</h2>
<p><strong>4.1</strong> Creating an Account</p>
<p>To make a booking, users must register an account by providing:</p>
<ul>
<li>Full name and contact details.</li>
<li>A verified email address.</li>
<li>A secure password.</li>
</ul>

<p><strong>4.2</strong> Account Responsibilities</p>
<p>Users must:</p>
<ul>
<li>Keep login credentials confidential.</li>
<li>Notify Vrooem.com immediately in case of unauthorized access.</li>
<li>Ensure that all provided data remains accurate and up to date.</li>
</ul>

<h2>5. Booking and Payment</h2>
<p><strong>5.1</strong> How Bookings Work</p>
<p>Users can browse available vehicles, select a rental, and proceed with a booking request. A rental provider may accept or decline requests at their discretion.</p>

<p><strong>5.2</strong> Payment Processing</p>
<p>Payments are processed securely via trusted third-party providers.</p>
<ul>
<li>Users must provide valid payment details to confirm a reservation.</li>
<li>Full or partial payment may be required upfront, depending on the rental provider\'s terms.</li>
</ul>

<h2>6. Rental Policies and Responsibilities</h2>
<p><strong>6.1</strong> User Responsibilities</p>
<p>Users must:</p>
<ul>
<li>Return the vehicle in the agreed condition.</li>
<li>Comply with traffic laws and rental agreements.</li>
<li>Are responsible for fuel, tolls, and parking fees.</li>
</ul>

<p><strong>6.2</strong> Rental Provider Responsibilities</p>
<p>Rental providers must:</p>
<ul>
<li>Ensure vehicles are safe and roadworthy.</li>
<li>Honor confirmed bookings and provide clear rental terms.</li>
<li>Handle disputes professionally and in compliance with legal requirements.</li>
</ul>

<h2>7. Cancellations and Refunds</h2>
<p><strong>7.1</strong> Cancellation Policy</p>
<p>Users may cancel bookings before the rental period starts, but cancellation fees may apply.</p>
<p>Rental providers reserve the right to cancel a booking in exceptional cases (e.g., vehicle unavailability, legal restrictions).</p>

<p><strong>7.2</strong> Refunds</p>
<p>Refunds depend on:</p>
<ul>
<li>The provider\'s cancellation policy.</li>
<li>Whether the user cancels within the allowed timeframe.</li>
<li>Any administrative or processing fees incurred.</li>
</ul>

<h2>8. Insurance and Liability</h2>
<p><strong>8.1</strong> Coverage and Responsibility</p>
<ul>
<li>Users must verify whether insurance is included in their rental.</li>
<li>Additional insurance coverage may be available as an add-on.</li>
<li>Users are responsible for damage, theft, or accidents unless covered by insurance.</li>
</ul>

<p><strong>8.2</strong> Rental Provider Liability</p>
<ul>
<li>Providers must ensure vehicles meet safety standards.</li>
<li>Providers are responsible for mechanical failures that are not user-caused.</li>
</ul>

<h2>9. Prohibited Uses</h2>
<p><strong>9.1</strong> Unacceptable Conduct</p>
<p>Users may not use Vrooem.com for:</p>
<ul>
<li>Illegal activities, such as vehicle theft, fraud, or identity misrepresentation.</li>
<li>Unauthorized sub-rentals, where a rented vehicle is re-rented to another party.</li>
<li>Dangerous driving behaviors, including reckless driving, racing, or off-road use unless permitted by the rental provider.</li>
<li>False reviews or misleading information to manipulate ratings.</li>
<li>Unauthorized modifications to rental vehicles.</li>
</ul>
<p>Failure to comply with these terms may result in account suspension or legal action.</p>

<h2>10. Platform and Service Limitations</h2>
<p><strong>10.1</strong> No Guarantee of Availability</p>
<p>Vrooem.com does not guarantee that:</p>
<ul>
<li>The platform will be error-free or uninterrupted at all times.</li>
<li>Every listed vehicle will be available for booking at all times.</li>
<li>Users will be approved for rentals by providers.</li>
</ul>

<p><strong>10.2</strong> Temporary Suspension of Services</p>
<p>Vrooem.com may temporarily suspend services for maintenance, system updates, or security purposes. We will notify users in advance where possible.</p>

<h2>11. Disclaimers and Limitation of Liability</h2>
<p><strong>11.1</strong> General Disclaimer</p>
<p>Vrooem.com provides a marketplace service and does not own or control rental vehicles. We are not responsible for:</p>
<ul>
<li>The condition, safety, or legality of rental vehicles.</li>
<li>Actions or failures of rental providers or users.</li>
<li>Losses due to user decisions, including delayed bookings, cancellations, or disputes.</li>
</ul>

<p><strong>11.2</strong> Limitation of Liability</p>
<p>Vrooem.com and its affiliates will not be liable for:</p>
<ul>
<li>Indirect damages, including lost profits, business disruptions, or reputational harm.</li>
<li>Damage to rented vehicles, unless caused by Vrooem.com\'s own negligence.</li>
<li>Errors or system failures affecting bookings.</li>
</ul>
<p>Users agree that their sole remedy for dissatisfaction is to stop using the platform.</p>

<h2>12. Dispute Resolution</h2>
<p><strong>12.1</strong> Handling Complaints</p>
<p>Users and rental providers should attempt to resolve disputes directly before escalating to Vrooem.com.</p>
<p>If a resolution cannot be reached, users may contact Vrooem.com\'s support team for mediation.</p>

<p><strong>12.2</strong> Arbitration and Legal Proceedings</p>
<p>Disputes that cannot be resolved through mediation may be submitted to arbitration.</p>
<p>Users waive their right to participate in class-action lawsuits against Vrooem.com.</p>

<h2>13. Governing Law and Jurisdiction</h2>
<p><strong>13.1</strong> Applicable Law</p>
<p>These Terms & Conditions are governed by the laws of Belgium, unless stated otherwise.</p>
<p>Any disputes shall be settled in Belgian courts, except where local laws dictate otherwise.</p>

<h2>14. Intellectual Property Rights</h2>
<p><strong>14.1</strong> Ownership of Content</p>
<p>All content on Vrooem.com, including text, images, logos, and software, is owned by Vrooem.com or its licensors.</p>
<p>Users may not copy, modify, distribute, or use platform content without explicit permission.</p>

<p><strong>14.2</strong> User-Generated Content</p>
<p>Users retain rights to any reviews, comments, or feedback they submit but grant Vrooem.com a non-exclusive, royalty-free license to use them for marketing and service improvements.</p>
<p>Vrooem.com reserves the right to remove or edit user-generated content that violates platform rules.</p>

<h2>15. Account Termination and Suspension</h2>
<p><strong>15.1</strong> Grounds for Termination</p>
<p>Vrooem.com may suspend or terminate accounts for:</p>
<ul>
<li>Violations of these Terms & Conditions.</li>
<li>Fraudulent activities or identity misrepresentation.</li>
<li>Repeated policy breaches or disputes with rental providers.</li>
</ul>

<p><strong>15.2</strong> User-Initiated Account Closure</p>
<p>Users can request account deletion by contacting support@vrooem.com. Upon deletion:</p>
<ul>
<li>User data may be retained for legal and compliance purposes.</li>
<li>Pending bookings and disputes must be resolved first.</li>
</ul>

<h2>16. Amendments to Terms & Conditions</h2>
<p><strong>16.1</strong> Policy Updates</p>
<p>Vrooem.com reserves the right to modify these terms at any time.</p>
<ul>
<li>Users will be notified of material changes via email or website notices.</li>
<li>Continued use of the platform after updates constitutes acceptance of the revised terms.</li>
</ul>

<h2>17. Force Majeure</h2>
<p><strong>17.1</strong> Events Beyond Our Control</p>
<p>Vrooem.com is not responsible for service failures caused by:</p>
<ul>
<li>Natural disasters (earthquakes, floods, fires, etc.).</li>
<li>Government actions or legal restrictions.</li>
<li>Cyberattacks or technical failures beyond our control.</li>
</ul>

<h2>18. Miscellaneous Provisions</h2>
<p><strong>18.1</strong> No Waiver</p>
<p>Failure to enforce any part of these Terms & Conditions does not constitute a waiver of rights.</p>

<p><strong>18.2</strong> Severability</p>
<p>If any section of these terms is found to be unenforceable, the remaining provisions remain in full effect.</p>

<h2>19. Contact Information</h2>
<p>For any questions about these Terms & Conditions, please contact: info@vrooem.com</p>';
    }

    private function getFrenchContent(): string
    {
        return '<h2>1. Introduction</h2>
<p>Bienvenue sur Vrooem.com, une plateforme numérique connectant les loueurs de voitures avec les utilisateurs recherchant des locations de véhicules. En utilisant notre site web, application ou services, vous acceptez de vous conformer à ces Conditions Générales. Si vous n\'êtes pas d\'accord, veuillez vous abstenir d\'utiliser notre plateforme.</p>

<h2>2. Définitions</h2>
<p><strong>Vrooem.com</strong> – Refère au site web, à l\'application et aux services offerts par Vrooem.</p>
<p><strong>Utilisateur</strong> – Toute personne ou entité utilisant Vrooem.com.</p>
<p><strong>Prestataire</strong> – Une entreprise professionnelle listant des véhicules à louer sur notre plateforme.</p>
<p><strong>Véhicule</strong> – Toute voiture, camionnette, camping-car ou autre option de location disponible via Vrooem.com.</p>
<p><strong>Réservation</strong> – Une transaction de location de véhicule confirmée via Vrooem.com.</p>

<h2>3. Éligibilité</h2>
<p>Pour utiliser Vrooem.com, vous devez :</p>
<ul>
<li>Avoir au moins 18 ans.</li>
<li>Avoir un permis de conduire valide (conditions spécifiques peuvent varier selon le loueur).</li>
<li>Accepter de fournir des informations exactes et à jour.</li>
<li>Ne pas être précédemment banni ou restreint de la plateforme.</li>
</ul>

<h2>4. Inscription de Compte</h2>
<p><strong>4.1</strong> Création d\'un Compte</p>
<p>Pour effectuer une réservation, les utilisateurs doivent s\'inscrire en fournissant :</p>
<ul>
<li>Nom complet et coordonnées.</li>
<li>Une adresse e-mail vérifiée.</li>
<li>Un mot de passe sécurisé.</li>
</ul>

<p><strong>4.2</strong> Responsabilités du Compte</p>
<p>Les utilisateurs doivent :</p>
<ul>
<li>Garder les identifiants confidentiels.</li>
<li>Notifier Vrooem.com immédiatement en cas d\'accès non autorisé.</li>
<li>Assurer que toutes les données fournies restent exactes et à jour.</li>
</ul>

<h2>5. Réservation et Paiement</h2>
<p><strong>5.1</strong> Fonctionnement des Réservations</p>
<p>Les utilisateurs peuvent parcourir les véhicules disponibles, sélectionner une location et procéder à une demande de réservation. Un prestataire peut accepter ou refuser les demandes à sa discrétion.</p>

<p><strong>5.2</strong> Traitement des Paiements</p>
<p>Les paiements sont traités en toute sécurité via des tiers de confiance.</p>
<ul>
<li>Les utilisateurs doivent fournir des détails de paiement valides pour confirmer une réservation.</li>
<li>Un paiement complet ou partiel peut être requis à l\'avance, selon les conditions du loueur.</li>
</ul>

<h2>6. Politiques et Responsabilités de Location</h2>
<p><strong>6.1</strong> Responsabilités de l\'Utilisateur</p>
<p>Les utilisateurs doivent :</p>
<ul>
<li>Retourner le véhicule dans l\'état convenu.</li>
<li>Respecter les lois de la circulation et les accords de location.</li>
<li>Sont responsables du carburant, des péages et des frais de stationnement.</li>
</ul>

<p><strong>6.2</strong> Responsabilités du Prestataire</p>
<p>Les prestataires doivent :</p>
<ul>
<li>Assurer que les véhicules sont sûrs et routiers.</li>
<li>Honorer les réservations confirmées et fournir des conditions claires.</li>
<li>Gérer les litiges professionnellement et en conformité avec les exigences légales.</li>
</ul>

<h2>7. Annulations et Remboursements</h2>
<p><strong>7.1</strong> Politique d\'Annulation</p>
<p>Les utilisateurs peuvent annuler les réservations avant le début de la période de location, mais des frais d\'annulation peuvent s\'appliquer.</p>
<p>Les prestataires se réservent le droit d\'annuler une réservation dans des cas exceptionnels (ex: indisponibilité du véhicule, restrictions légales).</p>

<p><strong>7.2</strong> Remboursements</p>
<p>Les remboursements dépendent de :</p>
<ul>
<li>La politique d\'annulation du prestataire.</li>
<li>Si l\'utilisateur annule dans le délai permis.</li>
<li>Tous frais administratifs ou de traitement encourus.</li>
</ul>

<h2>8. Assurance et Responsabilité</h2>
<p><strong>8.1</strong> Couverture et Responsabilité</p>
<ul>
<li>Les utilisateurs doivent vérifier si l\'assurance est incluse dans leur location.</li>
<li>Une couverture d\'assurance supplémentaire peut être disponible en option.</li>
<li>Les utilisateurs sont responsables des dommages, vols ou accidents sauf si couverts par l\'assurance.</li>
</ul>

<p><strong>8.2</strong> Responsabilité du Prestataire</p>
<ul>
<li>Les prestataires doivent assurer que les véhicules répondent aux normes de sécurité.</li>
<li>Les prestataires sont responsables des pannes mécaniques non causées par l\'utilisateur.</li>
</ul>

<h2>9. Utilisations Interdites</h2>
<p><strong>9.1</strong> Conduite Inacceptable</p>
<p>Les utilisateurs ne peuvent pas utiliser Vrooem.com pour :</p>
<ul>
<li>Activités illégales, comme vol de véhicule, fraude ou fausse identité.</li>
<li>Sous-locations non autorisées.</li>
<li>Comportements de conduite dangereux, y compris conduite imprudente, courses ou usage hors-route sauf si permis.</li>
<li>Faux avis ou informations trompeuses pour manipuler les notes.</li>
<li>Modifications non autorisées des véhicules de location.</li>
</ul>
<p>Le non-respect de ces termes peut entraîner la suspension du compte ou des poursuites judiciaires.</p>

<h2>10. Limitations de la Plateforme</h2>
<p><strong>10.1</strong> Aucune Garantie de Disponibilité</p>
<p>Vrooem.com ne garantit pas que :</p>
<ul>
<li>La plateforme sera exempte d\'erreurs ou ininterrompue à tout moment.</li>
<li>Tout véhicule listé sera disponible pour réservation à tout moment.</li>
<li>Les utilisateurs seront approuvés pour les locations par les prestataires.</li>
</ul>

<p><strong>10.2</strong> Suspension Temporaire des Services</p>
<p>Vrooem.com peut suspendre temporairement les services pour maintenance, mises à jour système ou sécurité. Nous notifierons les utilisateurs à l\'avance si possible.</p>

<h2>11. Clause de Non-Responsabilité</h2>
<p><strong>11.1</strong> Avertissement Général</p>
<p>Vrooem.com fournit un service de place de marché et ne possède pas ne contrôle les véhicules de location. Nous ne sommes pas responsables de :</p>
<ul>
<li>L\'état, la sécurité ou la légalité des véhicules de location.</li>
<li>Des actions ou échecs des prestataires ou utilisateurs.</li>
<li>Des pertes dues aux décisions des utilisateurs.</li>
</ul>

<p><strong>11.2</strong> Limitation de Responsabilité</p>
<p>Vrooem.com et ses affiliés ne seront pas responsables pour :</p>
<ul>
<li>Dommages indirects, y compris pertes de profits ou perturbations commerciales.</li>
<li>Dommages aux véhicules loués, sauf si causés par la négligence de Vrooem.com.</li>
<li>Erreurs ou pannes système affectant les réservations.</li>
</ul>

<h2>12. Résolution des Litiges</h2>
<p><strong>12.1</strong> Gestion des Plaintes</p>
<p>Les utilisateurs et prestataires devraient tenter de résoudre les litiges directement avant d\'escalader à Vrooem.com.</p>
<p>Si une résolution ne peut être atteinte, les utilisateurs peuvent contacter l\'équipe de support Vrooem.com pour médiation.</p>

<p><strong>12.2</strong> Arbitrage et Procédures Légales</p>
<p>Les litiges non résolus par médiation peuvent être soumis à arbitrage.</p>
<p>Les utilisateurs renoncent à leur droit de participer à des actions collectives contre Vrooem.com.</p>

<h2>13. Droit Applicable et Juridiction</h2>
<p><strong>13.1</strong> Droit Applicable</p>
<p>Ces Conditions Générales sont régies par les lois de la Belgique, sauf indication contraire.</p>
<p>Tout litige sera réglé par les tribunaux belges, sauf si les lois locales dictent autrement.</p>

<h2>14. Propriété Intellectuelle</h2>
<p><strong>14.1</strong> Propriété du Contenu</p>
<p>Tout le contenu sur Vrooem.com, y compris texte, images, logos et logiciels, est la propriété de Vrooem.com ou de ses concédants.</p>
<p>Les utilisateurs ne peuvent pas copier, modifier, distribuer ou utiliser le contenu de la plateforme sans autorisation explicite.</p>

<p><strong>14.2</strong> Contenu Généré par l\'Utilisateur</p>
<p>Les utilisateurs conservent les droits sur tout avis, commentaire ou feedback soumis mais accordent à Vrooem.com une licence non exclusive, libre de redevances pour les utiliser.</p>

<h2>15. Résiliation et Suspension</h2>
<p><strong>15.1</strong> Motifs de Résiliation</p>
<p>Vrooem.com peut suspendre ou résilier des comptes pour :</p>
<ul>
<li>Violations de ces Conditions Générales.</li>
<li>Activités frauduleuses ou fausse identité.</li>
<li>Violations répétées ou litiges avec les prestataires.</li>
</ul>

<p><strong>15.2</strong> Fermeture Initée par l\'Utilisateur</p>
<p>Les utilisateurs peuvent demander la suppression de compte en contactant support@vrooem.com.</p>

<h2>16. Modifications</h2>
<p><strong>16.1</strong> Mises à jour de la Politique</p>
<p>Vrooem.com se réserve le droit de modifier ces termes à tout moment.</p>
<ul>
<li>Les utilisateurs seront notifiés des changements par e-mail ou avis sur le site.</li>
<li>L\'utilisation continue après les mises à jour constitue l\'acceptation des termes révisés.</li>
</ul>

<h2>17. Force Majeure</h2>
<p><strong>17.1</strong> Événements Hors de Notre Contrôle</p>
<p>Vrooem.com n\'est pas responsable des défaillances de service causées par :</p>
<ul>
<li>Catastrophes naturelles (tremblements de terre, inondations, incendies, etc.).</li>
<li>Actions gouvernementales ou restrictions légales.</li>
<li>Cyberattaques ou pannes techniques hors de notre contrôle.</li>
</ul>

<h2>18. Dispositions Diverses</h2>
<p><strong>18.1</strong> Aucune Renonciation</p>
<p>Le défaut de faire respecter une partie de ces conditions ne constitue pas une renonciation aux droits.</p>

<p><strong>18.2</strong> Séparabilité</p>
<p>Si une section de ces termes est jugée inapplicable, les dispositions restantes restent en vigueur.</p>

<h2>19. Coordonnées de Contact</h2>
<p>Pour toute question sur ces Conditions Générales, contactez : info@vrooem.com</p>';
    }

    private function getDutchContent(): string
    {
        return '<h2>1. Introductie</h2>
<p>Welkom bij Vrooem.com, een digitaal platform dat autoverhuurbedrijven verbindt met gebruikers die voertuigen zoeken. Door onze website, app of diensten te gebruiken, gaat u akkoord met deze Algemene Voorwaarden. Als u niet akkoord gaat, gebruik onze platform dan niet.</p>

<h2>2. Definities</h2>
<p><strong>Vrooem.com</strong> – Verwijst naar de website, applicatie en diensten aangeboden door Vrooem.</p>
<p><strong>Gebruiker</strong> – Elke persoon of entiteit die Vrooem.com gebruikt.</p>
<p><strong>Verhuurder</strong> – Een professioneel bedrijf dat voertuigen te huur aanbiedt op ons platform.</p>
<p><strong>Voertuig</strong> – Elke auto, bestelwagen, camper of andere huuroptie beschikbaar via Vrooem.com.</p>
<p><strong>Boeking</strong> – Een bevestigde voertuigverhuurtransactie via Vrooem.com.</p>

<h2>3. Geschiktheid</h2>
<p>Om Vrooem.com te gebruiken, moet u:</p>
<ul>
<li>Minstens 18 jaar oud zijn.</li>
<li>Een geldig rijbewijs hebben (specifieke voorwaarden kunnen variëren per verhuurder).</li>
<li>Accoord gaan om nauwkeurige en actuele informatie te verstrekken.</li>
<li>Niet eerder verbannen of beperkt zijn van het platform.</li>
</ul>

<h2>4. Accountregistratie</h2>
<p><strong>4.1</strong> Een Account Maken</p>
<p>Om een boeking te maken, moeten gebruikers een account registreren door:</p>
<ul>
<li>Volledige naam en contactgegevens.</li>
<li>Een geverifieerd e-mailadres.</li>
<li>Een veilig wachtwoord.</li>
</ul>

<p><strong>4.2</strong> Accountverantwoordelijkheden</p>
<p>Gebruikers moeten:</p>
<ul>
<li>Inloggegevens vertrouwelijk houden.</li>
<li>Vrooem.com onmiddellijk op de hoogte stellen bij ongeautoriseerde toegang.</li>
<li>Zorgen dat alle verstrekte gegevens nauwkeurig en actueel blijven.</li>
</ul>

<h2>5. Boeking en Betaling</h2>
<p><strong>5.1</strong> Hoe Bookingen Werken</p>
<p>Gebruikers kunnen beschikbare voertuigen bekijken, een huur selecteren en een boekingsverzoek indienen. Een verhuurder kan verzoeken accepteren of weigeren.</p>

<p><strong>5.2</strong> Betalingsverwerking</p>
<p>Betalingen worden veilig verwerkt via vertrouwde derde partijen.</p>
<ul>
<li>Gebruikers moeten geldige betalingsgegevens verstrekken om een reservering te bevestigen.</li>
<li>Volledige of gedeeltelijke betaling kan vooraf vereist zijn.</li>
</ul>

<h2>6. Verhuurbeleid en Verantwoordelijkheden</h2>
<p><strong>6.1</strong> Gebruikersverantwoordelijkheden</p>
<p>Gebruikers moeten:</p>
<ul>
<li>Het voertuig in de overeengekomen staat teruggeven.</li>
<li>Voldoen aan verkeerswetten en huurovereenkomsten.</li>
<li>Verantwoordelijk zijn voor brandstof, tol en parkeerkosten.</li>
</ul>

<p><strong>6.2</strong> Verhuurdersverantwoordelijkheden</p>
<p>Verhuurders moeten:</p>
<ul>
<li>Zorgen dat voertuigen veilig en verkeerdig zijn.</li>
<li>Bevestigde boekingen eren en duidelijke huurvoorwaarden geven.</li>
<li>Geschillen professioneel afhandelen.</li>
</ul>

<h2>7. Annuleringen en Restituties</h2>
<p><strong>7.1</strong> Annuleringsbeleid</p>
<p>Gebruikers kunnen boekingen annuleren voordat de huurperiode begint, maar annuleringskosten kunnen van toepassing zijn.</p>
<p>Verhuurders behouden zich het recht voor een boeking te annuleren in uitzonderlijke gevallen.</p>

<p><strong>7.2</strong> Restituties</p>
<p>Restituties afhankelijk van:</p>
<ul>
<li>Het annuleringsbeleid van de verhuurder.</li>
<li>Of de gebruiker annuleert binnen de toegestane tijd.</li>
<li>Eventuele administratieve of verwerkingskosten.</li>
</ul>

<h2>8. Verzekering en Aansprakelijkheid</h2>
<p><strong>8.1</strong> Dekking en Verantwoordelijkheid</p>
<ul>
<li>Gebruikers moeten verifiëren of verzekering is inbegrepen.</li>
<li>Aanvullende verzekering kan beschikbaar zijn als extra.</li>
<li>Gebruikers zijn verantwoordelijk voor schade, diefstal of ongevallen tenzij gedekt.</li>
</ul>

<p><strong>8.2</strong> Verhuurdersaansprakelijkheid</p>
<ul>
<li>Verhuurders moeten zorgen dat voertuigen aan veiligheidsnormen voldoen.</li>
<li>Verhuurders zijn verantwoordelijk voor mechanische storingen niet door gebruiker veroorzaakt.</li>
</ul>

<h2>9. Verboden Gebruiken</h2>
<p><strong>9.1</strong> Onaanvaardbaar Gedrag</p>
<p>Gebruikers mogen Vrooem.com niet gebruiken voor:</p>
<ul>
<li>Illegale activiteiten zoals voertuigdiefstal, fraude of identiteitsmisleiding.</li>
<li>Niet-geautoriseerde onderverhuur.</li>
<li>Gevaarlijk rijgedrag inclusief roekeloos rijden, racen of off-road gebruik.</li>
<li>Valse beoordelingen of misleidende informatie.</li>
<li>Niet-geautoriseerde wijzigingen aan huurvoertuigen.</li>
</ul>
<p>Naleving faalt kan leiden tot accountopschorting of juridische stappen.</p>

<h2>10. Platformbeperkingen</h2>
<p><strong>10.1</strong> Geen Garantie van Beschikbaarheid</p>
<p>Vrooem.com garandeert niet dat:</p>
<ul>
<li>Het platform foutloos of ononderbroken zal zijn.</p>
<li>Elk gelist voertuig altijd beschikbaar zal zijn.</li>
<li>Gebruikers goedgekeurd zullen worden voor huur.</li>
</ul>

<p><strong>10.2</strong> Tijdelijke Opschorting</p>
<p>Vrooem.com kan diensten tijdelijk opschorten voor onderhoud of beveiliging. Waar mogelijk worden gebruikers op de hoogte gesteld.</p>

<h2>11. Vrijwaring en Aansprakelijkheidsbeperking</h2>
<p><strong>11.1</strong> Algemeen</p>
<p>Vrooem.com biedt een marktplaatsdienst en bezit of controleert geen huurvoertuigen. Wij zijn niet verantwoordelijk voor:</p>
<ul>
<li>De staat, veiligheid of legaliteit van voertuigen.</li>
<li>Acties of nalatigheid van verhuurders of gebruikers.</li>
<li>Verliezen door gebruikersbeslissingen.</li>
</ul>

<p><strong>11.2</strong> Beperking</p>
<p>Vrooem.com en haar affiliates zijn niet aansprakelijk voor:</p>
<ul>
<li>Indirecte schade包括 winstverlies of bedrijfsonderbreking.</li>
<li>Schade aan gehuurde voertuigen, tenzij veroorzaakt door Vrooem.com nalatigheid.</li>
<li>Fouten of systeemstoringen.</li>
</ul>

<h2>12. Geschillenbeslechting</h2>
<p><strong>12.1</strong> Klachten Afhandelen</p>
<p>Gebruikers en verhuurders moeten proberen geschillen direct op te lossen.</p>

<p><strong>12.2</strong> Arbitrage</p>
<p>Onopgeloste geschillen kunnen aan arbitrage worden onderworpen.</p>

<h2>13. Toepasselijk Recht</h2>
<p><strong>13.1</strong> Van Toepassing Zijnde Wet</p>
<p>Deze Voorwaarden worden beheerst door het recht van België.</p>

<h2>14. Intellectueel Eigendom</h2>
<p><strong>14.1</strong> Eigendom van Inhoud</p>
<p>Alle inhoud op Vrooem.com is eigendom van Vrooem.com of haar licentiegevers.</p>

<h2>15. Accountbeëindiging</h2>
<p><strong>15.1</strong> Gronden voor Beëindiging</p>
<p>Vrooem.com kan accounts opschorten of beëindigen voor schendingen.</p>

<h2>16. Wijzigingen</h2>
<p><strong>16.1</strong> Beleidswijzigingen</p>
<p>Vrooem.com behoudt zich het recht voor deze voorwaarden te wijzigen.</p>

<h2>17. Overmacht</h2>
<p><strong>17.1</strong> Gebeurtenissen Buiten Onze Controle</p>
<p>Vrooem.com is niet verantwoordelijk voor storingen door natuurrampen, overheidsacties of cyberaanvallen.</p>

<h2>18. Diversen</h2>
<p><strong>18.1</strong> Geen Verwijzing</p>
<p>Niet-handhaving van deze voorwaarden betekent geen afstand van rechten.</p>

<h2>19. Contact</h2>
<p>Voor vragen: info@vrooem.com</p>';
    }

    private function getSpanishContent(): string
    {
        return '<h2>1. Introducción</h2>
<p>Bienvenido a Vrooem.com, una plataforma digital que conecta proveedores de alquiler de autos con usuarios que buscan vehículos. Al usar nuestro sitio web, aplicación o servicios, acepta cumplir con estos Términos y Condiciones. Si no está de acuerdo, no use nuestra plataforma.</p>

<h2>2. Definiciones</h2>
<p><strong>Vrooem.com</strong> – Se refiere al sitio web, aplicación y servicios ofrecidos por Vrooem.</p>
<p><strong>Usuario</strong> – Cualquier persona o entidad que usa Vrooem.com.</p>
<p><strong>Proveedor</strong> – Un negocio profesional que lista vehículos para alquilar.</p>
<p><strong>Vehículo</strong> – Cualquier auto, camioneta, camper u opción de alquiler disponible.</p>
<p><strong>Reserva</strong> – Una transacción de alquiler confirmada vía Vrooem.com.</p>

<h2>3. Elegibilidad</h2>
<p>Para usar Vrooem.com, debe:</p>
<ul>
<li>Tener al menos 18 años.</li>
<li>Tener una licencia de conducir válida.</li>
<li>Acordar proporcionar información precisa y actualizada.</li>
<li>No estar previamente prohibido o restringido de la plataforma.</li>
</ul>

<h2>4. Registro de Cuenta</h2>
<p><strong>4.1</strong> Crear una Cuenta</p>
<p>Para hacer una reserva, los usuarios deben registrarse proporcionando:</p>
<ul>
<li>Nombre completo y detalles de contacto.</li>
<li>Una dirección de email verificada.</li>
<li>Una contraseña segura.</li>
</ul>

<p><strong>4.2</strong> Responsabilidades de la Cuenta</p>
<p>Los usuarios deben:</p>
<ul>
<li>Mantener las credenciales confidenciales.</li>
<li>Notificar a Vrooem.com inmediatamente en caso de acceso no autorizado.</li>
<li>Asegurar que todos los datos proporcionados sean precisos y actualizados.</li>
</ul>

<h2>5. Reserva y Pago</h2>
<p><strong>5.1</strong> Cómo Funcionan las Reservas</p>
<p>Los usuarios pueden explorar vehículos disponibles y proceder con una solicitud de reserva. Un proveedor puede aceptar o declinar solicitudes a su discreción.</p>

<p><strong>5.2</strong> Procesamiento de Pagos</p>
<p>Los pagos se procesan de forma segura a través de terceros de confianza.</p>
<ul>
<li>Los usuarios deben proporcionar detalles de pago válidos para confirmar una reserva.</li>
<li>Puede requerirse pago completo o parcial por adelantado.</li>
</ul>

<h2>6. Políticas y Responsabilidades de Alquiler</h2>
<p><strong>6.1</strong> Responsabilidades del Usuario</p>
<p>Los usuarios deben:</p>
<ul>
<li>Devolver el vehículo en el estado acordado.</li>
<li>Cumplir con las leyes de tránsito y acuerdos de alquiler.</li>
<li>Son responsables del combustible, peajes y tarifas de estacionamiento.</li>
</ul>

<p><strong>6.2</strong> Responsabilidades del Proveedor</p>
<p>Los proveedores deben:</p>
<ul>
<li>Asegurar que los vehículos sean seguros y aptos para la carretera.</li>
<li>Honrar reservas confirmadas y proporcionar términos claros.</li>
<li>Manejar disputas profesionalmente.</li>
</ul>

<h2>7. Cancelaciones y Reembolsos</h2>
<p><strong>7.1</strong> Política de Cancelación</p>
<p>Los usuarios pueden cancelar reservas antes de que comience el período de alquiler, pero pueden aplicarse tarifas.</p>
<p>Los proveedores se reservan el derecho de cancelar en casos excepcionales.</p>

<p><strong>7.2</strong> Reembolsos</p>
<p>Los reembolsos dependen de:</p>
<ul>
<li>La política de cancelación del proveedor.</li>
<li>Si el usuario cancela dentro del tiempo permitido.</li>
<li>Cualquier tarifa administrativa incurrida.</li>
</ul>

<h2>8. Seguro y Responsabilidad</h2>
<p><strong>8.1</strong> Cobertura y Responsabilidad</p>
<ul>
<li>Los usuarios deben verificar si el seguro está incluido.</li>
<li>Puede estar disponible cobertura adicional como complemento.</li>
<li>Los usuarios son responsables de daños, robo o accidentes a menos que estén cubiertos.</li>
</ul>

<p><strong>8.2</strong> Responsabilidad del Proveedor</p>
<ul>
<li>Los proveedores deben asegurar que los vehículos cumplan estándares de seguridad.</li>
<li>Son responsables de fallas mecánicas no causadas por el usuario.</li>
</ul>

<h2>9. Usos Prohibidos</h2>
<p><strong>9.1</strong> Conducta Inaceptable</p>
<p>Los usuarios no pueden usar Vrooem.com para:</p>
<ul>
<li>Actividades ilegales como robo de vehículo, fraude o falsificación de identidad.</li>
<li>Subarrendamientos no autorizados.</li>
<li>Conducción peligrosa incluyendo conducción temeraria, carreras o uso off-road.</li>
<li>Reseñas falsas o información engañosa.</li>
<li>Modificaciones no autorizadas a vehículos de alquiler.</li>
</ul>

<h2>10. Limitaciones de la Plataforma</h2>
<p><strong>10.1</strong> Sin Garantía de Disponibilidad</p>
<p>Vrooem.com no garantiza que:</p>
<ul>
<li>La plataforma esté libre de errores o sea ininterrumpida.</li>
<li>Cada vehículo listado esté disponible siempre.</li>
<li>Los usuarios sean aprobados para alquileres.</li>
</ul>

<p><strong>10.2</strong> Suspensión Temporal</p>
<p>Vrooem.com puede suspender temporalmente servicios para mantenimiento o actualizaciones.</p>

<h2>11. Exenciones y Limitación de Responsabilidad</h2>
<p><strong>11.1</strong> Exención General</p>
<p>Vrooem.com proporciona un servicio de mercado y no posee ni controla vehículos. No somos responsables de:</p>
<ul>
<li>La condición, seguridad o legalidad de vehículos.</li>
<li>Acciones o fallas de proveedores o usuarios.</li>
<li>Pérdidas debido a decisiones de usuarios.</li>
</ul>

<p><strong>11.2</strong> Limitación de Responsabilidad</p>
<p>Vrooem.com y sus afiliados no serán responsables por:</p>
<ul>
<li>Daños indirectos incluyendo pérdidas de ganancias.</li>
<li>Daños a vehículos alquilados, a menos que causados por negligencia de Vrooem.com.</li>
<li>Errores o fallas del sistema.</li>
</ul>

<h2>12. Resolución de Disputas</h2>
<p><strong>12.1</strong> Manejo de Quejas</p>
<p>Usuarios y proveedores deben intentar resolver disputas directamente.</p>

<p><strong>12.2</strong> Arbitraje</p>
<p>Las disputas no resueltas pueden someterse a arbitraje.</p>

<h2>13. Ley Aplicable y Jurisdicción</h2>
<p><strong>13.1</strong> Ley Aplicable</p>
<p>Estos Términos y Condiciones se rigen por las leyes de Bélgica.</p>

<h2>14. Derechos de Propiedad Intelectual</h2>
<p><strong>14.1</strong> Propiedad del Contenido</p>
<p>Todo el contenido en Vrooem.com es propiedad de Vrooem.com o sus licenciantes.</p>

<h2>15. Terminación de Cuenta</h2>
<p><strong>15.1</strong> Motivos de Terminación</p>
<p>Vrooem.com puede suspender o terminar cuentas por violaciones.</p>

<p><strong>15.2</strong> Cierre Iniciado por Usuario</p>
<p>Los usuarios pueden solicitar eliminación de cuenta contactando support@vrooem.com.</p>

<h2>16. Enmiendas</h2>
<p><strong>16.1</strong> Actualizaciones de Política</p>
<p>Vrooem.com se reserva el derecho de modificar estos términos.</p>

<h2>17. Fuerza Mayor</h2>
<p><strong>17.1</strong> Eventos Fuera de Nuestro Control</h2>
<p>Vrooem.com no es responsable por fallas causadas por desastres naturales, acciones gubernamentales o ciberataques.</p>

<h2>18. Disposiciones Varias</h2>
<p><strong>18.1</strong> Sin Renuncia</p>
<p>La falta de hacer cumplir cualquier parte no constituye renuncia de derechos.</p>

<h2>19. Información de Contacto</h2>
<p>Para preguntas: info@vrooem.com</p>';
    }

    private function getArabicContent(): string
    {
        return '<h2>1. مقدمة</h2>
<p>مرحباً بك في Vrooem.com، منصة رقمية تربط مقدمي خدمات تأجير السيارات بالمستخدمين الباحثين عن تأجير المركبات. باستخدام موقعنا الإلكتروني أو تطبيقنا أو خدماتنا، توافق على الالتزام بهذه الشروط والأحكام. إذا لم توافق، يرجى الامتناع عن استخدام منصتنا.</p>

<h2>2. التعريفات</h2>
<p><strong>Vrooem.com</strong> – تشير إلى الموقع الإلكتروني والتطبيق والخدمات التي تقدمها Vrooem.</p>
<p><strong>المستخدم</strong> – أي فرد أو كيان يستخدم Vrooem.com.</p>
<p><strong>مزود الخدمة</strong> – عمل تجاري محترف يدرج مركبات للإيجار على منصتنا.</p>
<p><strong>المركبة</strong> – أي سيارة أو شاحنة أو مركبة تخييم أو خيار تأجير آخر متاح عبر Vrooem.com.</p>
<p><strong>الحجز</strong> – معاملة تأجير مركبة مؤكدة عبر Vrooem.com.</p>

<h2>3. الأهلية</h2>
<p>لاستخدام Vrooem.com، يجب:</p>
<ul>
<li>أن يكون عمرك 18 عامًا على الأقل.</li>
<li>أن لديك رخصة قيادة سارية (قد تختلف الشروط حسب مقدم الخدمة).</li>
<li>الموافقة على تقديم معلومات دقيقة وحديثة.</li>
<li>ألا تكون ممنوعًا أو مقيدًا سابقًا من المنصة.</li>
</ul>

<h2>4. تسجيل الحساب</h2>
<p><strong>4.1</strong> إنشاء حساب</p>
<p>لعمل حجز، يجب على المستخدمين تسجيل حساب من خلال تقديم:</p>
<ul>
<li>الاسم الكامل وتفاصيل الاتصال.</li>
<li>عنوان بريد إلكتروني مُحقق.</li>
<li>كلمة مرور آمنة.</li>
</ul>

<p><strong>4.2</strong> مسؤوليات الحساب</p>
<p>يجب على المستخدمين:</p>
<ul>
<li>الحفاظ على سرية بيانات الاعتماد.</li>
<li>إخطار Vrooem.com فورًا في حالة الوصول غير المصرح به.</li>
<li>التأكد من أن جميع البيانات المقدمة تظل دقيقة وحديثة.</li>
</ul>

<h2>5. الحجز والدفع</h2>
<p><strong>5.1</strong> كيف تعمل الحجوزات</p>
<p>يمكن للمستخدمين تصفح المركبات المتاحة واختيار تأجير وتقديم طلب حجز. يمكن لمقدم الخدمة قبول أو رفض الطلبات حسب تقديره.</p>

<p><strong>5.2</strong> معالجة المدفوعات</p>
<p>تتم معالجة المدفوعات بأمان عبر مقدمي خدمات طرف ثالث موثوقين.</p>
<ul>
<li>يجب على المستخدمين تقديم تفاصيل دفع صالحة لتأكيد الحجز.</li>
<li>قد يكون مطلوبًا دفع كامل أو جزئي مقدمًا.</li>
</ul>

<h2>6. سياسات ومسؤوليات التأجير</h2>
<p><strong>6.1</strong> مسؤوليات المستخدم</p>
<p>يجب على المستخدمين:</p>
<ul>
<li>إعادة المركبة في الحالة المتفق عليها.</li>
<li>الامتثال لقوانين المرور واتفاقيات التأجير.</li>
<li>مسؤولون عن الوقود والرسوم ومواقف السيارات.</li>
</ul>

<p><strong>6.2</strong> مسؤوليات مقدم الخدمة</p>
<p>يجب على مقدمي الخدمة:</p>
<ul>
<li>ضمان سلامة المركبات وصلاحيتها للطرق.</li>
<li>تلبية الحجوزات المؤكدة وتقديم شروط واضحة.</li>
<li>التعامل مع النزاعات بشكل احترافي.</li>
</ul>

<h2>7. الإلغاءات والاستردادات</h2>
<p><strong>7.1</strong> سياسة الإلغاء</p>
<p>يمكن للمستخدمين إلغاء الحجوزات قبل بدء فترة التأجير، لكن قد تنطبق رسوم الإلغاء.</p>
<p>يحتفظ مقدمو الخدمة بالحق في إلغاء الحجز في حالات استثنائية.</p>

<p><strong>7.2</strong> الاستردادات</p>
<p>تعتمد الاستردادات على:</p>
<ul>
<li>سياسة الإلغاء الخاصة بمقدم الخدمة.</li>
<li>ما إذا كان المستخدم يلغي ضمن الإطار الزمني المسموح به.</li>
<li>أي رسوم إدارية أو معالجة تم تكبدها.</li>
</ul>

<h2>8. التأمين والمسؤولية</h2>
<p><strong>8.1</strong> التغطية والمسؤولية</p>
<ul>
<li>يجب على المستخدمين التحقق مما إذا كان التأمين مشمولاً.</li>
<li>قد يتوفر تغطية تأمينية إضافية كإضافة.</li>
<li>المستخدمون مسؤولون عن الأضرار أو السرقة أو الحوادث ما لم يكن مشمولاً بالتأمين.</li>
</ul>

<p><strong>8.2</strong> مسؤولية مقدم الخدمة</p>
<ul>
<li>يجب على مقدمي الخدمة ضمان أن المركبات تلبي معايير السلامة.</li>
<li>مسؤولون عن الأعطال الميكانيكية غير الناجمة عن المستخدم.</li>
</ul>

<h2>9. الاستخدامات المحظورة</h2>
<p><strong>9.1</strong> سلوك غير مقبول</p>
<p>لا يمكن للمستخدمين استخدام Vrooem.com لـ:</p>
<ul>
<li>أنشطة غير قانونية مثل سرقة المركبات أو الاحتيال.</li>
<li>التأجير الفرعي غير المصرح به.</li>
<li>سلوكيات قيادة خطرة بما في ذلك القيادة المتهورة أو السباق.</li>
<li>مراجعات كاذبة أو معلومات مضللة.</li>
<li>تعديلات غير مصرح بها على مركبات التأجير.</li>
</ul>
<p>قد يؤدي عدم الامتثال إلى تعليق الحساب أو إجراء قانوني.</p>

<h2>10. قيود المنصة</h2>
<p><strong>10.1</strong> عدم ضمان التوفر</p>
<p>Vrooem.com لا تضمن أن:</p>
<ul>
<li>المنصة ستكون خالية من الأخطاء أو غير متقطعة.</li>
<li>كل مركبة مدرجة ستكون متاحة للحجز دائمًا.</li>
<li>سيتم قبول المستخدمين للتأجير من قبل مقدمي الخدمة.</li>
</ul>

<p><strong>10.2</strong> التعليق المؤقت للخدمات</p>
<p>قد تعلق Vrooem.com الخدمات مؤقتًا للصيانة أو التحديثات الأمنية.</p>

<h2>11. إخلاء المسؤولية وتحديد المسؤولية</h2>
<p><strong>11.1</strong> إخلاء المسؤولية العام</p>
<p>Vrooem.com تقدم خدمة سوقية ولا تملك ولا تتحكم في مركبات التأجير. لسنا مسؤولين عن:</p>
<ul>
<li>حالة أو سلامة أو قانونية مركبات التأجير.</li>
<li>أفعال أو إخفاقات مقدمي الخدمة أو المستخدمين.</li>
<li>الخسائر بسبب قرارات المستخدمين.</li>
</ul>

<p><strong>11.2</strong> تحديد المسؤولية</p>
<p>لن تكون Vrooem.com وشركاتها التابعة مسؤولة عن:</p>
<ul>
<li>الأضرار غير المباشرة بما في ذلك خسارة الأرباح.</li>
<li>الأضرار للمركبات المؤجرة، ما لم تكن ناجمة عن إهمال Vrooem.com.</li>
<li>الأخطاء أو أعطال النظام.</li>
</ul>

<h2>12. حل النزاعات</h2>
<p><strong>12.1</strong> معالجة الشكاوى</p>
<p>يجب على المستخدمين ومقدمي الخدمة محاولة حل النزاعات مباشرة.</p>

<p><strong>12.2</strong> التحكيم</p>
<p>يمكن إخضاع النزاعات غير المحلولة للتحكيم.</p>

<h2>13. القانون الحاكم والاختصاص</h2>
<p><strong>13.1</strong> القانون المعمول به</p>
<p>تحكم هذه الشروط والأحكام قوانين بلجيكا.</p>

<h2>14. حقوق الملكية الفكرية</h2>
<p><strong>14.1</strong> ملكية المحتوى</p>
<p>جميع المحتويات على Vrooem.com مملوكة لـ Vrooem.com أو مرخصيها.</p>

<h2>15. إنهاء الحساب وتعليقه</h2>
<p><strong>15.1</strong> أسباب الإنهاء</p>
<p>قد تعلق Vrooem.com أو تنهي الحسابات بسبب:</p>
<ul>
<li>انتهاك هذه الشروط والأحكام.</li>
<li>أنشطة احتيالية أو سوء تمثيل للهوية.</li>
<li>انتهاكات متكررة للسياسات.</li>
</ul>

<p><strong>15.2</strong> إغلاق الحساب من قبل المستخدم</p>
<p>يمكن للمستخدمين طلب حذف الحساب بالاتصال بـ support@vrooem.com.</p>

<h2>16. التعديلات</h2>
<p><strong>16.1</strong> تحديثات السياسة</p>
<p>تحتفظ Vrooem.com بالحق في تعديل هذه الشروط في أي وقت.</p>

<h2>17. القوة القاهرة</h2>
<p><strong>17.1</strong> أحداث خارج سيطرتنا</h2>
<p>Vrooem.com ليست مسؤولة عن حالات فشل الخدمة الناجمة عن:</p>
<ul>
<li>الكوارث الطبيعية (زلازيل، فيضانات، حرائق، إلخ).</li>
<li>إجراءات حكومية أو قيود قانونية.</li>
<li>الهجمات الإلكترونية أو الأعطال التقنية.</li>
</ul>

<h2>18. أحكام متنوعة</h2>
<p><strong>18.1</strong> عدم التنازل</p>
<p>عدم إنفاذ أي جزء من هذه الشروط لا يشكل تنازلاً عن الحقوق.</p>

<p><strong>18.2</strong> الانفصالية</p>
<p>إذا وجد أن أي قسم من هذه الشروط غير قابل للإنفاذ، تظل الأحكام المتبقية سارية المفعول.</p>

<h2>19. معلومات الاتصال</h2>
<p>لأي أسئلة حول هذه الشروط والأحكام، يرجى الاتصال بـ: info@vrooem.com</p>';
    }
}
