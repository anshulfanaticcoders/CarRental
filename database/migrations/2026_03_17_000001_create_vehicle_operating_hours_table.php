<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_operating_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('day_of_week'); // 0=Monday ... 6=Sunday
            $table->boolean('is_open')->default(true);
            $table->string('open_time', 5)->nullable();  // HH:MM
            $table->string('close_time', 5)->nullable(); // HH:MM
            $table->timestamps();

            $table->unique(['vehicle_id', 'day_of_week']);
            $table->index('vehicle_id');
        });

        // Seed existing vehicles with all-day availability (00:00-23:59) for all 7 days
        $vehicleIds = DB::table('vehicles')->pluck('id');
        $now = now();
        $rows = [];

        foreach ($vehicleIds as $vehicleId) {
            for ($day = 0; $day <= 6; $day++) {
                $rows[] = [
                    'vehicle_id' => $vehicleId,
                    'day_of_week' => $day,
                    'is_open' => true,
                    'open_time' => '00:00',
                    'close_time' => '23:59',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Insert in batches to avoid memory issues
            if (count($rows) >= 700) {
                DB::table('vehicle_operating_hours')->insert($rows);
                $rows = [];
            }
        }

        if (!empty($rows)) {
            DB::table('vehicle_operating_hours')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_operating_hours');
    }
};
