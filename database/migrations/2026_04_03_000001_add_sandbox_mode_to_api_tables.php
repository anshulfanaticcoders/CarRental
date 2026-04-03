<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_consumers', function (Blueprint $table) {
            $table->enum('mode', ['sandbox', 'live'])->default('sandbox')->after('status');
        });

        Schema::table('api_bookings', function (Blueprint $table) {
            $table->boolean('is_test')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('api_consumers', function (Blueprint $table) {
            $table->dropColumn('mode');
        });

        Schema::table('api_bookings', function (Blueprint $table) {
            $table->dropColumn('is_test');
        });
    }
};
