<?php

namespace App\Services\Vehicles;

use App\Models\Vehicle;
use App\Models\VendorProfile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class LiveFleetEnrichmentService
{
    private array $countryAliases = [
        'maroc' => 'Morocco',
        'morocco' => 'Morocco',
        'espana' => 'Spain',
        'españa' => 'Spain',
        'spain' => 'Spain',
        'united arab emirates' => 'United Arab Emirates',
        'verenigde arabische emiraten' => 'United Arab Emirates',
        'belgium' => 'Belgium',
        'turkey' => 'Turkey',
        'united states' => 'United States',
        'lithuania' => 'Lithuania',
    ];

    private array $airportIataRules = [
        'marrakech menara airport' => 'RAK',
        'menara airport' => 'RAK',
        'orlando international airport' => 'MCO',
        'aeroport tanger ibn battouta' => 'TNG',
        'tangier ibn battouta intl airport' => 'TNG',
        'tangier ibn battouta airport' => 'TNG',
        'mohamed v airport' => 'CMN',
        'casablanca airport' => 'CMN',
        'dubai airport' => 'DXB',
        'antalya havalimani' => 'AYT',
        'antalya airport' => 'AYT',
        '4 oro uosto gatve' => 'KUN',
        'aeropuerto de tenerife sur tfs' => 'TFS',
        'aeropuerto de tenerife sur' => 'TFS',
        'santa cruz de tenerife airport' => 'TFS',
    ];

    private array $modelSpecOverrides = [
        ['needles' => ['gla 250'], 'overrides' => ['body_style' => 'suv', 'seating_capacity' => 5, 'number_of_doors' => 5]],
        ['needles' => [' q8 ', 'audi q8'], 'overrides' => ['body_style' => 'suv', 'seating_capacity' => 5, 'number_of_doors' => 5]],
        ['needles' => ['nissan rogue', ' rogue '], 'overrides' => ['body_style' => 'suv', 'seating_capacity' => 5, 'number_of_doors' => 5]],
        ['needles' => ['pathfinder'], 'overrides' => ['body_style' => 'suv', 'seating_capacity' => 8, 'number_of_doors' => 5]],
        ['needles' => ['captiva'], 'overrides' => ['body_style' => 'suv', 'seating_capacity' => 7, 'number_of_doors' => 5]],
        ['needles' => ['uni k', 'uni-k'], 'overrides' => ['body_style' => 'suv', 'seating_capacity' => 5, 'number_of_doors' => 5]],
        ['needles' => ['berlingo', 'jumpy', 'interstar', 'interstat', 'sprinter l3', 'sprinter'], 'overrides' => ['body_style' => 'van', 'seating_capacity' => 3, 'number_of_doors' => 4]],
        ['needles' => ['mini cooper convertible', 'cooper convertible'], 'overrides' => ['body_style' => 'convertible', 'seating_capacity' => 4, 'number_of_doors' => 2]],
        ['needles' => ['420i convertible', '430i convertible', 'e53 convertible', 'e450 convertible', 'cle convertible'], 'overrides' => ['body_style' => 'convertible', 'seating_capacity' => 4, 'number_of_doors' => 2]],
        ['needles' => ['911 targa'], 'overrides' => ['body_style' => 'convertible', 'seating_capacity' => 4, 'number_of_doors' => 2]],
        ['needles' => ['911 carrera'], 'overrides' => ['body_style' => 'sport', 'seating_capacity' => 4, 'number_of_doors' => 2]],
        ['needles' => ['718 boxster'], 'overrides' => ['body_style' => 'roadster', 'seating_capacity' => 2, 'number_of_doors' => 2]],
        ['needles' => ['cla 2023', ' mercedes benz cla ', ' cla '], 'overrides' => ['body_style' => 'sedan', 'seating_capacity' => 5, 'number_of_doors' => 4]],
        ['needles' => ['corvette'], 'overrides' => ['body_style' => 'sport', 'seating_capacity' => 2, 'number_of_doors' => 2]],
        ['needles' => ['huracan evo spide', 'huracan evo spider'], 'overrides' => ['body_style' => 'roadster', 'seating_capacity' => 2, 'number_of_doors' => 2]],
    ];

    public function __construct(
        private readonly VendorLocationSyncService $vendorLocationSyncService,
        private readonly SippCodeSuggestionService $sippCodeSuggestionService,
    ) {
    }

    public function enrich(Vehicle $vehicle): array
    {
        $categoryName = $vehicle->category?->name;
        $vendorProfile = $vehicle->vendorProfileData;
        $normalizedLocation = $this->normalizedLocationAttributes($vehicle, $vendorProfile);
        $specOverrides = $this->resolvedModelSpecOverrides($vehicle, $categoryName);

        $vendorLocation = null;
        if ($vehicle->vendor_id && $normalizedLocation !== null) {
            $vendorLocation = $this->vendorLocationSyncService->sync(
                (int) $vehicle->vendor_id,
                $normalizedLocation,
                $vehicle->vendor_location_id,
            );
        }

        $bodyStyle = $specOverrides['body_style'] ?? $this->inferBodyStyle($vehicle, $categoryName);
        $seatingCapacity = $specOverrides['seating_capacity'] ?? $vehicle->seating_capacity;
        $numberOfDoors = $specOverrides['number_of_doors'] ?? $vehicle->number_of_doors;
        $airConditioning = $this->inferAirConditioning($vehicle);
        $sippCode = $this->sippCodeSuggestionService->suggest([
            'category_name' => $categoryName,
            'body_style' => $bodyStyle,
            'transmission' => $vehicle->transmission,
            'fuel' => $vehicle->fuel,
            'air_conditioning' => $airConditioning,
            'seating_capacity' => $seatingCapacity,
            'number_of_doors' => $numberOfDoors,
            'horsepower' => $vehicle->horsepower,
            'brand' => $vehicle->brand,
            'model' => $vehicle->model,
        ]) ?? $this->sippCodeSuggestionService->resolve($vehicle->sipp_code, [
            'category_name' => $categoryName,
            'body_style' => $bodyStyle,
            'transmission' => $vehicle->transmission,
            'fuel' => $vehicle->fuel,
            'air_conditioning' => $airConditioning,
            'seating_capacity' => $seatingCapacity,
            'number_of_doors' => $numberOfDoors,
            'horsepower' => $vehicle->horsepower,
            'brand' => $vehicle->brand,
            'model' => $vehicle->model,
        ]);

        $updates = [
            'body_style' => $bodyStyle,
            'seating_capacity' => $seatingCapacity,
            'number_of_doors' => $numberOfDoors,
            'air_conditioning' => $airConditioning,
            'sipp_code' => $sippCode,
        ];

        if ($vendorLocation !== null) {
            $updates = array_merge(
                $updates,
                $this->vendorLocationSyncService->vehicleLocationAttributes($vendorLocation),
                ['vendor_location_id' => $vendorLocation->id],
            );
        }

        $vehicle->fill($updates);

        if ($vehicle->isDirty()) {
            $vehicle->save();
        }

        return [
            'vehicle_id' => $vehicle->id,
            'vendor_location_id' => $vehicle->vendor_location_id,
            'body_style' => $vehicle->body_style,
            'air_conditioning' => $vehicle->air_conditioning,
            'sipp_code' => $vehicle->sipp_code,
            'location_created' => $vendorLocation?->wasRecentlyCreated ?? false,
            'location_linked' => $vendorLocation !== null,
        ];
    }

    private function resolvedModelSpecOverrides(Vehicle $vehicle, ?string $categoryName): array
    {
        $lookup = ' ' . $this->normalizeLookup(implode(' ', array_filter([
            $categoryName,
            $vehicle->brand,
            $vehicle->model,
        ]))) . ' ';

        foreach ($this->modelSpecOverrides as $rule) {
            foreach ($rule['needles'] as $needle) {
                if ($needle !== '' && str_contains($lookup, ' ' . $this->normalizeLookup($needle) . ' ')) {
                    return $rule['overrides'];
                }
            }
        }

        return [];
    }

    private function normalizedLocationAttributes(Vehicle $vehicle, ?VendorProfile $vendorProfile): ?array
    {
        $locationType = $this->normalizeLocationType($vehicle->location_type, $vehicle->location, $vehicle->full_vehicle_address);
        $location = $this->canonicalLocationName($vehicle, $locationType);
        $city = $this->resolvedCity($vehicle, $location, $locationType);
        $country = $this->normalizeCountry($vehicle->country);
        $latitude = $this->normalizeFloat($vehicle->latitude);
        $longitude = $this->normalizeFloat($vehicle->longitude);

        if ($vehicle->vendor_id === null || $location === null || $city === null || $country === null || $latitude === null || $longitude === null) {
            return null;
        }

        $addressLine1 = $this->normalizedAddress($vehicle, $location, $city, $country);

        return [
            'vendor_location_id' => $vehicle->vendor_location_id,
            'location' => $location,
            'location_type' => $locationType,
            'location_code' => $this->locationCode($vehicle, $location, $city, $locationType),
            'full_vehicle_address' => $addressLine1,
            'address_line_2' => null,
            'city' => $city,
            'state' => $this->normalizeText($vehicle->state),
            'country' => $country,
            'country_code' => $this->countryCode($country),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'location_phone' => $this->resolveLocationPhone($vehicle, $vendorProfile),
            'pickup_instructions' => $this->resolvePickupInstructions($vehicle, $location, $locationType),
            'dropoff_instructions' => $this->resolveDropoffInstructions($vehicle, $location, $locationType),
            'iata_code' => $this->iataCode($vehicle, $location, $city, $country, $locationType),
        ];
    }

    private function canonicalLocationName(Vehicle $vehicle, string $locationType): ?string
    {
        $rawLocation = $this->normalizeText($vehicle->location);
        $city = $this->normalizeCity($vehicle->city);

        if ($locationType === 'airport') {
            $key = $this->normalizeLookup($rawLocation);
            $addressKey = $this->normalizeLookup($vehicle->full_vehicle_address);

            return match (true) {
                $key === 'menara airport',
                $key === 'marrakech menara airport' => 'Menara Airport',
                $key === 'aeroport' && $city === 'Marrakech' => 'Menara Airport',
                str_contains($key, 'mohamed v') || str_contains($addressKey, 'mohamed v') => 'Casablanca Airport',
                str_contains($key, 'ibn battouta') => 'Tangier Ibn Battouta Airport',
                str_contains($key, 'orlando international') => 'Orlando International Airport',
                str_contains($key, 'oro uosto') => 'Kaunas Airport',
                str_contains($key, 'tenerife sur') || str_contains($addressKey, 'tenerife sur') => 'Santa Cruz de Tenerife Airport',
                str_contains($key, 'dubai') || ($city === 'Dubai') => 'Dubai Airport',
                str_contains($key, 'antalya') => 'Antalya Airport',
                $city !== null => "{$city} Airport",
                default => $rawLocation,
            };
        }

        if ($rawLocation === null || in_array($this->normalizeLookup($rawLocation), ['downtown', 'industrial', 'city center'], true)) {
            return $city !== null ? "{$city} Downtown" : null;
        }

        return $this->cleanDisplayValue($rawLocation);
    }

    private function normalizeLocationType(?string $value, mixed $location = null, mixed $fullAddress = null): string
    {
        $normalized = implode(' ', array_filter([
            $this->normalizeLookup($value),
            $this->normalizeLookup($location),
            $this->normalizeLookup($fullAddress),
        ]));

        return $this->containsAny($normalized, ['airport', 'aeroport', 'aeropuerto', 'havalimani', 'uosto']) ? 'airport' : 'downtown';
    }

    private function normalizeCountry(?string $country): ?string
    {
        $normalized = $this->normalizeLookup($country);
        if ($normalized === '') {
            return null;
        }

        return $this->countryAliases[$normalized] ?? $this->cleanDisplayValue($country);
    }

    private function normalizeCity(?string $city): ?string
    {
        $city = $this->cleanDisplayValue($city);

        return $city === null ? null : preg_replace('/\s+/', ' ', $city);
    }

    private function countryCode(string $country): string
    {
        return match ($country) {
            'Morocco' => 'MA',
            'United Arab Emirates' => 'AE',
            'Belgium' => 'BE',
            'Spain' => 'ES',
            'Turkey' => 'TR',
            'United States' => 'US',
            'Lithuania' => 'LT',
            default => 'UN',
        };
    }

    private function iataCode(Vehicle $vehicle, string $location, string $city, string $country, string $locationType): ?string
    {
        if ($locationType !== 'airport') {
            return null;
        }

        $lookupKeys = [
            $this->normalizeLookup($location),
            $this->normalizeLookup($city . ' ' . $location),
            $this->normalizeLookup($vehicle->full_vehicle_address),
        ];

        foreach ($lookupKeys as $lookupKey) {
            foreach ($this->airportIataRules as $needle => $iata) {
                if ($lookupKey !== '' && str_contains($lookupKey, $needle)) {
                    return $iata;
                }
            }
        }

        if ($country === 'United Arab Emirates' && $city === 'Dubai') {
            return 'DXB';
        }

        if ($country === 'Morocco' && $city === 'Casablanca') {
            return 'CMN';
        }

        if ($country === 'Morocco' && $city === 'Marrakech') {
            return 'RAK';
        }

        if ($country === 'Morocco' && $city === 'Tangier') {
            return 'TNG';
        }

        if ($country === 'Turkey' && $city === 'Antalya') {
            return 'AYT';
        }

        if ($country === 'Spain' && $city === 'Santa Cruz de Tenerife') {
            return 'TFS';
        }

        return null;
    }

    private function locationCode(Vehicle $vehicle, string $location, string $city, string $locationType): string
    {
        $base = implode('-', array_filter([
            $vehicle->vendor_id,
            Str::slug($city),
            Str::slug($location),
            $locationType,
        ]));

        return Str::limit($base, 80, '');
    }

    private function normalizedAddress(Vehicle $vehicle, string $location, string $city, string $country): string
    {
        $address = $this->cleanDisplayValue($vehicle->full_vehicle_address);

        if ($address !== null) {
            return $address;
        }

        return implode(', ', array_filter([$location, $city, $country]));
    }

    private function resolveLocationPhone(Vehicle $vehicle, ?VendorProfile $vendorProfile): ?string
    {
        return $this->cleanDisplayValue($vehicle->location_phone)
            ?? $this->cleanDisplayValue($vendorProfile?->company_phone_number);
    }

    private function resolvePickupInstructions(Vehicle $vehicle, string $location, string $locationType): string
    {
        return $this->cleanDisplayValue($vehicle->pickup_instructions)
            ?? ($locationType === 'airport'
                ? "Collect the vehicle from {$location}. Please contact the office before arrival."
                : "Collect the vehicle from {$location}. Please contact the office before arrival."
            );
    }

    private function resolveDropoffInstructions(Vehicle $vehicle, string $location, string $locationType): string
    {
        return $this->cleanDisplayValue($vehicle->dropoff_instructions)
            ?? ($locationType === 'airport'
                ? "Return the vehicle to {$location} and follow the airport return instructions provided by the office."
                : "Return the vehicle to {$location} during office hours."
            );
    }

    private function inferBodyStyle(Vehicle $vehicle, ?string $categoryName): string
    {
        $haystack = $this->normalizeLookup(implode(' ', array_filter([
            $categoryName,
            $vehicle->brand,
            $vehicle->model,
        ])));

        if ($this->containsAny($haystack, ['spider', 'spyder', 'spide', 'boxster', 'roadster'])) {
            return 'roadster';
        }

        if ($this->containsAny($haystack, ['convertible', 'cabrio', 'cabriolet', 'targa'])) {
            return 'convertible';
        }

        if ($this->containsAny($haystack, [
            'sports car',
            'sport car',
            'performance',
            'supercar',
            'exotic',
            'corvette',
            'huracan',
            '911',
            'ferrari',
            'lamborghini',
            'mclaren',
            'aston martin',
            'amg gt',
            'r8',
            'gtr',
        ])) {
            return 'sport';
        }

        if ($this->containsAny($haystack, ['coupe'])) {
            return 'coupe';
        }

        if ($this->containsAny($haystack, ['van', 'vito', 'berlingo', 'jumpy', 'trafic', 'minibus'])) {
            return ((int) $vehicle->seating_capacity) >= 7 ? 'minivan' : 'van';
        }

        if ($this->containsAny($haystack, [
            'suv',
            'q3',
            'q8',
            'x6',
            'urus',
            'cayenne',
            'captiva',
            'tucson',
            'avenger',
            'duster',
            'tiggo',
            'juke',
            'gla',
            'escalade',
            'cullinan',
            'g63',
            'g class',
            'uni k',
            'gls',
        ])) {
            return 'suv';
        }

        if ($this->containsAny($haystack, ['city cars', 'corsa', 'clio', '208', 'logan', 'sandero', 'leon', 'a3'])) {
            return 'hatchback';
        }

        return 'sedan';
    }

    private function inferAirConditioning(Vehicle $vehicle): bool
    {
        $features = $this->decodeFeatures($vehicle->features);
        $lookup = $this->normalizeLookup(implode(' ', $features));

        if ($this->containsAny($lookup, ['no ac', 'without ac', 'non ac'])) {
            return false;
        }

        if ($this->containsAny($lookup, ['air conditioning', 'air-conditioning', 'ac', 'a/c', 'climate control'])) {
            return true;
        }

        return true;
    }

    private function resolvedCity(Vehicle $vehicle, string $location, string $locationType): ?string
    {
        $city = $this->normalizeCity($vehicle->city);

        if ($city !== null) {
            return $city;
        }

        $lookup = implode(' ', array_filter([
            $this->normalizeLookup($location),
            $this->normalizeLookup($vehicle->full_vehicle_address),
        ]));

        return match (true) {
            $locationType === 'airport' && str_contains($lookup, 'antalya') => 'Antalya',
            $locationType === 'airport' && str_contains($lookup, 'casablanca') => 'Casablanca',
            $locationType === 'airport' && str_contains($lookup, 'tenerife') => 'Santa Cruz de Tenerife',
            $locationType === 'airport' && str_contains($lookup, 'marrakech') => 'Marrakech',
            $locationType === 'airport' && str_contains($lookup, 'tangier') => 'Tangier',
            $locationType === 'airport' && str_contains($lookup, 'dubai') => 'Dubai',
            default => null,
        };
    }

    private function decodeFeatures(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? Arr::flatten($decoded) : [$value];
    }

    private function cleanDisplayValue(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/\s+/', ' ', $value);

        return trim((string) $value);
    }

    private function normalizeLookup(mixed $value): string
    {
        $value = $this->cleanDisplayValue($value) ?? '';
        $value = Str::ascii($value);
        $value = strtolower($value);

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '');
    }

    private function normalizeText(mixed $value): ?string
    {
        return $this->cleanDisplayValue($value);
    }

    private function normalizeFloat(mixed $value): ?float
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
