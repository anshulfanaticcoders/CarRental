<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_benefits', function (Blueprint $table) {
            $table->decimal('cancellation_fee_per_day', 10, 2)->nullable()->after('cancellation_available_per_day_date');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_benefits', function (Blueprint $table) {
            $table->dropColumn('cancellation_fee_per_day');
        });
    }
};
