<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vehicle_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');

            // Limited KM per price type
            $table->boolean('limited_km_per_day')->default(false);
            $table->boolean('limited_km_per_week')->default(false);
            $table->boolean('limited_km_per_month')->default(false);
            $table->integer('limited_km_per_day_range')->nullable();
            $table->integer('limited_km_per_week_range')->nullable();
            $table->integer('limited_km_per_month_range')->nullable();

            // Cancellation availability per price type
            $table->boolean('cancellation_available_per_day')->default(false);
            $table->boolean('cancellation_available_per_week')->default(false);
            $table->boolean('cancellation_available_per_month')->default(false);
            $table->integer('cancellation_available_per_day_date')->nullable();
            $table->integer('cancellation_available_per_week_date')->nullable();
            $table->integer('cancellation_available_per_month_date')->nullable();

            // Price per KM per price type
            $table->decimal('price_per_km_per_day', 10, 2)->nullable();
            $table->decimal('price_per_km_per_week', 10, 2)->nullable();
            $table->decimal('price_per_km_per_month', 10, 2)->nullable();

            // Minimum driver age requirement
            $table->integer('minimum_driver_age')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vehicle_benefits');
    }
};
