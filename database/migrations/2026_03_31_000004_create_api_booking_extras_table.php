<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_booking_extras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_booking_id')->constrained('api_bookings')->onDelete('cascade');
            $table->unsignedBigInteger('extra_id');
            $table->string('extra_name');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_booking_extras');
    }
};
