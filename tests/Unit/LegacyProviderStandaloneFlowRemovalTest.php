<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LegacyProviderStandaloneFlowRemovalTest extends TestCase
{
    #[Test]
    public function it_removes_legacy_standalone_provider_booking_and_detail_flows_that_search_results_no_longer_uses(): void
    {
        $this->assertFalse(Route::has('green-motion-cars'));
        $this->assertFalse(Route::has('green-motion-car.show'));
        $this->assertFalse(Route::has('green-motion-car.check-availability'));
        $this->assertFalse(Route::has('green-motion-vehicles'));
        $this->assertFalse(Route::has('green-motion-regions'));
        $this->assertFalse(Route::has('green-motion-service-areas'));

        $this->assertFalse(Route::has('adobe-booking.create'));
        $this->assertFalse(Route::has('adobe.booking.charge'));
        $this->assertFalse(Route::has('adobe.booking.success'));
        $this->assertFalse(Route::has('adobe.booking.cancel'));
        $this->assertFalse(Route::has('adobe-car.show'));
        $this->assertFalse(Route::has('api.adobe.vehicles'));
        $this->assertFalse(Route::has('api.adobe.vehicle-details'));
        $this->assertFalse(Route::has('api.adobe.dropoff-locations'));

        $this->assertFalse(Route::has('ok-mobility-booking.checkout'));
        $this->assertFalse(Route::has('ok-mobility-car.show'));
        $this->assertFalse(Route::has('ok-mobility-car.check-availability'));

        $this->assertFalse(Route::has('renteon-car.show'));

        $this->assertFalse(Route::has('locauto-rent-cars'));
        $this->assertFalse(Route::has('locauto-rent-car.show'));
        $this->assertFalse(Route::has('locauto-rent-booking.checkout'));
        $this->assertFalse(Route::has('locauto-rent-booking'));
        $this->assertFalse(Route::has('locauto-rent-booking-success'));
        $this->assertFalse(Route::has('locauto-rent-booking.charge'));
        $this->assertFalse(Route::has('locauto-rent-booking.success'));
        $this->assertFalse(Route::has('locauto-rent-booking-cancel'));
        $this->assertFalse(Route::has('api.locauto-rent.vehicles'));
        $this->assertFalse(Route::has('locauto-rent-car.check-availability'));

        $this->assertFalse(Route::has('wheelsys-car.show'));
        $this->assertFalse(Route::has('wheelsys.booking.create'));
        $this->assertFalse(Route::has('wheelsys.booking.store'));
        $this->assertFalse(Route::has('wheelsys.booking.payment'));
        $this->assertFalse(Route::has('wheelsys.booking.process-payment'));
        $this->assertFalse(Route::has('wheelsys.booking.success'));
        $this->assertFalse(Route::has('wheelsys.booking.show'));

        $this->assertTrue(Route::has('search'));
        $this->assertTrue(Route::has('api.stripe.checkout'));
        $this->assertTrue(Route::has('booking.success'));
        $this->assertTrue(Route::has('api.provider.dropoff-locations'));
        $this->assertFalse(Route::has('green-motion-locations'));
        $this->assertFalse(Route::has('green-motion-countries'));
        $this->assertFalse(Route::has('green-motion-terms-and-conditions'));

        $this->assertFileDoesNotExist(app_path('Http/Controllers/WheelsysCarController.php'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/WheelsysBookingController.php'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/AdobeController.php'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/AdobeBookingController.php'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/AdobeCarController.php'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/GreenMotionController.php'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/OkMobilityController.php'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/RenteonCarController.php'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/LocautoRentController.php'));

        $this->assertFileDoesNotExist(app_path('Models/WheelsysBooking.php'));
        $this->assertFileDoesNotExist(app_path('Models/AdobeBooking.php'));
        $this->assertFileDoesNotExist(app_path('Models/OkMobilityBooking.php'));
        $this->assertFileDoesNotExist(app_path('Models/LocautoBooking.php'));

        $this->assertFileDoesNotExist(resource_path('js/Components/GreenMotionSearchComponent.vue'));
        $this->assertFileDoesNotExist(resource_path('js/Pages/GreenMotionCars.vue'));
        $this->assertFileDoesNotExist(resource_path('js/Pages/WheelsysCar/Show.vue'));
        $this->assertFileDoesNotExist(resource_path('js/Pages/WheelsysCar/Payment.vue'));
        $this->assertFileDoesNotExist(resource_path('js/Pages/WheelsysCar/BookingSuccess.vue'));
        $this->assertFileDoesNotExist(resource_path('js/Pages/AdobeBooking.vue'));
        $this->assertFileDoesNotExist(resource_path('js/Pages/LocautoRentCars.vue'));
        $this->assertFileDoesNotExist(resource_path('js/Pages/LocautoRentBookingSuccess.vue'));
        $this->assertFileDoesNotExist(resource_path('js/Pages/LocautoRentBookingCancel.vue'));
        $this->assertFileDoesNotExist(resource_path('js/Pages/OkMobilitySuccess.vue'));
        $this->assertFileDoesNotExist(resource_path('js/Pages/OkMobilityCancel.vue'));

        $this->assertFileDoesNotExist(app_path('Services/GreenMotionService.php'));
        $this->assertFileDoesNotExist(app_path('Services/LocautoRentService.php'));
        $this->assertFileDoesNotExist(app_path('Services/OkMobilityService.php'));
        $this->assertFileDoesNotExist(app_path('Services/RenteonService.php'));
        $this->assertFileDoesNotExist(app_path('Services/WheelsysService.php'));
        $this->assertFileDoesNotExist(app_path('Services/SurpriceService.php'));
        $this->assertFileDoesNotExist(app_path('Services/SicilyByCarService.php'));
        $this->assertFileDoesNotExist(app_path('Services/FavricaService.php'));
        $this->assertFileDoesNotExist(app_path('Services/XDriveService.php'));
        $this->assertFileDoesNotExist(app_path('Services/AdobeCarService.php'));
        $this->assertFileDoesNotExist(app_path('Services/RecordGoService.php'));

        $this->assertFileDoesNotExist(app_path('Console/Commands/GreenMotionLocationsUpdateCommand.php'));
        $this->assertFileDoesNotExist(app_path('Console/Commands/UpdateUnifiedLocationsCommand.php'));
        $this->assertFileDoesNotExist(app_path('Console/Commands/LocautoRentTestApiCommand.php'));

        $this->assertFileDoesNotExist(app_path('Services/Locations/ProviderLocationFetchManager.php'));
        $this->assertFileDoesNotExist(app_path('Services/Locations/ProviderLocationFetcherInterface.php'));
        $this->assertFileDoesNotExist(app_path('Services/Locations/Fetchers/AbstractProviderLocationFetcher.php'));
        $this->assertFileDoesNotExist(app_path('Services/Locations/Fetchers/AbstractGreenMotionLocationFetcher.php'));
        $this->assertFileDoesNotExist(app_path('Services/Locations/Fetchers/InternalLocationFetcher.php'));
        $this->assertFileDoesNotExist(app_path('Services/Locations/Fetchers/GreenMotionLocationFetcher.php'));
        $this->assertFileDoesNotExist(app_path('Services/Locations/Fetchers/USaveLocationFetcher.php'));
        $this->assertFileDoesNotExist(app_path('Services/Locations/Fetchers/OkMobilityLocationFetcher.php'));
        $this->assertFileDoesNotExist(app_path('Services/Locations/Fetchers/AdobeLocationFetcher.php'));
        $this->assertFileDoesNotExist(app_path('Services/Locations/Fetchers/LocautoLocationFetcher.php'));
        $this->assertFileDoesNotExist(app_path('Services/Locations/Fetchers/WheelsysLocationFetcher.php'));
        $this->assertFileDoesNotExist(app_path('Services/Locations/Fetchers/RenteonLocationFetcher.php'));
        $this->assertFileDoesNotExist(app_path('Services/Locations/Fetchers/FavricaLocationFetcher.php'));
        $this->assertFileDoesNotExist(app_path('Services/Locations/Fetchers/XDriveLocationFetcher.php'));
        $this->assertFileDoesNotExist(app_path('Services/Locations/Fetchers/SicilyByCarLocationFetcher.php'));
        $this->assertFileDoesNotExist(app_path('Services/Locations/Fetchers/RecordGoLocationFetcher.php'));
        $this->assertFileDoesNotExist(app_path('Services/Locations/Fetchers/SurpriceLocationFetcher.php'));
        $this->assertFileDoesNotExist(app_path('Models/UnifiedLocationMapping.php'));
        $this->assertFileDoesNotExist(resource_path('js/utils/popularPlaceSearch.js'));
        $this->assertFileDoesNotExist(public_path('unified_locations.json'));
    }

    #[Test]
    public function it_removes_static_location_fallback_flags_and_file_based_location_search_artifacts(): void
    {
        $this->assertStringNotContainsString('location_search_enabled', file_get_contents(config_path('vrooem.php')));
        $this->assertStringNotContainsString('unified_locations.json', file_get_contents(app_path('Services/LocationSearchService.php')));
        $this->assertStringNotContainsString('shouldUseGateway', file_get_contents(app_path('Services/LocationSearchService.php')));
    }
}
