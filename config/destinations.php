<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Top-destination search defaults
    |--------------------------------------------------------------------------
    |
    | When a customer clicks a homepage "Top Destination", the search is
    | pre-filled with these defaults. Car suppliers rarely have inventory for
    | same-day/next-day pickups, so the default pickup is pushed a couple of
    | weeks out to maximise the chance of returning cars on click.
    */

    'default_lead_days' => (int) env('DESTINATION_DEFAULT_LEAD_DAYS', 14),
    'default_rental_days' => (int) env('DESTINATION_DEFAULT_RENTAL_DAYS', 3),
    'default_pickup_time' => env('DESTINATION_DEFAULT_PICKUP_TIME', '10:00'),
];
