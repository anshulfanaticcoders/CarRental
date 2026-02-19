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
        Schema::create('page_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->onDelete('cascade');
            $table->string('locale', 5)->default('en');
            $table->string('meta_key', 100);
            $table->longText('meta_value')->nullable();
            $table->timestamps();

            $table->unique(['page_id', 'locale', 'meta_key']);
            $table->index('meta_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_meta');
    }
};
