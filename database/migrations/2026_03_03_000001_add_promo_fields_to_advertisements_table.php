<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            $table->boolean('is_promo')->default(false)->after('is_external');
            $table->decimal('discount_percentage', 5, 2)->default(0)->after('is_promo');
            $table->decimal('promo_markup_rate', 5, 4)->default(0)->after('discount_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            $table->dropColumn(['is_promo', 'discount_percentage', 'promo_markup_rate']);
        });
    }
};
