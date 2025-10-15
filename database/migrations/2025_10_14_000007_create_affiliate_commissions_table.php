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
        Schema::create('affiliate_commissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('qr_scan_id')->nullable();

            // Financial Details
            $table->decimal('booking_amount', 10, 2);
            $table->decimal('commissionable_amount', 10, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('net_commission', 10, 2);

            // Booking Type
            $table->enum('booking_type', ['platform', 'greenmotion'])->default('platform');

            // Commission Configuration
            $table->enum('commission_type', ['percentage', 'fixed', 'tiered'])->default('percentage');
            $table->json('commission_tier')->nullable();

            // Status & Workflow
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid', 'cancelled', 'disputed'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            // Payment Information
            $table->unsignedBigInteger('payout_id')->nullable();
            $table->date('scheduled_payout_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('transaction_reference')->nullable();

            // Dispute Management
            $table->text('dispute_reason')->nullable();
            $table->timestamp('dispute_resolved_at')->nullable();
            $table->text('dispute_resolution')->nullable();

            // Audit & Compliance
            $table->json('audit_log')->nullable();
            $table->boolean('compliance_checked')->default(false);
            $table->boolean('fraud_review_required')->default(false);

            // Timestamps
            $table->timestamps();

            // Foreign Keys
            $table->foreign('business_id')->references('id')->on('affiliate_businesses')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('affiliate_business_locations')->onDelete('set null');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('qr_scan_id')->references('id')->on('affiliate_customer_scans')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('uuid');
            $table->index('business_id');
            $table->index('location_id');
            $table->index('booking_id');
            $table->index('customer_id');
            $table->index('qr_scan_id');
            $table->index('status');
            $table->index('payout_id');
            $table->index('booking_type');
            $table->index('created_at');

            // Constraints
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_commissions');
    }
};