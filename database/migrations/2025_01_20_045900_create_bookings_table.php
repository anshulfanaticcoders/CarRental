<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number')->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->dateTime('pickup_date');
            $table->dateTime('return_date');
            $table->string('pickup_location');
            $table->string('return_location');
            $table->string('pickup_time');
            $table->string('return_time');
            $table->string('plan')->nullable();
            $table->integer('plan_price')->nullable();
            $table->integer('total_days');
            $table->decimal('base_price', 10, 2);
            $table->decimal('extra_charges', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('pending_amount', 10, 2)->default(0);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('payment_status')->default('pending');
            $table->string('booking_status')->default('pending');
            $table->text('cancellation_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};