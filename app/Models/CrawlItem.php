<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrawlItem extends Model
{
    protected $fillable = ['batch_id', 'endpoint', 'id_sdm', 'nama_sdm', 'status', 'error'];
}
