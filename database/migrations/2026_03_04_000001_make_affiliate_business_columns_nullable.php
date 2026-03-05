<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change business_type from enum to string to accept registration form values
        DB::statement("ALTER TABLE affiliate_businesses MODIFY COLUMN business_type VARCHAR(100) NOT NULL DEFAULT 'other'");

        Schema::table('affiliate_businesses', function (Blueprint $table) {
            $table->string('contact_phone', 50)->nullable()->change();
            $table->text('legal_address')->nullable()->change();
            $table->string('city', 100)->nullable()->change();
            $table->string('country', 100)->nullable()->change();
            $table->string('postal_code', 20)->nullable()->change();
            $table->string('dashboard_access_token')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE affiliate_businesses MODIFY COLUMN business_type ENUM('hotel','hotel_chain','travel_agent','partner','corporate') NOT NULL DEFAULT 'hotel'");

        Schema::table('affiliate_businesses', function (Blueprint $table) {
            $table->string('contact_phone', 50)->nullable(false)->change();
            $table->text('legal_address')->nullable(false)->change();
            $table->string('city', 100)->nullable(false)->change();
            $table->string('country', 100)->nullable(false)->change();
            $table->string('postal_code', 20)->nullable(false)->change();
            $table->string('dashboard_access_token')->nullable(false)->change();
        });
    }
};
