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
        Schema::create('greenmotion_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('greenmotion_booking_ref')->nullable()->unique();
            $table->string('vehicle_id');
            $table->string('location_id');
            $table->date('start_date');
            $table->time('start_time');
            $table->date('end_date');
            $table->time('end_time');
            $table->integer('age');
            $table->string('rental_code')->nullable(); // BAS, PRE, PLU, PMP
            $table->json('customer_details'); // Store customer info as JSON
            $table->json('selected_extras')->nullable(); // Store selected extras as JSON
            $table->decimal('vehicle_total', 10, 2);
            $table->string('currency', 3);
            $table->decimal('grand_total', 10, 2);
            $table->string('payment_handler_ref')->nullable();
            $table->string('quote_id')->nullable();
            $table->string('payment_type', 10)->default('POA'); // POA or PREPAID
            $table->string('dropoff_location_id')->nullable();
            $table->text('remarks')->nullable();
            $table->string('booking_status')->default('pending'); // e.g., pending, confirmed, failed
            $table->json('api_response')->nullable(); // Store raw API response for debugging
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('greenmotion_bookings');
    }
};
