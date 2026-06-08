<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AlertService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    protected AlertService $alertService;

    public function __construct(AlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    /**
     * Obtener todas las alertas del dashboard vía AJAX
     */
    public function getAlerts(): JsonResponse
    {
        try {
            $alertas = $this->alertService->getAllAlerts();
            
            return response()->json([
                'success' => true,
                'data' => $alertas,
                'timestamp' => now()->format('d/m/Y H:i:s'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener alertas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Invalidar caché de alertas
     */
    public function invalidateCache(): JsonResponse
    {
        try {
            $this->alertService->invalidateCache();
            
            return response()->json([
                'success' => true,
                'message' => 'Caché de alertas invalidado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al invalidar caché',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}