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
        Schema::table('seo_metas', function (Blueprint $table) {
            $table->nullableMorphs('seoable');

            $table->string('route_name')->nullable()->index();
            $table->json('route_params')->nullable();
            $table->string('route_params_hash')->nullable()->index();

            $table->unique(['seoable_type', 'seoable_id']);
            $table->unique(['route_name', 'route_params_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seo_metas', function (Blueprint $table) {
            $table->dropUnique(['seoable_type', 'seoable_id']);
            $table->dropUnique(['route_name', 'route_params_hash']);

            $table->dropColumn(['route_name', 'route_params', 'route_params_hash']);
            $table->dropMorphs('seoable');
        });
    }
};
