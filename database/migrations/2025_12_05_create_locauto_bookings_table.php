<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('locauto_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('confirmation_number')->unique();
            $table->string('pickup_location_code');
            $table->string('dropoff_location_code');
            $table->date('pickup_date');
            $table->time('pickup_time');
            $table->date('return_date');
            $table->time('return_time');
            $table->string('vehicle_code');
            $table->json('vehicle_details');
            $table->json('customer_details');
            $table->json('selected_extras')->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->string('status')->default('pending');
            $table->string('payment_type', 10)->default('POA');
            $table->json('api_request')->nullable();
            $table->json('api_response')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();

            $table->index(['pickup_date', 'return_date']);
            $table->index('confirmation_number');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locauto_bookings');
    }
};