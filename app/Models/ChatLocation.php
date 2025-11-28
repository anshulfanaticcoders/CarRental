<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ChatLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_chat_id',
        'sender_id',
        'latitude',
        'longitude',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'map_url',
        'static_map_url',
        'metadata',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'metadata' => 'array',
    ];

    /**
     * Get the booking chat that owns the location.
     */
    public function bookingChat(): BelongsTo
    {
        return $this->belongsTo(BookingChat::class);
    }

    /**
     * Get the sender (user) that owns the location.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the full formatted address.
     */
    public function getFullAddressAttribute()
    {
        $parts = [];

        if ($this->address) {
            $parts[] = $this->address;
        }

        if ($this->city) {
            $parts[] = $this->city;
        }

        if ($this->state) {
            $parts[] = $this->state;
        }

        if ($this->country) {
            $parts[] = $this->country;
        }

        return implode(', ', $parts);
    }

    /**
     * Get the coordinates as an array.
     */
    public function getCoordinatesAttribute()
    {
        return [
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
        ];
    }

    /**
     * Get Google Maps URL for the location.
     */
    public function getGoogleMapsUrl()
    {
        if ($this->map_url) {
            return $this->map_url;
        }

        return "https://www.google.com/maps/search/?api=1&query={$this->latitude},{$this->longitude}";
    }

    /**
     * Get Google Maps static image URL.
     */
    public function getStaticMapUrl($width = 400, $height = 400)
    {
        if ($this->static_map_url) {
            return $this->static_map_url;
        }

        $apiKey = config('services.google_maps_api_key');
        if (!$apiKey) {
            return null;
        }

        $baseUrl = "https://maps.googleapis.com/maps/api/staticmap";
        $params = [
            'center' => "{$this->latitude},{$this->longitude}",
            'zoom' => 15,
            'size' => "{$width}x{$height}",
            'markers' => "color:red|{$this->latitude},{$this->longitude}",
            'key' => $apiKey,
        ];

        return $baseUrl . '?' . http_build_query($params);
    }

    /**
     * Generate static map image and save it.
     */
    public function generateStaticMap($width = 400, $height = 400)
    {
        $mapUrl = $this->getStaticMapUrl($width, $height);
        if (!$mapUrl) {
            return false;
        }

        try {
            $imageContent = file_get_contents($mapUrl);
            if ($imageContent) {
                $fileName = 'chat-locations/' . uniqid() . '_static_map.jpg';
                Storage::disk('public')->put($fileName, $imageContent);

                $this->update(['static_map_url' => $fileName]);
                return true;
            }
        } catch (\Exception $e) {
            // Log error or handle it appropriately
        }

        return false;
    }

    /**
     * Get the distance from another location in kilometers.
     */
    public function getDistanceFrom($latitude, $longitude)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($latitude);
        $lonTo = deg2rad($longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get the distance from another ChatLocation.
     */
    public function getDistanceFromLocation(ChatLocation $otherLocation)
    {
        return $this->getDistanceFrom($otherLocation->latitude, $otherLocation->longitude);
    }

    /**
     * Format the distance for display.
     */
    public function getFormattedDistanceFrom($latitude, $longitude)
    {
        $distance = $this->getDistanceFrom($latitude, $longitude);

        if ($distance < 1) {
            return round($distance * 1000) . ' m';
        } elseif ($distance < 10) {
            return round($distance, 1) . ' km';
        } else {
            return round($distance) . ' km';
        }
    }

    /**
     * Reverse geocode the coordinates to get address details.
     */
    public function reverseGeocode()
    {
        $apiKey = config('services.google_maps_api_key');
        if (!$apiKey) {
            return false;
        }

        $url = "https://maps.googleapis.com/maps/api/geocode/json";
        $params = [
            'latlng' => "{$this->latitude},{$this->longitude}",
            'key' => $apiKey,
        ];

        try {
            $response = file_get_contents($url . '?' . http_build_query($params));
            $data = json_decode($response, true);

            if ($data['status'] === 'OK' && !empty($data['results'])) {
                $result = $data['results'][0];
                $components = [];

                foreach ($result['address_components'] as $component) {
                    $types = $component['types'];

                    if (in_array('route', $types)) {
                        $components['address'] = $component['long_name'];
                    } elseif (in_array('locality', $types)) {
                        $components['city'] = $component['long_name'];
                    } elseif (in_array('administrative_area_level_1', $types)) {
                        $components['state'] = $component['long_name'];
                    } elseif (in_array('country', $types)) {
                        $components['country'] = $component['long_name'];
                    } elseif (in_array('postal_code', $types)) {
                        $components['postal_code'] = $component['long_name'];
                    }
                }

                $this->update(array_filter($components));
                return true;
            }
        } catch (\Exception $e) {
            // Log error or handle it appropriately
        }

        return false;
    }

    /**
     * Create a new location entry with automatic reverse geocoding.
     */
    public static function createWithGeocode($data)
    {
        $location = static::create($data);

        // Queue reverse geocoding if enabled
        if (config('services.google_maps_api_key')) {
            $location->reverseGeocode();
            $location->generateStaticMap();
        }

        return $location;
    }

    /**
     * Get the location accuracy from metadata.
     */
    public function getAccuracy()
    {
        return $this->metadata['accuracy'] ?? null;
    }

    /**
     * Get the location altitude from metadata.
     */
    public function getAltitude()
    {
        return $this->metadata['altitude'] ?? null;
    }

    /**
     * Get the location timestamp from metadata.
     */
    public function getLocationTimestamp()
    {
        return $this->metadata['timestamp'] ?? null;
    }

    /**
     * Check if the location is recent (within last hour).
     */
    public function isRecent()
    {
        $timestamp = $this->getLocationTimestamp();
        if (!$timestamp) {
            return false;
        }

        return (time() - $timestamp) < 3600; // 1 hour
    }
}
