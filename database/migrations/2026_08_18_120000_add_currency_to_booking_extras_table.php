<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_extras', function (Blueprint $table) {
            // Extras are stored in the booking's currency; persist it instead
            // of leaving it implicit. Nullable — historical rows predate this.
            $table->string('currency', 3)->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('booking_extras', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
