<?php

namespace App\Livewire\Tesoreria\CargaMasivaHaberes;

use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbTipo;
use App\Models\Tesoreria\MedioDePago;
use App\Models\Tesoreria\LibroDiario;
use App\Services\Tesoreria\CargaMasivaHaberesService;
use App\Services\Tesoreria\LibroDiarioService;
use Livewire\Component;

class Index extends Component
{
    public string $ruta = 'C:\OFICINA\HABERES\INFO. CONTABILIDAD\LISTINES';

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
        $this->cargarOpcionesDetalle();
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
     * Autocomplete: lista las subcarpetas que coincidan con la ruta parcial que escribe el usuario.
     */
    public function updatedRuta(): void
    {
        $this->autocompletarRuta();
    }

    public function autocompletarRuta(): void
    {
        $this->sugerencias = [];
        $this->mostrarSugerencias = false;

        $ruta = str_replace('/', '\\', trim($this->ruta));

        if ($ruta === '') {
            return;
        }

        // Si la ruta termina en \ buscamos dentro de esa carpeta
        if (str_ends_with($ruta, '\\') && is_dir($ruta)) {
            $dir = rtrim($ruta, '\\');
            $filtro = '';
        } else {
            // Buscamos en el directorio padre con el último segmento como filtro
            $dir = dirname($ruta);
            $filtro = mb_strtolower(basename($ruta));
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

                $rutaCompleta = $dir . '\\' . $item;
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
    public function procesarGeneracion(bool $descartarDuplicados): void
    {
        $this->error = null;
        $this->mensajeExito = null;

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
    public function procesarGeneracionSinDetalle(): void
    {
        $this->error = null;
        $this->mensajeExito = null;

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

    public function render()
    {
        $detalleFiltrado = $this->procesado ? $this->filtrarDetalle() : [];

        return view('livewire.tesoreria.carga-masiva-haberes.index', compact(
            'detalleFiltrado'
        ))->extends('layouts.app');
    }
}
