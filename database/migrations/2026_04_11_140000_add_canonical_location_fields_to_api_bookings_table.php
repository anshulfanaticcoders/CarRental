<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('api_bookings', 'pickup_vendor_location_id')) {
                $table->unsignedBigInteger('pickup_vendor_location_id')->nullable()->after('vehicle_id');
                $table->index('pickup_vendor_location_id');
            }

            if (!Schema::hasColumn('api_bookings', 'dropoff_vendor_location_id')) {
                $table->unsignedBigInteger('dropoff_vendor_location_id')->nullable()->after('pickup_vendor_location_id');
                $table->index('dropoff_vendor_location_id');
            }

            if (!Schema::hasColumn('api_bookings', 'provider_metadata')) {
                $table->json('provider_metadata')->nullable()->after('insurance_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('api_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('api_bookings', 'provider_metadata')) {
                $table->dropColumn('provider_metadata');
            }

            if (Schema::hasColumn('api_bookings', 'dropoff_vendor_location_id')) {
                $table->dropIndex(['dropoff_vendor_location_id']);
                $table->dropColumn('dropoff_vendor_location_id');
            }

            if (Schema::hasColumn('api_bookings', 'pickup_vendor_location_id')) {
                $table->dropIndex(['pickup_vendor_location_id']);
                $table->dropColumn('pickup_vendor_location_id');
            }
        });
    }
};
