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
 * Deliberately defensive: a table is dropped ONLY if it exists and is empty.
 * A non-empty table is left in place and logged, never destroyed — nothing a
 * deploy runs unattended should be able to delete data.
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

            if (DB::table($table)->exists()) {
                Log::warning("Legacy table {$table} is NOT empty — left in place, review manually.");

                continue;
            }

            Schema::drop($table);
        }
    }

    public function down(): void
    {
        // Intentionally irreversible: the original schemas live in the old
        // create migrations if a table ever needs recreating.
    }
};
