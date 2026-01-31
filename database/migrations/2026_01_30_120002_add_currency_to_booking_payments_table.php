<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('booking_payments')) {
            return;
        }

        Schema::table('booking_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('booking_payments', 'currency')) {
                $table->char('currency', 3)->nullable()->after('amount');
                $table->index('currency');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('booking_payments')) {
            return;
        }

        Schema::table('booking_payments', function (Blueprint $table) {
            if (Schema::hasColumn('booking_payments', 'currency')) {
                $table->dropIndex(['currency']);
                $table->dropColumn('currency');
            }
        });
    }
};
