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
        Schema::create('vehicle_provider_vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_provider_id', 100);  // Unique ID from provider
            $table->string('source', 20);                 // 'greenmotion', 'usave', 'adobe', 'okmobility'
            $table->string('brand', 100)->nullable();
            $table->string('model', 255)->nullable();
            $table->string('category', 50)->nullable();
            $table->string('group_name', 100)->nullable();
            $table->string('sipp_code', 20)->nullable();

            // Images
            $table->text('image_url')->nullable();
            $table->text('small_image_url')->nullable();
            $table->text('large_image_url')->nullable();

            // Vehicle Specifications
            $table->integer('adults')->default(0);
            $table->integer('children')->default(0);
            $table->integer('seating_capacity')->nullable();
            $table->integer('luggage_small')->default(0);
            $table->integer('luggage_medium')->default(0);
            $table->integer('luggage_large')->default(0);
            $table->integer('number_of_doors')->nullable();
            $table->string('transmission', 50)->nullable();
            $table->string('fuel_type', 50)->nullable();
            $table->string('mileage', 50)->nullable();
            $table->decimal('mpg', 10, 2)->nullable();
            $table->string('co2_emissions', 50)->nullable();
            $table->string('car_or_van', 20)->nullable();
            $table->boolean('air_conditioning')->default(false);

            // Pickup Location Information
            $table->string('provider_pickup_id', 50)->nullable();
            $table->string('provider_location_id', 50)->nullable();
            $table->decimal('pickup_latitude', 10, 8)->nullable();
            $table->decimal('pickup_longitude', 11, 8)->nullable();
            $table->string('pickup_full_address', 500)->nullable();
            $table->string('pickup_location_name', 255)->nullable();

            // Dropoff Location Information (NEW - CRITICAL!)
            $table->string('provider_dropoff_id', 50)->nullable();  // GreenMotion dropoff_location_id
            $table->decimal('dropoff_latitude', 10, 8)->nullable();
            $table->decimal('dropoff_longitude', 11, 8)->nullable();
            $table->string('dropoff_full_address', 500)->nullable();
            $table->string('dropoff_location_name', 255)->nullable();

            // Off-Location Service (NEW - GreenMotion delivery/collection)
            $table->boolean('offlocation_service_available')->default(false);
            $table->decimal('offlocation_delivery_km', 8, 2)->nullable();
            $table->decimal('offlocation_collection_km', 8, 2)->nullable();
            $table->decimal('offlocation_delivery_lat', 10, 8)->nullable();
            $table->decimal('offlocation_delivery_lng', 11, 8)->nullable();
            $table->decimal('offlocation_collection_lat', 10, 8)->nullable();
            $table->decimal('offlocation_collection_lng', 11, 8)->nullable();

            // Pricing (multi-currency support)
            $table->decimal('price_per_day', 10, 2)->nullable();
            $table->decimal('price_per_week', 10, 2)->nullable();
            $table->decimal('price_per_month', 10, 2)->nullable();
            $table->decimal('original_price', 10, 2)->nullable();
            $table->string('currency', 3)->notNull();
            $table->integer('tax_rate')->default(0);

            // Provider Specific Fields
            // GreenMotion/U-Save
            $table->string('greenmotion_id', 50)->nullable();
            $table->string('usave_vehicle_id', 50)->nullable();
            $table->text('payment_url')->nullable();
            $table->text('loyalty_info')->nullable();
            $table->boolean('drive_and_go')->default(false);

            // Adobe Specific
            $table->decimal('adobe_pli', 10, 2)->nullable();        // Liability Protection
            $table->decimal('adobe_ldw', 10, 2)->nullable();        // Loss Damage Waiver
            $table->decimal('adobe_spp', 10, 2)->nullable();        // Super Protection Plan
            $table->decimal('adobe_tdr', 10, 2)->nullable();        // Theft Damage Reduction
            $table->decimal('adobe_dro', 10, 2)->nullable();        // Driver Responsibility Option

            // OK Mobility Specific
            $table->string('okmobility_token', 100)->nullable();
            $table->string('okmobility_group_id', 50)->nullable();
            $table->string('okmobility_station_id', 50)->nullable();
            $table->string('okmobility_rate_code', 50)->nullable();
            $table->integer('okmobility_included_kms')->nullable();
            $table->integer('okmobility_minimum_days')->nullable();
            $table->integer('okmobility_maximum_days')->nullable();

            // Flexible Data Storage
            $table->json('benefits')->nullable();
            $table->json('protections')->nullable();
            $table->json('extras')->nullable();
            $table->json('specifications')->nullable();
            $table->json('metadata')->nullable();

            // Status and Timestamps
            $table->boolean('availability_status')->default(true);
            $table->boolean('featured')->default(false);
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            $table->unique(['vehicle_provider_id', 'source'], 'unique_vehicle');
            $table->index('source');
            $table->index(['pickup_latitude', 'pickup_longitude']);
            $table->index(['dropoff_latitude', 'dropoff_longitude']);
            $table->index(['provider_pickup_id', 'source']);
            $table->index(['provider_dropoff_id', 'source']);
            $table->index('availability_status');
            $table->index('last_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_provider_vehicles');
    }
};