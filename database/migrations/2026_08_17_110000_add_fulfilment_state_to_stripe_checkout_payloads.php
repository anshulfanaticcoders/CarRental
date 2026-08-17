<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stripe_checkout_payloads', function (Blueprint $table) {
            $table->string('payment_status', 30)->default('unpaid')->after('payload');
            $table->string('fulfilment_status', 30)->default('pending')->after('payment_status');
            $table->string('stripe_payment_intent_id')->nullable()->after('fulfilment_status');
            $table->unsignedBigInteger('booking_id')->nullable()->after('stripe_payment_intent_id');
            $table->unsignedInteger('fulfilment_attempts')->default(0)->after('booking_id');
            $table->timestamp('paid_at')->nullable()->after('fulfilment_attempts');
            $table->timestamp('fulfilled_at')->nullable()->after('paid_at');
            $table->timestamp('last_attempt_at')->nullable()->after('fulfilled_at');
            $table->text('last_error')->nullable()->after('last_attempt_at');
        });
    }

    public function down(): void
    {
        Schema::table('stripe_checkout_payloads', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'fulfilment_status',
                'stripe_payment_intent_id',
                'booking_id',
                'fulfilment_attempts',
                'paid_at',
                'fulfilled_at',
                'last_attempt_at',
                'last_error',
            ]);
        });
    }
};
