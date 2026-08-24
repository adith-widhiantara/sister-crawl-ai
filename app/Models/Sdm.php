<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sdm extends Model
{
    protected $fillable = [
        'id_sdm', 'nama_sdm', 'nidn', 'nip', 'nuptk',
        'nama_status_aktif', 'nama_status_pegawai', 'jenis_sdm',
    ];
}
