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
            $table->unsignedBigInteger('booking_id')->nullable()->index();
        });

        $dupes = DB::table('trabber_clicks')
            ->select('clickid', DB::raw('COUNT(*) as c'), DB::raw('MIN(id) as keep_id'))
            ->groupBy('clickid')
            ->having('c', '>', 1)
            ->get();
        foreach ($dupes as $dupe) {
            DB::table('trabber_clicks')
                ->where('clickid', $dupe->clickid)
                ->where('id', '!=', $dupe->keep_id)
                ->delete();
        }

        Schema::table('trabber_clicks', function (Blueprint $table) {
            $table->unique('clickid', 'trabber_clicks_clickid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('trabber_clicks', function (Blueprint $table) {
            $table->dropUnique('trabber_clicks_clickid_unique');
            $table->dropColumn('booking_id');
        });
    }
};
