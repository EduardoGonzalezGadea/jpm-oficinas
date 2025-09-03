<?php

namespace App\Http\Controllers;

use App\Models\InfraccionTransito;
use Illuminate\Http\Request;

class InfraccionTransitoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('infracciones.index');
    }

    /**
     * API endpoint para obtener infracciones (útil para integración con otros sistemas)
     */
    public function api(Request $request)
    {
        $query = InfraccionTransito::activas();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('articulo', 'like', '%' . $search . '%')
                    ->orWhere('apartado', 'like', '%' . $search . '%')
                    ->orWhere('descripcion', 'like', '%' . $search . '%');
            });
        }

        if ($request->has('articulo')) {
            $query->porArticulo($request->get('articulo'));
        }

        $infracciones = $query->orderBy('articulo')
            ->orderBy('apartado')
            ->paginate($request->get('per_page', 25));

        return response()->json($infracciones);
    }

    /**
     * Obtener una infracción específica
     */
    public function show($id)
    {
        $infraccion = InfraccionTransito::findOrFail($id);
        return response()->json($infraccion);
    }

    /**
     * Exportar infracciones a CSV
     */
    public function export()
    {
        $infracciones = InfraccionTransito::activas()
            ->orderBy('articulo')
            ->orderBy('apartado')
            ->get();

        $filename = 'infracciones_transito_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($infracciones) {
            $file = fopen('php://output', 'w');

            // Encabezados CSV
            fputcsv($file, [
                'ID',
                'Artículo',
                'Apartado',
                'Descripción',
                'Importe (UR)',
                'Decreto',
                'Estado'
            ]);

            // Datos
            foreach ($infracciones as $infraccion) {
                fputcsv($file, [
                    $infraccion->id,
                    $infraccion->articulo,
                    $infraccion->apartado,
                    $infraccion->descripcion,
                    $infraccion->importe_ur,
                    $infraccion->decreto,
                    $infraccion->activo ? 'Activa' : 'Inactiva'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
