<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBlockingDatesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('blocking_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->date('blocking_start_date');
            $table->date('blocking_end_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('blocking_dates');
    }
}
