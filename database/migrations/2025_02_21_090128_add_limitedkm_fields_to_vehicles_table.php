<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->boolean('limited_km')->default(false)->after('preferred_price_type');
            $table->boolean('cancellation_available')->default(false)->after('limited_km');
            $table->decimal('price_per_km', 10, 2)->nullable()->after('cancellation_available');
        });
    }

    public function down()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('limited_km');
            $table->dropColumn('cancellation_available');
            $table->dropColumn('price_per_km');
        });
    }
};
