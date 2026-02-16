<?php

$envPath = __DIR__ . '/../.env';
$lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (!$lines) {
    fwrite(STDERR, "No .env file found.\n");
    exit(1);
}

$env = [];
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }
    if (strpos($line, '=') === false) {
        continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $key = trim($key);
    $value = trim($value);
    $value = trim($value, "\"'");
    $env[$key] = $value;
}

$baseUrl = $env['RECORDRENTACAR_BASE_URL'] ?? 'https://api.recordgo.cloud';
$subscriptionKey = $env['RECORDRENTACAR_SUBSCRIPTION_KEY'] ?? '';
$partnerUser = $env['RECORDRENTACAR_PARTNER_USER'] ?? '';
$sellCode = $env['RECORDRENTACAR_SELLCODE_ITALY'] ?? ($env['RECORDRENTACAR_SELLCODE'] ?? '');

if ($subscriptionKey === '' || $partnerUser === '' || $sellCode === '') {
    fwrite(STDERR, "Missing RecordGo env values.\n");
    exit(1);
}

$payload = [
    'partnerUser' => $partnerUser,
    'country' => 'IT',
    'sellCode' => $sellCode,
    'pickupBranch' => 39001,
    'dropoffBranch' => 39001,
    'pickupDateTime' => '2026-05-10T12:30:00',
    'dropoffDateTime' => '2026-05-11T12:30:00',
    'driverAge' => 30,
    'language' => 'EN',
];

$normalizedBase = rtrim($baseUrl, '/');
$suffix = str_ends_with($normalizedBase, '/brokers') ? '' : '/brokers';
$url = $normalizedBase . $suffix . '/booking_getAvailability/';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Ocp-Apim-Subscription-Key: ' . $subscriptionKey,
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($response === false) {
    fwrite(STDERR, "Curl error: " . curl_error($ch) . "\n");
    exit(1);
}
curl_close($ch);

$data = json_decode($response, true);
echo "HTTP: {$httpCode}\n";
if (is_array($data)) {
    $status = $data['status'] ?? [];
    echo "status: " . ($status['idStatus'] ?? '') . " " . ($status['detailedStatus'] ?? '') . "\n";
    $acriss = $data['acriss'] ?? [];
    $count = is_array($acriss) ? count($acriss) : 0;
    echo "acriss_count: {$count}\n";
    if ($count > 0) {
        $first = $acriss[0];
        echo "first_acriss: " . ($first['acrissCode'] ?? '') . "\n";
    }
} else {
    echo "raw: " . substr($response, 0, 500) . "\n";
}
