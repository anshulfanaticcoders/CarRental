<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('vendor_vehicle_plans', function (Blueprint $table) {
            $table->unsignedBigInteger('plan_id')->after('vehicle_id'); // Add plan_id column
        });
    }

    public function down()
    {
        Schema::table('vendor_vehicle_plans', function (Blueprint $table) {
            $table->dropColumn('plan_id'); // Remove the column if rolled back
        });
    }
};

