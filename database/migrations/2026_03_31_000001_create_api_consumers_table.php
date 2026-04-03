<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_consumers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_name');
            $table->string('contact_email')->unique();
            $table->string('contact_phone', 50)->nullable();
            $table->string('company_url')->nullable();
            $table->enum('status', ['active', 'suspended', 'inactive'])->default('active');
            $table->enum('plan', ['basic', 'premium', 'enterprise'])->default('basic');
            $table->unsignedInteger('rate_limit')->default(60);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_consumers');
    }
};
