<?php

namespace App\Notifications\Booking;

use App\Notifications\Concerns\FormatsBookingAmounts;
use App\Notifications\Concerns\FormatsBookingLocations;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCreatedCustomerNotification extends Notification
{
    use FormatsBookingAmounts;
    use FormatsBookingLocations;
    use Queueable;

    protected $booking;

    protected $customer;

    protected $vehicle;

    public function __construct($booking, $customer, $vehicle)
    {
        $this->booking = $booking;
        $this->customer = $customer;
        $this->vehicle = $vehicle;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database']; // Notify via email and save to database
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amounts = $this->getCustomerAmounts($this->booking);
        $vehicleName = $this->getVehicleName();
        $location = $this->getLocation();
        $address = $this->getAddress();
        // $addressParts = array_filter([
        //     $this->vehicle->city,
        //     $this->vehicle->state,
        //     $this->vehicle->country,
        // ]);
        // $formattedAddress = implode(', ', $addressParts);

        $isOneWay = $this->isOneWayBooking($this->booking);
        $pickupLines = $this->formatLocationBlock($this->pickupDetails($this->booking), $this->booking->pickup_location ?? '');
        $dropoffLines = $this->formatLocationBlock($this->dropoffDetails($this->booking), $this->booking->return_location ?? '');
        $pickupInstr = $this->pickupInstructions($this->booking);
        $dropoffInstr = $this->dropoffInstructions($this->booking);

        $mail = (new MailMessage)
            ->subject('Your Booking Confirmation - #'.$this->booking->booking_number)
            ->greeting('Hello '.$this->customer->first_name.',')
            ->line('Thank you for your booking! It has been successfully submitted and is pending confirmation.')
            ->line('**Booking Details:**')
            ->line('**Booking Number:** '.$this->booking->booking_number)
            ->line('**Vehicle:** '.$vehicleName);

        if ($isOneWay) {
            $mail->line('**One-way rental**');
        }

        $mail->line('**Pickup Date:** '.$this->booking->pickup_date->format('Y-m-d').' at '.$this->booking->pickup_time);
        if (! empty($pickupLines)) {
            $mail->line('**Pickup Location:**');
            foreach ($pickupLines as $line) {
                $mail->line($line);
            }
        } elseif (! empty($location)) {
            $mail->line('**Pickup Location:** '.$location);
        }
        if ($pickupInstr) {
            $mail->line('_Pickup instructions:_ '.$pickupInstr);
        }

        $mail->line('**Return Date:** '.$this->booking->return_date->format('Y-m-d').' at '.$this->booking->return_time);
        if ($isOneWay && ! empty($dropoffLines)) {
            $mail->line('**Dropoff Location:**');
            foreach ($dropoffLines as $line) {
                $mail->line($line);
            }
            if ($dropoffInstr) {
                $mail->line('_Dropoff instructions:_ '.$dropoffInstr);
            }
        }

        return $mail
            ->line('**Total Amount:** '.$this->formatCurrencyAmount($amounts['total'], $amounts['currency']))
            ->line('**Amount Paid:** '.$this->formatCurrencyAmount($amounts['paid'], $amounts['currency']))
            ->line('**Pending Amount:** '.$this->formatCurrencyAmount($amounts['pending'], $amounts['currency']))
            ->action('View Your Booking', url('/'.app()->getLocale().'/booking/'.$this->booking->id))
            ->line('You will be notified once the booking is confirmed by the vendor.');
    }

    public function toArray(object $notifiable): array
    {
        $amounts = $this->getCustomerAmounts($this->booking);
        $vehicleName = $this->getVehicleName();
        $location = $this->getLocation();
        $address = $this->getAddress();
        // $addressParts = array_filter([
        //     $this->vehicle->city,
        //     $this->vehicle->state,
        //     $this->vehicle->country,
        // ]);
        // $formattedAddress = implode(', ', $addressParts);

        return [
            'title' => 'Booking Confirmed #'.$this->booking->booking_number,
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'vehicle' => $vehicleName,
            'location' => $location,
            'address' => $address,
            'is_one_way' => $this->isOneWayBooking($this->booking),
            'pickup_location_details' => $this->pickupDetails($this->booking) ?: null,
            'dropoff_location_details' => $this->dropoffDetails($this->booking) ?: null,
            'pickup_date' => $this->booking->pickup_date->format('Y-m-d'),
            'pickup_time' => $this->booking->pickup_time,
            'return_date' => $this->booking->return_date->format('Y-m-d'),
            'return_time' => $this->booking->return_time,
            'total_amount' => $amounts['total'],
            'amount_paid' => $amounts['paid'],
            'pending_amount' => $amounts['pending'],
            'currency_symbol' => $this->getCurrencySymbol($amounts['currency']),
            'role' => 'customer',
            'message' => 'Your booking has been submitted and is pending confirmation.',
        ];
    }

    private function getVehicleName(): string
    {
        $brand = $this->vehicle?->brand ?? '';
        $model = $this->vehicle?->model ?? '';
        $name = trim($brand.' '.$model);

        if ($name !== '') {
            return $name;
        }

        return $this->booking->vehicle_name ?? 'Vehicle';
    }

    private function getLocation(): string
    {
        $location = $this->vehicle?->location ?? null;
        if (! empty($location)) {
            return $location;
        }

        return $this->booking->pickup_location
            ?? $this->booking->return_location
            ?? 'N/A';
    }

    private function getAddress(): string
    {
        $parts = array_filter([
            $this->vehicle?->city ?? null,
            $this->vehicle?->state ?? null,
            $this->vehicle?->country ?? null,
        ]);

        if (! empty($parts)) {
            return implode(', ', $parts);
        }

        return $this->getLocation();
    }
}
