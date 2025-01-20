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
        Schema::create('booking_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->string('payment_method'); // e.g., 'Stripe', 'PayPal', 'Razorpay'
            $table->string('transaction_id')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('payment_status')->default('pending'); // e.g., 'pending', 'successful', 'failed'
            $table->timestamp('payment_date')->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_payments');
    }
};
