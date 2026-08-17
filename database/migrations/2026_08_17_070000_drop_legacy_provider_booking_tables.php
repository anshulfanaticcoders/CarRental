<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Pre-gateway, per-provider booking tables. No model, no query, no reference
 * anywhere in the codebase — all provider bookings live in `bookings` since
 * the unified gateway flow.
 *
 * Deployment migrations must not remove tables automatically. This migration
 * records what still exists so removal can be handled as a separately approved,
 * backed-up maintenance operation.
 */
return new class extends Migration
{
    private const LEGACY_TABLES = [
        'greenmotion_bookings',
        'ok_mobility_bookings',
        'adobe_bookings',
        'locauto_bookings',
        'wheelsys_bookings',
    ];

    public function up(): void
    {
        foreach (self::LEGACY_TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $rowCount = DB::table($table)->count();
            Log::notice("Legacy table {$table} left in place for manual review.", [
                'table' => $table,
                'row_count' => $rowCount,
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally irreversible: the original schemas live in the old
        // create migrations if a table ever needs recreating.
    }
};
