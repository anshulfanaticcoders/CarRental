<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrivacyPolicyPageSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $existingPage = DB::table('pages')->where('custom_slug', 'privacy-policy')->first();
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
            'slug' => 'privacy-policy-' . $now->format('YmdHis'),
            'template' => 'legal',
            'custom_slug' => 'privacy-policy',
            'status' => 'published',
            'sort_order' => 3,
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

        $this->command->info('Privacy Policy page seeded successfully!');
        $this->command->info("Page ID: {$pageId}");
    }

    private function getContentByLocale(): array
    {
        return [
            'en' => ['title' => 'Privacy Policy', 'slug' => 'privacy-policy', 'content' => $this->getEnglishContent()],
            'fr' => ['title' => 'Politique de confidentialité', 'slug' => 'politique-de-confidentialite', 'content' => $this->getFrenchContent()],
            'nl' => ['title' => 'Privacybeleid', 'slug' => 'privacybeleid', 'content' => $this->getDutchContent()],
            'es' => ['title' => 'Política de privacidad', 'slug' => 'politica-de-privacidad', 'content' => $this->getSpanishContent()],
            'ar' => ['title' => 'سياسة الخصوصية', 'slug' => 'سياسة-الخصوصية', 'content' => $this->getArabicContent()],
        ];
    }

    private function getEnglishContent(): string
    {
        return '<h2>1. Introduction</h2>
<p>At Vrooem.com, we highly value your privacy. This Privacy Policy explains how we collect, use, protect, and share your personal data when you use our platform.</p>

<h2>2. Data We Collect</h2>
<p><strong>2.1</strong> When using Vrooem.com, we may collect the following data:</p>
<ul>
<li>Personal identification information (such as name, email address, phone number, and driver\'s license details).</li>
<li>Payment details for processing transactions and reservations.</li>
<li>Location data to assist with vehicle searches and rentals.</li>
<li>Usage data regarding how you interact with our platform.</li>
</ul>

<h2>3. How We Use Your Data</h2>
<p><strong>3.1</strong> We use your data for the following purposes:</p>
<ul>
<li>To facilitate vehicle reservations.</li>
<li>To provide customer service and support.</li>
<li>To ensure security and fraud prevention.</li>
<li>To improve our services and user experience.</li>
<li>For marketing and personalized offers (only with your consent).</li>
</ul>

<h2>4. Data Sharing and Third Parties</h2>
<p><strong>4.1</strong> We share your data only in the following situations:</p>
<ul>
<li>With rental providers to process your reservation.</li>
<li>With payment providers to handle transactions securely.</li>
<li>With government authorities if legally required.</li>
<li>With service providers assisting in platform operation and security.</li>
</ul>

<h2>5. Data Security</h2>
<p><strong>5.1</strong> We implement appropriate technical and organizational measures to protect your data from unauthorized access, loss, or misuse.</p>

<h2>6. Your Rights</h2>
<p><strong>6.1</strong> You have the right to:</p>
<ul>
<li>Request access to your personal data.</li>
<li>Request corrections or deletion of your data.</li>
<li>Withdraw your consent for marketing purposes.</li>
<li>Object to certain types of data processing.</li>
</ul>

<h2>7. Cookies and Tracking Technologies</h2>
<p><strong>7.1</strong> Vrooem.com uses cookies to enhance platform functionality and optimize user experiences. You can manage your cookie preferences via your browser settings.</p>

<h2>8. Changes to This Privacy Policy</h2>
<p><strong>8.1</strong> We reserve the right to update this policy. Any changes will become effective upon publication on our platform.</p>

<h2>9. Contact Information</h2>
<p>For any questions regarding this Privacy Policy, you can contact us at info@vrooem.com.</p>

<h2>10. Data Retention</h2>
<p><strong>10.1</strong> How long we retain your data:</p>
<p>We retain your personal data only for as long as necessary to fulfill the purposes outlined in this policy, including legal, accounting, and reporting obligations. The retention period depends on the type of data:</p>
<ul>
<li>Account Information: Stored as long as your account remains active.</li>
<li>Transaction and Payment Data: Retained for tax and compliance purposes.</li>
<li>Communication Records: Stored to improve customer support and resolve disputes.</li>
<li>Marketing Preferences: Kept until you withdraw your consent.</li>
</ul>
<p><strong>10.2</strong> Data Deletion Requests: You may request the deletion of your personal data, subject to legal and operational constraints.</p>

<h2>11. International Data Transfers</h2>
<p><strong>11.1</strong> If your data is transferred internationally:</p>
<p>Vrooem.com operates globally and may transfer data across different countries. We ensure that all data transfers comply with applicable data protection laws and are safeguarded through:</p>
<ul>
<li>Standard Contractual Clauses (SCCs)</li>
<li>Privacy Shield Frameworks (if applicable)</li>
<li>Encryption and Anonymization where required</li>
</ul>

<h2>12. Automated Decision-Making</h2>
<p><strong>12.1</strong> How we use automated systems:</p>
<p>Vrooem.com may use automated decision-making processes, such as:</p>
<ul>
<li>Fraud detection: Identifying suspicious transactions to enhance security.</li>
<li>Personalized recommendations: Offering tailored vehicle rental options based on user behavior.</li>
<li>Risk assessment: Evaluating bookings based on past rental history.</li>
</ul>
<p>You have the right to request human intervention if an automated decision significantly impacts you.</p>

<h2>13. Third-Party Links and Services</h2>
<p><strong>13.1</strong> External websites:</p>
<p>Our platform may contain links to third-party websites or services. Vrooem.com is not responsible for their privacy policies or practices. We encourage you to review the privacy policies of external sites before providing personal data.</p>

<h2>14. Children\'s Privacy</h2>
<p><strong>14.1</strong> Age restrictions:</p>
<p>Vrooem.com is not intended for individuals under 18 years of age. We do not knowingly collect personal data from minors. If we become aware that a minor has provided personal information, we will take steps to remove it immediately.</p>

<h2>15. Legal Basis for Processing Data</h2>
<p><strong>15.1</strong> Our legal grounds for data processing:</p>
<p>We process personal data under the following legal bases:</p>
<ul>
<li>Performance of a contract: To provide rental services and process payments.</li>
<li>Legal compliance: To fulfill regulatory obligations.</li>
<li>Legitimate interests: To improve our platform, prevent fraud, and secure user accounts.</li>
<li>Consent: When required for marketing communications or non-essential data collection.</li>
</ul>

<h2>16. Contact & Complaints</h2>
<p><strong>16.1</strong> How to contact us:</p>
<p>If you have concerns about our data practices, you may contact us at: info@vrooem.com</p>
<p><strong>16.2</strong> Filing a complaint:</p>
<p>If you believe your data protection rights have been violated, you may file a complaint with your local data protection authority.</p>

<h2>17. User Responsibilities</h2>
<p><strong>17.1</strong> How you can protect your data:</p>
<p>While Vrooem.com takes extensive measures to protect your data, users also have a role in maintaining security:</p>
<ul>
<li>Use a strong, unique password for your account.</li>
<li>Keep your login credentials confidential and secure.</li>
<li>Be cautious when sharing personal or payment details over email or phone.</li>
<li>Regularly update your profile information to ensure accuracy.</li>
</ul>
<p>Failure to follow these precautions may increase the risk of unauthorized access to your data.</p>

<h2>18. Data Breach Notification</h2>
<p><strong>18.1</strong> How we handle security incidents:</p>
<p>In the event of a data breach that affects your personal data, Vrooem.com will:</p>
<ul>
<li>Investigate and assess the impact immediately.</li>
<li>Notify affected users as soon as possible if required by law.</li>
<li>Take corrective actions to prevent future incidents.</li>
<li>Work with relevant authorities to ensure compliance.</li>
</ul>

<h2>19. Data Portability</h2>
<p><strong>19.1</strong> Transferring your data:</p>
<p>You have the right to request a copy of your personal data in a structured, commonly used format (such as JSON, CSV, or XML) to transfer it to another service provider where technically feasible.</p>

<h2>20. Corporate Transactions & Business Changes</h2>
<p><strong>20.1</strong> What happens to your data if Vrooem.com changes ownership:</p>
<p>If Vrooem.com undergoes a merger, acquisition, sale of assets, or restructuring, your personal data may be transferred as part of the transaction. We will notify users before any significant change in data handling policies occurs.</p>

<h2>21. Law Enforcement & Legal Requests</h2>
<p><strong>21.1</strong> When we disclose data to authorities:</p>
<p>We may disclose personal data to law enforcement or regulatory bodies when:</p>
<ul>
<li>Required by court orders or legal processes.</li>
<li>Necessary to prevent fraud, security threats, or illegal activities.</li>
<li>Protecting the rights, safety, and property of users, employees, or the public.</li>
</ul>
<p>Any data disclosure will be strictly limited to what is legally necessary.</p>

<h2>22. Privacy Policy for Business Accounts</h2>
<p><strong>22.1</strong> Additional terms for business users:</p>
<p>Business customers renting vehicles via Vrooem.com may be subject to additional data processing agreements. This includes data shared between corporate accounts, fleet managers, and rental agencies for operational purposes.</p>

<h2>23. Accessibility and Translations</h2>
<p><strong>23.1</strong> Ensuring privacy for all users:</p>
<p>We strive to make our Privacy Policy accessible to all users, including providing translations where necessary. In case of discrepancies, the English version prevails as the legally binding document.</p>

<h2>24. Updates & User Notifications</h2>
<p><strong>24.1</strong> How we notify you of changes:</p>
<p>When we update our Privacy Policy, we will notify users by:</p>
<ul>
<li>Email alerts (if you have an account with us).</li>
<li>Prominent notices on our website or app.</li>
<li>Updates in the terms and conditions section.</li>
</ul>
<p>Continued use of Vrooem.com after changes means you accept the revised policy.</p>

<h2>25. Additional Support & Assistance</h2>
<p><strong>25.1</strong> Need more help?</p>
<p>If you have any questions or concerns about how we handle your data, you can reach out to our privacy team at: info@vrooem.com. We are committed to handling your privacy inquiries promptly and transparently.</p>

<h2>26. Third-Party Services and Integrations</h2>
<p><strong>26.1</strong> How third-party services interact with your data:</p>
<p>Vrooem.com may integrate with third-party services, such as payment processors, navigation tools, and analytics providers. When using these services:</p>
<ul>
<li>Your data may be processed outside of Vrooem.com\'s direct control.</li>
<li>Third parties have their own privacy policies and terms.</li>
<li>We carefully select partners who follow strong data protection standards.</li>
</ul>
<p>If you choose to connect your Vrooem.com account with a third-party service (such as a payment provider or social media login), you acknowledge that your data may be shared in accordance with both parties\' privacy policies.</p>

<h2>27. Behavioral Advertising and Analytics</h2>
<p><strong>27.1</strong> How we use tracking for improvements and advertising:</p>
<p>We use analytics tools to understand how users interact with our platform, improve services, and personalize experiences. This may include:</p>
<ul>
<li>Tracking user activity to enhance platform functionality.</li>
<li>Using cookies and tracking pixels for advertising relevance.</li>
<li>Creating aggregated, anonymous data to understand trends.</li>
</ul>
<p>Users can opt-out of targeted advertising by adjusting cookie settings in their browser or using \"Do Not Track\" options.</p>

<h2>28. Data Processing for Legal Compliance</h2>
<p><strong>28.1</strong> How we process data for legal purposes:</p>
<p>In some cases, Vrooem.com must process your personal data to comply with:</p>
<ul>
<li>Tax and accounting regulations.</li>
<li>Industry regulations related to vehicle rentals.</li>
<li>Legal investigations requiring the retention of records.</li>
</ul>
<p>We only store legally necessary data and minimize storage durations wherever possible.</p>

<h2>29. AI and Machine Learning in Data Processing</h2>
<p><strong>29.1</strong> How AI helps improve our platform:</p>
<p>Vrooem.com may use Artificial Intelligence (AI) and Machine Learning (ML) for various processes, including:</p>
<ul>
<li>Fraud detection to identify suspicious activities.</li>
<li>Personalized recommendations for vehicle rentals.</li>
<li>Automated customer support assistance.</li>
</ul>
<p>AI-based decisions are continuously reviewed, and users have the right to request human intervention if they believe an automated decision affects them unfairly.</p>

<h2>30. Community Guidelines and User Conduct</h2>
<p><strong>30.1</strong> Your responsibilities as a Vrooem.com user:</p>
<p>To maintain a safe and respectful community, users must adhere to:</p>
<ul>
<li>Honest and accurate data sharing (e.g., providing correct rental details).</li>
<li>Respect for rental providers and their terms.</li>
<li>Prohibition of fraudulent or abusive activity.</li>
</ul>
<p>Failure to comply may result in account suspension or legal action.</p>

<h2>31. Secure Payment Handling</h2>
<p><strong>31.1</strong> How we protect your payment details:</p>
<p>Vrooem.com works with certified payment processors to ensure:</p>
<ul>
<li>Encrypted transactions for all payments.</li>
<li>Fraud monitoring to detect suspicious activity.</li>
<li>Tokenized storage of payment details where necessary.</li>
</ul>
<p>We never store full payment card details on our servers.</p>

<h2>32. Geographic Restrictions and Service Availability</h2>
<p><strong>32.1</strong> Where Vrooem.com is available:</p>
<p>While Vrooem.com aims to provide services worldwide, some restrictions may apply due to:</p>
<ul>
<li>Local regulations and licensing laws.</li>
<li>Availability of rental providers in specific regions.</li>
<li>Sanctions or trade restrictions imposed by governments.</li>
</ul>
<p>Users will be notified if certain services are unavailable in their location.</p>

<h2>33. Privacy for Business Partners and Affiliates</h2>
<p><strong>33.1</strong> How we handle business data:</p>
<p>If you are a rental provider, affiliate, or corporate partner, your data may be processed differently:</p>
<ul>
<li>Business accounts may have additional contractual agreements.</li>
<li>Data shared for partnerships, analytics, and commission tracking is handled securely.</li>
<li>Sensitive business data is not shared without explicit consent.</li>
</ul>
<p>For business-related privacy concerns, partners can reach out to info@vrooem.com.</p>

<h2>34. Cross-Border Data Transfers and Compliance</h2>
<p><strong>34.1</strong> How we manage international data flows:</p>
<p>As a global platform, we may process data in multiple jurisdictions. We comply with:</p>
<ul>
<li>GDPR for users in the European Economic Area (EEA).</li>
<li>CCPA for users in California, USA.</li>
<li>Other local privacy laws where applicable.</li>
</ul>
<p>Users will be informed about significant jurisdictional changes affecting their data.</p>

<h2>35. Contact for Legal Inquiries</h2>
<p><strong>35.1</strong> Where to direct legal concerns:</p>
<p>If you require legal assistance regarding your data privacy, you can contact: info@vrooem.com. This includes requests from law enforcement, regulatory bodies, or legal representatives regarding data handling.</p>

<h2>36. User Account Security and Authentication</h2>
<p><strong>36.1</strong> How we protect your account:</p>
<p>To ensure the safety of user accounts, Vrooem.com implements:</p>
<ul>
<li>Two-Factor Authentication (2FA) for enhanced security.</li>
<li>Regular security audits to identify vulnerabilities.</li>
<li>Automatic logout after periods of inactivity.</li>
</ul>
<p>Users are encouraged to use strong passwords and enable security notifications for any unauthorized access attempts.</p>

<h2>37. Consent Management and User Preferences</h2>
<p><strong>37.1</strong> Managing your data preferences:</p>
<p>Vrooem.com provides tools to manage privacy settings, including:</p>
<ul>
<li>Opt-in and opt-out settings for marketing communications.</li>
<li>Granular control over cookie preferences.</li>
<li>Request forms for data access, deletion, or modification.</li>
</ul>
<p>Users can update their preferences in the privacy settings section of their account.</p>

<h2>38. Employee and Contractor Data Protection</h2>
<p><strong>38.1</strong> How we protect employee and contractor data:</p>
<p>Vrooem.com ensures that staff handling personal data are:</p>
<ul>
<li>Trained in data protection best practices.</li>
<li>Required to sign confidentiality agreements.</li>
<li>Granted access only to necessary information based on their role.</li>
</ul>
<p>Employee data is handled separately from customer data and is stored in secure internal systems.</p>

<h2>39. Data Encryption and Secure Storage</h2>
<p><strong>39.1</strong> How we keep your information safe:</p>
<p>All sensitive data at Vrooem.com is protected through:</p>
<ul>
<li>End-to-end encryption for data transmission.</li>
<li>Secure cloud storage with restricted access.</li>
<li>Data minimization techniques to avoid unnecessary data retention.</li>
</ul>
<p>Our encryption standards comply with industry best practices such as AES-256.</p>

<h2>40. Government Requests and User Rights</h2>
<p><strong>40.1</strong> Your rights regarding government data requests:</p>
<p>If a government agency requests user data, Vrooem.com will:</p>
<ul>
<li>Review the request for legal validity.</li>
<li>Inform the user when legally permitted.</li>
<li>Challenge overly broad or unlawful data requests.</li>
</ul>
<p>We are committed to protecting user privacy and ensuring transparency in compliance with legal obligations.</p>

<h2>41. Data Subject Rights Under GDPR and CCPA</h2>
<p><strong>41.1</strong> Your rights under data protection laws:</p>
<p>Depending on your location, you may have rights under laws such as GDPR (EU) or CCPA (California, USA), including:</p>
<ul>
<li>Right to be forgotten – Request deletion of your data.</li>
<li>Right to data portability – Obtain a copy of your data.</li>
<li>Right to object to processing – Restrict certain data uses.</li>
<li>Right to know – Understand what data is collected about you.</li>
</ul>
<p>Users can submit requests through info@vrooem.com.</p>

<h2>42. Ethical AI and Data Usage</h2>
<p><strong>42.1</strong> Ensuring fair and unbiased AI use:</p>
<p>Vrooem.com is committed to responsible AI usage, ensuring:</p>
<ul>
<li>No discriminatory profiling or decision-making.</li>
<li>Regular audits of AI models for bias prevention.</li>
<li>Clear opt-out options for AI-driven recommendations.</li>
</ul>
<p>We believe in transparency and giving users control over their data.</p>

<h2>43. Data Processing for Vehicle Rentals</h2>
<p><strong>43.1</strong> Special considerations for rental data:</p>
<p>Vrooem.com processes additional data during vehicle rentals, including:</p>
<ul>
<li>Driver verification checks for rental eligibility.</li>
<li>Damage reports and claim handling in case of incidents.</li>
<li>Rental history tracking for user convenience.</li>
</ul>
<p>All rental-related data is stored securely and shared only with necessary parties (e.g., rental providers, insurers).</p>

<h2>44. Compliance with Industry Standards</h2>
<p><strong>44.1</strong> How we align with data protection frameworks:</p>
<p>Vrooem.com adheres to globally recognized security and privacy standards, including:</p>
<ul>
<li>ISO 27001 for data security.</li>
<li>PCI DSS for payment transactions.</li>
<li>GDPR & CCPA for user data protection.</li>
</ul>
<p>Regular audits ensure ongoing compliance and trustworthiness in data handling.</p>

<h2>45. Final Provisions and Dispute Resolution</h2>
<p><strong>45.1</strong> Handling disputes related to privacy matters:</p>
<p>If a privacy dispute arises between a user and Vrooem.com:</p>
<ul>
<li>Users must first attempt to resolve the issue through customer support.</li>
<li>If unresolved, mediation or arbitration may be required before legal action.</li>
<li>Users in specific jurisdictions may file a complaint with their data protection authority.</li>
</ul>
<p>Vrooem.com strives to handle disputes fairly, transparently, and in compliance with regulations.</p>

<h2>46. Final Contact & Assistance</h2>
<p><strong>46.1</strong> How to reach us for any privacy concerns:</p>
<p>If you have any privacy-related questions, you can contact us at: info@vrooem.com. We are dedicated to maintaining the highest privacy standards for our users and continuously improving our security measures.</p>';
    }

    private function getFrenchContent(): string
    {
        return '<h2>1. Introduction</h2>
<p>Chez Vrooem.com, nous accordons une grande importance à votre vie privée. Cette Politique de Confidentialité explique comment nous collectons, utilisons, protégeons et partageons vos données personnelles lorsque vous utilisez notre plateforme.</p>

<h2>2. Données Collectées</h2>
<p><strong>2.1</strong> Lors de l\'utilisation de Vrooem.com, nous pouvons collecter les données suivantes :</p>
<ul>
<li>Informations d\'identification personnelle (telles que nom, adresse e-mail, numéro de téléphone et détails du permis de conduire).</li>
<li>Details de paiement pour traiter les transactions et réservations.</li>
<li>Données de localisation pour aider aux recherches et locations de véhicules.</li>
<li>Données d\'utilisation sur votre interaction avec notre plateforme.</li>
</ul>

<h2>3. Utilisation de Vos Données</h2>
<p><strong>3.1</strong> Nous utilisons vos données pour les purposes suivantes :</p>
<ul>
<li>Pour faciliter les réservations de véhicules.</li>
<li>Pour fournir un service client et un support.</li>
<li>Pour assurer la sécurité et prévenir la fraude.</li>
<li>Pour améliorer nos services et l\'expérience utilisateur.</li>
<li>Pour le marketing et les offres personnalisées (uniquement avec votre consentement).</li>
</ul>

<h2>4. Partage de Données et Tiers</h2>
<p><strong>4.1</strong> Nous partageons vos données uniquement dans les situations suivantes :</p>
<ul>
<li>Avec les loueurs pour traiter votre réservation.</li>
<li>Avec les prestataires de paiement pour gérer les transactions en toute sécurité.</li>
<li>Avec les autorités gouvernementales si légalement requis.</li>
<li>Avec les prestataires de services assistant à l\'exploitation et sécurité de la plateforme.</li>
</ul>

<h2>5. Sécurité des Données</h2>
<p><strong>5.1</strong> Nous mettons en œuvre des mesures techniques et organisationnelles appropriées pour protéger vos données contre l\'accès non autorisé, la perte ou la mauvaise utilisation.</p>

<h2>6. Vos Droits</h2>
<p><strong>6.1</strong> Vous avez le droit de :</p>
<ul>
<li>Demander l\'accès à vos données personnelles.</li>
<li>Demander la correction ou la suppression de vos données.</li>
<li>Retirer votre consentement pour le marketing.</li>
<li>Vous opposer à certains types de traitement des données.</li>
</ul>

<h2>7. Cookies et Technologies de Suivi</h2>
<p><strong>7.1</strong> Vrooem.com utilise des cookies pour améliorer les fonctionnalités de la plateforme et optimiser les expériences utilisateur. Vous pouvez gérer vos préférences de cookies via les paramètres de votre navigateur.</p>

<h2>8. Modifications de Cette Politique</h2>
<p><strong>8.1</strong> Nous nous réservons le droit de mettre à jour cette politique. Toute modification entrera en vigueur dès sa publication sur notre plateforme.</p>

<h2>9. Coordonnées de Contact</h2>
<p>Pour toute question concernant cette Politique de Confidentialité, vous pouvez nous contacter à info@vrooem.com.</p>

<h2>10. Rétention des Données</h2>
<p><strong>10.1</strong> Combien de temps nous conservons vos données :</p>
<p>Nous conservons vos données personnelles aussi longtemps que nécessaire pour remplir les objectifs décrits dans cette politique, y compris les obligations juridiques, comptables et de reporting. La durée de rétention dépend du type de données :</p>
<ul>
<li>Informations de compte : Stockées tant que votre compte reste actif.</li>
<li>Données de transaction et paiement : Conservées à des fiscales et de conformité.</li>
<li>Registres de communication : Stockés pour améliorer le support client et résoudre les litiges.</li>
<li>Préférences marketing : Conservées jusqu\'à ce que vous retiriez votre consentement.</li>
</ul>

<h2>11. Transferts Internationaux de Données</h2>
<p><strong>11.1</strong> Si vos données sont transférées internationalement :</p>
<p>Vrooem.com opère à l\'échelle mondiale et peut transférer des données à travers différents pays. Nous nous assurons que tous les transferts de données conforment aux lois de protection des données applicables et sont sécurisés par :</p>
<ul>
<li>Clauses Contractuelles Types (CCT)</li>
<li>Cadres Privacy Shield (si applicable)</li>
<li>Chiffrement et anonymisation lorsque requis</li>
</ul>

<h2>12. Prise de Décision Automatisée</h2>
<p><strong>12.1</strong> Comment nous utilisons les systèmes automatisés :</p>
<p>Vrooem.com peut utiliser des processus de décision automatisés, tels que :</p>
<ul>
<li>Détection de fraude : Identification des transactions suspectes pour renforcer la sécurité.</li>
<li>Recommandations personnalisées : Offres d\'options de location adaptées.</li>
<li>Évaluation des risques : Analyse des réservations basée sur l\'historique.</li>
</ul>

<h2>13. Liens et Services Tiers</h2>
<p><strong>13.1</strong> Sites web externes :</p>
<p>Notre plateforme peut contenir des liens vers des sites web ou services tiers. Vrooem.com n\'est pas responsable de leurs politiques de confidentialité. Nous vous encourageons à examiner les politiques de confidentialité des sites externes avant de fournir des données personnelles.</p>

<h2>14. Confidentialité des Enfants</h2>
<p><strong>14.1</strong> Restrictions d\'âge :</p>
<p>Vrooem.com n\'est pas destiné aux personnes de moins de 18 ans. Nous ne collectons pas consciemment des données personnelles de mineurs. Si nous prenons connaissance qu\'un mineur a fourni des informations personnelles, nous prendrons des mesures pour les supprimer immédiatement.</p>

<h2>15. Base Légale pour le Traitement</h2>
<p><strong>15.1</strong> Nos bases juridiques pour le traitement :</p>
<p>Nous traitons les données personnelles selon les bases juridiques suivantes :</p>
<ul>
<li>Exécution d\'un contrat : Pour fournir des services de location et traiter les paiements.</li>
<li>Conformité légale : Pour remplir les obligations réglementaires.</li>
<li>Intérêts légitimes : Pour améliorer notre plateforme, prévenir la fraude et sécuriser les comptes.</li>
<li>Consentement : Lorsque requis pour les communications marketing.</li>
</ul>

<h2>16. Contact et Plaintes</h2>
<p><strong>16.1</strong> Comment nous contacter :</p>
<p>Si vous avez des préoccupations sur nos pratiques de données, contactez-nous à : info@vrooem.com</p>

<h2>17. Responsabilités de l\'Utilisateur</h2>
<p><strong>17.1</strong> Comment vous pouvez protéger vos données :</p>
<p>Tandis que Vrooem.com prend des mesures étendues pour protéger vos données, les utilisateurs ont aussi un rôle dans le maintien de la sécurité :</p>
<ul>
<li>Utilisez un mot de passe fort et unique.</li>
<li>Gardez vos identifiants confidentiels et sécurisés.</li>
<li>Soyez prudent lors du partage d\'informations personnelles.</li>
<li>Mettez régulièrement à jour vos informations de profil.</li>
</ul>

<h2>18. Notification de Violation de Données</h2>
<p><strong>18.1</strong> Comment nous gérons les incidents de sécurité :</p>
<p>En cas de violation de données affectant vos informations personnelles, Vrooem.com :</p>
<ul>
<li>Enquêtera et évaluera l\'impact immédiatement.</li>
<li>Notifiera les utilisateurs affectés dès que possible si requis par la loi.</li>
<li>Prendra des actions correctives pour prévenir les incidents futurs.</li>
</ul>

<h2>19. Portabilité des Données</h2>
<p><strong>19.1</strong> Transfert de vos données :</p>
<p>Vous avez le droit de demander une copie de vos données personnelles dans un format structuré couramment utilisé (JSON, CSV ou XML) pour les transférer à un autre prestataire lorsque techniquement possible.</p>

<h2>20. Transactions Commerciales</h2>
<p><strong>20.1</strong> Ce qui arrive à vos données en cas de changement de propriété :</p>
<p>Si Vrooem.com subit une fusion, acquisition, ou restructuration, vos données peuvent être transférées. Nous notifierons les utilisateurs avant tout changement important des politiques de traitement des données.</p>

<h2>21. Application de la Loi</h2>
<p><strong>21.1</strong> Quand nous divulgons des données aux autorités :</p>
<p>Nous pouvons divulguer des données personnelles aux forces de l\'ordre ou organismes réglementaires lorsque :</p>
<ul>
<li>Requis par ordonnances judiciaires ou processus légaux.</li>
<li>Nécessaire pour prévenir la fraude ou activités illégales.</li>
<li>Pour protéger les droits, la sécurité et les biens des utilisateurs.</li>
</ul>

<h2>22. Comptes Professionnels</h2>
<p><strong>22.1</strong> Termes additionnels pour les utilisateurs professionnels :</p>
<p>Les clients professionnels louant des véhicules via Vrooem.com peuvent être soumis à des accords de traitement de données supplémentaires.</p>

<h2>23. Accessibilité et Traductions</h2>
<p><strong>23.1</strong> Assurer la confidentialité pour tous les utilisateurs :</p>
<p>Nous nous efforçons de rendre notre Politique de Confidentialité accessible à tous, y compris en fournissant des traductions si nécessaire. En cas de discrepancies, la version anglaise prévaut comme document juridiquement contraignant.</p>

<h2>24. Mises à Jour et Notifications</h2>
<p><strong>24.1</strong> Comment nous vous notifions des changements :</p>
<p>Lorsque nous mettons à jour notre Politique de Confidentialité, nous notifierons les utilisateurs par :</p>
<ul>
<li>Alertes e-mail (si vous avez un compte).</li>
<li>Avis visibles sur notre site web ou application.</li>
<li>Mises à jour dans la section des conditions.</li>
</ul>

<h2>25. Support Supplémentaire</h2>
<p><strong>25.1</strong> Besoin d\'aide ?</p>
<p>Si vous avez des questions ou préoccupations sur la gestion de vos données, contactez notre équipe de confidentialité à : info@vrooem.com</p>

<h2>46. Contact Final</h2>
<p>Pour toute question liée à la confidentialité, contactez-nous à : info@vrooem.com. Nous nous engageons à maintenir les normes de confidentialité les plus élevées pour nos utilisateurs.</p>';
    }

    private function getDutchContent(): string
    {
        return '<h2>1. Introductie</h2>
<p>Bij Vrooem.com hechten wij veel waarde aan uw privacy. Dit Privacybeleid legt uit hoe wij uw persoonlijke gegevens verzamelen, gebruiken, beschermen en delen wanneer u ons platform gebruikt.</p>

<h2>2. Gegevens Die We Verzamelen</h2>
<p><strong>2.1</strong> Bij het gebruik van Vrooem.com kunnen wij de volgende gegevens verzamelen:</p>
<ul>
<li>Persoonlijke identificatiegegevens (zoals naam, e-mailadres, telefoonnummer en rijbewijsgegevens).</li>
<li>Betaalgegevens voor verwerking van transacties en reserveringen.</li>
<li>Locatiegegevens voor assistentie bij voertuigzoektochten en huur.</li>
<li>Gebruiksgegevens over hoe u met ons platform omgaat.</li>
</ul>

<h2>3. Hoe We Uw Gegevens Gebruiken</h2>
<p><strong>3.1</strong> Wij gebruiken uw gegevens voor de volgende doeleinden:</p>
<ul>
<li>Om voertuigreserveringen te faciliteren.</li>
<li>Om klantenservice en ondersteuning te bieden.</li>
<li>Om veiligheid en fraudepreventie te waarborgen.</li>
<li>Om onze diensten en gebruikerservaring te verbeteren.</li>
<li>Voor marketing en gepersonaliseerde aanbiedingen (alleen met uw toestemming).</li>
</ul>

<h2>4. Gegevensdeling en Derden</h2>
<p><strong>4.1</strong> Wij delen uw gegevens alleen in de volgende situaties:</p>
<ul>
<li>Met verhuurbedrijven om uw reservering te verwerken.</li>
<li>Met betaalproviders om transacties veilig af te handelen.</li>
<li>Met overheidsinstanties indien wettelijk vereist.</li>
<li>Met serviceproviders die assistenten bij platformoperatie en veiligheid.</li>
</ul>

<h2>5. Gegevensbeveiliging</h2>
<p><strong>5.1</strong> Wij implementeren passende technische en organisatorische maatregelen om uw gegevens te beschermen tegen ongeautoriseerde toegang, verlies of misbruik.</p>

<h2>6. Uw Rechten</h2>
<p><strong>6.1</strong> U heeft het recht om:</p>
<ul>
<li>Toegang tot uw persoonlijke gegevens te vragen.</li>
<li>Correctie of verwijdering van uw gegevens te vragen.</li>
<li>Uw toestemming voor marketing in te trekken.</li>
<li>Bezwaar te maken tegen bepaalde gegevensverwerking.</li>
</ul>

<h2>7. Cookies en Trackingtechnologieën</h2>
<p><strong>7.1</strong> Vrooem.com gebruikt cookies om platformfunctionaliteit te verbeteren en gebruikerservaringen te optimaliseren. U kunt uw cookievoorkeuren beheren via uw browserinstellingen.</p>

<h2>8. Wijzigingen van Dit Privacybeleid</h2>
<p><strong>8.1</strong> Wij behouden ons het recht voor dit beleid bij te werken. Wijzigingen worden van kracht bij publicatie op ons platform.</p>

<h2>9. Contactinformatie</h2>
<p>Voor vragen over dit Privacybeleid kunt u ons contacteren op info@vrooem.com.</p>

<h2>10. Gegevensbewaring</h2>
<p><strong>10.1</strong> Hoe lang wij uw gegevens bewaren:</p>
<p>Wij bewaren uw persoonlijke gegevens alleen zolang als noodzakelijk voor de doeleinden in dit beleid, inclusief wettelijke, financiële en rapportageverplichtingen.</p>

<h2>11. Internationale Gegevensoverdrachten</h2>
<p><strong>11.1</strong> Als uw gegevens internationaal worden overgedragen:</p>
<p>Vrooem.com opereert wereldwijd en kan gegevens door verschillende landen overdragen. Wij waarborgen dat alle gegevensoverdrachten voldoen aan toepasselijke wetten en beveiligd worden door standaard contractuele clausules.</p>

<h2>12. Geautomatiseerde Besluitvorming</h2>
<p><strong>12.1</strong> Hoe we geautomatiseerde systemen gebruiken:</p>
<p>Vrooem.com kan geautomatiseerde besluitvormingsprocessen gebruiken zoals fraudedetectie en gepersonaliseerde aanbevelingen.</p>

<h2>13. Links en Services van Derden</h2>
<p><strong>13.1</strong> Externe websites:</p>
<p>Ons platform kan links bevatten naar websites van derden. Vrooem.com is niet verantwoordelijk voor hun privacybeleid.</p>

<h2>14. Privacy van Kinderen</h2>
<p><strong>14.1</strong> Leeftijdsbeperkingen:</p>
<p>Vrooem.com is niet bedoeld voor personen onder 18 jaar. Wij verzamelen geen gegevens van minderjarigen.</p>

<h2>15. Juridische Grondslag voor Verwerking</h2>
<p><strong>15.1</strong> Onze juridische gronden voor gegevensverwerking:</p>
<ul>
<li>Uitvoering van een overeenkomst: Voor verhuurdiensten en betalingsverwerking.</li>
<li>Wettelijke naleving: Voor wettelijke verplichtingen.</li>
<li>Legitieme belangen: Voor platformverbetering en fraudepreventie.</li>
<li>Toestemming: Voor marketingcommunicaties.</li>
</ul>

<h2>16. Contact en Klachten</h2>
<p><strong>16.1</strong> Hoe contact op te nemen:</p>
<p>Info@vrooem.com</p>

<h2>17. Gebruikersverantwoordelijkheden</h2>
<p><strong>17.1</strong> Hoe u uw gegevens kunt beschermen:</p>
<ul>
<li>Gebruik een sterk, uniek wachtwoord.</li>
<li>Houd uw inloggegevens vertrouwelijk.</li>
<li>Wees voorzichtig met delen van persoonlijke informatie.</li>
<li>Update uw profielgegevens regelmatig.</li>
</ul>

<h2>18. Melding van Datalek</h2>
<p><strong>18.1</strong> Hoe we veiligheidsincidenten afhandelen:</p>
<p>Bij een datalek onderzoeken en beoordelen wij de impact direct en notifying betrokken gebruikers waar wettelijk vereist.</p>

<h2>19. Gegevensportabiliteit</h2>
<p><strong>19.1</strong> Overdragen van uw gegevens:</p>
<p>U heeft het recht om een kopie van uw persoonlijke gegevens te vragen in een gestructureerd formaat (JSON, CSV of XML).</p>

<h2>20. Bedrijfstransacties</h2>
<p><strong>20.1</strong> Wat er met uw gegevens gebeurt bij eigendomswijziging:</p>
<p>Bij fusie, overname of herstructurering kunnen uw gegevens worden overgedragen. Wij notifieren gebruikers voor belangrijke wijzigingen.</p>

<h2>46. Eindcontact</h2>
<p>Voor privacyvragen: info@vrooem.com</p>';
    }

    private function getSpanishContent(): string
    {
        return '<h2>1. Introducción</h2>
<p>En Vrooem.com valoramos mucho su privacidad. Esta Política de Privacidad explica cómo recopilamos, usamos, protegemos y compartimos sus datos personales cuando usa nuestra plataforma.</p>

<h2>2. Datos Que Recopilamos</h2>
<p><strong>2.1</strong> Al usar Vrooem.com, podemos recopilar:</p>
<ul>
<li>Información de identificación personal (nombre, email, teléfono, licencia de conducir).</li>
<li>Detalles de pago para transacciones y reservaciones.</li>
<li>Datos de ubicación para búsquedas de vehículos.</li>
<li>Datos de uso sobre su interacción con la plataforma.</li>
</ul>

<h2>3. Cómo Usamos Sus Datos</h2>
<p><strong>3.1</strong> Usamos sus datos para:</p>
<ul>
<li>Facilitar reservaciones de vehículos.</li>
<li>Proporcionar servicio al cliente.</li>
<li>Garantizar seguridad y prevención de fraude.</li>
<li>Mejorar nuestros servicios.</li>
<li>Marketing y ofertas personalizadas (solo con su consentimiento).</li>
</ul>

<h2>4. Compartición de Datos</h2>
<p><strong>4.1</strong> Compartimos sus datos solo:</p>
<ul>
<li>Con proveedores de alquiler para procesar su reservación.</li>
<li>Con procesadores de pago para transacciones seguras.</li>
<li>Con autoridades gubernamentales si es legalmente requerido.</li>
<li>Con proveedores de servicios que asisten en la operación.</li>
</ul>

<h2>5. Seguridad de Datos</h2>
<p><strong>5.1</strong> Implementamos medidas técnicas y organizacionales apropiadas para proteger sus datos.</p>

<h2>6. Sus Derechos</h2>
<p><strong>6.1</strong> Usted tiene derecho a:</p>
<ul>
<li>Solicitar acceso a sus datos personales.</li>
<li>Solicitar corrección o eliminación de sus datos.</li>
<li>Retirar su consentimiento para marketing.</li>
<li>Oponerse a cierto tipo de procesamiento de datos.</li>
</ul>

<h2>7. Cookies</h2>
<p><strong>7.1</strong> Vrooem.com usa cookies para mejorar la funcionalidad. Puede gestionar sus preferencias a través de la configuración de su navegador.</p>

<h2>8. Cambios en Esta Política</h2>
<p><strong>8.1</strong> Nos reservamos el derecho de actualizar esta política. Los cambios entran en vigor al publicarse.</p>

<h2>9. Información de Contacto</h2>
<p>Para preguntas sobre esta Política de Privacidad: info@vrooem.com</p>

<h2>10. Retención de Datos</h2>
<p><strong>10.1</strong> Cuánto tiempo retenemos sus datos:</p>
<p>Retenemos sus datos solo lo necesario para los propósitos descritos, incluyendo obligaciones legales y contables.</p>

<h2>11. Transferencias Internacionales</h2>
<p><strong>11.1</strong> Si sus datos se transfieren internacionalmente:</p>
<p>Vrooem.com opera globalmente y puede transferir datos entre países. Aseguramos que todas las transferencias cumplan con las leyes de protección de datos aplicables.</p>

<h2>12. Toma de Decisiones Automatizada</h2>
<p><strong>12.1</strong> Cómo usamos sistemas automatizados:</p>
<p>Vrooem.com puede usar procesos de decisión automatizados como detección de fraude y recomendaciones personalizadas.</p>

<h2>13. Enlaces y Servicios de Terceros</h2>
<p><strong>13.1</strong> Sitios web externos:</p>
<p>Nuestra plataforma puede contener enlaces a sitios de terceros. Vrooem.com no es responsable de sus políticas de privacidad.</p>

<h2>14. Privacidad de Menores</h2>
<p><strong>14.1</strong> Restricciones de edad:</p>
<p>Vrooem.com no está destinado a menores de 18 años. No recopilamos intencionalmente datos de menores.</p>

<h2>15. Base Legal para Procesamiento</h2>
<p><strong>15.1</strong> Nuestras bases legales:</p>
<ul>
<li>Ejecución de contrato: Para servicios de alquiler y pagos.</li>
<li>Cumplimiento legal: Para obligaciones regulatorias.</li>
<li>Intereses legítimos: Para mejorar la plataforma y prevenir fraude.</li>
<li>Consentimiento: Para comunicaciones de marketing.</li>
</ul>

<h2>16. Contacto y Quejas</h2>
<p><strong>16.1</strong> Cómo contactarnos:</p>
<p>info@vrooem.com</p>

<h2>17. Responsabilidades del Usuario</h2>
<p><strong>17.1</strong> Cómo puede proteger sus datos:</p>
<ul>
<li>Use una contraseña fuerte y única.</li>
<li>Mantenga sus credenciales confidenciales y seguras.</li>
<li>Sea cauteloso al compartir información personal.</li>
<li>Actualice su información de perfil regularmente.</li>
</ul>

<h2>18. Notificación de Violación de Datos</h2>
<p><strong>18.1</strong> Cómo manejamos incidentes de seguridad:</p>
<p>En caso de violación de datos, investigaremos y evaluaremos el impacto inmediatamente, notificaremos a los usuarios afectados si es legalmente requerido y tomaremos acciones correctivas.</p>

<h2>19. Portabilidad de Datos</h2>
<p><strong>19.1</strong> Transferir sus datos:</p>
<p>Tiene derecho a solicitar una copia de sus datos personales en un formato estructurado (JSON, CSV o XML).</p>

<h2>20. Transacciones Corporativas</h2>
<p><strong>20.1</strong> Qué pasa con sus datos si cambia la propiedad:</p>
<p>Si Vrooem.com sufre fusión, adquisición o reestructuración, sus datos pueden transferirse como parte de la transacción.</p>

<h2>46. Contacto Final</h2>
<p>Para preguntas relacionadas con la privacidad: info@vrooem.com</p>';
    }

    private function getArabicContent(): string
    {
        return '<h2>1. مقدمة</h2>
<p>في Vrooem.com، نقدر قيمة عالية لخصوصيتك. تشرح سياسة الخصوصية هذه كيف نجمع ونستخدم ونحمي ونشارك بياناتك الشخصية عند استخدامك لمنصتنا.</p>

<h2>2. البيانات التي نجمعها</h2>
<p><strong>2.1</strong> عند استخدام Vrooem.com، قد نجمع البيانات التالية:</p>
<ul>
<li>معلومات الهوية الشخصية (الاسم، البريد الإلكتروني، رقم الهاتف، تفاصيل رخصة القيادة).</li>
<li>تفاصيل الدفع لمعالجة المعاملات والحجوزات.</li>
<li>بيانات الموقع للمساعدة في البحث عن المركبات وتأجيرها.</li>
<li>بيانات الاستخدام حول تفاعلك مع منصتنا.</li>
</ul>

<h2>3. كيف نستخدم بياناتك</h2>
<p><strong>3.1</strong> نستخدم بياناتك للأغراض التالية:</p>
<ul>
<li>تسهيل حجوزات المركبات.</li>
<li>تقديم خدمة العملاء والدعم.</li>
<li>ضمان الأمان ومنع الاحتيال.</li>
<li>تحسين خدماتنا وتجربة المستخدم.</li>
<li>للتسويق والعروض المخصصة (فقط بموافقتك).</li>
</ul>

<h2>4. مشاركة البيانات وأطراف ثالثة</h2>
<p><strong>4.1</strong> نشارك بياناتك فقط في الحالات التالية:</p>
<ul>
<li>مع مقدمي خدمات التأجير لمعالجة حجزك.</li>
<li>مع معالجات الدفع للتعامل مع المعاملات بأمان.</li>
<li>مع السلطات الحكومية إذا كان مطلوبًا قانونًا.</li>
<li>مع مقدمي الخدمات المساعدين في تشغيل المنصة.</li>
</ul>

<h2>5. أمان البيانات</h2>
<p><strong>5.1</strong> ننفذ تدابير تقنية وتنظيمية مناسبة لحماية بياناتك من الوصول غير المصرح به أو الفقدان أو سوء الاستخدام.</p>

<h2>6. حقوقك</h2>
<p><strong>6.1</strong> لديك الحق في:</p>
<ul>
<li>طلب الوصول إلى بياناتك الشخصية.</li>
<li>طلب التصحيح أو حذف بياناتك.</li>
<li>سحب موافقتك لأغراض التسويق.</li>
<li>الاعتراض على أنواع معينة من معالجة البيانات.</li>
</ul>

<h2>7. ملفات تعريف الارتباط والتتبع</h2>
<p><strong>7.1</strong> يستخدم Vrooem.com ملفات تعريف الارتباط لتحسين وظائف المنصة. يمكنك إدارة تفضيلات ملفات تعريف الارتباط من خلال إعدادات المتصفح.</p>

<h2>8. تغييرات سياسة الخصوصية</h2>
<p><strong>8.1</strong> نحتفظ بالحق في تحديث هذه السياسة. أي تغييرات ستصبح سارية المفعول عند النشر على منصتنا.</p>

<h2>9. معلومات الاتصال</h2>
<p>لأي أسئلة تتعلق بسياسة الخصوصية، يمكنك الاتصال بنا على info@vrooem.com.</p>

<h2>10. احتفاظ البيانات</h2>
<p><strong>10.1</strong> المدة التي نحتفظ ببياناتك:</p>
<p>نحتفظ ببياناتك الشخصية فقط طالما كان ضروريًا للأغراض الموضحة في هذه السياسة، بما في ذلك الالتزامات القانونية والمحاسبية.</p>

<h2>11. النقل الدولي للبيانات</h2>
<p><strong>11.1</strong> إذا تم نقل بياناتك دوليًا:</p>
<p>Vrooem.com يعمل عالميًا وقد ينقل البيانات عبر دول مختلفة. نضمن أن جميع عمليات النقل تلتزم بقوانين حماية البيانات المعمول بها.</p>

<h2>12. اتخاذ القرار الآلي</h2>
<p><strong>12.1</strong> كيف نستخدم الأنظمة الآلية:</p>
<p>قد يستخدم Vrooem.com عمليات اتخاذ قرارات آلية مثل اكتشاف الاحتيال والتوصيات المخصصة.</p>

<h2>13. روابط وخدمات الطرف الثالث</h2>
<p><strong>13.1</strong> مواقع الويب الخارجية:</p>
<p>قد تحتوي منصتنا على روابط لمواقع الطرف الثالث. Vrooem.com ليس مسؤولاً عن سياسات الخصوصية الخاصة بهم.</p>

<h2>14. خصوصية الأطفال</h2>
<p><strong>14.1</strong> قيود العمر:</p>
<p>Vrooem.com غير مخصص للأشخاص تحت سن 18 عامًا. نجمع بيانات شخصية من القاصرين.</p>

<h2>15. الأساس القانوني لمعالجة البيانات</h2>
<p><strong>15.1</strong> الأسس القانونية الخاصة بنا:</p>
<ul>
<li>تنفيذ العقد: لتقديم خدمات التأجير ومعالجة المدفوعات.</li>
<li>الامتثال القانوني: للوفاء بالالتزامات التنظيمية.</li>
<li>المصالح المشروعة: لتحسين منصتنا ومنع الاحتيال.</li>
<li>الموافقة: عند الطلب لاتصالات التسويق.</li>
</ul>

<h2>16. الاتصال والشكاوى</h2>
<p><strong>16.1</strong> كيف تتصل بنا:</p>
<p>info@vrooem.com</p>

<h2>17. مسؤوليات المستخدم</h2>
<p><strong>17.1</strong> كيف يمكنك حماية بياناتك:</p>
<ul>
<li>استخدم كلمة مرور قوية وفريدة.</li>
<li>احتفظ ببيانات الاعتماد الخاصة بك سرية وآمنة.</li>
<li>كن حذرًا عند مشاركة المعلومات الشخصية.</li>
<li>حدث معلومات ملفك الشخصي بانتظام.</li>
</ul>

<h2>18. إشعار خرق البيانات</h2>
<p><strong>18.1</strong> كيف نتعامل مع حوادث الأمان:</p>
<p>في حالة حدوث خرق بيانات يؤثر على معلوماتك الشخصية، سيقوم Vrooem.com بالتحقيق وتقييم الأثر على الفور وإشعار المستخدمين المتأثرين في أسرع وقت ممكن.</p>

<h2>19. قابلية نقل البيانات</h2>
<p><strong>19.1</strong> نقل بياناتك:</p>
<p>لديك الحق في طلب نسخة من بياناتك الشخصية بتنسيق منظم (JSON أو CSV أو XML).</p>

<h2>20. المعاملات التجارية</h2>
<p><strong>20.1</strong> ما الذي يحدث لبياناتك عند تغيير الملكية:</p>
<p>إذا خضع Vrooem.com للاندماج أو الاستحواذ أو إعادة الهيكلة، قد يتم نقل بياناتك كجزء من الصفقة.</p>

<h2>46. الاتصال النهائي</h2>
<p>لأي أسئلة متعلقة بالخصوصية: info@vrooem.com</p>';
    }
}
