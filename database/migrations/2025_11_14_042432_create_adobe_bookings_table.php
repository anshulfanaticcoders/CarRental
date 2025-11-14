<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('adobe_bookings', function (Blueprint $table) {
            $table->id();

            // Adobe Booking Reference
            $table->string('adobe_booking_ref')->nullable()->unique(); // Adobe booking number

            // Vehicle Information
            $table->string('vehicle_category'); // Adobe vehicle category (n, w, p, i, d, m, q, a, r, l, b, f, h, j, e, g, o, c, k)
            $table->string('vehicle_model'); // Vehicle model description from Adobe
            $table->string('pickup_location_id'); // Adobe office code (SJO, HFT, LIB, etc.)
            $table->string('dropoff_location_id')->nullable();

            // Rental Dates and Times
            $table->date('start_date');
            $table->time('start_time');
            $table->date('end_date');
            $table->time('end_time');

            // Customer Information
            $table->string('customer_code', 20)->default('Z11338'); // Adobe customer code
            $table->json('customer_details'); // Customer name, email, phone, country, etc.

            // Adobe Pricing Components
            $table->decimal('tdr_total', 10, 2)->default(0); // Time and Distance Rate
            $table->decimal('pli_total', 10, 2)->default(0); // Liability Protection Insurance
            $table->decimal('ldw_total', 10, 2)->default(0); // Loss Damage Waiver
            $table->decimal('spp_total', 10, 2)->default(0); // Super Protection Package
            $table->decimal('dro_total', 10, 2)->default(0); // Drop-off fee
            $table->decimal('extras_total', 10, 2)->default(0); // Additional extras
            $table->decimal('base_rate', 10, 2)->default(0); // Base rental rate

            // Booking Totals
            $table->decimal('vehicle_total', 10, 2); // Total before extras
            $table->decimal('grand_total', 10, 2); // Final total including all charges
            $table->string('currency', 3)->default('USD'); // Adobe uses USD

            // Protection and Extras
            $table->json('selected_protections')->nullable(); // PLI, LDW, SPP selections
            $table->json('selected_extras')->nullable(); // Additional services and extras

            // Payment Information
            $table->string('payment_handler_ref')->nullable(); // Stripe payment reference
            $table->string('payment_status', 20)->default('pending'); // pending, paid, failed, refunded
            $table->string('payment_type', 10)->default('POA'); // Pay on Arrival or PREPAID

            // Booking Management
            $table->string('booking_status', 20)->default('pending'); // pending, confirmed, cancelled, completed
            $table->text('customer_comment')->nullable();
            $table->string('reference')->nullable(); // Customer reference number
            $table->string('flight_number')->nullable();
            $table->string('language', 10)->default('en');

            // API Integration
            $table->json('api_response')->nullable(); // Store Adobe API response
            $table->string('pre_registration_link')->nullable(); // Adobe pre-registration URL

            // System Fields
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('stripe_checkout_session_id')->nullable();
            $table->timestamp('payment_completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['booking_status', 'created_at']);
            $table->index(['pickup_location_id', 'start_date']);
            $table->index(['user_id', 'booking_status']);
            $table->index('adobe_booking_ref');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adobe_bookings');
    }
};
