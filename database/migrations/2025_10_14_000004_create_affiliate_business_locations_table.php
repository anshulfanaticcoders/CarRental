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
        Schema::create('affiliate_business_locations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('business_id');
            $table->string('location_code', 50);

            // Location Details
            $table->string('name');
            $table->text('description')->nullable();

            // Address
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('country');
            $table->string('postal_code', 20);

            // Geographic Coordinates
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->integer('location_accuracy_radius')->default(50);

            // Contact for Location
            $table->string('location_email')->nullable();
            $table->string('location_phone', 50)->nullable();
            $table->string('manager_name')->nullable();

            // Operational Settings
            $table->string('timezone', 50)->default('UTC');
            $table->json('operating_hours')->nullable();

            // QR Code Settings
            $table->string('qr_code_url')->nullable();
            $table->string('qr_code_image_path')->nullable();
            $table->timestamp('qr_code_generated_at')->nullable();

            // Status
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->boolean('is_active')->default(true);

            // Timestamps
            $table->timestamps();
            $table->timestamp('verified_at')->nullable();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('business_id')->references('id')->on('affiliate_businesses')->onDelete('cascade');

            // Indexes
            $table->index('uuid');
            $table->index('business_id');
            $table->index('location_code');
            $table->index(['latitude', 'longitude'], 'aff_loc_coords_index');
            $table->index('is_active');

            // Constraints
            $table->unique(['business_id', 'location_code'], 'aff_loc_unique_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_business_locations');
    }
};