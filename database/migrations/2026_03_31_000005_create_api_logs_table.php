<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('api_consumer_id');
            $table->unsignedBigInteger('api_key_id');
            $table->string('method', 10);
            $table->string('endpoint', 255);
            $table->json('request_payload')->nullable();
            $table->unsignedSmallInteger('response_status');
            $table->string('ip_address', 45);
            $table->string('user_agent', 500)->nullable();
            $table->unsignedInteger('processing_time_ms');
            $table->timestamp('created_at');

            $table->index(['api_consumer_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
