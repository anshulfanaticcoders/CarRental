<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * trabber_clicks was write-only: no unique clickid (refreshes duplicated
 * rows) and no booking_id, so the server-side click record could never be
 * reconciled against the bookings it produced.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trabber_clicks', function (Blueprint $table) {
            // Historical duplicates are preserved. Exactly one existing row per
            // click ID becomes canonical; future writes target this unique key.
            $table->string('canonical_clickid')->nullable()->after('clickid');
            $table->unsignedBigInteger('booking_id')->nullable()->index();
        });

        DB::statement(
            'UPDATE trabber_clicks AS target
             INNER JOIN (
                 SELECT MIN(id) AS canonical_id, clickid
                 FROM trabber_clicks
                 GROUP BY clickid
             ) AS canonical ON canonical.canonical_id = target.id
             SET target.canonical_clickid = canonical.clickid'
        );

        Schema::table('trabber_clicks', function (Blueprint $table) {
            $table->unique('canonical_clickid', 'trabber_clicks_canonical_clickid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('trabber_clicks', function (Blueprint $table) {
            $table->dropUnique('trabber_clicks_canonical_clickid_unique');
            $table->dropColumn(['canonical_clickid', 'booking_id']);
        });
    }
};
