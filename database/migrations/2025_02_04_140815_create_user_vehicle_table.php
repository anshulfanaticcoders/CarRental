<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserVehicleTable extends Migration
{
    public function up()
    {
        Schema::create('user_vehicle', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Foreign key for users
            $table->unsignedBigInteger('vehicle_id'); // Foreign key for vehicles
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('cascade');

            // Ensure a user can't favourite the same vehicle more than once
            $table->unique(['user_id', 'vehicle_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_vehicle');
    }
}
