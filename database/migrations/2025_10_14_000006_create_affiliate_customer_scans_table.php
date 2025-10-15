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
        Schema::create('affiliate_customer_scans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('qr_code_id');
            $table->unsignedBigInteger('customer_id')->nullable();

            // Session & Tracking
            $table->string('session_id');
            $table->string('scan_token');
            $table->string('tracking_url');

            // Device & Session Information
            $table->string('device_id')->nullable();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->enum('device_type', ['desktop', 'mobile', 'tablet', 'unknown'])->default('unknown');
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();

            // Location Detection
            $table->enum('location_detection_method', ['gps', 'ip_geolocation', 'html5', 'manual', 'none'])->default('none');
            $table->decimal('detected_latitude', 10, 8)->nullable();
            $table->decimal('detected_longitude', 11, 8)->nullable();
            $table->integer('detected_accuracy')->nullable();
            $table->json('ip_geolocation')->nullable();
            $table->unsignedBigInteger('matched_location_id')->nullable();
            $table->decimal('location_match_distance', 8, 3)->nullable();

            // Temporal Information
            $table->date('scan_date');
            $table->tinyInteger('scan_hour');
            $table->string('user_timezone')->nullable();

            // Scan Results
            $table->enum('scan_result', ['success', 'expired', 'limit_reached', 'inactive', 'geo_restricted', 'device_limit'])->default('success');
            $table->decimal('discount_applied', 10, 2)->default(0.00);
            $table->decimal('discount_percentage', 5, 2)->default(0.00);

            // Conversion Tracking
            $table->boolean('booking_initiated')->default(false);
            $table->boolean('booking_completed')->default(false);
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->enum('booking_type', ['platform', 'greenmotion'])->nullable();
            $table->integer('conversion_time_minutes')->nullable();

            // Fraud Detection
            $table->integer('fraud_score')->default(0);
            $table->boolean('is_suspicious')->default(false);
            $table->json('fraud_flags')->nullable();

            // Technical Details
            $table->integer('processing_time_ms')->nullable();
            $table->string('server_region')->nullable();

            // Timestamps
            $table->timestamps();

            // Foreign Keys
            $table->foreign('qr_code_id')->references('id')->on('affiliate_qr_codes')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('matched_location_id')->references('id')->on('affiliate_business_locations')->onDelete('set null');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');

            // Indexes
            $table->index('uuid');
            $table->index('qr_code_id');
            $table->index('customer_id');
            $table->index('session_id');
            $table->index('scan_token');
            $table->index('ip_address');
            $table->index('scan_date');
            $table->index('matched_location_id');
            $table->index('scan_result');
            $table->index(['detected_latitude', 'detected_longitude'], 'aff_scan_coords_idx');
            $table->index('booking_id');

            // Constraints
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_customer_scans');
    }
};