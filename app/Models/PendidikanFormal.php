<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendidikanFormal extends Model
{
    protected $fillable = [
        'sister_id', 'id_sdm', 'jenjang_pendidikan', 'gelar_akademik',
        'bidang_studi', 'nama_perguruan_tinggi', 'tahun_lulus', 'jenis_ajuan',
    ];
}
