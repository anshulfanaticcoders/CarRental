<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('address_line1')->nullable()->default(null)->change();
            $table->string('address_line2')->nullable()->default(null)->change();
            $table->string('city')->nullable()->default(null)->change();
            $table->string('state')->nullable()->default(null)->change();
            $table->string('postal_code')->nullable()->default(null)->change();
            $table->string('title')->nullable()->default(null)->change();
            $table->string('tax_identification')->nullable()->default(null)->change();
            $table->string('languages')->nullable()->default(null)->change();
            $table->text('avatar')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        // No rollback needed - making columns nullable is safe
    }
};
