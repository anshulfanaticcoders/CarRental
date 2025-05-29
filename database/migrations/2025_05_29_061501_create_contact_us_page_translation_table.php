<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the new 'contact_us_page_translation' (singular) table.
     */
    public function up(): void
    {
        Schema::create('contact_us_page_translation', function (Blueprint $table) {
            $table->id();
            // Ensure the foreign key references the new singular table name 'contact_us_page'
            $table->foreignId('contact_us_page_id')->constrained('contact_us_page')->onDelete('cascade');
            $table->string('locale')->index();

            // Translatable fields
            $table->string('hero_title')->nullable();
            $table->text('hero_description')->nullable();
            $table->text('intro_text')->nullable();
            // Stores translated textual parts of contact points, 
            // e.g., [{'title': 'Sales', 'detail': 'Contact us for sales inquiries'}, ...]
            $table->json('contact_points')->nullable(); 
            $table->timestamps();

            $table->unique(['contact_us_page_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     * Drops the 'contact_us_page_translation' table.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_us_page_translation');
    }
};
