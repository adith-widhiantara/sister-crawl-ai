<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SertifikasiDosen extends Model
{
    protected $fillable = [
        'sister_id', 'id_sdm', 'jenis_sertifikasi', 'bidang_studi',
        'tahun_sertifikasi', 'sk_sertifikasi', 'nomor_registrasi',
    ];
}
