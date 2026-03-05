<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliate_businesses', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->string('bank_name', 100)->nullable()->after('currency');
            $table->string('bank_iban', 50)->nullable()->after('bank_name');
            $table->string('bank_bic', 20)->nullable()->after('bank_iban');
            $table->string('bank_account_name', 200)->nullable()->after('bank_bic');
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_businesses', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'bank_name', 'bank_iban', 'bank_bic', 'bank_account_name']);
        });
    }
};
