<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LegacyGreenMotionDirectFlowRemovalTest extends TestCase
{
    #[Test]
    public function it_removes_legacy_green_motion_direct_flow_artifacts_completely(): void
    {
        $this->assertFalse(Route::has('green-motion-booking.checkout'));
        $this->assertFalse(Route::has('green-motion-booking'));
        $this->assertFalse(Route::has('green-motion-cars'));
        $this->assertFalse(Route::has('green-motion-car.show'));
        $this->assertFalse(Route::has('green-motion-car.check-availability'));
        $this->assertFalse(Route::has('green-motion-vehicles'));
        $this->assertFalse(Route::has('green-motion-regions'));
        $this->assertFalse(Route::has('green-motion-service-areas'));
        $this->assertFalse(Route::has('green-motion-locations'));
        $this->assertFalse(Route::has('green-motion-countries'));
        $this->assertFalse(Route::has('green-motion-terms-and-conditions'));

        $this->assertFalse(class_exists(\App\Models\GreenMotionBooking::class, false));
        $this->assertFalse(class_exists(\App\Jobs\SendGreenMotionBookingNotificationJob::class, false));
        $this->assertFalse(class_exists(\App\Notifications\Booking\GreenMotionBookingCreatedAdminNotification::class, false));
        $this->assertFalse(class_exists(\App\Notifications\Booking\GreenMotionBookingCreatedCustomerNotification::class, false));
        $this->assertFalse(class_exists(\App\Notifications\GreenMotionBooking\BookingCreatedAdminNotification::class, false));
        $this->assertFalse(class_exists(\App\Notifications\GreenMotionBooking\BookingCreatedCustomerNotification::class, false));

        $this->assertFileDoesNotExist(app_path('Models/GreenMotionBooking.php'));
        $this->assertFileDoesNotExist(app_path('Jobs/SendGreenMotionBookingNotificationJob.php'));
        $this->assertFileDoesNotExist(app_path('Notifications/Booking/GreenMotionBookingCreatedAdminNotification.php'));
        $this->assertFileDoesNotExist(app_path('Notifications/Booking/GreenMotionBookingCreatedCustomerNotification.php'));
        $this->assertFileDoesNotExist(app_path('Notifications/GreenMotionBooking/BookingCreatedAdminNotification.php'));
        $this->assertFileDoesNotExist(app_path('Notifications/GreenMotionBooking/BookingCreatedCustomerNotification.php'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/GreenMotionController.php'));
        $this->assertFileDoesNotExist(app_path('Services/GreenMotionService.php'));
        $this->assertFileDoesNotExist(resource_path('js/Components/GreenMotionSearchComponent.vue'));
        $this->assertFileDoesNotExist(resource_path('js/Pages/GreenMotionCars.vue'));
        $this->assertFileDoesNotExist(resource_path('js/Pages/GreenMotionBooking.vue'));
    }
}
