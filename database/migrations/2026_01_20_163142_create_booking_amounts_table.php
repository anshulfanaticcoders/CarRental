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
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->char('base_currency', 3)->default('EUR');
            $table->char('booking_currency', 3)->nullable();
            $table->char('vendor_currency', 3)->nullable();
            $table->decimal('fx_rate', 12, 6)->nullable();
            $table->decimal('vendor_fx_rate', 12, 6)->nullable();
            $table->decimal('base_price', 12, 2)->default(0);
            $table->decimal('extras_total', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('pending_amount', 12, 2)->default(0);
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
