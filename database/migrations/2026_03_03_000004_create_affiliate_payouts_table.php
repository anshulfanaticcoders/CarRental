<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_payouts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('business_id');
            $table->foreign('business_id')->references('id')->on('affiliate_businesses')->onDelete('cascade');
            $table->decimal('total_amount', 12, 2);
            $table->string('currency', 10)->default('EUR');
            $table->enum('status', ['pending', 'processing', 'paid', 'failed'])->default('pending');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('bank_transfer_reference', 255)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->foreign('paid_by')->references('id')->on('users')->onDelete('set null');
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_payouts');
    }
};
