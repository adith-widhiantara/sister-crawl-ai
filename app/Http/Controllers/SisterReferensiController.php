<?php

namespace App\Http\Controllers;

use App\Services\SisterReferensiService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SisterReferensiController extends Controller
{
    public function __construct(private SisterReferensiService $sisterReferensiService) {}

    public function sdm(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['id_sp', 'nama', 'nidn', 'nip', 'nuptk']);

            return response()->json($this->sisterReferensiService->getSdm($filters));
        } catch (RequestException $e) {
            return response()->json([
                'message' => 'Failed to get data from SISTER API',
                'error' => $e->response->json() ?? $e->getMessage(),
            ], $e->response->status());
        }
    }
}
