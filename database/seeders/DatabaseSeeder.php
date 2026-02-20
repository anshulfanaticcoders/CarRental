<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Add other seeders here as needed
            // LocautoExtrasSeeder::class,
            // SippCodeSeeder::class,
            PrivacyPolicyPageSeeder::class,
            TermsAndConditionsPageSeeder::class,
        ]);
    }
}
