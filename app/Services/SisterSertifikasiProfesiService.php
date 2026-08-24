<?php

namespace App\Services;

class SisterSertifikasiProfesiService extends SisterEndpointService
{
    /**
     * GET /sertifikasi_profesi — riwayat sertifikasi profesi untuk satu id_sdm.
     */
    public function crawl(string $idSdm): array
    {
        return $this->get('/sertifikasi_profesi', ['id_sdm' => $idSdm])->json();
    }
}
