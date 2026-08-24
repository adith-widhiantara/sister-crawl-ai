<?php

namespace App\Services;

use App\Models\JabatanFungsional;
use App\Models\PendidikanFormal;
use App\Models\Publikasi;
use App\Models\SertifikasiDosen;
use App\Models\SertifikasiProfesi;

/**
 * One entry per crawlable id_sdm-scoped SISTER endpoint. Adding a new endpoint means
 * adding one line here (plus its migration/model/service) — no new job/controller code.
 */
class SisterEndpointRegistry
{
    public static function all(): array
    {
        return [
            'jabatan_fungsional' => [
                'label' => 'Jabatan Fungsional',
                'service' => SisterJabatanFungsionalService::class,
                'model' => JabatanFungsional::class,
            ],
            'pendidikan_formal' => [
                'label' => 'Pendidikan Formal',
                'service' => SisterPendidikanFormalService::class,
                'model' => PendidikanFormal::class,
            ],
            'publikasi' => [
                'label' => 'Publikasi',
                'service' => SisterPublikasiService::class,
                'model' => Publikasi::class,
            ],
            'sertifikasi_dosen' => [
                'label' => 'Sertifikasi Dosen',
                'service' => SisterSertifikasiDosenService::class,
                'model' => SertifikasiDosen::class,
            ],
            'sertifikasi_profesi' => [
                'label' => 'Sertifikasi Profesi',
                'service' => SisterSertifikasiProfesiService::class,
                'model' => SertifikasiProfesi::class,
            ],
        ];
    }

    public static function get(string $key): array
    {
        return static::all()[$key] ?? throw new \InvalidArgumentException("Unknown SISTER endpoint: {$key}");
    }

    public static function keys(): array
    {
        return array_keys(static::all());
    }
}
