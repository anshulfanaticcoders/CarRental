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
        Schema::create('wheelsys_bookings', function (Blueprint $table) {
            $table->id();

            // Wheelsys specific fields
            $table->string('wheelsys_booking_ref')->nullable()->unique(); // IRN from Wheelsys
            $table->string('wheelsys_quote_id')->nullable(); // Quote ID from price-quote endpoint
            $table->string('wheelsys_group_code'); // Vehicle group code (CCAR, ICAR, etc.)
            $table->string('wheelsys_status')->default('REQ'); // REQ, RES, CNC (from API)
            $table->string('wheelsys_ref_no')->nullable(); // Reference number from API

            // Vehicle information
            $table->string('vehicle_group_name'); // e.g., "Nissan Versa or Similar"
            $table->string('vehicle_category'); // Compact, Midsize, Fullsize, etc.
            $table->string('vehicle_sipp_code'); // ACRISS code
            $table->string('vehicle_image_url')->nullable();
            $table->integer('vehicle_passengers')->default(4);
            $table->integer('vehicle_doors')->default(4);
            $table->integer('vehicle_bags')->default(0);
            $table->integer('vehicle_suitcases')->default(0);

            // Rental details
            $table->string('pickup_station_code'); // Station code from API
            $table->string('pickup_station_name');
            $table->date('pickup_date');
            $table->time('pickup_time');
            $table->string('return_station_code');
            $table->string('return_station_name');
            $table->date('return_date');
            $table->time('return_time');
            $table->integer('rental_duration_days');

            // Customer details
            $table->json('customer_details'); // First name, last name, email, phone, etc.
            $table->integer('customer_age');
            $table->string('customer_driver_licence')->nullable();
            $table->string('customer_address')->nullable();

            // Pricing (Wheelsys uses integers, divide by 100 for actual values)
            $table->decimal('base_rate_total', 10, 2); // Vehicle rental cost
            $table->decimal('extras_total', 10, 2)->default(0); // Selected extras cost
            $table->decimal('taxes_total', 10, 2)->default(0); // Taxes and fees
            $table->decimal('grand_total', 10, 2); // Total amount paid
            $table->string('currency', 3)->default('USD');

            // Extras and options (JSON arrays from API)
            $table->json('selected_extras')->nullable(); // Selected extras with codes and rates
            $table->json('available_extras')->nullable(); // All available options for this booking

            // Payment processing
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_payment_status')->default('pending'); // pending, succeeded, failed
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->timestamp('paid_at')->nullable();

            // Booking management
            $table->enum('booking_status', ['pending', 'confirmed', 'paid', 'cancelled', 'completed', 'failed'])->default('pending');
            $table->text('customer_notes')->nullable();
            $table->text('admin_notes')->nullable();

            // API integration data
            $table->json('api_response')->nullable(); // Store raw API response for debugging
            $table->json('api_quote_response')->nullable(); // Store initial quote response
            $table->json('api_reservation_response')->nullable(); // Store new-res response

            // System fields
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('affiliate_code')->nullable();
            $table->json('affiliate_data')->nullable(); // Store affiliate information
            $table->string('session_id')->nullable(); // For guest tracking

            $table->timestamps();

            // Indexes for performance
            $table->index(['wheelsys_booking_ref']);
            $table->index(['user_id']);
            $table->index(['booking_status']);
            $table->index(['pickup_date', 'return_date']);
            $table->index(['wheelsys_group_code']);
            $table->index(['stripe_payment_intent_id']);
            $table->index(['affiliate_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wheelsys_bookings');
    }
};