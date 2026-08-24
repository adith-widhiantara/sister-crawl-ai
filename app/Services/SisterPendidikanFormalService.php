<?php

namespace App\Services;

class SisterPendidikanFormalService extends SisterEndpointService
{
    /**
     * GET /pendidikan_formal — riwayat pendidikan formal untuk satu id_sdm.
     */
    public function crawl(string $idSdm): array
    {
        return $this->get('/pendidikan_formal', ['id_sdm' => $idSdm])->json();
    }
}
