<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookingAddonsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('booking_addons')->insert([
            [
                'extra_type' => 'child_seat',
                'extra_name' => 'Infant Seat',
                'description' => 'Suitable for infants up to age 1, 0-9kg in weight',
                'quantity' => 0,
                'price' => 150,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'extra_type' => 'booster_seat',
                'extra_name' => 'Booster Seat',
                'description' => 'Suitable for infants up to age 4-7, 15-30kg in weight',
                'quantity' => 0,
                'price' => 200,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'extra_type' => 'additional_driver',
                'extra_name' => 'Additional Driver',
                'description' => 'Add additional drivers to drive the rental car. All drivers need to provide valid documentation.',
                'quantity' => 0,
                'price' => 300,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'extra_type' => 'gps',
                'extra_name' => 'GPS Navigation Device',
                'description' => 'GPS navigation device interface and language are usually in the local language.',
                'quantity' => 0,
                'price' => 200,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}