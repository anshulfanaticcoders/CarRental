<?php
use App\Models\Booking;
$bookings = Booking::whereNotNull('provider_source')
    ->where('provider_source','!=','internal')
    ->whereNull('provider_booking_ref')
    ->orderByDesc('id')->limit(5)->get();
if ($bookings->isEmpty()) { echo "No external bookings missing provider ref.\n"; return; }
foreach ($bookings as $b) {
    echo str_repeat('=',70)."\n";
    echo "id={$b->id}  number={$b->booking_number}  created={$b->created_at}\n";
    echo "provider_source={$b->provider_source}  vehicle_id={$b->vehicle_id}\n";
    echo "booking_status={$b->booking_status}  payment_status={$b->payment_status}\n";
    echo "provider_booking_ref=".var_export($b->provider_booking_ref,true)."\n";
    echo "stripe_payment_intent_id={$b->stripe_payment_intent_id}\n";
    echo "total_amount={$b->total_amount}  currency={$b->booking_currency}\n";
    $m = $b->provider_metadata ?? [];
    echo "provider_metadata keys: ".implode(', ', array_keys($m))."\n";
    foreach (['reservation_manual_check','reservation_unknown_at','gateway_error','reservation_last_error','reservation_last_failed_at','manual_refund_required','gateway_vehicle_id','gateway_search_id','package'] as $k) {
        if (array_key_exists($k,$m)) echo "  $m[$k]" === '' ? '' : "  {$k} = ".(is_scalar($m[$k])?$m[$k]:json_encode($m[$k]))."\n";
    }
    echo "  -- reservation_gateway_last_error: ".json_encode($m['reservation_gateway_last_error'] ?? null)."\n";
    echo "  -- reservation_gateway_result: ".json_encode($m['reservation_gateway_result'] ?? null)."\n";
}
