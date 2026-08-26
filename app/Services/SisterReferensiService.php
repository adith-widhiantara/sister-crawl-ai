<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class SisterReferensiService
{
    public function __construct(private SisterAuthService $authService) {}

    /**
     * GET /referensi/sdm — pegawai di perguruan tinggi.
     *
     * @param  array{id_sp?: string, nama?: string, nidn?: string, nip?: string, nuptk?: string}  $filters
     *
     * @throws RequestException
     */
    public function getSdm(array $filters): array
    {
        $response = Http::baseUrl(config('services.sister.host'))
            ->withToken($this->authService->getCachedToken())
            ->timeout(120) // /referensi/sdm balikin seluruh pegawai sekaligus, bisa lambat
            ->get('/referensi/sdm', $filters)
            ->throw();

        return $response->json();
    }
}
