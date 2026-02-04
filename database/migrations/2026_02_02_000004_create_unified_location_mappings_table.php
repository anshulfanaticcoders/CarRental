<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unified_location_mappings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('unified_location_id');
            $table->string('provider', 40);
            $table->string('provider_location_id', 120);
            $table->enum('status', ['auto', 'manual', 'blocked'])->default('auto');
            $table->string('match_reason', 120)->nullable();
            $table->timestamp('last_matched_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_location_id'], 'unified_location_mapping_unique');
            $table->index('unified_location_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unified_location_mappings');
    }
};
