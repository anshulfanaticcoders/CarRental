<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('vendor_vehicle_plans', function (Blueprint $table) {
            $table->dropForeign(['plan_id']); // Drop the foreign key
            $table->dropColumn('plan_id'); // Remove the column (optional)
        });
    }

    public function down()
    {
        Schema::table('vendor_vehicle_plans', function (Blueprint $table) {
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade');
        });
    }
};

