<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcesarCfeRequest;
use App\Models\TesCfePendiente;
use App\Services\CfeProcessorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CfeController extends Controller
{
    protected $cfeProcessorService;

    public function __construct(CfeProcessorService $cfeProcessorService)
    {
        $this->cfeProcessorService = $cfeProcessorService;
    }

    /**
     * Procesar un CFE enviado desde la extensión del navegador.
     *
     * @param  ProcesarCfeRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function procesarCfe(ProcesarCfeRequest $request)
    {
        try {
            $userId = $request->user()->id;
            $cfePendiente = $this->cfeProcessorService->procesarPdf($request->file('pdf_file'), $request->source_url, $userId);

            return response()->json([
                'success' => true,
                'message' => 'CFE recibido y almacenado correctamente.',
                'cfe_pendiente' => $cfePendiente
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el CFE: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todos los CFEs pendientes.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function pendientes()
    {
        try {
            $pendientes = TesCfePendiente::where('estado', 'pendiente')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($pendientes);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener CFEs pendientes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirmar un CFE pendiente.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmarCfe($id)
    {
        try {
            $cfe = TesCfePendiente::find($id);
            if (!$cfe || $cfe->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'CFE no encontrado o no está pendiente.'
                ], 404);
            }

            $cfe->estado = 'confirmado';
            $cfe->procesado_por = Auth::id();
            $cfe->procesado_at = now();
            $cfe->save();

            return response()->json([
                'success' => true,
                'message' => 'CFE confirmado correctamente.',
                'cfe' => $cfe
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar el CFE: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechazar un CFE pendiente.
     *
     * @param  string  $id
     * @param  string  $motivo
     * @return \Illuminate\Http\JsonResponse
     */
    public function rechazarCfe($id, Request $request)
    {
        try {
            $cfe = TesCfePendiente::find($id);
            if (!$cfe || $cfe->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'CFE no encontrado o no está pendiente.'
                ], 404);
            }

            $cfe->estado = 'rechazado';
            $cfe->motivo_rechazo = $request->input('motivo');
            $cfe->save();

            return response()->json([
                'success' => true,
                'message' => 'CFE rechazado correctamente.',
                'cfe' => $cfe
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar el CFE: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analizar un PDF para determinar si contiene un CFE.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function analizarCfe(Request $request)
    {
        try {
            $filepath = $request->input('filepath');
            $filename = $request->input('filename');

            // Verificar si el archivo existe
            if (!$filepath || !file_exists($filepath)) {
                return response()->json([
                    'es_cfe' => false,
                    'mensaje' => 'No se puede acceder al archivo: ' . $filepath
                ]);
            }

            // Usar el servicio para analizar el PDF
            $resultado = $this->cfeProcessorService->analizarPdf($filepath);

            return response()->json($resultado);
        } catch (\Exception $e) {
            return response()->json([
                'es_cfe' => false,
                'mensaje' => 'Error al analizar el PDF: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Crear un registro a partir de los datos del CFE.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function crearRegistro(Request $request)
    {
        try {
            $filepath = $request->input('filepath');
            $tipoCfe = $request->input('tipo_cfe');
            $datos = $request->input('datos');

            // Usar el servicio para crear el registro en el módulo correspondiente
            $resultado = $this->cfeProcessorService->crearRegistroDesdeAnalisis($tipoCfe, $datos, $filepath);

            return response()->json($resultado);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al crear el registro: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Analizar un PDF enviado directamente como archivo.
     * Este endpoint es usado por la extensión del navegador.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function analizarCfeConArchivo(Request $request)
    {
        try {
            $filepath = $request->input('filepath');
            $filename = $request->input('filename');
            $pdfFile = $request->file('pdf_file');

            // Si se recibió un archivo, guardarlo temporalmente
            if ($pdfFile && $pdfFile->isValid()) {
                // Guardar el archivo en storage/app/temp-cfe
                $tempPath = $pdfFile->store('temp-cfe');
                $fullPath = storage_path('app/' . $tempPath);

                // Usar el servicio para analizar el PDF
                $resultado = $this->cfeProcessorService->analizarPdf($fullPath);

                // Eliminar el archivo temporal
                unlink($fullPath);

                return response()->json($resultado);
            }

            // Fallback: intentar con la ruta si el archivo no se recibió
            if ($filepath && file_exists($filepath)) {
                $resultado = $this->cfeProcessorService->analizarPdf($filepath);
                return response()->json($resultado);
            }

            return response()->json([
                'es_cfe' => false,
                'mensaje' => 'No se pudo acceder al archivo. filepath: ' . ($filepath ?? 'no proporcionado')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'es_cfe' => false,
                'mensaje' => 'Error al analizar el PDF: ' . $e->getMessage()
            ]);
        }
    }
}
