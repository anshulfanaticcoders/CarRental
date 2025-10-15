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
        Schema::create('affiliate_global_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Global Discount Configuration
            $table->enum('global_discount_type', ['percentage', 'fixed_amount'])->default('percentage');
            $table->decimal('global_discount_value', 10, 2)->default(0.00);
            $table->decimal('global_min_booking_amount', 10, 2)->nullable();
            $table->decimal('global_max_discount_amount', 10, 2)->nullable();

            // Global Commission Configuration
            $table->decimal('global_commission_rate', 5, 2)->default(0.00);
            $table->enum('global_commission_type', ['percentage', 'fixed', 'tiered'])->default('percentage');
            $table->decimal('global_payout_threshold', 10, 2)->default(100.00);

            // System Configuration
            $table->integer('max_qr_codes_per_business')->default(100);
            $table->integer('qr_code_validity_days')->default(365);
            $table->integer('session_tracking_hours')->default(24);

            // Status & Configuration
            $table->boolean('allow_business_override')->default(true);
            $table->boolean('require_business_verification')->default(true);
            $table->boolean('auto_approve_commissions')->default(true);

            // Audit & Control
            $table->unsignedBigInteger('configured_by');
            $table->timestamp('last_updated_at')->useCurrent()->onUpdateCurrentTimestamp();

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('uuid');
            $table->index('configured_by');

            // Foreign Keys
            $table->foreign('configured_by')->references('id')->on('users')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_global_settings');
    }
};