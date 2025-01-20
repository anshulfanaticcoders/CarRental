<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehicleCategorySeeder extends Seeder
{
    public function run()
    {
        // Insert categories with the correct fields based on your schema
        DB::table('vehicle_categories')->insert([
            [
                'name' => 'SUV Car',
                'slug' => 'suv-car',
                'description' => 'Sports Utility Vehicle (SUV) with great capacity and comfort.',
                'icon' => 'SuvCarIcon',  // Adjust this to the actual icon if needed
                'status' => 'active',
                'image' => 'path/to/suv-car-image.jpg',  // Adjust if you have an image
            ],
            [
                'name' => 'Luxury',
                'slug' => 'luxury',
                'description' => 'High-end luxury cars with top features.',
                'icon' => 'LuxuryCarIcon',
                'status' => 'active',
                'image' => 'path/to/luxury-car-image.jpg',
            ],
            [
                'name' => 'Sedan Car',
                'slug' => 'sedan-car',
                'description' => 'Comfortable and compact sedan cars for city and highway travel.',
                'icon' => 'SedanCarIcon',
                'status' => 'active',
                'image' => 'path/to/sedan-car-image.jpg',
            ],
            [
                'name' => 'Mini Car',
                'slug' => 'mini-car',
                'description' => 'Small and fuel-efficient cars for quick commutes.',
                'icon' => 'MiniCarIcon',
                'status' => 'active',
                'image' => 'path/to/mini-car-image.jpg',
            ],
        ]);
    }
}
