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
        Schema::create('seo_meta_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seo_meta_id')->constrained('seo_metas')->onDelete('cascade');
            $table->string('locale')->index();
            $table->string('url_slug')->nullable();
            $table->string('seo_title', 60)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->string('keywords')->nullable();
            $table->timestamps();

            $table->unique(['seo_meta_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_meta_translations');
    }
};
