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
        Schema::table('popular_places', function (Blueprint $table) {
            if (!Schema::hasColumn('popular_places', 'unified_location_id')) {
                $table->unsignedBigInteger('unified_location_id')->nullable()->after('longitude');
                $table->index('unified_location_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('popular_places', function (Blueprint $table) {
            if (Schema::hasColumn('popular_places', 'unified_location_id')) {
                $table->dropIndex(['unified_location_id']);
                $table->dropColumn('unified_location_id');
            }
        });
    }
};

