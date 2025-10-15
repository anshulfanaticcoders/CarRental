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
        Schema::create('ok_mobility_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('ok_mobility_booking_ref')->unique();
            $table->string('vehicle_id');
            $table->string('location_id');
            $table->string('vehicle_location')->nullable();
            $table->date('start_date');
            $table->string('start_time');
            $table->date('end_date');
            $table->string('end_time');
            $table->integer('age');
            $table->string('rental_code')->nullable();
            $table->json('customer_details');
            $table->json('selected_extras')->nullable();
            $table->decimal('vehicle_total', 10, 2);
            $table->string('currency', 3);
            $table->decimal('grand_total', 10, 2);
            $table->string('payment_handler_ref')->nullable();
            $table->string('quote_id');
            $table->string('payment_type')->default('POA');
            $table->string('dropoff_location_id')->nullable();
            $table->text('remarks')->nullable();
            $table->string('booking_status')->default('pending');
            $table->json('api_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ok_mobility_bookings');
    }
};
