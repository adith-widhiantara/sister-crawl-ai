<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SisterAuthService
{
    /**
     * Get an access token from the SISTER API.
     *
     * @return array{token: string, role: string}
     *
     * @throws RequestException
     */
    public function getToken(): array
    {
        $response = Http::baseUrl(config('services.sister.host'))
            ->post('/authorize', [
                'username' => config('services.sister.username'),
                'password' => config('services.sister.password'),
                'id_pengguna' => config('services.sister.id_pengguna'),
            ])
            ->throw();

        return $response->json();
    }

    /**
     * Get the bearer token, cached (token is valid for 60 minutes on SISTER's side).
     *
     * @throws RequestException
     */
    public function getCachedToken(): string
    {
        // ponytail: cache key is global (no per-user tenancy), fine since there's one shared SISTER credential.
        return Cache::remember('sister_api_token', now()->addMinutes(55), fn () => $this->getToken()['token']);
    }
}
