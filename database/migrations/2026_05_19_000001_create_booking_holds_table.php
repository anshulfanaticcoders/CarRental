<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('booking_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('search_session_id')->nullable()->index();
            $table->string('stripe_session_id')->nullable()->index();
            $table->string('customer_email')->nullable()->index();
            $table->date('pickup_date');
            $table->string('pickup_time', 5);
            $table->date('dropoff_date');
            $table->string('dropoff_time', 5);
            $table->timestamp('expires_at')->index();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();

            $table->index(['vehicle_id', 'status', 'expires_at'], 'booking_holds_vehicle_status_exp_idx');
            $table->index(['vehicle_id', 'pickup_date', 'dropoff_date'], 'booking_holds_vehicle_dates_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_holds');
    }
};
