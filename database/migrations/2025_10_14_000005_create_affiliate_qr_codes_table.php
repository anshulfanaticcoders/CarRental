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
        Schema::create('affiliate_qr_codes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('location_id')->nullable();

            // QR Code Identity
            $table->string('qr_code_value')->unique();
            $table->string('qr_hash', 64)->unique();
            $table->string('short_code', 20)->unique();
            $table->string('qr_url')->unique();

            // QR Code Files
            $table->string('qr_image_path');
            $table->string('qr_pdf_path')->nullable();

            // Discount Configuration (from business model or global)
            $table->enum('discount_type', ['percentage', 'fixed_amount', 'free_delivery', 'free_upgrade']);
            $table->decimal('discount_value', 10, 2);
            $table->decimal('min_booking_amount', 10, 2)->nullable();
            $table->decimal('max_discount_amount', 10, 2)->nullable();

            // Status
            $table->enum('status', ['active', 'inactive', 'expired', 'suspended', 'pending'])->default('active');

            // Tracking
            $table->integer('total_scans')->default(0);
            $table->integer('unique_scans')->default(0);
            $table->integer('conversion_count')->default(0);
            $table->decimal('total_revenue_generated', 15, 2)->default(0.00);

            // Timestamps
            $table->timestamps();
            $table->timestamp('last_scanned_at')->nullable();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('business_id')->references('id')->on('affiliate_businesses')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('affiliate_business_locations')->onDelete('set null');

            // Indexes
            $table->index('uuid');
            $table->index('business_id');
            $table->index('location_id');
            $table->index('qr_code_value');
            $table->index('qr_hash');
            $table->index('short_code');
            $table->index('qr_url');
            $table->index('status');

            // Constraints
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_qr_codes');
    }
};