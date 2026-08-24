<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Shared HTTP plumbing for the simple id_sdm-scoped SISTER endpoints
 * (jabatan_fungsional, pendidikan_formal, sertifikasi_dosen, sertifikasi_profesi, publikasi).
 */
abstract class SisterEndpointService
{
    public function __construct(protected SisterAuthService $authService) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    abstract public function crawl(string $idSdm): array;

    protected function get(string $path, array $query): Response
    {
        return Http::baseUrl(config('services.sister.host'))
            ->withToken($this->authService->getCachedToken())
            ->get($path, $query)
            ->throw();
    }
}
