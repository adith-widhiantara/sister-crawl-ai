<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SertifikasiProfesi extends Model
{
    protected $fillable = [
        'sister_id', 'id_sdm', 'jenis_sertifikasi', 'bidang_studi', 'sk_sertifikasi',
        'id_lembaga_sertifikasi', 'nama_lembaga_sertifikasi', 'terhitung_mulai_tanggal',
        'terhitung_sampai_tanggal', 'nomor_registrasi', 'tahun_sertifikasi',
    ];
}
