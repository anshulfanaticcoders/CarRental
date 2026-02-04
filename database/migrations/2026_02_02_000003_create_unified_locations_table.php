<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unified_locations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('match_key', 255)->nullable();
            $table->string('name', 255);
            $table->json('aliases')->nullable();
            $table->string('city', 120)->nullable();
            $table->string('country', 120)->nullable();
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->string('location_type', 50)->default('unknown');
            $table->char('iata', 3)->nullable();
            $table->unsignedTinyInteger('confidence')->default(0);
            $table->unsignedTinyInteger('is_manual')->default(0);
            $table->unsignedTinyInteger('is_active')->default(1);
            $table->timestamps();

            $table->unique('match_key');
            $table->index(['country', 'city']);
            $table->index('iata');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unified_locations');
    }
};
