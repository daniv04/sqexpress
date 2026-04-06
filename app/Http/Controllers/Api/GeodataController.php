<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GeodataService;
use Illuminate\Http\JsonResponse;

class GeodataController extends Controller
{
    protected $geodataService;

    public function __construct(GeodataService $geodataService)
    {
        $this->geodataService = $geodataService;
    }

    public function getProvincias(): JsonResponse
    {
        $provincias = $this->geodataService->getProvincias();
        return response()->json($provincias);
    }

    public function getCantones(int $provinciaId): JsonResponse
    {
        $cantones = $this->geodataService->getCantonesByProvincia($provinciaId);
        return response()->json($cantones);
    }

    public function getDistritos(int $cantonId): JsonResponse
    {
        $distritos = $this->geodataService->getDistritosByCanton($cantonId);
        return response()->json($distritos);
    }
}
