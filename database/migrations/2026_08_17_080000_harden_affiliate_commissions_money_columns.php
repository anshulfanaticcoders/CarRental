<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Money-correctness for affiliate commissions:
 * - `currency`: amounts were stored with no currency and later SUMMED across
 *   USD/GBP/EUR bookings into one payout number. Backfilled from the booking.
 * - unique(booking_id): one commission per booking — a webhook replay could
 *   insert a second identical commission row.
 * - customer_id nullable: guest checkouts legitimately have no user id; the
 *   NOT NULL column made every guest QR referral fail silently.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliate_commissions', function (Blueprint $table) {
            $table->string('currency', 3)->default('EUR')->after('commission_amount');
        });

        // Raw DDL — ->change() needs doctrine/dbal, which isn't a direct dependency.
        DB::statement('ALTER TABLE affiliate_commissions MODIFY customer_id BIGINT UNSIGNED NULL');

        DB::statement(
            'UPDATE affiliate_commissions ac
             JOIN bookings b ON b.id = ac.booking_id
             SET ac.currency = COALESCE(b.booking_currency, "EUR")'
        );

        // Guard against pre-existing duplicates before adding the unique index.
        $dupes = DB::table('affiliate_commissions')
            ->select('booking_id', DB::raw('COUNT(*) as c'), DB::raw('MIN(id) as keep_id'))
            ->groupBy('booking_id')
            ->having('c', '>', 1)
            ->get();
        foreach ($dupes as $dupe) {
            DB::table('affiliate_commissions')
                ->where('booking_id', $dupe->booking_id)
                ->where('id', '!=', $dupe->keep_id)
                ->whereNotIn('status', ['paid'])
                ->delete();
        }

        Schema::table('affiliate_commissions', function (Blueprint $table) {
            $table->unique('booking_id', 'affiliate_commissions_booking_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_commissions', function (Blueprint $table) {
            $table->dropUnique('affiliate_commissions_booking_id_unique');
            $table->dropColumn('currency');
        });
    }
};
