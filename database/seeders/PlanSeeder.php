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
                'features' => json_encode([
                    'Limited Features',
                    'Basic Support',
                    'Trial for Premium Features',
                    'Community Access',
                    'No Commitment'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'plan_type' => 'Exclusive plan',
                'plan_value' => 100,
                'features' => json_encode([
                    'Premium Features',
                    'Custom Integrations',
                    'Personalised Onboarding',
                    'Dedicated Account Manager',
                    'Higher API Limits'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}