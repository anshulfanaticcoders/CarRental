<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the new 'contact_us_page' (singular) table.
     */
    public function up(): void
    {
        Schema::create('contact_us_page', function (Blueprint $table) {
            $table->id();
            $table->string('hero_image_url')->nullable();
            $table->json('contact_point_icons')->nullable(); // Stores an array of icon URLs or identifiers
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     * Drops the 'contact_us_page' table.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_us_page');
    }
};
