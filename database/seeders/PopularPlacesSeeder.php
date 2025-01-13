<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class popularPlacesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $popularPlaces = [
            [
                "place_name" => "Neyyar Wildlife Sanctuary",
                "city" => "Kallikkad",
                "state" => "Kerala",
                "country" => "India",
                "latitude" => "8.534849",
                "longitude" => "77.150116",
                "image" => "storage/popularPlaces/neyyar-wildlife-sanctuary.jpeg",
            ],
            [
                "place_name" => "Aaliya Bet",
                "city" => "Bhadbhut",
                "state" => "Gujarat",
                "country" => "India",
                "latitude" => "21.676350",
                "longitude" => "72.876266",
                "image" => "storage/popularPlaces/aaliya-bet-kadmat-Island-beach.jpg",
            ],
            [
                "place_name" => "Madh Island",
                "city" => "Ambu Bet, Mumbai",
                "state" => "Maharashtra",
                "country" => "India",
                "latitude" => "19.135754",
                "longitude" => "72.795319",
                "image" => "storage/popularPlaces/madh-island.jpg",
            ],
            [
                "place_name" => "Thirumullavaram Beach",
                "city" => "Thirumullavaram",
                "state" => "Kerala",
                "country" => "India",
                "latitude" => "8.894748",
                "longitude" => "76.553513",
                "image" => "storage/popularPlaces/thirumullavaram-beach.jpg",
            ],
            [
                "place_name" => "Girgaum Chowpatty",
                "city" => "Mumbai",
                "state" => "Maharashtra",
                "country" => "India",
                "latitude" => "18.954269",
                "longitude" => "72.811501",
                "image" => "storage/popularPlaces/girgaum-chowpatty.jpg",
            ],
            [
                "place_name" => "Table-Land Plateau",
                "city" => "Panchgani",
                "state" => "Maharashtra",
                "country" => "India",
                "latitude" => "17.926609",
                "longitude" => "73.807198",
                "image" => "storage/popularPlaces/Panchghani_Hill.jpg",
            ],
            [
                "place_name" => "Ganga Nagar Amausi Bridge",
                "city" => "Lucknow",
                "state" => "Uttar Pradesh",
                "country" => "India",
                "latitude" => "26.759199",
                "longitude" => "80.858360",
                "image" => "storage/popularPlaces/ganganagar-bridge.jpg",
            ],
            [
                "place_name" => "Elephanta Caves",
                "city" => "Mumbai",
                "state" => "Maharashtra",
                "country" => "India",
                "latitude" => "18.963253",
                "longitude" => "72.931442",
                "image" => "storage/popularPlaces/elephant-caves.jpeg",
            ],
            [
                "place_name" => "Red Carpet Wax Museum",
                "city" => "Mumbai",
                "state" => "Maharashtra",
                "country" => "India",
                "latitude" => "19.099356",
                "longitude" => "72.916344",
               "image" => "storage/popularPlaces/red-carpet-wax-museum.jpg",
            ],
        ];

        foreach ($popularPlaces as $place) {
            DB::table('popular_places')->insert($place);
        }
    }
}
