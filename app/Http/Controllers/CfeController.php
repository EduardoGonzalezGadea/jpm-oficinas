<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcesarCfeRequest;
use App\Repositories\CfePendienteRepository;
use App\Services\CfeProcessorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CfeController extends Controller
{
    public function __construct(
        private readonly CfeProcessorService    $cfeProcessorService,
        private readonly CfePendienteRepository $repository
    ) {}

    /**
     * Procesa un CFE enviado desde la extensión del navegador y lo persiste como pendiente.
     */
    public function procesarCfe(ProcesarCfeRequest $request): JsonResponse
    {
        try {
            $cfePendiente = $this->cfeProcessorService->procesarPdf(
                $request->file('pdf_file'),
                $request->source_url,
                $request->user()->id
            );

            return response()->json([
                'success'       => true,
                'message'       => 'CFE recibido y almacenado correctamente.',
                'cfe_pendiente' => $cfePendiente,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el CFE: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lista todos los CFEs en estado "pendiente".
     */
    public function pendientes(): JsonResponse
    {
        try {
            $pendientes = $this->repository->buscarPorEstado('pendiente');
            return response()->json($pendientes);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener CFEs pendientes: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirma un CFE pendiente registrando quién lo procesó y cuándo.
     */
    public function confirmarCfe(int $id): JsonResponse
    {
        try {
            $cfe = $this->repository->buscarPorId($id);

            if (!$cfe || $cfe->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'CFE no encontrado o no está pendiente.',
                ], 404);
            }

            $cfe->estado        = 'confirmado';
            $cfe->procesado_por = auth()->id();
            $cfe->procesado_at  = now();
            $cfe->save();

            return response()->json([
                'success' => true,
                'message' => 'CFE confirmado correctamente.',
                'cfe'     => $cfe,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar el CFE: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rechaza un CFE pendiente registrando el motivo.
     */
    public function rechazarCfe(int $id, Request $request): JsonResponse
    {
        try {
            $cfe = $this->repository->buscarPorId($id);

            if (!$cfe || $cfe->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'CFE no encontrado o no está pendiente.',
                ], 404);
            }

            $cfe->estado         = 'rechazado';
            $cfe->motivo_rechazo = $request->input('motivo');
            $cfe->save();

            return response()->json([
                'success' => true,
                'message' => 'CFE rechazado correctamente.',
                'cfe'     => $cfe,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar el CFE: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Analiza un PDF por ruta local y retorna si es CFE + datos extraídos.
     * Usado por la extensión del navegador para previsualizar antes de confirmar.
     */
    public function analizarCfe(Request $request): JsonResponse
    {
        try {
            $filepath = $request->input('filepath');

            if (!$filepath || !file_exists($filepath)) {
                return response()->json([
                    'es_cfe'  => false,
                    'mensaje' => 'No se puede acceder al archivo: ' . $filepath,
                ]);
            }

            return response()->json($this->cfeProcessorService->analizarPdf($filepath));
        } catch (\Exception $e) {
            return response()->json([
                'es_cfe'  => false,
                'mensaje' => 'Error al analizar el PDF: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Analiza un PDF enviado como archivo (upload) o por ruta.
     * Endpoint primario de la extensión del navegador.
     */
    public function analizarCfeConArchivo(Request $request): JsonResponse
    {
        try {
            $pdfFile = $request->file('pdf_file');

            if ($pdfFile && $pdfFile->isValid()) {
                $tempPath = $pdfFile->store('temp-cfe');
                $fullPath = storage_path('app/' . $tempPath);

                $resultado = $this->cfeProcessorService->analizarPdf($fullPath);
                @unlink($fullPath);

                return response()->json($resultado);
            }

            $filepath = $request->input('filepath');
            if ($filepath && file_exists($filepath)) {
                return response()->json($this->cfeProcessorService->analizarPdf($filepath));
            }

            return response()->json([
                'es_cfe'  => false,
                'mensaje' => 'No se pudo acceder al archivo. filepath: ' . ($filepath ?? 'no proporcionado'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'es_cfe'  => false,
                'mensaje' => 'Error al analizar el PDF: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Prepara un registro desde el análisis previo y retorna la URL de redirección
     * al formulario del módulo correspondiente (con datos pre-cargados en caché).
     */
    public function crearRegistro(Request $request): JsonResponse
    {
        try {
            $resultado = $this->cfeProcessorService->crearRegistroDesdeAnalisis(
                $request->input('tipo_cfe'),
                $request->input('datos', []),
                $request->input('filepath')
            );

            return response()->json($resultado);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al crear el registro: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Registra automáticamente una multa desde los datos del CFE y retorna la URL
     * de redirección al índice de multas con el modal de edición abierto.
     */
    public function registrarMultaAuto(Request $request): JsonResponse
    {
        try {
            $cobro = $this->cfeProcessorService->registrarMultaAuto(
                $request->input('datos', []),
                auth()->id() ?? 1
            );

            session()->flash('message', 'Multa registrada automáticamente desde CFE.');

            return response()->json([
                'success'      => true,
                'redirect_url' => route('tesoreria.multas-cobradas.index', ['edit_id' => $cobro->id]),
                'mensaje'      => 'Multa registrada correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al registrar multa: ' . $e->getMessage(),
            ]);
        }
    }
}
