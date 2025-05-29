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
        Schema::create('contact_us_page_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_us_page_id')->constrained('contact_us_pages')->onDelete('cascade');
            $table->string('locale')->index();
            $table->string('hero_title')->nullable();
            $table->text('hero_description')->nullable();
            $table->text('intro_text')->nullable();
            $table->json('contact_points')->nullable(); // To store translated titles for contact points
            $table->timestamps();

            $table->unique(['contact_us_page_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_us_page_translations');
    }
};
