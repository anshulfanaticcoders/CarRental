<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed the admin user
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@example.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'phone' => '1234567890', 
                'password' => Hash::make('password123'), 
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'last_login_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
