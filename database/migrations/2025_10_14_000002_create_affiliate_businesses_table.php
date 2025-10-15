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
        Schema::create('affiliate_businesses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('business_registration_number', 100)->unique()->nullable();
            $table->string('tax_id', 50)->unique()->nullable();
            $table->string('name');
            $table->enum('business_type', ['hotel', 'hotel_chain', 'travel_agent', 'partner', 'corporate']);
            $table->unsignedBigInteger('parent_business_id')->nullable();

            // Contact Information
            $table->string('contact_email')->unique();
            $table->string('contact_phone', 50);
            $table->string('website')->nullable();

            // Address Information
            $table->text('legal_address');
            $table->text('billing_address')->nullable();
            $table->string('city', 100);
            $table->string('state', 100)->nullable();
            $table->string('country', 100);
            $table->string('postal_code', 20);

            // Financial Configuration (Business Model Reference)
            $table->string('currency', 3)->default('EUR');

            // Business Configuration
            $table->integer('max_qr_codes_per_month')->default(100);
            $table->json('allowed_discount_types')->nullable();

            // Verification & Status
            $table->enum('verification_status', ['pending', 'verified', 'rejected', 'suspended'])->default('pending');
            $table->string('verification_token')->unique()->nullable();
            $table->json('verification_documents')->nullable();
            $table->text('verification_notes')->nullable();
            $table->enum('status', ['active', 'inactive', 'pending', 'suspended'])->default('pending');

            // Security & Access
            $table->string('dashboard_access_token')->unique();
            $table->timestamp('dashboard_token_expires_at')->nullable();
            $table->timestamp('last_dashboard_access')->nullable();

            // Timestamps & Audit
            $table->timestamps();
            $table->timestamp('verified_at')->nullable();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('parent_business_id')->references('id')->on('affiliate_businesses')->onDelete('set null');

            // Indexes
            $table->index('uuid');
            $table->index('contact_email');
            $table->index('dashboard_access_token');
            $table->index('verification_token');
            $table->index('business_registration_number');
            $table->index('business_type');
            $table->index('status');
            $table->index('parent_business_id');

            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_businesses');
    }
};