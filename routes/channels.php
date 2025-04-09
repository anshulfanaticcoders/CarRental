<?php

use App\Models\Booking;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.{bookingId}', function ($user, $bookingId) {
    $booking = Booking::findOrFail($bookingId);
    return $user->id === $booking->customer->user_id || $user->id === $booking->vehicle->vendor_id;
});
