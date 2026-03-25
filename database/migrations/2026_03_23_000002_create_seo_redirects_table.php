<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_url', 768)->unique();
            $table->string('to_url', 768)->nullable(); // null = 410 Gone
            $table->smallInteger('status_code')->default(301); // 301 or 410
            $table->unsignedBigInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->string('note')->nullable(); // e.g. "Blog deleted", "Slug changed"
            $table->timestamps();

            $table->index('status_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_redirects');
    }
};
