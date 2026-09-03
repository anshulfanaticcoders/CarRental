<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->timestamp('supplier_confirmation_deadline_at')->nullable()->index();
            $table->timestamp('supplier_pending_notified_at')->nullable();
            $table->timestamp('supplier_confirmed_notified_at')->nullable();
            $table->timestamp('supplier_resolution_notified_at')->nullable();
            $table->timestamp('supplier_capture_review_notified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex(['supplier_confirmation_deadline_at']);
            $table->dropColumn([
                'supplier_confirmation_deadline_at',
                'supplier_pending_notified_at',
                'supplier_confirmed_notified_at',
                'supplier_resolution_notified_at',
                'supplier_capture_review_notified_at',
            ]);
        });
    }
};
