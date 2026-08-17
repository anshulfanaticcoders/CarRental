<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partner API settlement + callbacks:
 * - platform_commission / vendor_net: partner bookings passed through at
 *   100% vendor price with no settlement record — the moment a markup rate
 *   is configured, every booking now records who is owed what.
 * - cancellation_fee: the advertised cancellation policy was never enforced
 *   or recorded; the fee is now computed at cancel time as a settlement line.
 * - webhook_url/secret on consumers: our-side status changes (admin/vendor
 *   cancel, auto-expiry) were invisible to partners until they polled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_bookings', function (Blueprint $table) {
            $table->decimal('platform_commission', 10, 2)->default(0)->after('total_amount');
            $table->decimal('vendor_net', 10, 2)->nullable()->after('platform_commission');
            $table->decimal('cancellation_fee', 10, 2)->nullable()->after('cancellation_reason');
        });

        Schema::table('api_consumers', function (Blueprint $table) {
            $table->string('webhook_url', 500)->nullable();
            $table->string('webhook_secret', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('api_bookings', function (Blueprint $table) {
            $table->dropColumn(['platform_commission', 'vendor_net', 'cancellation_fee']);
        });

        Schema::table('api_consumers', function (Blueprint $table) {
            $table->dropColumn(['webhook_url', 'webhook_secret']);
        });
    }
};
