<?php

namespace App\Livewire\Tesoreria\CargaMasivaHaberes;

use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbDetalle;
use App\Models\Tesoreria\LbTipo;
use App\Models\Tesoreria\MedioDePago;
use App\Models\Tesoreria\LibroDiario;
use App\Services\Tesoreria\CargaMasivaHaberesService;
use App\Services\Tesoreria\LibroDiarioService;
use App\Livewire\Concerns\NormalizaDocumentoReferencia;
use Livewire\Component;

class Index extends Component
{
    use NormalizaDocumentoReferencia;
    public string $ruta = '';

    public bool $cargando = false;
    public bool $procesado = false;
    public ?string $error = null;

    public array $resumen = [];
    public array $detalle = [];
    public array $totales = [];
    public array $errores = [];

    public string $filtro_mes = '';
    public string $filtro_tipo = '';
    public string $filtro_ventanilla = '';
    public string $buscar = '';

    /** Confirmar ingreso a caja al generar asientos */
    public bool $entrada_confirmada = false;

    /** Documento de referencia opcional para todos los asientos generados */
    public string $documento_referencia = '';

    public array $mesesDisponibles = [];
    public array $tiposDisponibles = [];

    /** Sugerencias de subcarpetas para autocompletado */
    public array $sugerencias = [];
    public bool $mostrarSugerencias = false;

    /** Selección de ítems (índice => bool) */
    public array $seleccionados = [];

    /** Detalle de libro diario asignado por ítem (índice => detalle_id) */
    public array $detalleAsignado = [];

    /** Descripción por ítem */
    public array $descripcionItem = [];

    /** Fecha para los asientos */
    public string $fechaAsiento = '';

    /** Opciones de detalle del concepto "Boletos en ventanilla" */
    public array $opcionesDetalle = [];

    /** Nombre para nuevo detalle a crear desde el modal */
    public string $nuevoDetalleNombre = '';

    public array $opcionesAdicionales = [];
    public array $adicionalesSeleccionados = [];

    /** Mensaje de éxito tras generar asientos */
    public ?string $mensajeExito = null;

    /** Detalle a asignar masivamente a todos los seleccionados */
    public string $detalleMasivo = '';

    /** Índices de ítems duplicados encontrados */
    public array $duplicadosEncontrados = [];

    /** Ítems sin detalle para alerta SweetAlert2 */
    public array $itemsSinDetallePendientes = [];
    public array $itemsConDetalleValidos = [];

    protected CargaMasivaHaberesService $service;
    protected LibroDiarioService $libroDiarioService;

    public function boot(CargaMasivaHaberesService $service, LibroDiarioService $libroDiarioService): void
    {
        $this->service = $service;
        $this->libroDiarioService = $libroDiarioService;
    }

    public function mount(): void
    {
        $this->fechaAsiento = now()->format('Y-m-d');
        $this->ruta = $this->obtenerRutaDefault();
        $this->cargarOpcionesDetalle();
    }

    /**
     * Obtiene la ruta inicial por defecto según el sistema operativo (Windows / Linux).
     * En ambos sistemas comienza en la carpeta de Documentos / Mis Documentos y dentro de ella en 'LISTINES'.
     * En Linux busca preferentemente /home/jpmontevideo/Documentos/LISTINES.
     */
    public function obtenerRutaDefault(): string
    {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWindows) {
            $userProfile = getenv('USERPROFILE') ?: (getenv('HOMEDRIVE') . getenv('HOMEPATH'));
            $candidatos = [];

            if ($userProfile) {
                $candidatos[] = $userProfile . DIRECTORY_SEPARATOR . 'Documents' . DIRECTORY_SEPARATOR . 'LISTINES';
                $candidatos[] = $userProfile . DIRECTORY_SEPARATOR . 'Documentos' . DIRECTORY_SEPARATOR . 'LISTINES';
                $candidatos[] = $userProfile . DIRECTORY_SEPARATOR . 'Mis Documentos' . DIRECTORY_SEPARATOR . 'LISTINES';
            }
            $candidatos[] = 'C:\\Users\\Usuario\\Documents\\LISTINES';

            foreach ($candidatos as $candidato) {
                if (is_dir($candidato)) {
                    return $candidato;
                }
            }

            if ($userProfile) {
                if (is_dir($userProfile . DIRECTORY_SEPARATOR . 'Documentos')) {
                    return $userProfile . DIRECTORY_SEPARATOR . 'Documentos' . DIRECTORY_SEPARATOR . 'LISTINES';
                }
                if (is_dir($userProfile . DIRECTORY_SEPARATOR . 'Mis Documentos')) {
                    return $userProfile . DIRECTORY_SEPARATOR . 'Mis Documentos' . DIRECTORY_SEPARATOR . 'LISTINES';
                }
                return $userProfile . DIRECTORY_SEPARATOR . 'Documents' . DIRECTORY_SEPARATOR . 'LISTINES';
            }

            return 'C:\\Users\\Usuario\\Documents\\LISTINES';
        } else {
            // Linux / Unix: /home/jpmontevideo/Documentos/LISTINES
            $home = getenv('HOME') ?: '/home/jpmontevideo';
            $candidatos = [
                '/home/jpmontevideo/Documentos/LISTINES',
                '/home/jpmontevideo/Documents/LISTINES',
                $home . '/Documentos/LISTINES',
                $home . '/Documents/LISTINES',
            ];

            foreach ($candidatos as $candidato) {
                if (is_dir($candidato)) {
                    return $candidato;
                }
            }

            return '/home/jpmontevideo/Documentos/LISTINES';
        }
    }

    /**
     * Carga los detalles del concepto "Boletos en ventanilla" desde la BD.
     */
    protected function cargarOpcionesDetalle(): void
    {
        $concepto = LbConcepto::where('nombre', 'like', '%Boletos en ventanilla%')->first();

        if ($concepto) {
            $this->opcionesDetalle = $concepto->detalles()
                ->activos()
                ->ordenado()
                ->get(['id', 'nombre'])
                ->toArray();
        }
    }

    /**
     * Autocomplete multiplataforma: lista las subcarpetas que coincidan con la ruta parcial que escribe el usuario.
     */
    public function updatedRuta(): void
    {
        $this->autocompletarRuta();
    }

    /**
     * Cuando se selecciona un detalle de Libro Diario para un ítem,
     * se establece consecuentemente el mismo detalle para los restantes ítems que no tengan
     * aún un detalle asignado y cuyos valores de TIPO + ARCHIVO + PAGO sean iguales.
     */
    public function updatedDetalleAsignado($value, $key): void
    {
        if (empty($value) || !isset($this->detalle[$key])) {
            return;
        }

        $itemBase = $this->detalle[$key];
        $tipoBase = $itemBase['tipo'] ?? null;
        $archivoBase = $itemBase['archivo'] ?? null;
        $pagoBase = $itemBase['es_ventanilla'] ?? null;

        foreach ($this->detalle as $otherIdx => $otherItem) {
            if ((string)$otherIdx === (string)$key) {
                continue;
            }

            // Solo aplicar a ítems que NO tengan aún un detalle asignado
            if (!empty($this->detalleAsignado[$otherIdx])) {
                continue;
            }

            // Comparar TIPO + ARCHIVO + PAGO
            if (
                ($otherItem['tipo'] ?? null) === $tipoBase &&
                ($otherItem['archivo'] ?? null) === $archivoBase &&
                ($otherItem['es_ventanilla'] ?? null) === $pagoBase
            ) {
                $this->detalleAsignado[$otherIdx] = $value;
            }
        }
    }

    public function autocompletarRuta(): void
    {
        $this->sugerencias = [];
        $this->mostrarSugerencias = false;

        $ruta = trim($this->ruta);
        if ($ruta === '') {
            return;
        }

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $ds = $isWindows ? '\\' : '/';
        $altDs = $isWindows ? '/' : '\\';

        // Normalizar separadores según el sistema operativo
        $rutaNorm = str_replace($altDs, $ds, $ruta);

        // Si la ruta termina en separador de directorios y es un directorio existente
        if ((str_ends_with($rutaNorm, '/') || str_ends_with($rutaNorm, '\\')) && is_dir($rutaNorm)) {
            $dir = rtrim($rutaNorm, '/\\');
            $filtro = '';
        } else {
            $dir = dirname($rutaNorm);
            $filtro = mb_strtolower(basename($rutaNorm));
        }

        // Si dirname() devolvió '.' o directorio no válido, intentar evaluar si $rutaNorm es un directorio existente completo
        if (!is_dir($dir) && is_dir($rutaNorm)) {
            $dir = rtrim($rutaNorm, '/\\');
            $filtro = '';
        }

        if (!is_dir($dir)) {
            return;
        }

        try {
            $items = @scandir($dir);
            if ($items === false) {
                return;
            }

            $resultados = [];
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }

                $rutaCompleta = rtrim($dir, '/\\') . $ds . $item;
                if (!is_dir($rutaCompleta)) {
                    continue;
                }

                if ($filtro !== '' && !str_contains(mb_strtolower($item), $filtro)) {
                    continue;
                }

                $resultados[] = $rutaCompleta;
                if (count($resultados) >= 50) {
                    break;
                }
            }

            if (!empty($resultados)) {
                $this->sugerencias = $resultados;
                $this->mostrarSugerencias = true;
            }
        } catch (\Throwable) {
            // silenciar errores de lectura de disco
        }
    }

    /**
     * Selecciona una sugerencia del autocompletado.
     */
    public function seleccionarSugerencia(string $carpeta): void
    {
        $this->ruta = $carpeta;
        $this->sugerencias = [];
        $this->mostrarSugerencias = false;
    }

    /**
     * Oculta las sugerencias (se llama al hacer clic fuera).
     */
    public function ocultarSugerencias(): void
    {
        $this->mostrarSugerencias = false;
    }

    public function procesar(): void
    {
        $this->validate([
            'ruta' => 'required|string',
        ]);

        $this->cargando = true;
        $this->error = null;
        $this->procesado = false;
        $this->mensajeExito = null;
        $this->seleccionados = [];
        $this->detalleAsignado = [];
        $this->descripcionItem = [];

        try {
            $result = $this->service->procesarCarpeta($this->ruta);

            $this->resumen = $result['resumen'];
            $this->detalle = $result['detalle'];
            $this->totales = $result['totales'];
            $this->errores = $result['errores'];
            $this->procesado = true;

            $this->mesesDisponibles = array_unique(array_column($this->resumen, 'mes'));
            $this->tiposDisponibles = array_unique(array_column($this->resumen, 'tipo'));

            // Inicializar selección, detalle y descripción para cada ítem
            foreach ($this->detalle as $idx => $d) {
                $this->seleccionados[$idx] = false;
                $this->detalleAsignado[$idx] = '';
                $this->descripcionItem[$idx] = '';
            }

            if (empty($this->resumen) && empty($this->errores)) {
                $this->error = 'No se encontraron archivos Excel válidos en la carpeta especificada.';
                $this->dispatch('alert', ['type' => 'error', 'message' => $this->error, 'toast' => true]);
            } elseif (!empty($this->errores)) {
                $this->dispatch('alert', ['type' => 'warning', 'message' => count($this->errores) . ' archivo(s) tuvieron errores al procesarse. Revise la tabla de errores.', 'toast' => true]);
            }
        } catch (\Exception $e) {
            $this->error = 'Error al procesar: ' . $e->getMessage();
            $this->dispatch('alert', ['type' => 'error', 'message' => $this->error, 'toast' => true]);
        }

        $this->cargando = false;
    }

    /**
     * Seleccionar/deseleccionar todos los ítems visibles (filtrados).
     */
    public function seleccionarTodos(bool $valor): void
    {
        $filtrados = $this->filtrarDetalle();
        foreach ($filtrados as $d) {
            $this->seleccionados[$d['_idx']] = $valor;
        }
    }

    /**
     * Genera los asientos en el libro diario para los ítems seleccionados.
     */
    public function generarAsientos(): void
    {
        $this->error = null;
        $this->mensajeExito = null;

        // Validar fecha
        if (empty($this->fechaAsiento)) {
            $this->error = 'Debe seleccionar una fecha para los asientos.';
            $this->dispatch('alert', ['type' => 'error', 'message' => $this->error, 'toast' => true]);
            return;
        }

        // Advertir si hay más de un ítem seleccionado y no se ingresó Documento de referencia
        $cantidadSeleccionados = count(array_filter($this->seleccionados));
        if ($cantidadSeleccionados > 1 && empty(trim($this->documento_referencia ?? ''))) {
            $this->dispatch('swal:sin-documento-referencia', [
                'cantidad' => $cantidadSeleccionados,
            ]);
            return;
        }

        $this->ejecutarGeneracionAsientos();
    }

    /**
     * Continúa la generación de asientos sin documento de referencia (tras confirmación del usuario).
     */
    public function confirmarGeneracionSinDocumento(): void
    {
        $this->ejecutarGeneracionAsientos();
    }

    /**
     * Lógica central de generación de asientos reutilizable.
     */
    private function ejecutarGeneracionAsientos(): void
    {
        $this->error = null;

        // Obtener IDs fijos
        $tipoEntrada = LbTipo::where('nombre', 'Entrada')->first();
        if (!$tipoEntrada) {
            $this->error = 'No se encontró el tipo "Entrada" en la base de datos.';
            $this->dispatch('alert', ['type' => 'error', 'message' => $this->error, 'toast' => true]);
            return;
        }

        $concepto = LbConcepto::where('nombre', 'like', '%Boletos en ventanilla%')->first();
        if (!$concepto) {
            $this->error = 'No se encontró el concepto "Boletos en ventanilla" en la base de datos.';
            $this->dispatch('alert', ['type' => 'error', 'message' => $this->error, 'toast' => true]);
            return;
        }

        $medioEfectivo = MedioDePago::where('nombre', 'Efectivo')->first();
        if (!$medioEfectivo) {
            $this->error = 'No se encontró el medio "Efectivo" en la base de datos.';
            $this->dispatch('alert', ['type' => 'error', 'message' => $this->error, 'toast' => true]);
            return;
        }

        // Recopilar ítems seleccionados y validar
        $itemsParaGenerar = [];
        $itemsSinDetalle = [];
        foreach ($this->seleccionados as $idx => $seleccionado) {
            if (!$seleccionado || !isset($this->detalle[$idx])) {
                continue;
            }

            $detalleId = $this->detalleAsignado[$idx] ?? '';
            if (empty($detalleId)) {
                $itemsSinDetalle[] = [
                    'idx' => $idx,
                    'ci' => $this->detalle[$idx]['ci'],
                    'nombre' => $this->detalle[$idx]['nombre'],
                    'monto' => $this->detalle[$idx]['monto'],
                ];
                continue;
            }

            $itemsParaGenerar[] = [
                'idx' => $idx,
                'detalle_id' => (int) $detalleId,
                'data' => $this->detalle[$idx],
                'descripcion' => $this->descripcionItem[$idx] ?? '',
            ];
        }

        // Si hay ítems sin detalle, mostrar advertencia con SweetAlert2
        if (!empty($itemsSinDetalle)) {
            // Si NO hay ítems válidos, mostrar error directo
            if (empty($itemsParaGenerar)) {
                $this->error = 'Ninguno de los ítems seleccionados tiene un detalle asignado en el Libro Diario.';
                $this->dispatch('alert', ['type' => 'error', 'message' => $this->error, 'toast' => true]);
                return;
            }

            $this->itemsSinDetallePendientes = $itemsSinDetalle;
            $this->itemsConDetalleValidos = $itemsParaGenerar;

            $htmlItems = '<div class="text-left" style="max-height: 300px; overflow-y: auto;">';
            $htmlItems .= '<p><strong>Los siguientes ítems no tienen un detalle asignado en el Libro Diario:</strong></p>';
            $htmlItems .= '<table class="table table-sm table-bordered mb-2" style="font-size: 0.8rem;">';
            $htmlItems .= '<thead><tr><th>C.I.</th><th>Nombre</th><th class="text-right">Monto</th></tr></thead><tbody>';
            foreach ($itemsSinDetalle as $item) {
                $htmlItems .= '<tr>';
                $htmlItems .= '<td>' . e($item['ci']) . '</td>';
                $htmlItems .= '<td>' . e($item['nombre']) . '</td>';
                $htmlItems .= '<td class="text-right">$' . number_format($item['monto'], 0, ',', '.') . '</td>';
                $htmlItems .= '</tr>';
            }
            $htmlItems .= '</tbody></table>';
            $htmlItems .= '<p class="small text-muted mb-1">Estos ítems serán omitidos al generar los asientos.</p>';
            $htmlItems .= '<p><strong>¿Desea continuar con los ' . count($itemsParaGenerar) . ' ítems válidos?</strong></p>';
            $htmlItems .= '</div>';

            $this->dispatch('swal:items-sin-detalle', [
                'html' => $htmlItems,
                'cantidadSinDetalle' => count($itemsSinDetalle),
                'cantidadConDetalle' => count($itemsParaGenerar),
            ]);
            return;
        }

        if (empty($itemsParaGenerar)) {
            $this->error = 'No hay ítems seleccionados para generar asientos.';
            $this->dispatch('alert', ['type' => 'error', 'message' => $this->error, 'toast' => true]);
            return;
        }

        // Detectar duplicados en Libro Diario
        $this->duplicadosEncontrados = [];
        foreach ($itemsParaGenerar as $item) {
            $existe = LibroDiario::where('identidad', $item['data']['ci'])
                ->where('denominacion', $item['data']['nombre'])
                ->where('concepto_id', $concepto->id)
                ->where('detalle_id', $item['detalle_id'])
                ->where('monto', $item['data']['monto'])
                ->exists();

            if ($existe) {
                $this->duplicadosEncontrados[] = $item['idx'];
            }
        }

        if (!empty($this->duplicadosEncontrados)) {
            $this->dispatch('swal:confirmar-duplicados', [
                'cantidad' => count($this->duplicadosEncontrados),
            ]);
            return;
        }

        $this->procesarGeneracion(false);
    }

    /**
     * Ejecuta la generación de asientos, descartando o no los duplicados detectados.
     */
    #[On('procesar-generacion')]
    public function procesarGeneracion(bool $descartarDuplicados = false): void
    {
        $this->error = null;
        $this->mensajeExito = null;
        $this->normalizarDocumentoReferencia();

        $tipoEntrada = LbTipo::where('nombre', 'Entrada')->first();
        $concepto = LbConcepto::where('nombre', 'like', '%Boletos en ventanilla%')->first();
        $medioEfectivo = MedioDePago::where('nombre', 'Efectivo')->first();

        $creados = 0;
        $erroresGen = [];

        foreach ($this->seleccionados as $idx => $seleccionado) {
            if (!$seleccionado || !isset($this->detalle[$idx])) {
                continue;
            }

            if ($descartarDuplicados && in_array($idx, $this->duplicadosEncontrados)) {
                $this->seleccionados[$idx] = false;
                continue;
            }

            $detalleId = $this->detalleAsignado[$idx] ?? '';
            if (empty($detalleId)) continue;

            try {
                $this->libroDiarioService->registrarAsiento([
                    'fecha' => $this->fechaAsiento,
                    'tipo_id' => $tipoEntrada->id,
                    'signo_efectivo' => 1,
                    'identidad' => $this->detalle[$idx]['ci'],
                    'denominacion' => $this->detalle[$idx]['nombre'],
                    'descripcion' => ($this->descripcionItem[$idx] ?? '') ?: null,
                    'concepto_id' => $concepto->id,
                    'detalle_id' => (int) $detalleId,
                    'medio_id' => $medioEfectivo->id,
                    'monto' => $this->detalle[$idx]['monto'],
                    'asociar' => null,
                    'confirmado' => $this->entrada_confirmada,
                    'documento_referencia' => $this->documento_referencia ?: null,
                ]);

                $creados++;

                $this->seleccionados[$idx] = false;
            } catch (\Exception $e) {
                $erroresGen[] = "{$this->detalle[$idx]['nombre']}: {$e->getMessage()}";
            }
        }

        if (!empty($erroresGen)) {
            $this->error = "Se crearon {$creados} asientos, pero hubo errores:\n" . implode("\n", $erroresGen);
            $this->dispatch('alert', ['type' => 'error', 'message' => "Se crearon {$creados} asientos, pero hubo errores en algunos.", 'toast' => true]);
        } else {
            $this->mensajeExito = "Se generaron {$creados} asientos correctamente en el Libro Diario.";
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->mensajeExito, 'toast' => true]);
        }
    }

    /**
     * Genera asientos descartando los ítems sin detalle asignado.
     * Invocado desde SweetAlert2 cuando el usuario confirma continuar.
     */
    #[On('procesar-generacion-sin-detalle')]
    public function procesarGeneracionSinDetalle(): void
    {
        $this->error = null;
        $this->mensajeExito = null;
        $this->normalizarDocumentoReferencia();

        $tipoEntrada = LbTipo::where('nombre', 'Entrada')->first();
        $concepto = LbConcepto::where('nombre', 'like', '%Boletos en ventanilla%')->first();
        $medioEfectivo = MedioDePago::where('nombre', 'Efectivo')->first();

        if (!$tipoEntrada || !$concepto || !$medioEfectivo) {
            $this->error = 'No se encontraron los registros base (tipo, concepto o medio de pago).';
            $this->dispatch('alert', ['type' => 'error', 'message' => $this->error, 'toast' => true]);
            return;
        }

        // Usar los ítems válidos pre-filtrados
        $itemsParaGenerar = $this->itemsConDetalleValidos;
        $itemsOmitidos = $this->itemsSinDetallePendientes;

        // Detectar duplicados entre los ítems válidos
        $duplicados = [];
        foreach ($itemsParaGenerar as $item) {
            $existe = LibroDiario::where('identidad', $item['data']['ci'])
                ->where('denominacion', $item['data']['nombre'])
                ->where('concepto_id', $concepto->id)
                ->where('detalle_id', $item['detalle_id'])
                ->where('monto', $item['data']['monto'])
                ->exists();

            if ($existe) {
                $duplicados[] = $item['idx'];
            }
        }

        // Si hay duplicados, despachar evento de confirmación
        if (!empty($duplicados)) {
            $this->duplicadosEncontrados = $duplicados;
            $this->dispatch('swal:confirmar-duplicados', [
                'cantidad' => count($duplicados),
            ]);
            return;
        }

        // Generar asientos
        $creados = 0;
        $erroresGen = [];

        foreach ($itemsParaGenerar as $item) {
            $idx = $item['idx'];
            $detalleId = $item['detalle_id'];

            try {
                $this->libroDiarioService->registrarAsiento([
                    'fecha' => $this->fechaAsiento,
                    'tipo_id' => $tipoEntrada->id,
                    'signo_efectivo' => 1,
                    'identidad' => $item['data']['ci'],
                    'denominacion' => $item['data']['nombre'],
                    'descripcion' => ($item['descripcion'] ?: null),
                    'concepto_id' => $concepto->id,
                    'detalle_id' => $detalleId,
                    'medio_id' => $medioEfectivo->id,
                    'monto' => $item['data']['monto'],
                    'asociar' => null,
                    'confirmado' => $this->entrada_confirmada,
                    'documento_referencia' => $this->documento_referencia ?: null,
                ]);

                $creados++;
                $this->seleccionados[$idx] = false;
            } catch (\Exception $e) {
                $erroresGen[] = "{$item['data']['nombre']}: {$e->getMessage()}";
            }
        }

        // Limpiar datos temporales
        $this->itemsSinDetallePendientes = [];
        $this->itemsConDetalleValidos = [];

        $mensajeOmitidos = count($itemsOmitidos) > 0
            ? " Se omitieron " . count($itemsOmitidos) . " ítems sin detalle."
            : '';

        if (!empty($erroresGen)) {
            $this->error = "Se crearon {$creados} asientos, pero hubo errores:\n" . implode("\n", $erroresGen) . $mensajeOmitidos;
            $this->dispatch('alert', ['type' => 'error', 'message' => "Se crearon {$creados} asientos, pero hubo errores en algunos.", 'toast' => true]);
        } else {
            $this->mensajeExito = "Se generaron {$creados} asientos correctamente en el Libro Diario." . $mensajeOmitidos;
            $this->dispatch('alert', ['type' => 'success', 'message' => $this->mensajeExito, 'toast' => true]);
        }
    }

    public function filtrarDetalle(): array
    {
        $detalle = $this->detalle;

        // Agregar índice original a cada ítem para mantener referencia
        foreach ($detalle as $idx => &$d) {
            $d['_idx'] = $idx;
        }
        unset($d);

        if ($this->filtro_mes) {
            $detalle = array_filter($detalle, fn($d) => $d['mes'] === $this->filtro_mes);
        }
        if ($this->filtro_tipo) {
            $detalle = array_filter($detalle, fn($d) => $d['tipo'] === $this->filtro_tipo);
        }
        if ($this->filtro_ventanilla !== '') {
            $esVent = $this->filtro_ventanilla === '1';
            $detalle = array_filter($detalle, fn($d) => $d['es_ventanilla'] === $esVent);
        }
        if ($this->buscar) {
            $q = mb_strtoupper($this->buscar);
            $detalle = array_filter($detalle, fn($d) =>
                str_contains(mb_strtoupper($d['ci']), $q) ||
                str_contains(mb_strtoupper($d['nombre']), $q)
            );
        }

        return array_values($detalle);
    }

    /**
     * Cuenta cuántos ítems están seleccionados.
     */
    public function getCantidadSeleccionadosProperty(): int
    {
        return count(array_filter($this->seleccionados));
    }

    /**
     * Suma de montos de los ítems seleccionados.
     */
    public function getTotalSeleccionadoProperty(): float
    {
        $total = 0;
        foreach ($this->seleccionados as $idx => $seleccionado) {
            if ($seleccionado && isset($this->detalle[$idx])) {
                $total += $this->detalle[$idx]['monto'];
            }
        }
        return round($total, 2);
    }

    /**
     * Suma total de TOTAL VENTANILLA extraído únicamente de los archivos
     * que tienen al menos un ítem seleccionado.
     */
    public function getTotalVentanillaExcelProperty(): float
    {
        $archivosConSeleccion = [];
        foreach ($this->seleccionados as $idx => $seleccionado) {
            if ($seleccionado && isset($this->detalle[$idx])) {
                $archivosConSeleccion[$this->detalle[$idx]['archivo']] = true;
            }
        }

        if (empty($archivosConSeleccion)) {
            return 0;
        }

        $total = 0;
        foreach ($this->resumen as $entry) {
            if (isset($archivosConSeleccion[$entry['archivo']])) {
                $total += $entry['total_ventanilla_excel'] ?? 0;
            }
        }
        return round($total, 2);
    }

    /**
     * Indica si todos los ítems seleccionados pertenecen al mismo tipo y archivo.
     */
    public function getPuedeAsignarDetalleMasivoProperty(): bool
    {
        $selected = array_filter($this->seleccionados);
        if (count($selected) < 2) return false;

        $tipos = [];
        $archivos = [];
        foreach ($selected as $idx => $val) {
            if (!isset($this->detalle[$idx])) continue;
            $tipos[$this->detalle[$idx]['tipo']] = true;
            $archivos[$this->detalle[$idx]['archivo']] = true;
        }

        return count($tipos) === 1 && count($archivos) === 1;
    }

    /**
     * Asigna el detalle seleccionado a todos los ítems marcados.
     */
    public function asignarDetalleMasivo($detalleId): void
    {
        if (!$detalleId) return;

        foreach ($this->seleccionados as $idx => $seleccionado) {
            if ($seleccionado && isset($this->detalle[$idx])) {
                $this->detalleAsignado[$idx] = $detalleId;
            }
        }
    }

    /**
     * Devuelve las plantillas de variantes adicionales para el concepto Boletos en ventanilla.
     */
    private function getOpcionesAdicionales(): array
    {
        return [
            '{detalle} (rechazo BROU)',
            '{detalle} (rechazo otros bancos)',
            '{detalle} (con quitas)',
            'Retención Judicial de {detalle}',
            'Retención Judicial de {detalle} (rechazo BROU)',
            'Retención Judicial de {detalle} (rechazo otros bancos)',
            'Retención Judicial de {detalle} (con quitas)',
            'Aguinaldo de {detalle}',
            'Aguinaldo de {detalle} (rechazo BROU)',
            'Aguinaldo de {detalle} (rechazo otros bancos)',
            'Aguinaldo de {detalle} (con quitas)',
            'Retención Judicial de Aguinaldo de {detalle}',
            'Retención Judicial de Aguinaldo de {detalle} (rechazo BROU)',
            'Retención Judicial de Aguinaldo de {detalle} (rechazo otros bancos)',
            'Retención Judicial de Aguinaldo de {detalle} (con quitas)',
        ];
    }

    /**
     * Selecciona o deselecciona todas las opciones adicionales de variantes.
     */
    public function seleccionarTodasAdicionales(bool $valor): void
    {
        $this->adicionalesSeleccionados = array_fill(0, count($this->opcionesAdicionales), $valor);
    }

    /**
     * Abre el modal para crear un nuevo detalle de Libro Diario con sus variantes.
     */
    public function abrirModalNuevoDetalle(): void
    {
        $this->resetErrorBag();
        $this->nuevoDetalleNombre = '';
        $this->opcionesAdicionales = $this->getOpcionesAdicionales();
        $this->adicionalesSeleccionados = array_fill(0, count($this->opcionesAdicionales), false);
        $this->dispatch('show-modal', id: 'modalNuevoDetalle');
    }

    /**
     * Guarda el detalle principal y las variantes adicionales seleccionadas asociadas al concepto "Boletos en ventanilla".
     */
    public function guardarNuevoDetalle(): void
    {
        $this->validate([
            'nuevoDetalleNombre' => 'required|string|max:100',
        ], [
            'nuevoDetalleNombre.required' => 'El nombre del detalle es obligatorio.',
            'nuevoDetalleNombre.max' => 'El nombre no puede superar los 100 caracteres.',
        ]);

        $concepto = LbConcepto::where('nombre', 'like', '%Boletos en ventanilla%')->first();

        if (!$concepto) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'No se encontró el concepto "Boletos en ventanilla".',
                'toast' => true,
            ]);
            return;
        }

        $nombre = trim($this->nuevoDetalleNombre);
        $creados = 0;
        $yaExistentes = 0;
        $nuevoId = null;

        // Crear o encontrar el detalle principal
        $detallePrincipal = LbDetalle::where('concepto_id', $concepto->id)
            ->where('nombre', $nombre)
            ->first();

        if (!$detallePrincipal) {
            $detallePrincipal = LbDetalle::create([
                'concepto_id' => $concepto->id,
                'nombre' => $nombre,
            ]);
            $creados++;
        } else {
            $yaExistentes++;
        }
        $nuevoId = $detallePrincipal->id;

        // Crear variantes adicionales seleccionadas
        if (!empty($this->adicionalesSeleccionados)) {
            foreach ($this->adicionalesSeleccionados as $idx => $seleccionado) {
                if ($seleccionado && isset($this->opcionesAdicionales[$idx])) {
                    $nombreAdicional = str_replace('{detalle}', $nombre, $this->opcionesAdicionales[$idx]);

                    if (!LbDetalle::where('concepto_id', $concepto->id)->where('nombre', $nombreAdicional)->exists()) {
                        LbDetalle::create([
                            'concepto_id' => $concepto->id,
                            'nombre' => $nombreAdicional,
                        ]);
                        $creados++;
                    } else {
                        $yaExistentes++;
                    }
                }
            }
        }

        // Recargar opciones de detalle para que estén disponibles en el selector
        $this->cargarOpcionesDetalle();

        $msg = "Se crearon {$creados} registro(s)";
        if ($yaExistentes > 0) {
            $msg .= ", {$yaExistentes} ya existían y fueron omitidos";
        }
        $msg .= '.';

        $this->dispatch('alert', [
            'type' => $creados > 0 ? 'success' : 'info',
            'message' => $msg,
            'toast' => true,
        ]);

        $this->nuevoDetalleNombre = '';
        $this->opcionesAdicionales = [];
        $this->adicionalesSeleccionados = [];
        $this->dispatch('hide-modal', id: 'modalNuevoDetalle');
    }

    public function render()
    {
        $detalleFiltrado = $this->procesado ? $this->filtrarDetalle() : [];

        return view('livewire.tesoreria.carga-masiva-haberes.index', compact(
            'detalleFiltrado'
        ))->extends('layouts.app');
    }
}
