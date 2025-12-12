# CarRental Project Overview

This repository is a Laravel-based multi-tenant car rental platform with an Inertia + Vue 3 frontend. It includes public booking flows, vendor and admin portals, affiliate tracking, messaging, and integrations with multiple external rental providers and payment services.

## Tech Stack

- **Backend**
  - PHP ^8.1 with **Laravel 10** (`laravel/framework`).
  - Authentication & APIs: Laravel Sanctum (`laravel/sanctum`), standard Laravel auth scaffolding.
  - Frontend bridge: Inertia (`inertiajs/inertia-laravel`) with Ziggy (`tightenco/ziggy`) for route helpers.
  - Storage & media: AWS S3 via `aws/aws-sdk-php` and `league/flysystem-aws-s3-v3`, Intervention Image for image handling.
  - Payments & billing: Stripe PHP SDK (`stripe/stripe-php`), Tapfiliate SDK (`tapfiliate/sdk-php`) for affiliate payouts.
  - Communications & notifications: Twilio SDK (`twilio/sdk`), Pusher (`pusher/pusher-php-server`) + Laravel Echo.
  - Documents & exports: `dompdf/dompdf` for PDFs, `phpoffice/phpspreadsheet` for Excel import/export, QR generation via `simplesoftwareio/simple-qrcode`.
  - Geo & phone utilities: `stevebauman/location` (IP geolocation), `propaganistas/laravel-phone` (phone validation).

- **Frontend**
  - **Vue 3** SPA powered by **Inertia.js** (`@inertiajs/inertia`, `@inertiajs/vue3`) and **Vite**.
  - Styling: **Tailwind CSS** with forms plugin and `tailwindcss-animate`; utility helpers via `class-variance-authority`, `clsx`, `tailwind-merge`.
  - HTTP & state: `axios`, Vue composables (`@vueuse/core`), simple stores in `resources/js/stores`.
  - UI & UX: component libraries (`radix-vue`, `reka-ui`, `@radix-icons/vue`, `lucide-vue-next`), date pickers (`@vuepic/vue-datepicker`, `v-calendar`), sliders, timepickers, toast notifications, and Lottie animations.
  - Maps & locations: Leaflet + Vue wrappers (`leaflet`, `@vue-leaflet/vue-leaflet`, `leaflet.locatecontrol`, `leaflet.markercluster`).
  - Charts & reporting: Chart.js with `vue-chartjs`, Unovis (`@unovis/ts`, `@unovis/vue`) for data visualizations.
  - Payments: Stripe.js (`@stripe/stripe-js`) and Vue Stripe (`@vue-stripe/vue-stripe`) on the client side.

## High-Level Architecture

- **Laravel application skeleton**
  - `app/` contains domain logic: controllers, middleware, jobs, models, services, notifications, policies, rules, and helpers.
  - `routes/web.php` defines web routes for the public site, vendor portal, and admin dashboard (mostly Inertia-powered pages).
  - `routes/api.php` exposes JSON APIs for vehicles, bookings, locations, SEO meta, reviews, and configuration data (currencies, payment percentages, etc.).
  - `database/migrations` defines tables for users, profiles, vendors, vehicles, bookings, payments, content pages, SEO metadata, affiliate entities, and more.
  - `config/` holds standard Laravel configuration plus integration-specific settings (e.g. mail, broadcasting, queue, services).

- **Inertia + Vue frontend**
  - Root Blade entries in `resources/views/app.blade.php` and `resources/views/welcome.blade.php` mount the SPA.
  - Main JS entry in `resources/js/app.js` and bootstrapping in `resources/js/bootstrap.js`.
  - Layout shells in `resources/js/Layouts/` (guest, authenticated, profile, admin dashboard, headers) organize major sections of the UI.
  - Page-level views in `resources/js/Pages/` implement flows for bookings, search results, messages, notifications, vendor/admin dashboards, and provider-specific booking pages.
  - Reusable UI pieces live in `resources/js/Components/` (buttons, navigation, car cards, search bars, filters, pagination, modals, date pickers, payment widgets, chat widgets, etc.).

## Core Domain Areas

- **Bookings & Payments**
  - Controllers such as `BookingController`, `BookingAddonController`, `PlanController`, and `PaymentController` handle the booking lifecycle, add-ons, plans, and payment interactions.
  - Models include `Booking`, `BookingPayment`, `BookingAddon`, `BookingExtra`, `Plan`, and related entities to track reservations, extras, and financials.
  - Frontend pages under `resources/js/Pages/Booking/` (e.g. `Success.vue`, `Unsuccess.vue`, `BookingDetails.vue`, `Cancel.vue`) manage the user-facing booking flow.

- **Vehicle & Category Management**
  - Models like `Vehicle`, `VehicleCategory`, `VehicleFeature`, `VehicleBenefit`, `VehicleImage`, `VehicleSpecification` represent the fleet and its attributes.
  - Controllers `VehicleController`, `VehicleCategoryController`, `VehicleCsvImportController`, `BulkVehicleUploadController`, `VendorVehicleController`, and `VendorVehicleAddonController` support CRUD operations, CSV import, bulk uploads, and vendor-specific configuration.

- **Vendors & Vendor Portal**
  - Vendor-facing controllers under `App\Http\Controllers\Vendor` (`VendorOverviewController`, `VendorBookingController`, `VendorVehiclePlanController`, `PlanController`, `BlockingDateController`, `DamageProtectionController`) provide dashboards, booking views, and configuration tools.
  - Models `VendorProfile`, `VendorDocument`, `VendorVehiclePlan`, `VendorVehicleAddon`, `VendorBulkVehicleImage` represent vendor profiles, documents, and pricing/plan structures.

- **Admin Panel**
  - Admin controllers under `App\Http\Controllers\Admin` manage:
    - Users and reports (`UsersController`, `UsersReportController`, `UserReportDownloadController`).
    - Vehicles and fleet dashboards (`VehicleDashboardController`, `VehicleCategoriesController`, `VehicleAddonsController`).
    - Bookings & payments dashboards (`BookingDashboardController`, `PaymentDashboardController`, `BusinessReportsController`, `PayableSettingController`).
    - Content & SEO (`PageController`, `BlogController`, `FaqController`, `TestimonialController`, `SchemaController`, `SeoMetaController`, `HeaderFooterScriptController`, `ContactUsPageController`).
    - Marketing & vendors (`AdminAdvertisementController`, `PopularPlacesController`, `VendorsDashboardController`, `VendorsReportController`).
    - Activity logs and admin profile (`ActivityLogsController`, `DashboardController`, `HomePageController`).

- **Affiliate Program**
  - Controllers under `App\Http\Controllers\Affiliate` (`AffiliateBusinessController`, `AffiliateQrCodeController`) and models in `App\Models\Affiliate\*` manage affiliate businesses, business models, QR codes, commissions, customer scans, global settings, and dashboard sessions.
  - QR-based referrals and commission tracking integrate with tapfiliate and internal models.

- **Messaging & Notifications**
  - `MessageController`, `NotificationController`, and models like `Message`, `Notification`, `ChatStatus`, `ChatTypingStatus`, `MessageReadReceipt` implement a chat/messaging system between customers, vendors, and admins.
  - Real-time behavior is powered by Pusher (`pusher/pusher-php-server`, `pusher-js`) and Laravel Echo on the frontend, with UI components like `ChatComponent.vue` and `NotificationBell.vue`.

- **Content, SEO & Marketing**
  - Page/content models: `Page`, `PageTranslation`, `Blog`, `Faq`, `FaqTranslation`, `Testimonial`, `PopularPlace`, `Advertisement`, `Schema`, `SeoMeta`, `SeoMetaTranslation`.
  - Controllers expose content to the frontend and APIs (e.g. `/recent-blogs`, `/faqs`, `/popular-places`, `/seo-meta`).
  - Components like `Testimonials.vue`, `Faq.vue`, `AdvertisementSection.vue`, and `SchemaInjector.vue` render marketing and SEO-enhanced content.

- **Localization & Multi-Currency**
  - Middleware in `app/Http/Middleware` (`LocalizationMiddleware`, `SetLocale`, `ShareCountryFromUrl`, `CurrencyDetectionMiddleware`, `SetCurrency`) handle language and currency resolution from URL, headers, or user profile.
  - `LanguageController` and `CurrencyController` expose available languages and currencies; API routes serve currency configuration (`/currencies`) and payment percentages.
  - Public sitemaps (multiple languages) and localized XML files live in `public/` (e.g. `sitemap_en_vehicles.xml`, `sitemap_fr_vehicles.xml`, etc.).

- **External Rental Providers & Integrations**
  - Controllers:
    - `GreenMotionController`, `GreenMotionBookingController`.
    - `OkMobilityController`, `OkMobilityBookingController`.
    - `AdobeController`, `AdobeCarController`, `AdobeBookingController`.
    - `LocautoRentController`.
    - `WheelsysCarController`, `WheelsysBookingController`.
  - Public data files in `public/` (e.g. `greenmotion_locations.json`, `unified_locations.json`, `wheelsys_*_response.json`) support provider search and demo/test data.
  - Route groupings in `routes/api.php` expose provider-specific endpoints for vehicles, locations, and drop-off locations, plus a unified location search.

- **User Profiles & Documents**
  - Models: `UserProfile`, `UserDocument`, `VendorDocument`, `Customer`, `ContactSubmission`, `ContactUsPage`, `NewContactUsPage`, and their translation models.
  - Controllers: `ProfileController`, `AdminProfileController`, `UserDocumentController`, `ContactFormController`.
  - File uploads (e.g. identification documents, vendor documents) integrate with the media library via `Media` model and `AdminMediaController`.

- **Background Jobs & Notifications**
  - Jobs like `SendGreenMotionBookingNotificationJob` (and others as added) handle asynchronous tasks such as booking notifications.
  - Laravel’s queue system is configured in the usual places (`config/queue.php`) and driven via `php artisan queue:work`.

## Frontend Structure Highlights

- **Layouts (`resources/js/Layouts/`)**
  - `GuestLayout.vue`, `AuthenticatedLayout.vue`, `AuthenticatedHeaderLayout.vue`, `AdminDashboardLayout.vue`, `MyProfileLayout.vue`, `GuestHeader.vue` define the high-level frames for each major area.

- **Pages (`resources/js/Pages/`)**
  - Booking: pages for success, failure, cancellation, and booking details.
  - Messaging: `Messages/Index.vue`, `Messages/Show.vue`, `Messages/VendorIndex.vue` for chat views.
  - Search & vehicles: `SearchResults.vue`, `SingleCar.vue`, provider-specific pages such as `GreenMotionCars.vue`, `OkMobilitySingle.vue`, `OkMobilityCancel.vue`.
  - Admin & dashboards: `AdminDashboard.vue` and other admin/vendor-specific pages (grouped by Inertia route names in `routes/web.php`).
  - Frontend informational pages under `Frontend/` (e.g. `AboutUsPage.vue`, generic `Page.vue` fed by CMS-like models).

- **Components (`resources/js/Components/`)**
  - Form & UI primitives: buttons, inputs, labels, dropdowns, checkboxes, text areas.
  - Navigation & layout: `NavLink.vue`, `ResponsiveNavLink.vue`, `SiderBar.vue`, `AdminSiderBar.vue`, `Footer.vue`, `ScrollToTop.vue`.
  - Booking & search: `CarCard.vue`, `SearchBar.vue`, `CategorySearchBar.vue`, `FilterSlot.vue`, `BookingSelectionModal.vue`, `PriceDisplay.vue`, `CategoryCarousel.vue`, `Pagination.vue`.
  - Payments: `StripeCheckout.vue`, `GreenMotionStripeCheckout.vue`, `WheelsysStripeCheckout.vue`, `OKMobilityStripeCheckout.vue`.
  - Engagement: `Testimonials.vue`, `Faq.vue`, `AffiliateSignupPopup.vue`, `FavoriteButton.vue`, `NotificationBell.vue`, `ChatComponent.vue`, `EsimSection.vue`, `ESimBanner.vue`, `AdvertisementSection.vue`, `FloatingBubbles.vue`.
  - Utility: `Lightbox.vue`, `LocationPicker.vue`, `CountrySelector.vue`, `SchemaInjector.vue`, `MediaLibraryModal.vue`, and generic modal components.

## Data & Supporting Assets

- **Database**
  - Migrations define schema for users, profiles, vendors, vehicles, bookings, payments, add-ons, content pages, SEO metadata, affiliate entities, chat/messages, and various settings.
  - Seeders and factories in `database/seeders` and `database/factories` support local development data.

- **Public assets (`public/`)**
  - Static JSON data for countries, currencies, locations, and provider responses (e.g. `countries.json`, `currency.json`, `greenmotion_locations.json`, `unified_locations.json`, `wheelsys_*_response.json`).
  - Sitemaps for multiple languages and content types (`sitemap_*_*.xml` and `sitemap_index.xml`).
  - Images, SVGs, animations, and compiled frontend assets in `public/images`, `public/animations`, and `public/build`.

- **Documentation & reference files (`docs/`)**
  - Provider documentation: PDFs and Word/Excel files describing Locauto, OTA specifications, extra service codes, and Vrooem rental agreements.
  - Reference images used in docs.

## Tests & Tooling

- **Tests**
  - Backend tests live under `tests/Feature` and `tests/Unit`, configured via `phpunit.xml`.
  - Laravel’s standard `TestCase` scaffold in `tests/TestCase.php` and `tests/CreatesApplication.php`.

- **Tooling & configuration**
  - `phpunit.xml` for testing, `tailwind.config.js`, `postcss.config.js`, `vite.config.js`, and `jsconfig.json` / `tsconfig.json` for the frontend toolchain.
  - `nixpacks.toml` and `codex.config.json` provide deployment/editor tooling configuration.

This summary should give you a fast mental map of the CarRental codebase so you can navigate by domain (bookings, vehicles, vendors, admin, affiliate, messaging, content, integrations) and quickly locate the relevant controllers, models, and Vue pages for any feature you want to work on.

