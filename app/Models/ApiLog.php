<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'api_consumer_id',
        'api_key_id',
        'method',
        'endpoint',
        'request_payload',
        'response_status',
        'ip_address',
        'user_agent',
        'processing_time_ms',
        'created_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_status' => 'integer',
        'processing_time_ms' => 'integer',
        'created_at' => 'datetime',
    ];
}
