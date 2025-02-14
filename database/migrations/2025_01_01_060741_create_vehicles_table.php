<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVehiclesTable extends Migration
{
    public function up()
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('vehicle_categories')->onDelete('cascade');
            $table->integer('seating_capacity');
            $table->string('brand');
            $table->string('model');
            $table->integer('number_of_doors');
            $table->string('transmission');
            $table->integer('luggage_capacity');
            $table->integer('horsepower');
            $table->string('fuel');
            $table->string('co2');
            $table->string('color');
            $table->integer('mileage');
            $table->string('location');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->enum('status', ['available', 'rented', 'maintenance'])->default('available');
            $table->json('features')->nullable();
            $table->boolean('featured')->default(false);
            $table->decimal('security_deposit', 10, 2);
            $table->string('payment_method');
            $table->decimal('price_per_day', 10, 2);
            $table->text('discounts')->nullable(); 
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vehicles');
    }
}