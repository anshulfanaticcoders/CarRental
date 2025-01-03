<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVehicleFeaturesTable extends Migration
{
    public function up()
    {
        Schema::create('vehicle_features', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Feature name
            $table->timestamps();
        });

        // Insert predefined features
        DB::table('vehicle_features')->insert([
            ['name' => 'Bluetooth'],
            ['name' => 'Music System'],
            ['name' => 'Toolkit'],
            ['name' => 'USB Charger'],
            ['name' => 'Key Lock'],
            ['name' => 'Back Camera'],
            ['name' => 'Voice Control'],
            ['name' => 'Navigation'],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('vehicle_features');
    }
} 