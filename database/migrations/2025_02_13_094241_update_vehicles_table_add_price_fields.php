<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            
            $table->decimal('price_per_week', 10, 2)->nullable();
            $table->decimal('price_per_month', 10, 2)->nullable();
            $table->string('preferred_price_type')->default('day'); // Options: 'day', 'week', or 'month'
        });
    }

    public function down()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Revert the changes
            $table->decimal('price_per_day', 10, 2);
            $table->dropColumn(['price_per_week', 'price_per_month', 'preferred_price_type']);
        });
    }
};
