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
use App\Http\Controllers\Admin\VendorsReportController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailValidationController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BulkVehicleController;
use App\Http\Controllers\BulkVehicleUploadController;
use App\Http\Controllers\ContactFormController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FrontendPageController;
use App\Http\Controllers\GeocodingController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OkMobilityBookingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Admin\VehicleCategoriesController;
use App\Http\Controllers\VehicleCsvImportController;
use App\Http\Controllers\VehicleImportController;
use App\Http\Controllers\Vendor\BlockingDateController;
use App\Http\Controllers\Vendor\DamageProtectionController;
use App\Http\Controllers\Vendor\PlanController;
use App\Http\Controllers\Vendor\VendorBookingController;
use App\Http\Controllers\Vendor\VendorOverviewController;
use App\Http\Controllers\Vendor\VendorVehicleAddonController;
use App\Http\Controllers\Vendor\VendorVehicleController;
use App\Http\Controllers\Vendor\VendorVehiclePlanController;
use App\Http\Controllers\VendorBulkImageController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Admin\VendorsDashboardController;
use App\Http\Controllers\Admin\PayableSettingController;
use App\Http\Controllers\WheelsysCarController;
use App\Http\Controllers\WheelsysBookingController;
use App\Models\Booking;
use App\Models\Message;
use App\Models\Page;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UserDocumentController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\GreenMotionController;
use App\Http\Controllers\GreenMotionBookingController; // Import the new GreenMotionBookingController
use App\Http\Controllers\OkMobilityController; // Import the OkMobilityController
use App\Http\Controllers\AdobeCarController; // Import the AdobeCarController
use App\Http\Controllers\AdobeBookingController; // Import the AdobeBookingController
use App\Http\Controllers\AdminProfileController; // Import the AdminProfileController
use App\Http\Controllers\Affiliate\AffiliateBusinessController; // Import the AffiliateBusinessController
use App\Http\Controllers\Admin\AffiliateBusinessModelController; // Import the AffiliateBusinessModelController
use App\Http\Controllers\Affiliate\AffiliateQrCodeController; // Import the AffiliateQrCodeController
use Stevebauman\Location\Facades\Location;

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
    Route::resource('booking-addons', VehicleAddonsController::class)->middleware(['auth']);
    Route::resource('popular-places', PopularPlacesController::class)->except(['show']);
    Route::resource('admin/plans', PlansController::class);
    Route::resource('blogs', BlogController::class)->names('admin.blogs');
    Route::get('/admin-dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Activity logs
    Route::get('/activity-logs', [ActivityLogsController::class, 'index'])->name('activity-logs.index');

    // Payments
    Route::get('/admin/payments', [PaymentDashboardController::class, 'index'])->name('admin.payments.index');

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
    Route::put('admin/settings/faq/{faq}', [FaqController::class, 'update'])->name('admin.settings.faq.update');
    Route::delete('admin/settings/faq/{faq}', [FaqController::class, 'destroy'])->name('admin.settings.faq.destroy');

    // Contact Us routes
    Route::get('admin/contact-us', [ContactUsPageController::class, 'index'])->name('admin.contact-us.index');
    Route::get('admin/contact-us/edit', [ContactUsPageController::class, 'edit'])->name('admin.contact-us.edit');
    Route::post('admin/contact-us/update', [ContactUsPageController::class, 'update'])->name('admin.contact-us.update');
    Route::delete('admin/contact-us/delete', [ContactUsPageController::class, 'destroy'])->name('admin.contact-us.delete');

    // Contact form submissions and notifications
    Route::get('/contact-us-mails', [ContactFormController::class, 'fetchSubmissions'])->name('contact.mails');
    Route::get('/notifications/unread', [ContactFormController::class, 'unreadNotifications'])->name('notifications.unread');
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

    // Schema Management Routes
    Route::resource('admin/schemas', SchemaController::class)->names('admin.schemas');

    // Admin Profile Settings
    Route::get('/admin/settings/profile', [AdminProfileController::class, 'index'])->name('admin.settings.profile');
    Route::post('/admin/settings/profile', [AdminProfileController::class, 'update'])->name('admin.settings.profile');

    // Payable Amount Settings
    Route::get('/admin/settings/payable-amount', [PayableSettingController::class, 'index'])->name('admin.settings.payable-amount.index');
    Route::post('/admin/settings/payable-amount', [PayableSettingController::class, 'update'])->name('admin.settings.payable-amount.update');

    // Affiliate Business Model Management
    Route::get('/admin/affiliate/business-model', [AffiliateBusinessModelController::class, 'index'])->name('admin.affiliate.business-model.index');
    Route::get('/admin/affiliate/global-settings', [AffiliateBusinessModelController::class, 'getGlobalSettings'])->name('admin.affiliate.global-settings');
    Route::post('/admin/affiliate/global-settings', [AffiliateBusinessModelController::class, 'updateGlobalSettings'])->name('admin.affiliate.global-settings.update');
    Route::get('/admin/affiliate/businesses', [AffiliateBusinessModelController::class, 'getBusinesses'])->name('admin.affiliate.businesses');
    Route::post('/admin/affiliate/businesses/{businessId}/model', [AffiliateBusinessModelController::class, 'updateBusinessModel'])->name('admin.affiliate.businesses.model.update');
    Route::delete('/admin/affiliate/businesses/{businessId}/model', [AffiliateBusinessModelController::class, 'deleteBusinessModel'])->name('admin.affiliate.businesses.model.delete');
    Route::get('/admin/affiliate/statistics', [AffiliateBusinessModelController::class, 'getBusinessStatistics'])->name('admin.affiliate.statistics');
  Route::get('/admin/affiliate/statistics-data', [AffiliateBusinessModelController::class, 'getBusinessStatisticsData'])->name('admin.affiliate.statistics-data');
    Route::get('/admin/affiliate/businesses/{businessId}/preview', [AffiliateBusinessModelController::class, 'previewBusinessRates'])->name('admin.affiliate.businesses.preview');
    Route::get('/admin/affiliate/businesses/{businessId}', [AffiliateBusinessModelController::class, 'getBusinessDetails'])->name('admin.affiliate.businesses.show');
    Route::put('/admin/affiliate/businesses/{businessId}', [AffiliateBusinessModelController::class, 'updateBusinessDetails'])->name('admin.affiliate.businesses.update');
    Route::put('/admin/affiliate/businesses/{businessId}/status', [AffiliateBusinessModelController::class, 'updateBusinessStatus'])->name('admin.affiliate.businesses.status.update');
    Route::get('/admin/affiliate/businesses/{businessId}/export', [AffiliateBusinessModelController::class, 'exportBusinessData'])->name('admin.affiliate.businesses.export');

    // Business Model Dashboard Routes
    Route::get('/admin/affiliate/business-statistics', [AffiliateBusinessModelController::class, 'businessStatistics'])->name('admin.affiliate.business-statistics');
    Route::get('/admin/affiliate/business-verification', [AffiliateBusinessModelController::class, 'businessVerification'])->name('admin.affiliate.business-verification');
    Route::get('/admin/affiliate/payment-tracking', [AffiliateBusinessModelController::class, 'paymentTracking'])->name('admin.affiliate.payment-tracking');
    Route::get('/admin/affiliate/business-details/{businessId?}', [AffiliateBusinessModelController::class, 'businessDetails'])->name('admin.affiliate.business-details');
    Route::get('/admin/affiliate/commission-management', [AffiliateBusinessModelController::class, 'commissionManagement'])->name('admin.affiliate.commission-management');
    Route::get('/admin/affiliate/qr-analytics', [AffiliateBusinessModelController::class, 'qrAnalytics'])->name('admin.affiliate.qr-analytics');

    // Admin Business Registration Routes
    Route::get('/admin/affiliate/business-register', [AffiliateBusinessModelController::class, 'registerBusiness'])->name('admin.affiliate.business-register');
    Route::post('/admin/affiliate/business-register', [AffiliateBusinessModelController::class, 'storeBusiness'])->name('admin.affiliate.business-register.store');

    // Commission Management API Routes
    Route::get('/admin/affiliate/commissions-data', [AffiliateBusinessModelController::class, 'getCommissionsData'])->name('admin.affiliate.commissions-data');
    Route::get('/admin/affiliate/commission-statistics', [AffiliateBusinessModelController::class, 'getCommissionStatistics'])->name('admin.affiliate.commission-statistics');
    Route::patch('/admin/affiliate/commissions/{commissionId}/status', [AffiliateBusinessModelController::class, 'updateCommissionStatus'])->name('admin.affiliate.commissions.status.update');
    Route::post('/admin/affiliate/commissions/{commissionId}/pay', [AffiliateBusinessModelController::class, 'processCommissionPayment'])->name('admin.affiliate.commissions.pay');
    Route::get('/admin/affiliate/commissions-export', [AffiliateBusinessModelController::class, 'exportCommissions'])->name('admin.affiliate.commissions-export');

    // QR Analytics API Routes
    Route::get('/admin/affiliate/qr-analytics-data', [AffiliateBusinessModelController::class, 'getQrAnalyticsData'])->name('admin.affiliate.qr-analytics-data');
    Route::get('/admin/affiliate/qr-analytics-export', [AffiliateBusinessModelController::class, 'exportQrAnalytics'])->name('admin.affiliate.qr-analytics-export');

    // Business Verification Actions
    Route::post('/admin/affiliate/businesses/{businessId}/verify', [AffiliateBusinessModelController::class, 'verifyBusiness'])->name('admin.affiliate.businesses.verify');
    Route::post('/admin/affiliate/businesses/{businessId}/reject', [AffiliateBusinessModelController::class, 'rejectBusiness'])->name('admin.affiliate.businesses.reject');
    Route::post('/admin/affiliate/businesses/{businessId}/suspend', [AffiliateBusinessModelController::class, 'suspendBusiness'])->name('admin.affiliate.businesses.suspend');
    Route::post('/admin/affiliate/businesses/{businessId}/activate', [AffiliateBusinessModelController::class, 'activateBusiness'])->name('admin.affiliate.businesses.activate');
    Route::post('/admin/affiliate/businesses/bulk-verify', [AffiliateBusinessModelController::class, 'bulkVerifyBusinesses'])->name('admin.affiliate.businesses.bulk-verify');
    Route::post('/admin/affiliate/businesses/bulk-reject', [AffiliateBusinessModelController::class, 'bulkRejectBusinesses'])->name('admin.affiliate.businesses.bulk-reject');
});

// Locale-prefixed routes (for customer and vendor)
Route::group([
    'prefix' => '{locale}',
    'where' => ['locale' => '(en|fr|nl|es|ar)'],
    'middleware' => ['set_locale', 'share.country']
], function () {
    Route::get('/', function () {
        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
        ]);
    })->name('welcome');

    require __DIR__ . '/auth.php';

    // Sitemap Routes
    Route::get('/sitemap_en_blogs.xml', [App\Http\Controllers\SiteMapController::class, 'blogsEn']);
    Route::get('/sitemap_fr_blogs.xml', [App\Http\Controllers\SiteMapController::class, 'blogsFr']);
    Route::get('/sitemap_nl_blogs.xml', [App\Http\Controllers\SiteMapController::class, 'blogsNl']);
    Route::get('/sitemap_es_blogs.xml', [App\Http\Controllers\SiteMapController::class, 'blogsEs']);
    Route::get('/sitemap_ar_blogs.xml', [App\Http\Controllers\SiteMapController::class, 'blogsAr']);

    // Sitemap Routes for Vehicles
    Route::get('/sitemap_en_vehicles.xml', [App\Http\Controllers\SiteMapController::class, 'vehiclesEn']);
    Route::get('/sitemap_fr_vehicles.xml', [App\Http\Controllers\SiteMapController::class, 'vehiclesFr']);
    Route::get('/sitemap_nl_vehicles.xml', [App\Http\Controllers\SiteMapController::class, 'vehiclesNl']);

    // Sitemap Routes for Vehicle Categories
    Route::get('/sitemap_en_categories.xml', [App\Http\Controllers\SiteMapController::class, 'categoriesEn']);
    Route::get('/sitemap_fr_categories.xml', [App\Http\Controllers\SiteMapController::class, 'categoriesFr']);
    Route::get('/sitemap_nl_categories.xml', [App\Http\Controllers\SiteMapController::class, 'categoriesNl']);

    // Sitemap Routes for Popular Places
    Route::get('/sitemap_en_places.xml', [App\Http\Controllers\SiteMapController::class, 'placesEn']);
    Route::get('/sitemap_fr_places.xml', [App\Http\Controllers\SiteMapController::class, 'placesFr']);
    Route::get('/sitemap_nl_places.xml', [App\Http\Controllers\SiteMapController::class, 'placesNl']);

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

        // Notification routes
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/mark-read/{id}', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
        Route::delete('/notifications/clear-all', [NotificationController::class, 'clearAll'])->name('notifications.clear-all');
        Route::get('/notifications/unread', [NotificationController::class, 'getUnreadCount'])->name('notifications.unread');

        Route::get('/profile/completion', [ProfileController::class, 'getProfileCompletion'])->name('profile.completion');
    });

    // Open routes for non-logged-in users
    Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
    Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
    Route::get('/s', [SearchController::class, 'search'])->name('search');
    Route::post('/store-search', [SearchController::class, 'storeSearchData'])->name('search.store');
    Route::get('/search/category/{category_slug?}', [SearchController::class, 'searchByCategory'])->name('search.category');
    Route::get('/api/geocoding/autocomplete', [GeocodingController::class, 'autocomplete']);
    Route::get('/api/geocoding/reverse', [GeocodingController::class, 'reverse']);
    // Route::get('/blog', [BlogController::class, 'showBlogPage'])->name('blog');
    Route::get('/page/{slug}', [PageController::class, 'showPublic'])->name('pages.show');
    Route::get('/faq', [FaqController::class, 'showPublicFaqPage'])->name('faq.show');
    Route::post('/validate-email', [EmailValidationController::class, 'validateEmail'])->name('validate-email');
    Route::post('/validate-contact', [EmailValidationController::class, 'validateContact'])->name('validate-contact');
    Route::get('/contact-us', [ContactUsPageController::class, 'show'])->name('contact-us');
    Route::post('/contact', [ContactFormController::class, 'store'])->name('contact.submit');
    Route::get('/vendor/{vendorProfileId}/reviews', [ReviewController::class, 'vendorAllReviews'])->name('vendor.reviews.all');

    // Wheelsys Single Car Page (Open Route)
    Route::get('/wheelsys-car/{id}', [WheelsysCarController::class, 'show'])->name('wheelsys-car.show');

    // Wheelsys Booking Routes (Open - Accessible by both guests and authenticated users)
    Route::get('/wheelsys-booking/create/{groupCode}', [WheelsysBookingController::class, 'create'])->name('wheelsys.booking.create');
    Route::post('/wheelsys-booking', [WheelsysBookingController::class, 'store'])->name('wheelsys.booking.store');
    Route::get('/wheelsys-booking/payment/{booking}', [WheelsysBookingController::class, 'payment'])->name('wheelsys.booking.payment');
    Route::post('/wheelsys-booking/process-payment/{booking}', [WheelsysBookingController::class, 'processPayment'])->name('wheelsys.booking.process-payment');
    Route::get('/wheelsys-booking/success/{booking}', [WheelsysBookingController::class, 'success'])->name('wheelsys.booking.success');
    Route::get('/wheelsys-booking/{booking}', [WheelsysBookingController::class, 'show'])->name('wheelsys.booking.show');

    // Affiliate Business Routes
    Route::prefix('business')->name('affiliate.business.')->group(function () {
        Route::get('/register', [AffiliateBusinessController::class, 'create'])->name('register');
        Route::post('/register', [AffiliateBusinessController::class, 'store'])->name('store');
        Route::get('/verify/{token}', [AffiliateBusinessController::class, 'verifyEmail'])->name('verify');
        Route::get('/dashboard/{token}', [AffiliateBusinessController::class, 'dashboard'])->name('dashboard');
        Route::post('/dashboard/{token}/refresh', [AffiliateBusinessController::class, 'refreshToken'])->name('refresh.token');
        Route::get('/logout/{token}', [AffiliateBusinessController::class, 'logout'])->name('logout');
        Route::post('/check-email', [AffiliateBusinessController::class, 'checkEmail'])->name('check-email');
        Route::post('/login', [AffiliateBusinessController::class, 'login'])->name('login')->middleware('throttle:5,1');
        Route::post('/refresh-access', [AffiliateBusinessController::class, 'refreshAccess'])->name('refresh-access')->middleware('throttle:3,1');

        // Location Management Routes
        Route::post('/locations/create/{token}', [AffiliateBusinessController::class, 'createLocation'])->name('locations.create');

        // QR Code Management Routes
        Route::get('/qr-codes/{token}', [AffiliateQrCodeController::class, 'index'])->name('qr-codes.index');
        Route::get('/qr-codes/create/{token}', [AffiliateQrCodeController::class, 'create'])->name('qr-codes.create');
        Route::post('/qr-codes/store/{token}', [AffiliateQrCodeController::class, 'store'])->name('qr-codes.store');
        Route::get('/qr-codes/show/{token}/{qrCodeId}', [AffiliateQrCodeController::class, 'show'])->name('qr-codes.show');
        Route::get('/qr-codes/image/{token}/{qrCodeId}', [AffiliateQrCodeController::class, 'viewImage'])->name('qr-codes.image');
        Route::get('/qr-codes/download/{token}/{qrCodeId}', [AffiliateQrCodeController::class, 'download'])->name('qr-codes.download');
        Route::put('/qr-codes/{token}/{qrCodeId}', [AffiliateQrCodeController::class, 'update'])->name('qr-codes.update');
        Route::post('/qr-codes/revoke/{token}/{qrCodeId}', [AffiliateQrCodeController::class, 'revoke'])->name('qr-codes.revoke');
        Route::post('/qr-codes/reactivate/{token}/{qrCodeId}', [AffiliateQrCodeController::class, 'reactivate'])->name('qr-codes.reactivate');
        Route::delete('/qr-codes/{token}/{qrCodeId}', [AffiliateQrCodeController::class, 'destroy'])->name('qr-codes.destroy')->withoutMiddleware(['web']);
        Route::delete('/qr-codes/bulk-delete/{token}', [AffiliateQrCodeController::class, 'bulkDestroy'])->name('qr-codes.bulk-destroy')->withoutMiddleware(['web']);
        Route::get('/qr-codes/analytics/{token}/{qrCodeId}', [AffiliateQrCodeController::class, 'analytics'])->name('qr-codes.analytics');

        // Admin-only routes (will be protected by middleware)
        Route::middleware(['auth', 'role:admin'])->group(function () {
            Route::get('/statistics', [AffiliateBusinessController::class, 'statistics'])->name('statistics');
        });

        // Manual verification route (for testing purposes)
        Route::get('/manual-verify/{token}', [AffiliateBusinessController::class, 'manualVerify'])->name('manual.verify');

        // Debug route to check token status
        Route::get('/debug-token/{token}', [AffiliateBusinessController::class, 'debugToken'])->name('debug.token');
    });

  
    // Show Blogs on Home page
    Route::get('/', [BlogController::class, 'homeBlogs'])->name('welcome');
    // Route::get('/blog/{blog:slug}', [BlogController::class, 'show'])->name('blog.show');
    Route::get('/vehicle/{id}', [VehicleController::class, 'show'])->name('vehicle.show');
    Route::get('/api/footer-places', [PopularPlacesController::class, 'getFooterPlaces']);
    Route::get('/api/footer-categories', [VehicleCategoriesController::class, 'getFooterCategories']);
    Route::get('/api/vehicles/search-locations', [VehicleController::class, 'searchLocations']);

    
    // Public QR Code Tracking Routes (with locale prefix)
    Route::get('/affiliate/track/{trackingData}', [AffiliateQrCodeController::class, 'track'])->name('affiliate.qr.track');
    Route::get('/affiliate/qr/{shortCode}', [AffiliateQrCodeController::class, 'qrLanding'])->name('affiliate.qr.landing');

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
            ->where(function($query) use ($currentCountry) {
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
    Route::get('/test-location', function () {
        $request = request();

        // Test our new GeoLocationService
        $detectedCountry = \App\Services\GeoLocationService::detectCountry($request);

        // Test old Stevebauman Location
        try {
            $oldPosition = \Stevebauman\Location\Facades\Location::get();
            $oldCountry = strtolower($oldPosition->countryCode ?? 'NULL');
        } catch (\Exception $e) {
            $oldCountry = 'ERROR: ' . $e->getMessage();
        }

        return response()->json([
            'real_ip' => \App\Services\GeoLocationService::getUserRealIp($request),
            'new_service_country' => $detectedCountry,
            'old_stevebauman_country' => $oldCountry,
            'session_country' => session('country'),
            'all_session_data' => session()->all(),
            'testing_info' => 'This shows if our new dynamic detection works vs old hardcoded system'
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
        try {
            $position = Location::get();
            if ($position && $position->countryCode) {
                $country = strtolower($position->countryCode);
                return redirect("/{$locale}/{$country}/blog");
            }
        } catch (\Exception $e) {
            \Log::error('Location detection failed in blog redirect: ' . $e->getMessage());
        }
        // Fallback to session country if available
        $country = session('country');
        if ($country) {
            return redirect("/{$locale}/{$country}/blog");
        }
        // If no country detected, show error page or handle appropriately
        return redirect("/{$locale}")->with('error', 'Could not detect your location for blog content');
    });

    Route::get('/blog/{slug}', function ($locale, $slug) {
        try {
            $position = Location::get();
            if ($position && $position->countryCode) {
                $country = strtolower($position->countryCode);
                return redirect("/{$locale}/{$country}/blog/{$slug}");
            }
        } catch (\Exception $e) {
            \Log::error('Location detection failed in blog slug redirect: ' . $e->getMessage());
        }
        // Fallback to session country if available
        $country = session('country');
        if ($country) {
            return redirect("/{$locale}/{$country}/blog/{$slug}");
        }
        // If no country detected, show error page or handle appropriately
        return redirect("/{$locale}")->with('error', 'Could not detect your location for blog content');
    });

    Route::middleware(['redirect.country'])->group(function () {

        Route::get('/{country}/blog', [BlogController::class, 'showBlogPage'])
            ->where('country', '[a-zA-Z]{2}')
            ->name('blog');

        Route::get('/{country}/blog/{blog:slug}', [BlogController::class, 'show'])
            ->where('country', '[a-zA-Z]{2}')
            ->name('blog.show');
        
    });

    // Stripe Routes
    Route::get('/payment', [PaymentController::class, 'index'])->name('payment.index');
    Route::post('/retry-payment', [PaymentController::class, 'retryPayment'])->name('payment.retry');
    Route::post('/payment/charge', [PaymentController::class, 'charge'])->name('payment.charge');
    Route::get('/payment/cancel', [PaymentController::class, 'cancel'])->name('payment.cancel');
    Route::get('/payment/cancel/{booking_id}', [PaymentController::class, 'cancel'])->name('payment.cancel.with.booking');
    Route::get('/booking-success/details', [PaymentController::class, 'success'])->name('booking-success.details');

    // Vendor Routes
    Route::middleware(['auth', 'role:vendor'])->group(function () {
        Route::inertia('vendor-approved', 'Vendor/VendorApproved');
        Route::inertia('vendor-pending', 'Vendor/VendorPending');
        Route::inertia('vendor-rejected', 'Vendor/VendorRejected');
        Route::get('/vendor/documents', [VendorController::class, 'index'])->name('vendor.documents.index');
        Route::post('/vendor/update', [VendorController::class, 'update'])->name('vendor.update');
        Route::get('/vehicle-categories', [VehicleController::class, 'getCategories'])->name('vehicle.categories');

        // Vendor Bookings
        Route::resource('bookings', VendorBookingController::class)->names('bookings');
        Route::post('api/bookings/{booking}/cancel', [VendorBookingController::class, 'cancel'])->name('bookings.cancel');
        Route::get('customer-documents/{customer}', [VendorBookingController::class, 'viewCustomerDocuments'])->name('vendor.customer-documents.index');

        // Vendor Payments
        Route::get('/vendor/payments', [BookingController::class, 'getVendorPaymentHistory'])->name('vendor.payments');

    
        // Vendor Vehicles
        Route::resource('current-vendor-vehicles', VendorVehicleController::class);
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

        // Vendor Plans
        Route::get('/plans', [PlanController::class, 'index'])->name('VendorPlanIndex');
        Route::get('vendor/plans/{id}/edit', [PlanController::class, 'edit'])->name('VendorPlanEdit');
        Route::put('vendor/plans/{id}', [PlanController::class, 'update'])->name('VendorPlanUpdate');
        Route::post('/vendor/plan', [PlanController::class, 'store'])->name('VendorPlanStore');
        Route::delete('vendor/plans/{id}', [PlanController::class, 'destroy'])->name('VendorPlanDestroy');

        // Vehicle Addons
        Route::resource('vendor-vehicle-addons', VendorVehicleAddonController::class);

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

        // Booking confirmation routes
        Route::get('/profile/bookings/cancelled', [BookingController::class, 'getCancelledBookings'])->name('profile.bookings.cancelled');

        // Apply for vendor
        Route::post('/vendor/store', [VendorController::class, 'store'])->name('vendor.store');
        Route::get('/vendor/register', [VendorController::class, 'create'])->name('vendor.register');

        // Booking Routes
        Route::post('/booking/allow-access', [BookingController::class, 'allowAccess'])->name('booking.allow_access');
        Route::post('/booking/cancel', [BookingController::class, 'cancelBooking'])->name('booking.cancel');
        Route::inertia('/booking-unsuccess', 'Booking/Unsuccess');
        Route::get('/booking/{id}', [VehicleController::class, 'booking'])->name('booking.show');
        Route::get('/booking', [BookingController::class, 'create'])->name('booking.create');
        Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');
        Route::get('/customer/bookings', [BookingController::class, 'getCustomerBookingData'])->name('customer.bookings');

        Route::get('/booking-success', function () {
            return Inertia::render('Booking/BookingDetails', [
                'payment_intent' => request('payment_intent'),
                'session_id' => request('session_id')
            ]);
        })->name('booking-success');

        // Booking confirmation routes
        Route::get('/profile/bookings/pending', [BookingController::class, 'getPendingBookings'])->name('profile.bookings.pending');
        Route::get('/profile/bookings/confirmed', [BookingController::class, 'getConfirmedBookings'])->name('profile.bookings.confirmed');
        Route::get('/profile/bookings/completed', [BookingController::class, 'getCompletedBookings'])->name('profile.bookings.completed');
        Route::get('/profile/bookings/green-motion', [GreenMotionBookingController::class, 'getCustomerGreenMotionBookings'])->name('profile.bookings.green-motion');
        Route::get('/profile/bookings/adobe', [AdobeBookingController::class, 'getCustomerAdobeBookings'])->name('profile.bookings.adobe');

        // Favourite vehicles
        Route::post('/vehicles/{vehicle}/favourite', [FavoriteController::class, 'favourite'])->name('vehicles.favourite');
        Route::post('/vehicles/{vehicle}/unfavourite', [FavoriteController::class, 'unfavourite'])->name('vehicles.unfavourite');
        Route::get('/favorites', [FavoriteController::class, 'getFavorites']);
        Route::get('/favorites/status', [FavoriteController::class, 'getFavoriteStatus'])->name('favorites.status');

         // GreenMotion Booking Routes
    Route::post('/green-motion-booking/charge', [GreenMotionBookingController::class, 'processGreenMotionBookingPayment'])->name('greenmotion.booking.charge');
     Route::get('/green-motion-booking-success', [GreenMotionBookingController::class, 'greenMotionBookingSuccess'])->name('greenmotion.booking.success');
        Route::get('/green-motion-booking-cancel', [GreenMotionBookingController::class, 'greenMotionBookingCancel'])->name('greenmotion.booking.cancel');

        // Adobe Booking Routes
        Route::get('/adobe-booking/create/{id}', [AdobeBookingController::class, 'create'])->name('adobe-booking.create');
        Route::post('/adobe-booking/charge', [AdobeBookingController::class, 'processAdobeBookingPayment'])->name('adobe.booking.charge');
        Route::get('/adobe-booking-success', [AdobeBookingController::class, 'adobeBookingSuccess'])->name('adobe.booking.success');
        Route::get('/adobe-booking-cancel', [AdobeBookingController::class, 'adobeBookingCancel'])->name('adobe.booking.cancel');

        // OK Mobility Booking Routes
    Route::post('/ok-mobility-booking/charge', [OkMobilityBookingController::class, 'processBookingPayment'])->name('okmobility.booking.charge');
    Route::get('/ok-mobility-booking-success', [OkMobilityBookingController::class, 'bookingSuccess'])->name('okmobility.booking.success');
    Route::get('/ok-mobility-booking-cancel', [OkMobilityBookingController::class, 'bookingCancel'])->name('okmobility.booking.cancel');
    });

    // Vendor status check for vehicle creation
    Route::middleware(['auth', 'vendor.status'])->group(function () {
        Route::get('/vehicles/create', [VehicleController::class, 'create'])->name('vehicles.create');
        Route::inertia('vehicle-listing', 'Auth/VehicleListing');
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
    // GreenMotion Cars Page
    Route::get('/green-motion-cars', [GreenMotionController::class, 'showGreenMotionCars'])->name('green-motion-cars');

    // OK Mobility Cars Page
    Route::get('/ok-mobility-cars', [OkMobilityController::class, 'showOkMobilityCars'])->name('ok-mobility-cars');

    // GreenMotion Booking Page
    Route::get('/green-motion-booking/{id}/checkout', [GreenMotionController::class, 'showGreenMotionBookingPage'])
        ->where('id', '[0-9]+') // Ensure ID is numeric
        ->name('green-motion-booking.checkout');
    
    // Route::get('/{slug}', [ContactUsPageController::class, 'show'])->name('contact.show');

    // GreenMotion Single Car Page
    Route::get('/green-motion-car/{id}', [GreenMotionController::class, 'showGreenMotionCar'])
        ->name('green-motion-car.show');
    Route::post('/green-motion-car/check-availability', [GreenMotionController::class, 'checkAvailability'])->name('green-motion-car.check-availability');

    // Adobe Car Single Car Page
    Route::get('/adobe-car/{id}', [AdobeCarController::class, 'show'])
        ->name('adobe-car.show');

    // OK Mobility Single Car Page
    Route::get('/ok-mobility-car/{id}', [OkMobilityController::class, 'showOkMobilityCar'])
        ->name('ok-mobility-car.show');

    // OK Mobility Booking Page
    Route::get('/ok-mobility-booking/{id}/checkout', [OkMobilityController::class, 'showOkMobilityBookingPage'])
        ->name('ok-mobility-booking.checkout');

    // OK Mobility Check Availability
    Route::post('/ok-mobility-car/check-availability', [OkMobilityController::class, 'checkAvailability'])->name('ok-mobility-car.check-availability');


}); // End of locale group

// Public QR Code Tracking Routes (outside locale prefix for backward compatibility)
Route::get('/affiliate/track/{trackingData}', [AffiliateQrCodeController::class, 'track'])->name('affiliate.qr.track.legacy');
Route::get('/affiliate/qr/{shortCode}', [AffiliateQrCodeController::class, 'qrLanding'])->name('affiliate.qr.landing.legacy');

    Route::get('/green-motion-vehicles', [GreenMotionController::class, 'getGreenMotionVehicles'])->name('green-motion-vehicles');
    Route::get('/green-motion-countries', [GreenMotionController::class, 'getGreenMotionCountries'])->name('green-motion-countries');
    Route::get('/green-motion-locations', [GreenMotionController::class, 'getGreenMotionLocations'])->name('green-motion-locations');
    Route::get('/green-motion-terms-and-conditions', [GreenMotionController::class, 'getGreenMotionTermsAndConditions'])->name('green-motion-terms-and-conditions');
    Route::get('/green-motion-regions', [GreenMotionController::class, 'getGreenMotionRegions'])->name('green-motion-regions');
    Route::get('/green-motion-service-areas', [GreenMotionController::class, 'getGreenMotionServiceAreas'])->name('green-motion-service-areas');
    Route::post('/green-motion-booking', [GreenMotionController::class, 'makeGreenMotionBooking'])->name('green-motion-booking');

Route::get('/fetch-ok-mobility-stations', function () {
    $okMobilityService = app(\App\Services\OkMobilityService::class);
    $stationsXml = $okMobilityService->getStations();
    return response($stationsXml, 200, ['Content-Type' => 'application/xml']);
});

// Global Sitemap Routes (outside locale prefix)
Route::get('/sitemap.xml', function () {
    $content = '<?xml version="1.0" encoding="UTF-8"?>';
    $content .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

    // Blog sitemaps for all locales
    $locales = ['en', 'fr', 'nl', 'es', 'ar'];
    foreach ($locales as $locale) {
        $content .= '<sitemap>';
        $content .= '<loc>' . url("/sitemap_{$locale}_blogs.xml") . '</loc>';
        $content .= '<lastmod>' . now()->toAtomString() . '</lastmod>';
        $content .= '</sitemap>';
    }

    // Add blog listings sitemap
    $content .= '<sitemap>';
    $content .= '<loc>' . url("/sitemap-blog-listings.xml") . '</loc>';
    $content .= '<lastmod>' . now()->toAtomString() . '</lastmod>';
    $content .= '</sitemap>';

    // Get unique countries from blogs for country-specific sitemaps
    $countries = \App\Models\Blog::where('is_published', true)
        ->whereNotNull('countries')
        ->pluck('countries')
        ->flatten()
        ->unique()
        ->toArray();

    if (empty($countries)) {
        $countries = ['us'];
    }

    // Add country-specific sitemaps
    foreach ($countries as $country) {
        $country = strtolower($country);
        $content .= '<sitemap>';
        $content .= '<loc>' . url("/sitemap-blogs-{$country}.xml") . '</loc>';
        $content .= '<lastmod>' . now()->toAtomString() . '</lastmod>';
        $content .= '</sitemap>';
    }

    $content .= '</sitemapindex>';

    return Response::make($content, 200, [
        'Content-Type' => 'application/xml'
    ]);
});

// Country-specific blog sitemap routes
Route::get('/sitemap-blogs-{country}.xml', [App\Http\Controllers\SiteMapController::class, 'blogsByCountry'])
    ->where('country', '[a-zA-Z]{2}');

// Blog listings sitemap
Route::get('/sitemap-blog-listings.xml', [App\Http\Controllers\SiteMapController::class, 'blogListings']);

// Simple test route to verify routes are working
Route::get('/test-business-debug/{token}', function($token) {
    $business = \App\Models\Affiliate\AffiliateBusiness::where('dashboard_access_token', $token)->first();
    if ($business) {
        return response()->json(['found' => true, 'business_id' => $business->id, 'name' => $business->name]);
    } else {
        return response()->json(['found' => false, 'total_businesses' => \App\Models\Affiliate\AffiliateBusiness::count()]);
    }
});

Route::fallback(function () {
    return inertia('Error', ['status' => 404]);
});
