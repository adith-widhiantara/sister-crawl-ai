<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Publikasi extends Model
{
    protected $fillable = [
        'sister_id', 'id_sdm', 'kategori_kegiatan', 'judul', 'quartile',
        'bidang_keilmuan', 'jenis_publikasi', 'tanggal', 'asal_data',
    ];

    protected $casts = [
        'bidang_keilmuan' => 'array',
    ];
}
