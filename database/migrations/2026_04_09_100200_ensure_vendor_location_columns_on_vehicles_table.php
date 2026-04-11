<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'vendor_location_id')) {
                $table->foreignId('vendor_location_id')
                    ->nullable()
                    ->after('vendor_id')
                    ->constrained('vendor_locations')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('vehicles', 'sipp_code')) {
                $table->string('sipp_code', 4)
                    ->nullable()
                    ->after('fuel');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'vendor_location_id')) {
                $table->dropConstrainedForeignId('vendor_location_id');
            }

            if (Schema::hasColumn('vehicles', 'sipp_code')) {
                $table->dropColumn('sipp_code');
            }
        });
    }
};
