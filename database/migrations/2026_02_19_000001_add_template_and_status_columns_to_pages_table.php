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
        Schema::table('pages', function (Blueprint $table) {
            $table->string('template', 50)->default('default')->after('slug');
            $table->string('custom_slug', 255)->nullable()->after('template');
            $table->enum('status', ['published', 'draft'])->default('draft')->after('custom_slug');
            $table->integer('sort_order')->default(0)->after('status');

            $table->unique('custom_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique(['custom_slug']);

            $table->dropColumn(['template', 'custom_slug', 'status', 'sort_order']);
        });
    }
};
