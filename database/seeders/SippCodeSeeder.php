<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SippCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sippCodes = [
            [
                'code' => 'MBMR',
                'description' => 'Mini Car (Group A) - 2 doors, manual transmission',
                'category' => 'Mini',
                'doors' => 2,
                'transmission' => 'Manual',
                'fuel_ac' => 'Petrol/AC',
                'typical_vehicle' => 'Fiat 500, Toyota Aygo, Kia Picanto'
            ],
            [
                'code' => 'ECAR',
                'description' => 'Economy Car (Group B) - 2/4 doors, manual transmission',
                'category' => 'Economy',
                'doors' => '2/4',
                'transmission' => 'Manual',
                'fuel_ac' => 'Petrol/AC',
                'typical_vehicle' => 'Fiat Panda, Volkswagen Up, Renault Twingo'
            ],
            [
                'code' => 'CCAR',
                'description' => 'Compact Car (Group C) - 2/4 doors, manual transmission',
                'category' => 'Compact',
                'doors' => '2/4',
                'transmission' => 'Manual',
                'fuel_ac' => 'Petrol/AC',
                'typical_vehicle' => 'Ford Focus, Opel Astra, Volkswagen Golf'
            ],
            [
                'code' => 'CDAR',
                'description' => 'Compact Car (Group D) - 4/5 doors, manual transmission',
                'category' => 'Compact',
                'doors' => '4/5',
                'transmission' => 'Manual',
                'fuel_ac' => 'Diesel/AC',
                'typical_vehicle' => 'Skoda Octavia, Peugeot 308, Toyota Corolla'
            ],
            [
                'code' => 'EDMR',
                'description' => 'Economy Car (Group E) - 2/4 doors, manual transmission',
                'category' => 'Economy',
                'doors' => '2/4',
                'transmission' => 'Manual',
                'fuel_ac' => 'Diesel/AC',
                'typical_vehicle' => 'Fiat Tipo, Seat Ibiza, Hyundai i20'
            ],
            [
                'code' => 'FDAR',
                'description' => 'Full Size Car (Group F) - 4/5 doors, automatic transmission',
                'category' => 'Full Size',
                'doors' => '4/5',
                'transmission' => 'Automatic',
                'fuel_ac' => 'Petrol/AC',
                'typical_vehicle' => 'Volkswagen Passat, Ford Mondeo, Opel Insignia'
            ],
            [
                'code' => 'IFAR',
                'description' => 'Intermediate SUV (Group I) - SUV, automatic transmission',
                'category' => 'SUV',
                'doors' => '5',
                'transmission' => 'Automatic',
                'fuel_ac' => 'Petrol/AC',
                'typical_vehicle' => 'Nissan Qashqai, Peugeot 3008, Hyundai Tucson'
            ],
            [
                'code' => 'IWAV',
                'description' => 'Intermediate Wagon (Group I) - Estate, manual transmission',
                'category' => 'Wagon/EST',
                'doors' => '5',
                'transmission' => 'Manual',
                'fuel_ac' => 'Diesel/AC',
                'typical_vehicle' => 'Skoda Octavia Combi, Ford Focus Wagon, VW Golf Variant'
            ],
            [
                'code' => 'IVAR',
                'description' => 'Intermediate Van (Group I) - Van, manual transmission',
                'category' => 'Van',
                'doors' => '3/5',
                'transmission' => 'Manual',
                'fuel_ac' => 'Diesel/AC',
                'typical_vehicle' => 'Ford Transit Custom, VW Transporter, Renault Trafic'
            ],
            [
                'code' => 'LDAR',
                'description' => 'Luxury Car (Group L) - 4 doors, automatic transmission',
                'category' => 'Luxury',
                'doors' => '4',
                'transmission' => 'Automatic',
                'fuel_ac' => 'Petrol/AC',
                'typical_vehicle' => 'Mercedes E-Class, BMW 5 Series, Audi A6'
            ],
            [
                'code' => 'LCAR',
                'description' => 'Luxury Car (Group L) - 4 doors, automatic transmission',
                'category' => 'Luxury',
                'doors' => '4',
                'transmission' => 'Automatic',
                'fuel_ac' => 'Diesel/AC',
                'typical_vehicle' => 'Mercedes S-Class, BMW 7 Series, Audi A8'
            ],
            [
                'code' => 'MDAR',
                'description' => 'Midsize Car (Group M) - 4 doors, manual transmission',
                'category' => 'Midsize',
                'doors' => '4',
                'transmission' => 'Manual',
                'fuel_ac' => 'Petrol/AC',
                'typical_vehicle' => 'Toyota Camry, Honda Accord, Mazda 6'
            ],
            [
                'code' => 'MFAR',
                'description' => 'Midsize SUV (Group M) - SUV, manual transmission',
                'category' => 'SUV',
                'doors' => '5',
                'transmission' => 'Manual',
                'fuel_ac' => 'Diesel/AC',
                'typical_vehicle' => 'Hyundai Santa Fe, Kia Sorento, Mitsubishi Outlander'
            ],
            [
                'code' => 'PCAR',
                'description' => 'Premium Car (Group P) - 4 doors, automatic transmission',
                'category' => 'Premium',
                'doors' => '4',
                'transmission' => 'Automatic',
                'fuel_ac' => 'Petrol/AC',
                'typical_vehicle' => 'Mercedes C-Class, BMW 3 Series, Audi A4'
            ],
            [
                'code' => 'PFAR',
                'description' => 'Premium SUV (Group P) - SUV, automatic transmission',
                'category' => 'SUV',
                'doors' => '5',
                'transmission' => 'Automatic',
                'fuel_ac' => 'Petrol/AC',
                'typical_vehicle' => 'BMW X3, Mercedes GLC, Audi Q5'
            ],
            [
                'code' => 'SFAR',
                'description' => 'Standard SUV (Group S) - SUV, automatic transmission',
                'category' => 'SUV',
                'doors' => '5',
                'transmission' => 'Automatic',
                'fuel_ac' => 'Diesel/AC',
                'typical_vehicle' => 'Toyota RAV4, Nissan X-Trail, Honda CR-V'
            ],
            [
                'code' => 'SMIN',
                'description' => 'Sports Car (Group S) - 2 doors, manual transmission',
                'category' => 'Sports',
                'doors' => 2,
                'transmission' => 'Manual',
                'fuel_ac' => 'Petrol/AC',
                'typical_vehicle' => 'Mazda MX-5, BMW Z4, Audi TT'
            ],
            [
                'code' => 'STAR',
                'description' => 'Standard Car (Group S) - 4/5 doors, automatic transmission',
                'category' => 'Standard',
                'doors' => '4/5',
                'transmission' => 'Automatic',
                'fuel_ac' => 'Petrol/AC',
                'typical_vehicle' => 'Volkswagen Jetta, Toyota Corolla, Honda Civic'
            ],
            [
                'code' => 'SVAR',
                'description' => 'Standard Van (Group S) - Van, manual transmission',
                'category' => 'Van',
                'doors' => '3/5',
                'transmission' => 'Manual',
                'fuel_ac' => 'Diesel/AC',
                'typical_vehicle' => 'Ford Transit, VW Crafter, Mercedes Sprinter'
            ],
            [
                'code' => 'XCAR',
                'description' => 'Special Car (Group X) - Special category',
                'category' => 'Special',
                'doors' => 'Various',
                'transmission' => 'Various',
                'fuel_ac' => 'Various',
                'typical_vehicle' => 'Convertible, Electric, Hybrid'
            ],
            [
                'code' => 'XFAR',
                'description' => 'Special SUV (Group X) - Special SUV category',
                'category' => 'SUV',
                'doors' => 'Various',
                'transmission' => 'Various',
                'fuel_ac' => 'Various',
                'typical_vehicle' => 'Electric SUV, Luxury SUV, 7-Seater SUV'
            ]
        ];

        foreach ($sippCodes as $sipp) {
            DB::table('sipp_codes')->updateOrInsert(
                ['code' => $sipp['code']],
                [
                    'description' => $sipp['description'],
                    'category' => $sipp['category'],
                    'doors' => $sipp['doors'],
                    'transmission' => $sipp['transmission'],
                    'fuel_ac' => $sipp['fuel_ac'],
                    'typical_vehicle' => $sipp['typical_vehicle'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}