<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliate_businesses', function (Blueprint $table) {
            $table->dropUnique('affiliate_businesses_contact_email_unique');
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_businesses', function (Blueprint $table) {
            $table->unique('contact_email');
        });
    }
};
