<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the hot operational queries that used to scan the bookings
 * table: the payment_failed/refund/dispute webhooks (by payment intent), the
 * 15-minute rescue sweep, the every-minute auto-complete (by return_date),
 * and the database queue's job pop.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->index('stripe_payment_intent_id', 'bookings_stripe_payment_intent_id_index');
            $table->index(['booking_status', 'provider_source', 'payment_status', 'updated_at'], 'bookings_rescue_sweep_index');
            $table->index('return_date', 'bookings_return_date_index');
        });

        Schema::table('jobs', function (Blueprint $table) {
            // The queue pop filters on (queue, reserved_at) FOR UPDATE; the
            // queue-only index has one distinct value in practice.
            $table->index(['queue', 'reserved_at'], 'jobs_queue_reserved_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_stripe_payment_intent_id_index');
            $table->dropIndex('bookings_rescue_sweep_index');
            $table->dropIndex('bookings_return_date_index');
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex('jobs_queue_reserved_at_index');
        });
    }
};
