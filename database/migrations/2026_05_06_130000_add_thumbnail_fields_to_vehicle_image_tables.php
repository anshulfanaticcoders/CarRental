<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_images', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicle_images', 'thumbnail_path')) {
                $table->string('thumbnail_path')->nullable()->after('image_url');
            }

            if (!Schema::hasColumn('vehicle_images', 'thumbnail_url')) {
                $table->string('thumbnail_url')->nullable()->after('thumbnail_path');
            }
        });

        Schema::table('vendor_bulk_vehicle_images', function (Blueprint $table) {
            if (!Schema::hasColumn('vendor_bulk_vehicle_images', 'thumbnail_path')) {
                $table->string('thumbnail_path')->nullable()->after('image_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_images', function (Blueprint $table) {
            if (Schema::hasColumn('vehicle_images', 'thumbnail_url')) {
                $table->dropColumn('thumbnail_url');
            }

            if (Schema::hasColumn('vehicle_images', 'thumbnail_path')) {
                $table->dropColumn('thumbnail_path');
            }
        });

        Schema::table('vendor_bulk_vehicle_images', function (Blueprint $table) {
            if (Schema::hasColumn('vendor_bulk_vehicle_images', 'thumbnail_path')) {
                $table->dropColumn('thumbnail_path');
            }
        });
    }
};
