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
        Schema::create('affiliate_business_models', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('business_id');

            // Business-Specific Discount Configuration
            $table->enum('discount_type', ['percentage', 'fixed_amount'])->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->decimal('min_booking_amount', 10, 2)->nullable();
            $table->decimal('max_discount_amount', 10, 2)->nullable();

            // Business-Specific Commission Configuration
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->enum('commission_type', ['percentage', 'fixed', 'tiered'])->nullable();
            $table->decimal('payout_threshold', 10, 2)->nullable();

            // Custom Configuration
            $table->integer('max_qr_codes_per_month')->nullable();
            $table->integer('qr_code_validity_days')->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            // Audit
            $table->unsignedBigInteger('configured_by')->nullable();
            $table->timestamp('configured_at')->nullable();

            // Timestamps
            $table->timestamps();

            // Foreign Keys
            $table->foreign('business_id')->references('id')->on('affiliate_businesses')->onDelete('cascade');
            $table->foreign('configured_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('uuid');
            $table->index('business_id');
            $table->index('is_active');

            // Constraints

            // Unique constraint - one business model per business
            $table->unique('business_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_business_models');
    }
};