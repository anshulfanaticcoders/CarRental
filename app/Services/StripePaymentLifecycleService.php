<?php

namespace App\Services;

use App\Exceptions\StripePaymentAlreadyCapturedException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripePaymentLifecycleService
{
    public function isAuthorized(string $paymentIntentId): bool
    {
        $intent = $this->retrieve($paymentIntentId);

        return ($intent->capture_method ?? null) === 'manual'
            && ($intent->status ?? null) === 'requires_capture'
            && (int) ($intent->amount_capturable ?? 0) > 0;
    }

    public function captureAuthorized(string $paymentIntentId, string $idempotencyKey): void
    {
        $intent = $this->retrieve($paymentIntentId);
        if (($intent->status ?? null) === 'succeeded') {
            return;
        }
        if (($intent->status ?? null) !== 'requires_capture') {
            throw new \RuntimeException(
                'Stripe PaymentIntent is not capturable (status '.($intent->status ?? 'unknown').').'
            );
        }

        $captured = $intent->capture([], ['idempotency_key' => $idempotencyKey]);
        if (($captured->status ?? null) !== 'succeeded') {
            throw new \RuntimeException(
                'Stripe capture did not succeed (status '.($captured->status ?? 'unknown').').'
            );
        }
    }

    public function releaseAuthorization(string $paymentIntentId, string $idempotencyKey): void
    {
        $intent = $this->retrieve($paymentIntentId);
        if (($intent->status ?? null) === 'canceled') {
            return;
        }
        if (($intent->status ?? null) === 'succeeded') {
            throw new StripePaymentAlreadyCapturedException(
                'Captured Stripe payment cannot be released as an authorization.'
            );
        }

        $cancelled = $intent->cancel([], ['idempotency_key' => $idempotencyKey]);
        if (($cancelled->status ?? null) !== 'canceled') {
            throw new \RuntimeException(
                'Stripe authorization release did not succeed (status '.($cancelled->status ?? 'unknown').').'
            );
        }
    }

    private function retrieve(string $paymentIntentId): PaymentIntent
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        return PaymentIntent::retrieve($paymentIntentId);
    }
}
