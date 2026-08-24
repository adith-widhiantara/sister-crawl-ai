<?php

namespace App\Services;

class SisterJabatanFungsionalService extends SisterEndpointService
{
    /**
     * GET /jabatan_fungsional — riwayat jabatan fungsional dosen untuk satu id_sdm.
     */
    public function crawl(string $idSdm): array
    {
        return $this->get('/jabatan_fungsional', ['id_sdm' => $idSdm])->json();
    }
}
