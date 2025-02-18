<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDiscountFieldsToVehiclesTable extends Migration
{
    public function up()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->decimal('weekly_discount', 5, 2)->nullable()->after('price_per_week');
            $table->decimal('monthly_discount', 5, 2)->nullable()->after('price_per_month');
        });
    }

    public function down()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['weekly_discount', 'monthly_discount']);
        });
    }
}