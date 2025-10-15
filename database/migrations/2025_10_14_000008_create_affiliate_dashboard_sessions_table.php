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
        Schema::create('affiliate_dashboard_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('business_id');
            $table->string('session_token')->unique();

            // Session Information
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->enum('device_type', ['desktop', 'mobile', 'tablet', 'unknown'])->default('unknown');

            // Session Validity
            $table->timestamp('expires_at');
            $table->timestamp('last_accessed_at')->useCurrent();
            $table->boolean('is_active')->default(true);

            // Security
            $table->timestamp('revoked_at')->nullable();
            $table->text('revoke_reason')->nullable();

            // Timestamps
            $table->timestamps();

            // Foreign Keys
            $table->foreign('business_id')->references('id')->on('affiliate_businesses')->onDelete('cascade');

            // Indexes
            $table->index('uuid');
            $table->index('business_id');
            $table->index('session_token');
            $table->index('expires_at');
            $table->index('is_active');

            // Constraints
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_dashboard_sessions');
    }
};