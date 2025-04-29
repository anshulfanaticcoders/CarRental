<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Radius extends Model
{
    protected $table = 'radiuses';
    protected $fillable = ['city', 'state', 'country', 'radius_km'];

    /**
     * Determine if a record with the same location attributes already exists
     * 
     * @param string|null $city
     * @param string|null $state
     * @param string|null $country
     * @param int|null $excludeId ID to exclude from the check
     * @return bool
     */
    public static function locationExists($city, $state, $country, $excludeId = null)
    {
        $query = self::query();
        
        // Handle null values explicitly
        if ($city === null) {
            $query->whereNull('city');
        } else {
            $query->where('city', $city);
        }
        
        if ($state === null) {
            $query->whereNull('state');
        } else {
            $query->where('state', $state);
        }
        
        if ($country === null) {
            $query->whereNull('country');
        } else {
            $query->where('country', $country);
        }
        
        // Exclude the current record if editing
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
}