<?php

namespace App\Services;

class SisterPublikasiService extends SisterEndpointService
{
    private const PER_PAGE = 100;

    /**
     * GET /publikasi — paginated, so loop pages until a short page tells us we're done.
     */
    public function crawl(string $idSdm): array
    {
        $rows = [];
        $page = 1;

        do {
            $pageRows = $this->get('/publikasi', [
                'id_sdm' => $idSdm,
                'per_page' => self::PER_PAGE,
                'page' => $page,
            ])->json();

            $rows = array_merge($rows, $pageRows);
            $page++;
        } while (count($pageRows) === self::PER_PAGE);

        return $rows;
    }
}
