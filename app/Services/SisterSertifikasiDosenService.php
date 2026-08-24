<?php

namespace App\Services;

class SisterSertifikasiDosenService extends SisterEndpointService
{
    /**
     * GET /sertifikasi_dosen — riwayat sertifikasi dosen untuk satu id_sdm.
     */
    public function crawl(string $idSdm): array
    {
        return $this->get('/sertifikasi_dosen', ['id_sdm' => $idSdm])->json();
    }
}
