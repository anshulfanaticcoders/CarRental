<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicles', 'body_style')) {
                $table->string('body_style', 50)
                    ->nullable()
                    ->after('fuel');
            }

            if (!Schema::hasColumn('vehicles', 'air_conditioning')) {
                $table->boolean('air_conditioning')
                    ->nullable()
                    ->after('body_style');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'air_conditioning')) {
                $table->dropColumn('air_conditioning');
            }

            if (Schema::hasColumn('vehicles', 'body_style')) {
                $table->dropColumn('body_style');
            }
        });
    }
};
