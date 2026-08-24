<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSearchLog extends Model
{
    protected $fillable = [
        'question', 'provider', 'model', 'tool_arguments', 'columns', 'rows',
        'result_count', 'summary', 'status', 'error', 'duration_ms',
    ];

    protected $casts = [
        'tool_arguments' => 'array',
        'columns' => 'array',
        'rows' => 'array',
    ];
}
