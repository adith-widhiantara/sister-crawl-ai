<?php

namespace App\Http\Controllers;

use App\Services\SisterAuthService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;

class SisterAuthController extends Controller
{
    public function __construct(private SisterAuthService $sisterAuthService) {}

    public function getToken(): JsonResponse
    {
        try {
            return response()->json($this->sisterAuthService->getToken());
        } catch (RequestException $e) {
            return response()->json([
                'message' => 'Failed to get token from SISTER API',
                'error' => $e->response->json() ?? $e->getMessage(),
            ], $e->response->status());
        }
    }
}
