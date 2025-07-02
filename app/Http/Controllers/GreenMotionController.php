<?php

namespace App\Http\Controllers;

use App\Services\GreenMotionService;
use Illuminate\Http\Request;
use SimpleXMLElement;

class GreenMotionController extends Controller
{
    public function __construct(private GreenMotionService $greenMotionService)
    {
    }

    public function getGreenMotionVehicles()
    {
        $xml = $this->greenMotionService->getVehicles(61627, '2032-01-06', '09:00', '2032-01-08', '09:00', 1, 35);

        $xmlObject = new SimpleXMLElement($xml);

        $vehicles = [];
        foreach ($xmlObject->response->vehicles->vehicle as $vehicle) {
            $products = [];
            foreach ($vehicle->product as $product) {
                $products[] = [
                    'type' => (string) $product['type'],
                    'total' => (string) $product->total,
                    'currency' => (string) $product->total['currency'],
                    'deposit' => (string) $product->deposit,
                    'excess' => (string) $product->excess,
                    'fuelpolicy' => (string) $product->fuelpolicy,
                    'purpose' => (string) $product->purpose,
                    'mileage' => (string) $product->mileage,
                    'costperextradistance' => (string) $product->costperextradistance,
                    'minage' => (string) $product->minage,
                    'excludedextras' => (string) $product->excludedextras,
                ];
            }

            $vehicles[] = [
                'name' => (string) $vehicle['name'],
                'id' => (string) $vehicle['id'],
                'image' => urldecode((string) $vehicle['image']),
                'products' => $products,
                'groupName' => (string) $vehicle->groupName,
                'adults' => (string) $vehicle->adults,
                'children' => (string) $vehicle->children,
                'luggageSmall' => (string) $vehicle->luggageSmall,
                'luggageMed' => (string) $vehicle->luggageMed,
                'luggageLarge' => (string) $vehicle->luggageLarge,
                'fuel' => (string) $vehicle->fuel,
                'mpg' => (string) $vehicle->mpg,
                'acriss' => (string) $vehicle->acriss,
                'co2' => (string) $vehicle->co2,
                'carorvan' => (string) $vehicle->carorvan,
                'airConditioning' => (string) $vehicle->airConditioning,
                'refrigerated' => (string) $vehicle->refrigerated,
                'keyngo' => (string) $vehicle->keyngo,
                'transmission' => (string) $vehicle->transmission,
                'paymentURL' => (string) $vehicle->paymentURL,
                'driveandgo' => (string) $vehicle->driveandgo,
            ];
        }

        dd($vehicles);
    }
}
