<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number')->unique();
            $table->foreignId('api_consumer_id')->constrained('api_consumers')->onDelete('cascade');
            $table->unsignedBigInteger('vehicle_id');
            $table->string('vehicle_name');
            $table->string('vehicle_image')->nullable();
            $table->string('driver_first_name');
            $table->string('driver_last_name');
            $table->string('driver_email');
            $table->string('driver_phone', 50);
            $table->unsignedTinyInteger('driver_age');
            $table->string('driver_license_number', 50);
            $table->string('driver_license_country', 2);
            $table->dateTime('pickup_date');
            $table->string('pickup_time', 5);
            $table->dateTime('return_date');
            $table->string('return_time', 5);
            $table->string('pickup_location');
            $table->string('return_location');
            $table->unsignedInteger('total_days');
            $table->decimal('daily_rate', 10, 2);
            $table->decimal('base_price', 10, 2);
            $table->decimal('extras_total', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('flight_number', 20)->nullable();
            $table->text('special_requests')->nullable();
            $table->unsignedBigInteger('insurance_id')->nullable();
            $table->timestamps();

            $table->index(['api_consumer_id', 'status']);
            $table->index('vehicle_id');
            $table->index('driver_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_bookings');
    }
};
