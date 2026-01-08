<?php
$url = "https://adobecar.cr:42800";
$ch = curl_init($url . "/Auth/Login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['userName' => 'Z11338', 'password' => '11338']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$token = json_decode(curl_exec($ch))->token;

echo "Fetching booking 1301802 details...\n";
$ch = curl_init($url . "/Booking?bookingNumber=1301802&customerCode=Z11338");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
$response = curl_exec($ch);
echo json_encode(json_decode($response), JSON_PRETTY_PRINT);
