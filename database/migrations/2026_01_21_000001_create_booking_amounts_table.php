<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('booking_amounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');

            $table->string('admin_currency', 3);
            $table->decimal('admin_total_amount', 12, 2)->default(0);
            $table->decimal('admin_paid_amount', 12, 2)->default(0);
            $table->decimal('admin_pending_amount', 12, 2)->default(0);
            $table->decimal('admin_extra_amount', 12, 2)->default(0);

            $table->string('vendor_currency', 3)->nullable();
            $table->decimal('vendor_total_amount', 12, 2)->nullable();
            $table->decimal('vendor_paid_amount', 12, 2)->nullable();
            $table->decimal('vendor_pending_amount', 12, 2)->nullable();
            $table->decimal('vendor_extra_amount', 12, 2)->nullable();

            $table->timestamps();

            $table->unique('booking_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_amounts');
    }
};
