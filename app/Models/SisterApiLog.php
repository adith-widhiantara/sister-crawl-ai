<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SisterApiLog extends Model
{
    protected $fillable = [
        'method', 'url', 'request_headers', 'request_body',
        'response_status', 'response_headers', 'response_body',
        'duration_ms', 'error',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'request_body' => 'array',
        'response_headers' => 'array',
        'response_body' => 'array',
    ];
}
