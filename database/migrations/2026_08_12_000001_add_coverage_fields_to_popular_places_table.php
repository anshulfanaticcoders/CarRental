<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('popular_places', function (Blueprint $table) {
            // Live bookable-provider coverage for the bound gateway location.
            // is_active is driven off it so destinations with no cars auto-hide.
            $table->unsignedInteger('provider_count')->default(0)->after('unified_location_id');
            $table->timestamp('last_verified_at')->nullable()->after('provider_count');
            $table->boolean('is_active')->default(true)->after('last_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('popular_places', function (Blueprint $table) {
            $table->dropColumn(['provider_count', 'last_verified_at', 'is_active']);
        });
    }
};
