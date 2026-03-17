<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleOperatingHour extends Model
{
    use HasFactory;

    const DAY_MONDAY = 0;
    const DAY_TUESDAY = 1;
    const DAY_WEDNESDAY = 2;
    const DAY_THURSDAY = 3;
    const DAY_FRIDAY = 4;
    const DAY_SATURDAY = 5;
    const DAY_SUNDAY = 6;

    const DAY_NAMES = [
        self::DAY_MONDAY => 'Monday',
        self::DAY_TUESDAY => 'Tuesday',
        self::DAY_WEDNESDAY => 'Wednesday',
        self::DAY_THURSDAY => 'Thursday',
        self::DAY_FRIDAY => 'Friday',
        self::DAY_SATURDAY => 'Saturday',
        self::DAY_SUNDAY => 'Sunday',
    ];

    protected $fillable = [
        'vehicle_id',
        'day_of_week',
        'is_open',
        'open_time',
        'close_time',
    ];

    protected $casts = [
        'is_open' => 'boolean',
        'day_of_week' => 'integer',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function dayName(): string
    {
        return self::DAY_NAMES[$this->day_of_week] ?? 'Unknown';
    }
}
