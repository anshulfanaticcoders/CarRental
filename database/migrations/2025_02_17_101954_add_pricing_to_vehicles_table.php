<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->decimal('price_per_week', 10, 2)->nullable()->after('price_per_day');
            $table->decimal('price_per_month', 10, 2)->nullable()->after('price_per_week');
            $table->string('preferred_price_type')->default('day')->after('price_per_month');
        });
    }

    public function down()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['price_per_week', 'price_per_month', 'preferred_price_type']);
        });
    }
};