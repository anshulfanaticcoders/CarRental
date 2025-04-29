<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRadiusesTable extends Migration
{
    public function up()
    {
        Schema::create('radiuses', function (Blueprint $table) {
            $table->id();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->decimal('radius_km', 8, 2); // Radius in kilometers
            $table->timestamps();

            // Create a composite index instead of a unique constraint
            // This gives more flexibility when dealing with null values
            $table->index(['city', 'state', 'country']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('radiuses');
    }
}