<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('plans')->insert([
            [
                'plan_type' => 'Free plan',
                'plan_value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'plan_type' => 'Exclusive plan',
                'plan_value' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}