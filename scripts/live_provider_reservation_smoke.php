<?php

declare(strict_types=1);

use App\Services\VrooemGatewayService;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$options = parseCliOptions($argv);

if (($options['template'] ?? false) === true) {
    echo json_encode(planTemplate(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit(0);
}

$planPath = (string) ($options['plan'] ?? '');
if ($planPath === '' || ! is_file($planPath)) {
    fwrite(STDERR, usage().PHP_EOL);
    exit(1);
}

$confirmLive = ($options['confirm-live'] ?? '') === 'YES';
$providerFilter = isset($options['provider']) ? strtolower((string) $options['provider']) : null;
$plan = json_decode((string) file_get_contents($planPath), true);
if (! is_array($plan)) {
    fwrite(STDERR, "Plan file is not valid JSON: {$planPath}".PHP_EOL);
    exit(1);
}

$cases = array_values(array_filter($plan['providers'] ?? [], function ($case) use ($providerFilter) {
    if (! is_array($case)) {
        return false;
    }
    if ($providerFilter === null) {
        return true;
    }

    return strtolower((string) ($case['supplier_id'] ?? $case['provider'] ?? '')) === $providerFilter;
}));

if ($cases === []) {
    fwrite(STDERR, 'No provider cases found in plan.'.PHP_EOL);
    exit(1);
}

$gateway = app(VrooemGatewayService::class);
$report = [
    'generated_at' => now()->toIso8601String(),
    'mode' => $confirmLive ? 'live-confirmed' : 'dry-run',
    'gateway_url' => config('vrooem.url'),
    'results' => [],
];

foreach ($cases as $case) {
    $row = [
        'provider' => $case['supplier_id'] ?? $case['provider'] ?? null,
        'label' => $case['label'] ?? null,
    ];

    $validationErrors = validateCase($case);
    if ($validationErrors !== []) {
        $row['status'] = 'blocked';
        $row['errors'] = $validationErrors;
        $report['results'][] = $row;

        continue;
    }

    if (($case['allow_live_provider_booking'] ?? false) !== true) {
        $row['status'] = 'blocked';
        $row['errors'] = ['allow_live_provider_booking must be true for each live case'];
        $report['results'][] = $row;

        continue;
    }

    $payload = buildBookingPayload($case);
    $row['payload_summary'] = summarizePayload($payload);

    if (! $confirmLive) {
        $row['status'] = 'dry-run';
        $row['message'] = 'No provider reservation was created. Re-run with --confirm-live=YES to execute.';
        $report['results'][] = $row;

        continue;
    }

    try {
        $bookingResponse = $gateway->createBooking($payload);
        $row['create_response'] = redact($bookingResponse);

        $status = strtolower((string) ($bookingResponse['status'] ?? ''));
        $supplierBookingId = trim((string) ($bookingResponse['supplier_booking_id'] ?? ''));
        $gatewayBookingId = trim((string) ($bookingResponse['gateway_booking_id'] ?? $bookingResponse['id'] ?? ''));
        $supplierId = trim((string) ($bookingResponse['supplier_id'] ?? $case['supplier_id'] ?? ''));

        if ($status !== 'confirmed' || $supplierBookingId === '' || $gatewayBookingId === '' || $supplierId === '') {
            $row['status'] = 'create_failed';
            $row['message'] = 'Reservation was not confirmed, so cancellation was not attempted.';
            $row['last_error'] = redact($gateway->getLastError());
            $report['results'][] = $row;

            continue;
        }

        $cancelResponse = $gateway->cancelBooking(
            $gatewayBookingId,
            $supplierId,
            $supplierBookingId,
            'Vrooem live smoke test cancellation'
        );

        $row['cancel_response'] = redact($cancelResponse);
        $row['status'] = $cancelResponse ? 'created_and_cancelled' : 'cancel_failed';
        $row['last_error'] = redact($gateway->getLastError());
    } catch (Throwable $e) {
        $row['status'] = 'exception';
        $row['message'] = $e->getMessage();
        $row['last_error'] = redact($gateway->getLastError());
    }

    $report['results'][] = $row;
}

$reportDir = storage_path('app/audits');
if (! is_dir($reportDir)) {
    mkdir($reportDir, 0775, true);
}

$reportPath = $reportDir.'/live-provider-reservation-smoke-'.now()->format('Ymd-His').'.json';
file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "Report written: {$reportPath}".PHP_EOL;
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;

function parseCliOptions(array $argv): array
{
    $options = [];
    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--template') {
            $options['template'] = true;

            continue;
        }
        if (str_starts_with($arg, '--') && str_contains($arg, '=')) {
            [$key, $value] = explode('=', substr($arg, 2), 2);
            $options[$key] = $value;
        }
    }

    return $options;
}

function validateCase(array $case): array
{
    $required = [
        'supplier_id',
        'vehicle_id',
        'search_id',
        'driver.first_name',
        'driver.last_name',
        'driver.email',
        'driver.phone',
        'pickup_date',
        'pickup_time',
        'dropoff_date',
        'dropoff_time',
    ];

    $errors = [];
    foreach ($required as $field) {
        if (trim((string) data_get($case, $field, '')) === '') {
            $errors[] = "{$field} is required";
        }
    }

    foreach (($case['extras'] ?? []) as $index => $extra) {
        if (trim((string) ($extra['extra_id'] ?? '')) === '') {
            $errors[] = "extras.{$index}.extra_id is required";
        }
        if ((int) ($extra['quantity'] ?? 0) < 1) {
            $errors[] = "extras.{$index}.quantity must be >= 1";
        }
    }

    return $errors;
}

function buildBookingPayload(array $case): array
{
    return array_filter([
        'vehicle_id' => (string) $case['vehicle_id'],
        'search_id' => (string) $case['search_id'],
        'driver' => [
            'first_name' => (string) data_get($case, 'driver.first_name'),
            'last_name' => (string) data_get($case, 'driver.last_name'),
            'email' => (string) data_get($case, 'driver.email'),
            'phone' => (string) data_get($case, 'driver.phone'),
            'age' => (int) data_get($case, 'driver.age', 35),
            'address' => (string) data_get($case, 'driver.address', ''),
            'city' => (string) data_get($case, 'driver.city', ''),
            'postal_code' => (string) data_get($case, 'driver.postal_code', ''),
            'country' => (string) data_get($case, 'driver.country', ''),
            'driving_license_number' => data_get($case, 'driver.driving_license_number'),
            'driving_license_country' => data_get($case, 'driver.driving_license_country'),
        ],
        'extras' => array_values($case['extras'] ?? []),
        'insurance_id' => $case['insurance_id'] ?? null,
        'flight_number' => $case['flight_number'] ?? null,
        'special_requests' => 'Vrooem live smoke test booking. Cancel immediately.',
        'pickup_date' => (string) $case['pickup_date'],
        'pickup_time' => (string) $case['pickup_time'],
        'dropoff_date' => (string) $case['dropoff_date'],
        'dropoff_time' => (string) $case['dropoff_time'],
        'laravel_booking_id' => isset($case['laravel_booking_id']) ? (int) $case['laravel_booking_id'] : null,
        'laravel_booking_number' => $case['laravel_booking_number'] ?? 'LIVE-SMOKE-TEST',
    ], static fn ($value) => $value !== null && $value !== '');
}

function summarizePayload(array $payload): array
{
    return [
        'vehicle_id' => $payload['vehicle_id'] ?? null,
        'search_id' => $payload['search_id'] ?? null,
        'pickup_date' => $payload['pickup_date'] ?? null,
        'dropoff_date' => $payload['dropoff_date'] ?? null,
        'extras_count' => count($payload['extras'] ?? []),
        'insurance_id' => $payload['insurance_id'] ?? null,
    ];
}

function redact(mixed $value): mixed
{
    $sensitive = [
        'address',
        'city',
        'country',
        'customer',
        'driver',
        'email',
        'first_name',
        'last_name',
        'name',
        'phone',
        'postal_code',
    ];

    if (is_array($value)) {
        $clean = [];
        foreach ($value as $key => $child) {
            if (is_string($key) && in_array(strtolower($key), $sensitive, true)) {
                $clean[$key] = '[redacted]';

                continue;
            }
            $clean[$key] = redact($child);
        }

        return $clean;
    }

    if (is_string($value) && strlen($value) > 2000) {
        return substr($value, 0, 2000);
    }

    return $value;
}

function planTemplate(): array
{
    return [
        'notes' => [
            'Run a fresh gateway search first and copy vehicle_id/search_id from the selected vehicle.',
            'This script creates real provider reservations only with --confirm-live=YES.',
            'Each confirmed reservation is cancelled immediately through the gateway cancellation endpoint.',
        ],
        'providers' => [
            [
                'label' => 'Surprice CAG smoke test',
                'supplier_id' => 'surprice',
                'allow_live_provider_booking' => false,
                'vehicle_id' => 'gw_vehicle_id_from_fresh_search',
                'search_id' => 'search_id_from_fresh_search',
                'pickup_date' => '2027-01-12',
                'pickup_time' => '09:00',
                'dropoff_date' => '2027-01-13',
                'dropoff_time' => '09:00',
                'driver' => [
                    'first_name' => 'Vrooem',
                    'last_name' => 'Smoke Test',
                    'email' => 'provider-smoke-test@example.com',
                    'phone' => '+32000000000',
                    'age' => 35,
                    'address' => 'Test address',
                    'city' => 'Test city',
                    'postal_code' => '1000',
                    'country' => 'BE',
                    'driving_license_number' => 'TEST123456',
                    'driving_license_country' => 'BE',
                ],
                'extras' => [],
                'insurance_id' => null,
            ],
        ],
    ];
}

function usage(): string
{
    return <<<'TXT'
Usage:
  php scripts/live_provider_reservation_smoke.php --template
  php scripts/live_provider_reservation_smoke.php --plan=storage/app/audits/live-provider-plan.json
  php scripts/live_provider_reservation_smoke.php --plan=storage/app/audits/live-provider-plan.json --provider=surprice --confirm-live=YES

Safety:
  Without --confirm-live=YES, this performs dry-run validation only.
  Each case must also set allow_live_provider_booking=true.
  Use only provider-approved test dates/locations.
TXT;
}
