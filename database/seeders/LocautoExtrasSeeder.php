<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocautoExtrasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $extras = [
            [
                'code' => '6',
                'name' => 'Protez.Anti Infortuni / Protect. Against  Injuries',
                'price' => 0,
                'unit' => 'day',
                'max_days' => null
            ],
            [
                'code' => '9',
                'name' => 'Secondo Guidatore / Additional Driver',
                'price' => 0,
                'unit' => 'day',
                'max_days' => null
            ],
            [
                'code' => '78',
                'name' => 'Seggiolino per bambini  / Child seat',
                'price' => 0,
                'unit' => 'day',
                'max_days' => null
            ],
            [
                'code' => '19',
                'name' => 'Navigatore satellitare / GPS',
                'price' => 0,
                'unit' => 'day',
                'max_days' => null
            ],
            [
                'code' => '43',
                'name' => 'Assistenza Stradale / Roadside Plus',
                'price' => 0,
                'unit' => 'day',
                'max_days' => null
            ],
            [
                'code' => '55',
                'name' => 'Catene da neve / Snow chains',
                'price' => 0,
                'unit' => 'day',
                'max_days' => null
            ],
            [
                'code' => '89',
                'name' => 'Bau the way',
                'price' => 0,
                'unit' => 'day',
                'max_days' => null
            ],
            [
                'code' => '137',
                'name' => 'Additional Driver',
                'price' => 0,
                'unit' => 'day',
                'max_days' => null
            ],
            [
                'code' => '138',
                'name' => 'Pool driving (3+ drivers)',
                'price' => 0,
                'unit' => 'day',
                'max_days' => null
            ],
            [
                'code' => '139',
                'name' => 'Guidatore Giovane / Young Driver (19-24)',
                'price' => 0,
                'unit' => 'day',
                'max_days' => null
            ],
            [
                'code' => '136',
                'name' => 'Don\'t Worry',
                'price' => 0,
                'unit' => 'day',
                'max_days' => null
            ],
            [
                'code' => '140',
                'name' => 'Glass and Wheels',
                'price' => 0,
                'unit' => 'day',
                'max_days' => null
            ],
            [
                'code' => '145',
                'name' => 'Body Protection',
                'price' => 0,
                'unit' => 'day',
                'max_days' => null
            ],
            [
                'code' => '146',
                'name' => 'Super Theft Protection',
                'price' => 0,
                'unit' => 'day',
                'max_days' => null
            ],
            [
                'code' => '147',
                'name' => 'Smart Cover',
                'price' => 0,
                'unit' => 'day',
                'max_days' => null
            ]
        ];

        foreach ($extras as $extra) {
            DB::table('locauto_extras')->updateOrInsert(
                ['code' => $extra['code']],
                [
                    'name' => $extra['name'],
                    'price' => $extra['price'],
                    'unit' => $extra['unit'],
                    'max_days' => $extra['max_days'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}