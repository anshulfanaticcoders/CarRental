<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->enum('fuel_policy', ['full_to_full', 'same_to_same', 'pre_purchase'])->default('full_to_full')->after('fuel');
            $table->text('pickup_instructions')->nullable()->after('terms_policy');
            $table->text('dropoff_instructions')->nullable()->after('pickup_instructions');
            $table->string('location_phone', 50)->nullable()->after('dropoff_instructions');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['fuel_policy', 'pickup_instructions', 'dropoff_instructions', 'location_phone']);
        });
    }
};
