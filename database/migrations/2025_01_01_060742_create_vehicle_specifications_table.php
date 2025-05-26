<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVehicleSpecificationsTable extends Migration
{
    public function up()
    {
        Schema::create('vehicle_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->string('registration_number');
            $table->string('registration_country');
            $table->date('registration_date');
            $table->integer('gross_vehicle_mass');
            $table->decimal('vehicle_height', 8, 2)->nullable();
            $table->decimal('dealer_cost', 10, 2)->nullable();
            $table->string('phone_number');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vehicle_specifications');
    }
}