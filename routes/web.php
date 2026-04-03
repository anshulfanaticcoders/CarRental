<?php

use App\Http\Controllers\Admin\ActivityLogsController;
use App\Http\Controllers\Admin\AdminFeaturesController;
use App\Http\Controllers\Admin\AdminMediaController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\Admin\AdminUserDocumentController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\BookingDashboardController;
use App\Http\Controllers\Admin\BusinessReportsController;
use App\Http\Controllers\Admin\ContactUsPageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PaymentDashboardController;
use App\Http\Controllers\Admin\PlansController;
use App\Http\Controllers\Admin\PopularPlacesController;
use App\Http\Controllers\PopularPlacesController as PublicPopularPlacesController;
use App\Http\Controllers\Admin\RadiusController;
use App\Http\Controllers\Admin\SchemaController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\UserReportDownloadController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\UsersReportController;
use App\Http\Controllers\Admin\VehicleAddonsController;
use App\Http\Controllers\Admin\VehicleDashboardController;
use App\Http\Controllers\Admin\DamageProtectionController as AdminDamageProtectionController;
use App\Http\Controllers\Admin\SeoMetaController;
use App\Http\Controllers\Admin\NewsletterSubscriberController;
use App\Http\Controllers\Admin\NewsletterCampaignController;
use App\Http\Controllers\Admin\VendorsReportController;
use App\Http\Controllers\NewsletterTrackingController;
use App\Http\Controllers\Admin\AdminAdvertisementController;
use App\Http\Controllers\Admin\AdminAffiliateController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\EmailValidationController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BulkVehicleController;
use App\Http\Controllers\BulkVehicleUploadController;
use App\Http\Controllers\ContactFormController;
use App\Http\Controllers\EsimAccessController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FrontendPageController;
use App\Http\Controllers\GeocodingController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\NewsletterSubscriptionController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Admin\VehicleCategoriesController;
use App\Http\Controllers\VehicleCsvImportController;
use App\Http\Controllers\VehicleImportController;
use App\Http\Controllers\Vendor\BlockingDateController;
use App\Http\Controllers\Vendor\DamageProtectionController;
use App\Http\Controllers\Vendor\VendorBookingController;
use App\Http\Controllers\Vendor\VendorOverviewController;
use App\Http\Controllers\Vendor\VendorVehicleController;
use App\Http\Controllers\Vendor\VendorExternalBookingController;
use App\Http\Controllers\Vendor\VendorVehiclePlanController;
use App\Http\Controllers\VendorBulkImageController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Admin\VendorsDashboardController;
use App\Http\Controllers\Admin\PayableSettingController;
use App\Models\Booking;
use App\Models\Advertisement;
use App\Models\Message;
use App\Models\Page;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UserDocumentController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\AdminProfileController; // Import the AdminProfileController
use App\Http\Controllers\Admin\AffiliateBusinessModelController; // Import the AffiliateBusinessModelController
use App\Http\Controllers\Affiliate\AffiliateQrCodeController; // Import the AffiliateQrCodeController

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Admin-specific logout route (outside locale prefix)
Route::post('/admin/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.logout');

Route::middleware('guest')->group(function () {
    Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
        ->whereIn('provider', ['google', 'facebook'])
        ->name('oauth.redirect.global');

    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->whereIn('provider', ['google', 'facebook'])
        ->name('oauth.callback.global');
});

Route::post('/language/change', [LanguageController::class, 'change'])->name('language.change');

Route::post('/currency', [CurrencyController::class, 'update'])->name('currency.update');

// Test route to force automatic detection (clears detection time)
Route::get('/force-currency-detection', function () {
    session()->forget('currency');
    session()->forget('currency_detection_time');

    return response()->json([
        'message' => 'Currency detection forced. Refresh page to see automatic detection.',
        'session_cleared' => true
    ]);
});

Route::get('/', function () {
    $locale = session('locale', config('app.fallback_locale', 'en'));
    return redirect($locale);
});

// Admin Routes (Moved outside of locale prefix)
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::inertia('admin-dashboard', 'AdminDashboard');
    Route::resource('vehicles-categories', VehicleCategoriesController::class)->parameters(['vehicles-categories' => 'vehicleCategory']);
    Route::patch('/vehicles-categories/{vehicleCategory}/status', [VehicleCategoriesController::class, 'updateStatus'])->name('vehicles-categories.status');
    Route::delete('/vehicles-categories/bulk-delete', [VehicleCategoriesController::class, 'bulkDelete'])->name('vehicles-categories.bulk-delete');
    Route::patch('/vehicles-categories/bulk-status', [VehicleCategoriesController::class, 'bulkUpdateStatus'])->name('vehicles-categories.bulk-status');
    Route::resource('vendors', VendorsDashboardController::class)->except(['create', 'edit', 'show']);
    Route::put('/vendors/{vendorProfile}/status', [VendorsDashboardController::class, 'updateStatus'])->name('vendors.updateStatus');

    Route::resource('users', UsersController::class)->except(['create', 'edit', 'show']);
    Route::resource('vendor-vehicles', VehicleDashboardController::class)->except(['create', 'show'])->names([
        'edit' => 'admin.vehicles.edit',
        'update' => 'admin.vehicles.update',
        'index' => 'admin.vehicles.index',
        'store' => 'admin.vehicles.store',
        'destroy' => 'admin.vehicles.destroy',
    ]);
    Route::resource('customer-bookings', BookingDashboardController::class)->except(['create', 'edit', 'show']);
    Route::get('/customer-bookings/pending', [BookingDashboardController::class, 'pending'])->name('customer-bookings.pending');
    Route::get('/customer-bookings/confirmed', [BookingDashboardController::class, 'confirmed'])->name('customer-bookings.confirmed');
    Route::get('/customer-bookings/completed', [BookingDashboardController::class, 'completed'])->name('customer-bookings.completed');
    Route::get('/customer-bookings/cancelled', [BookingDashboardController::class, 'cancelled'])->name('customer-bookings.cancelled');
    Route::post('/customer-bookings/{id}/cancel', [BookingDashboardController::class, 'cancelBooking'])->name('customer-bookings.cancel');



    Route::resource('booking-addons', VehicleAddonsController::class);
    Route::resource('popular-places', PopularPlacesController::class)->except(['show']);
    Route::resource('admin/plans', PlansController::class);
    Route::resource('blogs', BlogController::class)->names('admin.blogs');
    Route::get('/admin-dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Activity logs
    Route::get('/activity-logs', [ActivityLogsController::class, 'index'])->name('activity-logs.index');

    // Payments
    Route::get('/admin/payments', [PaymentDashboardController::class, 'index'])->name('admin.payments.index');

    // Newsletter Subscribers
    Route::get('/admin/newsletter-subscribers', [NewsletterSubscriberController::class, 'index'])
        ->name('admin.newsletter-subscribers.index');
    Route::patch('/admin/newsletter-subscribers/{subscription}/cancel', [NewsletterSubscriberController::class, 'cancel'])
        ->name('admin.newsletter-subscribers.cancel');

    // Newsletter Campaigns
    Route::get('/admin/newsletter-campaigns', [NewsletterCampaignController::class, 'index'])
        ->name('admin.newsletter-campaigns.index');
    Route::get('/admin/newsletter-campaigns/create', [NewsletterCampaignController::class, 'create'])
        ->name('admin.newsletter-campaigns.create');
    Route::post('/admin/newsletter-campaigns', [NewsletterCampaignController::class, 'store'])
        ->name('admin.newsletter-campaigns.store');
    Route::get('/admin/newsletter-campaigns/{campaign}', [NewsletterCampaignController::class, 'show'])
        ->name('admin.newsletter-campaigns.show');
    Route::get('/admin/newsletter-campaigns/{campaign}/edit', [NewsletterCampaignController::class, 'edit'])
        ->name('admin.newsletter-campaigns.edit');
    Route::put('/admin/newsletter-campaigns/{campaign}', [NewsletterCampaignController::class, 'update'])
        ->name('admin.newsletter-campaigns.update');
    Route::delete('/admin/newsletter-campaigns/{campaign}', [NewsletterCampaignController::class, 'destroy'])
        ->name('admin.newsletter-campaigns.destroy');
    Route::post('/admin/newsletter-campaigns/{campaign}/send', [NewsletterCampaignController::class, 'send'])
        ->name('admin.newsletter-campaigns.send');
    Route::post('/admin/newsletter-campaigns/{campaign}/schedule', [NewsletterCampaignController::class, 'schedule'])
        ->name('admin.newsletter-campaigns.schedule');
    Route::post('/admin/newsletter-campaigns/{campaign}/cancel', [NewsletterCampaignController::class, 'cancel'])
        ->name('admin.newsletter-campaigns.cancel');
    Route::post('/admin/newsletter-campaigns/{campaign}/test', [NewsletterCampaignController::class, 'testEmail'])
        ->name('admin.newsletter-campaigns.test');
    Route::get('/admin/newsletter-subscribers/export', [NewsletterCampaignController::class, 'exportSubscribers'])
        ->name('admin.newsletter-subscribers.export');

    // Analytics
    Route::get('/admin/analytics', [\App\Http\Controllers\Admin\AnalyticsDashboardController::class, 'index'])
        ->name('admin.analytics.index');

    // User reports
    Route::get('/users-report', [UsersReportController::class, 'index'])->name('admin.users.reports');
    Route::get('/vendors-report', [VendorsReportController::class, 'index'])->name('admin.vendors.reports');
    Route::get('/business-report', [BusinessReportsController::class, 'index'])->name('admin.business.reports');

    Route::get('/admin/reports/users/download', [UserReportDownloadController::class, 'downloadXML']);

    Route::resource('pages', PageController::class)->names('admin.pages');

    // Routes for user documents verifications
    Route::prefix('admin')->group(function () {
        Route::get('/user-documents', [AdminUserDocumentController::class, 'index'])->name('admin.user-documents.index');
        Route::put('/user-documents/{userDocument}', [AdminUserDocumentController::class, 'update'])->name('admin.user-documents.update');
        Route::get('/user-documents/{userDocument}', [AdminUserDocumentController::class, 'show'])->name('admin.user-documents.show');
        Route::get('/user-documents-stats', [AdminUserDocumentController::class, 'stats'])->name('admin.user-documents.stats');
        Route::get('/user-documents-recent', [AdminUserDocumentController::class, 'recent'])->name('admin.user-documents.recent');
    });

    // Routes for footer setting
    Route::get('/admin/settings/footer', [PopularPlacesController::class, 'footerSettings'])->name('admin.settings.footer');
    Route::post('/admin/settings/footer/update', [PopularPlacesController::class, 'updateFooterSettings'])->name('admin.settings.footer.update');
    Route::get('/admin/settings/footer-categories', [VehicleCategoriesController::class, 'footerSettings'])->name('admin.settings.footer-categories');
    Route::post('/admin/settings/footer-categories/update', [VehicleCategoriesController::class, 'updateFooterSettings'])->name('admin.settings.footer-categories.update');

    // Route for FAQ
    Route::get('admin/settings/faq', [FaqController::class, 'index'])->name('admin.settings.faq.index');
    Route::post('admin/settings/faq', [FaqController::class, 'store'])->name('admin.settings.faq.store');
    Route::post('admin/settings/faq/bulk', [FaqController::class, 'storeBulk'])->name('admin.settings.faq.bulk');
    Route::put('admin/settings/faq/{faq}', [FaqController::class, 'update'])->name('admin.settings.faq.update');
    Route::delete('admin/settings/faq/{faq}', [FaqController::class, 'destroy'])->name('admin.settings.faq.destroy');

    // Contact Us routes
    Route::get('admin/contact-us', [ContactUsPageController::class, 'index'])->name('admin.contact-us.index');
    Route::get('admin/contact-us/edit', [ContactUsPageController::class, 'edit'])->name('admin.contact-us.edit');
    Route::post('admin/contact-us/update', [ContactUsPageController::class, 'update'])->name('admin.contact-us.update');
    Route::delete('admin/contact-us/delete', [ContactUsPageController::class, 'destroy'])->name('admin.contact-us.delete');

    // Contact form submissions and notifications
    Route::get('/contact-us-mails', [ContactFormController::class, 'fetchSubmissions'])->name('contact.mails');
    Route::get('/notifications/unread', [ContactFormController::class, 'unreadNotifications'])->name('admin.notifications.unread');
    Route::post('/notifications/mark-as-read/{id}', [ContactFormController::class, 'markAsRead'])->name('notifications.markAsRead');

    // Testimonials
    Route::get('/testimonials', [TestimonialController::class, 'index'])->name('testimonials.index');
    Route::post('/testimonials', [TestimonialController::class, 'store'])->name('testimonials.store');
    Route::get('/testimonials/{id}', [TestimonialController::class, 'show'])->name('testimonials.show');
    Route::post('/testimonials/{id}', [TestimonialController::class, 'update'])->name('testimonials.update');
    Route::delete('/testimonials/{id}', [TestimonialController::class, 'destroy'])->name('testimonials.destroy');

    // Radiuses
    Route::get('/radiuses', [RadiusController::class, 'index'])->name('radiuses.index');
    Route::post('/radiuses', [RadiusController::class, 'store'])->name('radiuses.store');
    Route::put('/radiuses/{radius}', [RadiusController::class, 'update'])->name('radiuses.update');
    Route::delete('/radiuses/{radius}', [RadiusController::class, 'destroy'])->name('radiuses.destroy');

    // Reviews
    Route::get('/admin/reviews', [AdminReviewController::class, 'index'])->name('admin.reviews.index');
    Route::delete('/admin/reviews/{review}', [AdminReviewController::class, 'destroy'])->name('admin.reviews.destroy');
    Route::patch('/admin/reviews/{review}/status', [AdminReviewController::class, 'updateStatus'])->name('admin.reviews.update-status');

    // Vehicle Features Routes
    Route::get('features', [AdminFeaturesController::class, 'index'])->name('admin.features.index');
    Route::get('features/create/{category}', [AdminFeaturesController::class, 'create'])->name('admin.features.create');
    Route::post('features', [AdminFeaturesController::class, 'store'])->name('admin.features.store');
    Route::get('features/{feature}/edit', [AdminFeaturesController::class, 'edit'])->name('admin.features.edit');
    Route::put('features/{feature}', [AdminFeaturesController::class, 'update'])->name('admin.features.update');
    Route::delete('features/{feature}', [AdminFeaturesController::class, 'destroy'])->name('admin.features.destroy');

    // Media Library Routes
    Route::get('media', [AdminMediaController::class, 'index'])->name('admin.media.index');
    Route::post('media', [AdminMediaController::class, 'store'])->name('admin.media.store');
    Route::delete('media/{medium}', [AdminMediaController::class, 'destroy'])->name('admin.media.destroy');

    // Damage Protection Records for Admin
    Route::get('/damage-protection-records', [AdminDamageProtectionController::class, 'index'])->name('admin.damage-protection.index');

    // SEO Meta Settings
    Route::resource('admin/seo-meta', SeoMetaController::class)
        ->parameters(['seo-meta' => 'seo_meta'])
        ->names('admin.seo-meta');

    // Header Footer Scripts Settings
    Route::resource('admin/header-footer-scripts', \App\Http\Controllers\Admin\HeaderFooterScriptController::class)
        ->parameters(['header-footer-scripts' => 'headerFooterScript'])
        ->names('admin.header-footer-scripts');

    // Home Page Settings
    Route::get('/admin/settings/homepage', [\App\Http\Controllers\Admin\HomePageController::class, 'index'])->name('admin.settings.homepage');
    Route::post('/admin/settings/homepage/hero-image', [\App\Http\Controllers\Admin\HomePageController::class, 'updateHeroImage'])->name('admin.settings.homepage.hero-image');

    // Schema Management Routes
    Route::resource('admin/schemas', SchemaController::class)->names('admin.schemas');

    // Admin Profile Settings
    Route::get('/admin/settings/profile', [AdminProfileController::class, 'index'])->name('admin.settings.profile');
    Route::post('/admin/settings/profile', [AdminProfileController::class, 'update'])->name('admin.settings.profile.update');

    // Advertisements Management
    Route::resource('admin/advertisements', AdminAdvertisementController::class)->names('admin.advertisements');

    // Payable Amount Settings
    Route::get('/admin/settings/payable-amount', [PayableSettingController::class, 'index'])->name('admin.settings.payable-amount.index');
    Route::post('/admin/settings/payable-amount', [PayableSettingController::class, 'update'])->name('admin.settings.payable-amount.update');

    // Affiliate Business Model Settings (kept — already clean)
    Route::get('/admin/affiliate/business-model', [AffiliateBusinessModelController::class, 'index'])->name('admin.affiliate.business-model.index');
    Route::get('/admin/affiliate/global-settings', [AffiliateBusinessModelController::class, 'getGlobalSettings'])->name('admin.affiliate.global-settings');
    Route::post('/admin/affiliate/global-settings', [AffiliateBusinessModelController::class, 'updateGlobalSettings'])->name('admin.affiliate.global-settings.update');
    Route::post('/admin/affiliate/businesses/{businessId}/model', [AffiliateBusinessModelController::class, 'updateBusinessModel'])->name('admin.affiliate.businesses.model.update');
    Route::delete('/admin/affiliate/businesses/{businessId}/model', [AffiliateBusinessModelController::class, 'deleteBusinessModel'])->name('admin.affiliate.businesses.model.delete');
    Route::get('/admin/affiliate/businesses', [AffiliateBusinessModelController::class, 'getBusinesses'])->name('admin.affiliate.businesses');

    // Business Verification Actions (kept — used by partner detail page)
    Route::post('/admin/affiliate/businesses/{businessId}/verify', [AffiliateBusinessModelController::class, 'verifyBusiness'])->name('admin.affiliate.businesses.verify');
    Route::post('/admin/affiliate/businesses/{businessId}/reject', [AffiliateBusinessModelController::class, 'rejectBusiness'])->name('admin.affiliate.businesses.reject');
    Route::post('/admin/affiliate/businesses/{businessId}/suspend', [AffiliateBusinessModelController::class, 'suspendBusiness'])->name('admin.affiliate.businesses.suspend');
    Route::post('/admin/affiliate/businesses/{businessId}/activate', [AffiliateBusinessModelController::class, 'activateBusiness'])->name('admin.affiliate.businesses.activate');

    // New Affiliate Admin Pages
    Route::get('/admin/affiliate/overview', [AdminAffiliateController::class, 'overview'])->name('admin.affiliate.overview');
    Route::get('/admin/affiliate/partners', [AdminAffiliateController::class, 'partners'])->name('admin.affiliate.partners');
    Route::get('/admin/affiliate/partners/{id}', [AdminAffiliateController::class, 'partnerDetail'])->name('admin.affiliate.partners.show');
    Route::delete('/admin/affiliate/partners/{id}', [AdminAffiliateController::class, 'destroy'])->name('admin.affiliate.partners.destroy');
    Route::get('/admin/affiliate/commissions', [AdminAffiliateController::class, 'commissions'])->name('admin.affiliate.commissions');
    Route::patch('/admin/affiliate/commissions/{id}/status', [AdminAffiliateController::class, 'updateCommissionStatus'])->name('admin.affiliate.commissions.status.update');

    // Scout Payout Management (kept — already clean)
    Route::get('/admin/affiliate/payouts', [\App\Http\Controllers\Admin\AffiliateScoutPayoutController::class, 'index'])->name('admin.affiliate.payouts');
    Route::post('/admin/affiliate/payouts', [\App\Http\Controllers\Admin\AffiliateScoutPayoutController::class, 'createPayout'])->name('admin.affiliate.payouts.create');
    Route::post('/admin/affiliate/payouts/{payout}/mark-paid', [\App\Http\Controllers\Admin\AffiliateScoutPayoutController::class, 'markAsPaid'])->name('admin.affiliate.payouts.mark-paid');

    // API Consumer Management
    Route::resource('api-consumers', \App\Http\Controllers\Admin\ApiConsumerController::class)->names('admin.api-consumers');
    Route::post('/api-consumers/{apiConsumer}/generate-key', [\App\Http\Controllers\Admin\ApiConsumerController::class, 'generateKey'])->name('admin.api-consumers.generate-key');
    Route::post('/api-consumers/keys/{apiKey}/rotate', [\App\Http\Controllers\Admin\ApiConsumerController::class, 'rotateKey'])->name('admin.api-consumers.rotate-key');
    Route::post('/api-consumers/keys/{apiKey}/revoke', [\App\Http\Controllers\Admin\ApiConsumerController::class, 'revokeKey'])->name('admin.api-consumers.revoke-key');
    Route::patch('/api-consumers/{apiConsumer}/toggle-status', [\App\Http\Controllers\Admin\ApiConsumerController::class, 'toggleStatus'])->name('admin.api-consumers.toggle-status');
});

// Newsletter tracking (public signed routes)
Route::get('/newsletter/track/open/{log}', [NewsletterTrackingController::class, 'trackOpen'])
    ->middleware('signed')
    ->name('newsletter.track.open');
Route::get('/newsletter/track/click/{log}', [NewsletterTrackingController::class, 'trackClick'])
    ->middleware('signed')
    ->name('newsletter.track.click');
Route::get('/newsletter/unsubscribe/{subscription}', [NewsletterTrackingController::class, 'unsubscribe'])
    ->middleware('signed')
    ->name('newsletter.unsubscribe');

// Locale-prefixed routes (for customer and vendor)
Route::group([
    'prefix' => '{locale}',
    'where' => ['locale' => '(en|fr|nl|es|ar)'],
    'middleware' => ['set_locale', 'share.country']
], function () {
    // Homepage is served by BlogController::homeBlogs (see later in this group).

    require __DIR__ . '/auth.php';

    Route::get('/newsletter/confirm/{subscription}', [NewsletterSubscriptionController::class, 'confirm'])
        ->middleware(['signed:relative', 'throttle:6,1'])
        ->name('newsletter.confirm');

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->middleware(['auth', 'verified'])->name('dashboard');


    // Authenticated routes
    Route::middleware(['auth'])->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
        Route::get('/user', [ProfileController::class, 'show'])->name('user.profile');
        Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');

        // Message routes
        Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
        Route::get('/customer-chat-partners', [MessageController::class, 'getCustomerChatPartners'])->name('messages.customer.partners');
        Route::get('/messages/vendor', [MessageController::class, 'vendorIndex'])->name('messages.vendor.index');
        Route::get('/messages/{booking}', [MessageController::class, 'show'])->name('messages.show');
        Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
        Route::get('/messages/unread', [MessageController::class, 'getUnreadCount'])->name('messages.unread');
        Route::get('/messages/{booking}/last', [MessageController::class, 'getLastMessage'])->name('messages.last');
        Route::delete('/messages/{id}', [MessageController::class, 'destroy'])->name('messages.destroy');
        Route::post('/messages/{id}/restore', [MessageController::class, 'restore'])->name('messages.restore');
        Route::post('/messages/{booking}/mark-as-read', [MessageController::class, 'markMessagesAsRead'])->name('messages.mark-as-read');

        // Typing indicator routes
        Route::post('/messages/typing', [MessageController::class, 'startTyping'])->name('messages.typing.start');
        Route::post('/messages/stop-typing', [MessageController::class, 'stopTyping'])->name('messages.typing.stop');
        Route::get('/messages/{booking}/typing-users', [MessageController::class, 'getTypingUsers'])->name('messages.typing.users');

        // DEBUG route
        Route::get('/messages/debug', [MessageController::class, 'debugChatPartners'])->name('messages.debug');

        // Notification routes
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/mark-read/{id}', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
        Route::delete('/notifications/clear-all', [NotificationController::class, 'clearAll'])->name('notifications.clear-all');
        Route::get('/notifications/unread', [NotificationController::class, 'getUnreadCount'])->name('notifications.unread');

        Route::get('/profile/completion', [ProfileController::class, 'getProfileCompletion'])->name('profile.completion');
    });


    Route::get('/s', [SearchController::class, 'search'])->name('search');
    Route::get('/destinations', [PublicPopularPlacesController::class, 'destinations'])->name('destinations.index');
    Route::post('/store-search', [SearchController::class, 'storeSearchData'])->name('search.store');
    Route::get('/api/geocoding/autocomplete', [GeocodingController::class, 'autocomplete']);
    Route::get('/api/geocoding/reverse', [GeocodingController::class, 'reverse']);
    Route::get('/api/esim/countries', [EsimAccessController::class, 'countries'])->name('api.esim.countries');
    Route::get('/api/esim/plans/{countryCode}', [EsimAccessController::class, 'plans'])->name('api.esim.plans');
    Route::post('/api/esim/order', [EsimAccessController::class, 'order'])->name('api.esim.order');
    Route::get('/page/{slug}', [PageController::class, 'showPublic'])->name('pages.show');
    Route::get('/faq', [FaqController::class, 'showPublicFaqPage'])->name('faq.show');
    Route::post('/validate-email', [EmailValidationController::class, 'validateEmail'])->name('validate-email');
    Route::post('/validate-contact', [EmailValidationController::class, 'validateContact'])->name('validate-contact');

    // Dynamic page custom slugs (managed via admin Pages > custom_slug field)
    Route::get('/{customSlug}', [\App\Http\Controllers\Admin\PageController::class, 'showByCustomSlug'])
        ->name('pages.custom')
        ->where('customSlug', 'contact-us|about-us|privacy-policy|terms-and-conditions');

    Route::post('/contact', [ContactFormController::class, 'store'])->name('contact.submit')->middleware('throttle:5,1');
    Route::get('/vendor/{vendorProfileId}/reviews', [ReviewController::class, 'vendorAllReviews'])->name('vendor.reviews.all');

    // Show Blogs on Home page
    Route::get('/', [BlogController::class, 'homeBlogs'])->name('welcome');
    Route::get('/vehicle/{id}', [VehicleController::class, 'show'])->name('vehicle.show');
    Route::get('/api/footer-places', [PopularPlacesController::class, 'getFooterPlaces']);
    Route::get('/api/footer-categories', [VehicleCategoriesController::class, 'getFooterCategories']);
    Route::get('/api/vehicles/search-locations', [VehicleController::class, 'searchLocations']);

    // Booking Flow Routes (Public)
    Route::get('/booking/success', [\App\Http\Controllers\StripeCheckoutController::class, 'success'])->name('booking.success');

    Route::get('/booking/cancel', function () {
        return Inertia::render('Booking/Cancel');
    })->name('booking.cancel.page');

    // Booking Details Route - moved to authenticated customer routes with locale prefix (line 849)

    // Public QR Code Tracking Routes (with locale prefix)
    Route::get('/affiliate/track/{trackingData}', [AffiliateQrCodeController::class, 'track'])->name('affiliate.qr.track');
    Route::get('/affiliate/qr/{shortCode}', [AffiliateQrCodeController::class, 'qrLanding'])->name('affiliate.qr.landing');

    // Public affiliate registration
    Route::get('/affiliate/register', [\App\Http\Controllers\Affiliate\AffiliateRegisteredController::class, 'create'])->name('affiliate.register');
    Route::post('/affiliate/register', [\App\Http\Controllers\Affiliate\AffiliateRegisteredController::class, 'store'])->name('affiliate.register.store');

    // Auth-based affiliate routes
    Route::middleware(['auth', 'affiliate'])->prefix('affiliate')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Affiliate\AffiliateDashboardController::class, 'index'])->name('affiliate.dashboard');
        Route::get('/commissions', [\App\Http\Controllers\Affiliate\AffiliateDashboardController::class, 'commissions'])->name('affiliate.commissions');
        Route::get('/qr-codes', [\App\Http\Controllers\Affiliate\AffiliateDashboardController::class, 'qrCodes'])->name('affiliate.qr-codes');
        Route::post('/qr-codes', [\App\Http\Controllers\Affiliate\AffiliateDashboardController::class, 'createQrCode'])->name('affiliate.qr-codes.store');
        Route::get('/settings', [\App\Http\Controllers\Affiliate\AffiliateDashboardController::class, 'settings'])->name('affiliate.settings');
        Route::put('/settings', [\App\Http\Controllers\Affiliate\AffiliateDashboardController::class, 'updateSettings'])->name('affiliate.settings.update');
        Route::put('/bank-details', [\App\Http\Controllers\Affiliate\AffiliateDashboardController::class, 'updateBankDetails'])->name('affiliate.bank-details.update');
        Route::put('/password', [\App\Http\Controllers\Affiliate\AffiliateDashboardController::class, 'updatePassword'])->name('affiliate.password.update');
    });

    // Test route to manually set country for testing
    Route::get('/set-country/{country}', function ($country) {
        session(['country' => strtolower($country)]);

        return response()->json([
            'message' => "Country set to: " . strtolower($country),
            'session_country' => session('country'),
            'all_session_data' => session()->all()
        ]);
    });

    // Test route to verify unified IP detection (currency + country)
    Route::get('/test-unified-detection', function () {
        return response()->json([
            'session_currency' => session('currency', 'not set'),
            'session_country' => session('country', 'not set'),
            'unified_detection_working' => (session('currency') && session('country')),
            'message' => session('currency') && session('country')
                ? '✅ Unified IP detection working! Both currency and country detected.'
                : '❌ Unified detection not complete. Check SetCurrency middleware.',
            'all_session_data' => session()->all()
        ]);
    });

    // Test route to verify comprehensive country coverage
    Route::get('/test-country-coverage/{country}', function ($country) {
        // Test the exact mapping logic from SetCurrency middleware
        $detectedCurrency = match (strtoupper($country)) {
            // North America
            'US' => 'USD',
            'CA' => 'CAD',
            'MX' => 'MXN',
            'NI' => 'NIO',
            'CR' => 'CRC',
            'PA' => 'PAB',
            'GT' => 'GTQ',
            'HN' => 'HNL',
            'SV' => 'SVC',
            'BZ' => 'BZD',
            'JM' => 'JMD',
            'BB' => 'BBD',
            'TT' => 'TTD',
            'DO' => 'DOP',
            'HT' => 'HTG',
            'AG' => 'XCD',
            'DM' => 'XCD',
            'GD' => 'XCD',
            'KN' => 'XCD',
            'LC' => 'XCD',
            'VC' => 'XCD',

            // Europe - Eurozone (All 20 countries)
            'DE', 'FR', 'IT', 'ES', 'NL', 'BE', 'AT', 'PT', 'FI', 'IE',
            'GR', 'CY', 'MT', 'SK', 'SI', 'EE', 'LV', 'LT', 'LU', 'HR' => 'EUR',

            // Europe - Non-Eurozone
            'GB' => 'GBP',
            'CH' => 'CHF',
            'SE' => 'SEK',
            'NO' => 'NOK',
            'DK' => 'DKK',
            'IS' => 'ISK',
            'PL' => 'PLN',
            'CZ' => 'CZK',
            'HU' => 'HUF',
            'RO' => 'RON',
            'BG' => 'BGN',

            // Key countries people will test
            'IN' => 'INR',
            'MA' => 'MAD',
            'RU' => 'RUB',
            'TR' => 'TRY',
            'CN' => 'CNY',
            'JP' => 'JPY',
            'AU' => 'AUD',
            'NZ' => 'NZD',

            default => 'USD',
        };

        return response()->json([
            'test_country' => $country,
            'detected_currency' => $detectedCurrency,
            'supported' => $detectedCurrency !== 'USD' || strtoupper($country) === 'US',
            'message' => $detectedCurrency !== 'USD' || strtoupper($country) === 'US'
                ? "✅ {$country} → {$detectedCurrency}"
                : "❌ {$country} → Not supported, defaults to USD",
        ]);
    });

    // Test route to check blog filtering
    Route::get('/debug-blog-filtering', function () {
        $currentCountry = session('country', 'not set');

        // Test the exact query used in controllers
        $blogsQuery = \App\Models\Blog::with('translations')
            ->where('is_published', true)
            ->where(function ($query) use ($currentCountry) {
                $query->whereJsonContains('countries', $currentCountry)
                    ->orWhereNull('countries');
            });

        $allBlogs = \App\Models\Blog::with('translations')
            ->where('is_published', true)
            ->get();

        $filteredBlogs = $blogsQuery->get();

        $blogDetails = [];
        foreach ($allBlogs as $blog) {
            $blogDetails[] = [
                'id' => $blog->id,
                'title' => $blog->title,
                'countries' => $blog->countries,
                'should_show' => $filteredBlogs->contains($blog),
                'contains_country' => $blog->countries ? in_array($currentCountry, $blog->countries) : false,
                'is_global' => is_null($blog->countries)
            ];
        }

        return response()->json([
            'current_country' => $currentCountry,
            'total_published_blogs' => $allBlogs->count(),
            'filtered_blogs_count' => $filteredBlogs->count(),
            'blog_details' => $blogDetails,
            'sql_query' => $blogsQuery->toSql(),
            'session_data' => session()->all()
        ]);
    });

    // Debug route to test geolocation
    Route::get('/debug-location', function () {
        $request = request();

        // Get real IP like our middleware does
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        $realIp = null;
        $allIps = [];
        foreach ($ipKeys as $key) {
            if ($request->server($key) && !empty($request->server($key))) {
                $ips = explode(',', $request->server($key));
                $ip = trim($ips[0]);
                $allIps[$key] = $ip;

                if (!$realIp && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    $realIp = $ip;
                }
            }
        }

        if (!$realIp) {
            $realIp = $request->ip();
        }

        try {
            $position = \Stevebauman\Location\Facades\Location::get($realIp);
            $detectedCountry = strtolower($position->countryCode ?? null);
            $locationError = null;
        } catch (\Exception $e) {
            $position = null;
            $detectedCountry = null;
            $locationError = $e->getMessage();
        }

        $sessionCountry = session('country', 'not set');

        return response()->json([
            'request_ip' => $request->ip(),
            'real_ip' => $realIp,
            'all_detected_ips' => $allIps,
            'detected_country' => $detectedCountry,
            'session_country' => $sessionCountry,
            'position_data' => $position,
            'location_error' => $locationError,
            'location_config_testing' => config('location.testing.enabled'),
            'location_driver' => config('location.driver'),
            'all_session_data' => session()->all()
        ]);
    });

    Route::get('/blog', function ($locale) {
        $country = session('country', 'us');
        return redirect("/{$locale}/{$country}/blog");
    });

    Route::get('/blog/{slug}', function ($locale, $slug) {
        $country = session('country', 'us');
        return redirect("/{$locale}/{$country}/blog/{$slug}");
    });

    Route::middleware(['redirect.country'])->group(function () {

        Route::get('/{country}/blog', [BlogController::class, 'showBlogPage'])
            ->where('country', '[a-zA-Z]{2}')
            ->name('blog');

        Route::get('/{country}/blog/{blog:slug}', [BlogController::class, 'show'])
            ->where('country', '[a-zA-Z]{2}')
            ->name('blog.show');

    });

    // Vendor Routes
    Route::middleware(['auth', 'role:vendor'])->group(function () {
        Route::inertia('vendor-approved', 'Vendor/VendorApproved');
        Route::inertia('vendor-pending', 'Vendor/VendorPending');
        Route::inertia('vendor-rejected', 'Vendor/VendorRejected');
        Route::get('/vendor/documents', [VendorController::class, 'index'])->name('vendor.documents.index');
        Route::match(['post', 'put'], '/vendor/update', [VendorController::class, 'update'])->name('vendor.update');
        Route::get('/vehicle-categories', [VehicleController::class, 'getCategories'])->name('vehicle.categories');

        // Vendor Bookings
        Route::resource('bookings', VendorBookingController::class)->names('bookings');
        Route::post('api/bookings/{booking}/cancel', [VendorBookingController::class, 'cancel'])->name('bookings.cancel');
        Route::get('customer-documents/{customer}', [VendorBookingController::class, 'viewCustomerDocuments'])->name('vendor.customer-documents.index');

        // Vendor Payments
        Route::get('/vendor/payments', [BookingController::class, 'getVendorPaymentHistory'])->name('vendor.payments');


        // Vendor Vehicles
        Route::resource('current-vendor-vehicles', VendorVehicleController::class)->except(['create', 'store', 'show']);
        Route::patch('current-vendor-vehicles/{vehicle}/parking-address', [VendorVehicleController::class, 'updateParkingAddress'])
            ->name('current-vendor-vehicles.update-parking-address');
        Route::post('current-vendor-vehicles/bulk-destroy', [VendorVehicleController::class, 'bulkDestroy'])->name('current-vendor-vehicles.bulk-destroy');
        Route::delete('/current-vendor-vehicles/{vehicle}/images/{image}', [VendorVehicleController::class, 'deleteImage'])->name('current-vendor-vehicles.deleteImage');

        // Blocking Dates
        Route::resource('blocking-dates', BlockingDateController::class)->names('vendor.blocking-dates');

        // Customer Reviews
        Route::get('/customer-reviews', [ReviewController::class, 'vendorReviews'])->name('vendor.reviews');
        Route::patch('/reviews/{review}/status', [ReviewController::class, 'updateStatus'])->name('reviews.update-status');

        // Vendor Overview
        Route::get('/vendor-overview', [VendorOverviewController::class, 'index'])->name('vendor.overview');

        // Damage Protection
        Route::get('/damage-protection/{booking}', [DamageProtectionController::class, 'index'])->name('vendor.damage-protection.index');
        Route::post('/damage-protection/{booking}/upload-before', [DamageProtectionController::class, 'uploadBeforeImages'])->name('vendor.damage-protection.upload-before');
        Route::post('/damage-protection/{booking}/upload-after', [DamageProtectionController::class, 'uploadAfterImages'])->name('vendor.damage-protection.upload-after');
        Route::delete('/damage-protection/{booking}/delete-before', [DamageProtectionController::class, 'deleteBeforeImages'])->name('vendor.damage-protection.delete-before-images');
        Route::delete('/damage-protection/{booking}/delete-after', [DamageProtectionController::class, 'deleteAfterImages'])->name('vendor.damage-protection.delete-after-images');

        // Vehicle Plans
        Route::get('/plans', [VendorVehiclePlanController::class, 'getPlans']);
        Route::post('/vehicle-plans', [VendorVehiclePlanController::class, 'store']);
        Route::get('/vehicle-plans/{vehicleId}', [VendorVehiclePlanController::class, 'getVehiclePlans']);
        Route::delete('/vehicle-plans/{id}', [VendorVehiclePlanController::class, 'destroy']);

        Route::get('/vendor-status', [VendorController::class, 'status'])->name('vendor.status');

        // Bulk Vehicle Upload Routes
        Route::get('/vehicles/bulk-upload', [BulkVehicleUploadController::class, 'create'])->name('vehicles.bulk-upload.create');
        Route::post('/vehicles/bulk-upload', [BulkVehicleUploadController::class, 'store'])->name('vehicles.bulk-upload.store');
        Route::get('/vehicles/bulk-upload/template', [BulkVehicleUploadController::class, 'downloadTemplate'])->name('vehicles.bulk-upload.template');

        // Bulk Vehicle Image Routes
        Route::get('/bulk-vehicle-images', [VendorBulkImageController::class, 'index'])->name('vendor.bulk-vehicle-images.index');
        Route::post('/bulk-vehicle-images', [VendorBulkImageController::class, 'store'])->name('vendor.bulk-vehicle-images.store');
        Route::delete('/bulk-vehicle-images/{image}', [VendorBulkImageController::class, 'destroy'])->name('vendor.bulk-vehicle-images.destroy');
        Route::post('/bulk-vehicle-images/bulk-destroy', [VendorBulkImageController::class, 'bulkDestroy'])->name('vendor.bulk-vehicle-images.bulk-destroy');

        // External Bookings (Provider API)
        Route::get('/external-bookings', [VendorExternalBookingController::class, 'index'])->name('vendor.external-bookings.index');
        Route::get('/external-bookings/{apiBooking}', [VendorExternalBookingController::class, 'show'])->name('vendor.external-bookings.show');
        Route::patch('/external-bookings/{apiBooking}/status', [VendorExternalBookingController::class, 'updateStatus'])->name('vendor.external-bookings.update-status');
    });


    // Customer Routes
    Route::middleware(['auth', 'role:customer'])->group(function () {
        // User Profile routes
        Route::get('/user/documents', [UserDocumentController::class, 'index'])->name('user.documents.index');
        Route::get('/user/documents/create', [UserDocumentController::class, 'create'])->name('user.documents.create');
        Route::post('/user/documents', [UserDocumentController::class, 'store'])->name('user.documents.store');
        Route::get('/user/documents/{document}/edit', [UserDocumentController::class, 'edit'])->name('user.documents.edit');
        Route::patch('/user/documents/{document}', [UserDocumentController::class, 'update'])->name('user.documents.update');
        Route::post('/user/documents/bulk-upload', [UserDocumentController::class, 'bulkUpload'])->name('user.documents.bulk-upload');
        Route::post('/user/documents/{document}', [UserDocumentController::class, 'update'])->name('user.documents.update.post');
        Route::delete('/user/documents/{document}', [UserDocumentController::class, 'destroy'])->name('user.documents.destroy');

        Route::inertia('completed-bookings', 'Profile/CompletedBookings');
        Route::inertia('confirmed-bookings', 'Profile/ConfirmedBookings');
        Route::inertia('pending-bookings', 'Profile/PendingBookings');
        Route::get('/profile/payments', [BookingController::class, 'getCustomerPaymentHistory'])->name('profile.payments');
        Route::inertia('review', 'Profile/Review');
        Route::get('/favourites', [FavoriteController::class, 'getFavorites'])->name('profile.favourites');
        Route::inertia('inbox', 'Profile/Inbox');

        Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
        Route::get('/profile/reviews', [ReviewController::class, 'userReviews'])->name('profile.reviews');

        // Apply for vendor
        Route::post('/vendor/store', [VendorController::class, 'store'])->name('vendor.store');
        Route::get('/vendor/register', [VendorController::class, 'create'])->name('vendor.register');

        // Booking Routes
        Route::get('/booking/{id}', [BookingController::class, 'show'])->name('booking.show');
        Route::get('/booking/{id}/download-pdf', [BookingController::class, 'downloadPDF'])->name('booking.download.pdf');
        Route::post('/booking/allow-access', [BookingController::class, 'allowAccess'])->name('booking.allow_access');
        Route::post('/booking/cancel', [BookingController::class, 'cancelBooking'])->name('booking.cancel');
        Route::inertia('/booking-unsuccess', 'Booking/Unsuccess');
        Route::get('/booking', [BookingController::class, 'create'])->name('booking.create');
        Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');
        Route::get('/customer/bookings', [BookingController::class, 'getCustomerBookingData'])->name('customer.bookings');



        // Unified booking route (replaces fragmented status routes)
        Route::get('/profile/bookings', [BookingController::class, 'getAllCustomerBookings'])->name('profile.bookings.all');

        // Favourite vehicles
        Route::post('/vehicles/{vehicle}/favourite', [FavoriteController::class, 'favourite'])->name('vehicles.favourite');
        Route::post('/vehicles/{vehicle}/unfavourite', [FavoriteController::class, 'unfavourite'])->name('vehicles.unfavourite');
        Route::get('/favorites', [FavoriteController::class, 'getFavorites']);
        Route::get('/favorites/status', [FavoriteController::class, 'getFavoriteStatus'])->name('favorites.status');
        Route::post('/favorites/provider/toggle', [FavoriteController::class, 'toggleProviderFavourite'])->name('favorites.provider.toggle');

    });

    // Vendor status check for vehicle creation
    Route::middleware(['auth', 'role:vendor', 'vendor.status'])->group(function () {
        Route::get('/vehicles/create', [VehicleController::class, 'create'])->name('vehicles.create');
        Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
        Route::inertia('vehicle-listing', 'Auth/VehicleListingNew');
    });

    // Message polling route
    Route::middleware('auth:sanctum')->get('/messages/{booking}/poll', function (Request $request, $booking) {
        $user = Auth::user();
        $lastMessageId = $request->input('last_message_id', 0);

        $booking = Booking::with(['vehicle.vendor', 'customer'])->findOrFail($booking);

        // Ensure the authenticated user is either the customer or the vendor
        $customerId = $booking->customer->user_id;
        $vendorId = $booking->vehicle->vendor_id;

        if ($user->id !== $customerId && $user->id !== $vendorId) {
            abort(403, 'Unauthorized access to this conversation');
        }

        // Get the other participant in the conversation
        $otherUserId = ($user->id === $customerId) ? $vendorId : $customerId;

        // Get new messages
        $messages = Message::where('id', '>', $lastMessageId)
            ->where('booking_id', $booking->id)
            ->where(function ($query) use ($user, $otherUserId) {
                $query->where(function ($q) use ($user, $otherUserId) {
                    $q->where('sender_id', $user->id)
                        ->where('receiver_id', $otherUserId);
                })
                    ->orWhere(function ($q) use ($user, $otherUserId) {
                        $q->where('sender_id', $otherUserId)
                            ->where('receiver_id', $user->id);
                    });
            })
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'messages' => $messages
        ]);
    });
}); // End of locale group


// Simple test route to verify routes are working
Route::get('/test-business-debug/{token}', function ($token) {
    $business = \App\Models\Affiliate\AffiliateBusiness::where('dashboard_access_token', $token)->first();
    if ($business) {
        return response()->json(['found' => true, 'business_id' => $business->id, 'name' => $business->name]);
    } else {
        return response()->json(['found' => false, 'total_businesses' => \App\Models\Affiliate\AffiliateBusiness::count()]);
    }
});

// Sitemap route (must be before fallback)
Route::get('/sitemap.xml', function () {
    $sitemapPath = public_path('sitemap.xml');
    if (!file_exists($sitemapPath)) {
        abort(404);
    }
    return response()->file($sitemapPath, ['Content-Type' => 'application/xml']);
})->name('sitemap');

// Serve individual sitemap files from sitemaps directory
Route::get('/sitemaps/{filename}', function ($filename) {
    $sitemapPath = public_path('sitemaps/' . $filename);
    if (!file_exists($sitemapPath)) {
        abort(404);
    }
    return response()->file($sitemapPath, ['Content-Type' => 'application/xml']);
})->where('filename', '.*\.xml$')->name('sitemaps.file');

// 410/301 redirects are handled by HandleSeoRedirects middleware + seo_redirects DB table.
// Run: php artisan seo:seed-legacy-redirects (one time after migration)

Route::fallback(function () {
    return inertia('Error', ['status' => 404]);
});
