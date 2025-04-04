<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockingDate extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'blocking_start_date',
        'blocking_end_date',
    ];

    protected $casts = [
        'blocking_start_date' => 'date',
        'blocking_end_date' => 'date',
    ];
    
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}

