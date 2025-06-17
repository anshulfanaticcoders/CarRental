<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Helpers\SchemaBuilder; // Import SchemaBuilder

class FrontendPageController extends Controller
{

    /**
     * Generates and returns the Organization schema.
     * This can be called from AppServiceProvider or a middleware to share globally.
     * @return array
     */
    public static function getOrganizationSchema(): array
    {
        return SchemaBuilder::organization(
            name: 'vrooem',
            url: 'www.vrooem.com',
            logoUrl: 'https://my-public-bucket.4tcl8.upcloudobjects.com/my_media/vroeemlogo-1749185393-oqJNr.png',
            telephone: '+32493000000',
            email: 'info@vrooem.com',
            address: [
                'streetAddress' => 'Nijverheidsstraat 70',
                'addressLocality' => 'Wommelgem',
                'postalCode' => '2160',
                'addressCountry' => 'BE',
            ]
        );
    }
}
