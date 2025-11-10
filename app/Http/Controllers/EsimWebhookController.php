<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EsimWebhookController extends Controller
{
    /**
     * Handle eSIM Access webhook notifications
     */
    public function handle(Request $request)
    {
        // Log incoming webhook for debugging
        Log::info('eSIM webhook received', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);

        // Verify webhook signature if provided
        $signature = $request->header('X-Webhook-Signature');
        if ($signature) {
            // TODO: Verify signature using your webhook secret
            // You would need to get the secret from eSIM Access dashboard
            Log::info('Webhook signature found', ['signature' => $signature]);
        }

        try {
            $data = $request->all();

            // Process order completion
            if (isset($data['order_id']) && isset($data['status']) && $data['status'] === 'completed') {
                $this->sendCustomerNotification($data);
            }

            return response()->json(['status' => 'success', 'message' => 'Webhook processed']);

        } catch (\Exception $e) {
            Log::error('Error processing eSIM webhook', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process webhook'
            ], 500);
        }
    }

    /**
     * Send customer notification email
     */
    private function sendCustomerNotification($orderData)
    {
        try {
            $customerEmail = $orderData['customer_email'] ?? null;
            $orderId = $orderData['order_id'] ?? null;
            $esimDetails = $orderData['esim'] ?? [];

            if (!$customerEmail || !$orderId) {
                Log::warning('Missing customer email or order ID in webhook data', [
                    'data' => $orderData
                ]);
                return;
            }

            // TODO: Create and send email notification
            Log::info('Sending customer notification', [
                'email' => $customerEmail,
                'order_id' => $orderId,
                'esim_details' => $esimDetails
            ]);

            // You can implement email sending here:
            // Mail::to($customerEmail)->send(new EsimOrderCompleted($orderData));

        } catch (\Exception $e) {
            Log::error('Failed to send customer notification', [
                'error' => $e->getMessage(),
                'order_data' => $orderData
            ]);
        }
    }
}