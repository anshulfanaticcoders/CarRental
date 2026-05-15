<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Refuse to add the unique index while duplicates already exist — those
        // must be reconciled manually before this index can be added safely.
        $duplicates = DB::table('bookings')
            ->select('stripe_session_id', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('stripe_session_id')
            ->groupBy('stripe_session_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $list = $duplicates->map(fn ($r) => $r->stripe_session_id.' ('.$r->cnt.' rows)')->implode(', ');
            throw new RuntimeException(
                'Cannot add unique index: duplicate stripe_session_id rows exist. Reconcile first: '.$list
            );
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->unique('stripe_session_id', 'bookings_stripe_session_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique('bookings_stripe_session_id_unique');
        });
    }
};
