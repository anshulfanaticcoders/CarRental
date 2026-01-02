<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Make vehicle_id nullable for external provider bookings
            $table->unsignedBigInteger('vehicle_id')->nullable()->change();

            // Provider fields for external bookings (GreenMotion, USave, etc.)
            $table->string('provider_source')->nullable()->after('vehicle_id'); // 'greenmotion', 'usave', etc.
            $table->string('provider_vehicle_id')->nullable()->after('provider_source'); // External vehicle ID
            $table->string('provider_booking_ref')->nullable()->after('provider_vehicle_id'); // Provider confirmation number
            $table->string('vehicle_name')->nullable()->after('provider_booking_ref'); // Brand + Model for display
            $table->string('vehicle_image')->nullable()->after('vehicle_name'); // Vehicle image URL

            // Stripe fields for webhook matching
            $table->string('stripe_session_id')->nullable()->after('notes'); // Checkout session ID
            $table->string('stripe_payment_intent_id')->nullable()->after('stripe_session_id'); // Payment intent ID
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'provider_source',
                'provider_vehicle_id',
                'provider_booking_ref',
                'vehicle_name',
                'vehicle_image',
                'stripe_session_id',
                'stripe_payment_intent_id'
            ]);
        });
    }
};
