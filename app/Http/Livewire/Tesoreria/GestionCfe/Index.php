<?php

namespace App\Http\Livewire\Tesoreria\GestionCfe;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Tesoreria\TesCfe;
use App\Models\Tesoreria\TesCfeItem;
use App\Models\Tesoreria\TesCfeMedioPago;
use App\Models\Tesoreria\CajaConcepto;
use App\Services\Tesoreria\CfeUniversalParserService;
use Illuminate\Support\Facades\DB;

class Index extends Component
{
    use WithPagination, WithFileUploads;

    /**
     * Listeners for Livewire events.
     */
    protected $listeners = ['borrarCfe'];

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $archivoPdf;

    // --- Estado del preview/confirmación ---
    public bool $mostrarModalConfirmacion = false;
    public array $datosExtraidos = [];
    public string $nombreArchivoOriginal = '';
    public string $rutaArchivoTemporal = '';
    public ?int $cajaConceptoSeleccionado = null;
    public ?int $siifDependenciaSeleccionado = 1; // Jefatura de Policía de Montevideo
    public array $itemDistribuciones = [];
    public ?int $filtroConcepto = null;
    public array $filtroMeses = [];
    public ?int $filtroAno = null;
 
    public function mount(): void
    {
        $this->filtroAno = (int) date('Y');
    }
 
    public function updatingSearch(): void
    {
        $this->resetPage();
    }
 
    public function updatingFiltroConcepto(): void
    {
        $this->resetPage();
    }
 
    public function updatingFiltroMeses(): void
    {
        $this->resetPage();
    }
 
    public function updatingFiltroAno(): void
    {
        $this->resetPage();
    }
 
    public function limpiarFiltroMeses(): void
    {
        $this->filtroMeses = [];
        $this->resetPage();
    }

    /**
     * Se dispara cuando el usuario selecciona un PDF.
     * Parsea el archivo y muestra el modal de confirmación
     * con los datos extraídos y el selector de concepto pre-seleccionado.
     */
    public function updatedArchivoPdf(): void
    {
        $this->validate([
            'archivoPdf' => 'required|mimes:pdf|max:5120',
        ]);

        try {
            $parser = app(CfeUniversalParserService::class);
            $datos = $parser->parsePdf($this->archivoPdf->getRealPath());
            $nombreOriginal = $this->archivoPdf->getClientOriginalName();

            // Guardar el PDF en temporal para usarlo después en la confirmación
            $path = $this->archivoPdf->storeAs('cfes_cargados', time() . '_' . $nombreOriginal, 'local');

            $this->datosExtraidos = $datos;
            $this->nombreArchivoOriginal = $nombreOriginal;
            $this->rutaArchivoTemporal = $path;

            // Pre-seleccionar el concepto de caja según el primer ítem extraído
            $this->cajaConceptoSeleccionado = $this->detectarConceptoAutomatico($datos);
 
            // Inicializar las distribuciones por cada ítem
            $this->itemDistribuciones = [];
            if (!empty($datos['items'])) {
                foreach ($datos['items'] as $index => $item) {
                    $this->itemDistribuciones[$index] = '';
                }
            }
 
            $this->mostrarModalConfirmacion = true;
            $this->dispatchBrowserEvent('abrir-modal-confirmacion-cfe');

        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'error',
                'title' => 'Error al procesar',
                'text' => 'Hubo un problema procesando el archivo: ' . $e->getMessage(),
            ]);
        }

        $this->reset('archivoPdf');
    }

    /**
     * Detecta automáticamente el concepto de caja según el primer ítem extraído.
     * Compara sin distinción de mayúsculas/minúsculas ni acentos.
     * Retorna el ID del concepto si coincide, o null si no hay coincidencia.
     */
    private function detectarConceptoAutomatico(array $datos): ?int
    {
        $primerDetalle = trim($datos['items'][0]['detalle'] ?? '');

        if (empty($primerDetalle)) {
            return null;
        }

        $conceptos = CajaConcepto::whereNull('deleted_at')->get();
        $detalleNorm = $this->normalizarTexto($primerDetalle);

        foreach ($conceptos as $concepto) {
            $conceptoNorm = $this->normalizarTexto($concepto->caja_concepto);

            // Coincidencia exacta o el detalle contiene completamente el texto del concepto
            if ($detalleNorm === $conceptoNorm || str_contains($detalleNorm, $conceptoNorm)) {
                return $concepto->id;
            }
        }

        return null;
    }

    /**
     * Normaliza un texto: minúsculas y sin acentos para comparación flexible.
     */
    private function normalizarTexto(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        $from = ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ', 'à', 'è', 'ì', 'ò', 'ù', 'â', 'ê', 'î', 'ô', 'û'];
        $to = ['a', 'e', 'i', 'o', 'u', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u'];
        return str_replace($from, $to, $texto);
    }

    /**
     * Confirma la carga del CFE y lo persiste en la base de datos.
     */
    public function confirmarCarga(bool $force = false): void
    {
        if (empty($this->cajaConceptoSeleccionado)) {
            $this->dispatchBrowserEvent('swal:toast-error', [
                'text' => 'Debe seleccionar un concepto de caja antes de confirmar.',
            ]);
            return;
        }

        $rules = [
            'cajaConceptoSeleccionado' => 'required|integer|min:1|exists:tes_caja_conceptos,id',
            'siifDependenciaSeleccionado' => 'nullable|integer|exists:siif_distribucion_dependencias,id',
        ];

        $cajaConcepto = CajaConcepto::find($this->cajaConceptoSeleccionado);
        $requiereDistribucion = $cajaConcepto ? $cajaConcepto->requiere_distribucion : false;

        if ($requiereDistribucion) {
            $hasMissingDistribution = false;
            if (!empty($this->datosExtraidos['items'])) {
                foreach ($this->datosExtraidos['items'] as $index => $item) {
                    if (empty($this->itemDistribuciones[$index])) {
                        $hasMissingDistribution = true;
                        break;
                    }
                }
            }

            if ($hasMissingDistribution) {
                $this->dispatchBrowserEvent('swal:toast-error', [
                    'text' => 'El concepto de caja seleccionado requiere asignar una distribución SIIF a cada uno de los ítems.',
                ]);
                return;
            }

            $rules['itemDistribuciones.*'] = 'required|integer|exists:siif_distribucions,id';
        } else {
            $rules['itemDistribuciones.*'] = 'nullable|integer|exists:siif_distribucions,id';
        }

        $this->validate($rules, [
            'cajaConceptoSeleccionado.required' => 'Debe seleccionar un concepto de caja antes de confirmar.',
            'cajaConceptoSeleccionado.min' => 'Debe seleccionar un concepto de caja válido.',
            'cajaConceptoSeleccionado.exists' => 'El concepto de caja seleccionado no existe.',
            'siifDependenciaSeleccionado.exists' => 'La dependencia de distribución SIIF seleccionada no existe.',
            'itemDistribuciones.*.required' => 'Debe seleccionar una distribución para todos los ítems.',
            'itemDistribuciones.*.exists' => 'La distribución SIIF seleccionada no existe.',
        ]);

        // Verificar si la referencia ya existe en otro CFE registrado
        $referencia = trim($this->datosExtraidos['referencias'] ?? '');
        if (!$force && $referencia !== '') {
            $cfeExistente = TesCfe::where('referencias', $referencia)->whereNull('deleted_at')->first();
            if ($cfeExistente) {
                $documentoIdentificador = "{$cfeExistente->documento_tipo} {$cfeExistente->documento_serie}-{$cfeExistente->documento_numero}";
                $this->dispatchBrowserEvent('swal:confirmar-guardar-referencia-duplicada', [
                    'documentoReferencia' => $referencia,
                    'documentoExistente' => $documentoIdentificador,
                ]);
                return;
            }
        }

        try {
            DB::beginTransaction();

            // Obtener el tipo de distribución SIIF desde el concepto de caja
            $cajaConcepto = CajaConcepto::find($this->cajaConceptoSeleccionado);
            $siifTipoId = $cajaConcepto ? $cajaConcepto->siif_distribucion_tipo_id : null;

            $datos = $this->datosExtraidos;

            $cfe = TesCfe::create([
                'emisor_nombre' => $datos['emisor_nombre'] ?? null,
                'emisor_direccion' => $datos['emisor_direccion'] ?? null,
                'emisor_localidad' => $datos['emisor_localidad'] ?? null,
                'emisor_telefono' => $datos['emisor_telefono'] ?? null,
                'emisor_correo' => $datos['emisor_correo'] ?? null,
                'emisor_ruc' => $datos['emisor_ruc'] ?? null,
                'documento_tipo' => $datos['documento_tipo'] ?? null,
                'documento_serie' => $datos['documento_serie'] ?? null,
                'documento_numero' => $datos['documento_numero'] ?? null,
                'forma_pago' => $datos['forma_pago'] ?? null,
                'vencimiento' => $datos['vencimiento'] ?? null,
                'comprobante_tipo' => $datos['comprobante_tipo'] ?? null,
                'receptor_documento_ruc' => $datos['receptor_documento_ruc'] ?? null,
                'receptor_nombre_denominacion' => $datos['receptor_nombre_denominacion'] ?? null,
                'receptor_domicilio_fiscal' => $datos['receptor_domicilio_fiscal'] ?? null,
                'periodo' => $datos['periodo'] ?? null,
                'nro_compra' => $datos['nro_compra'] ?? null,
                'fecha' => $datos['fecha'] ?? null,
                'moneda' => $datos['moneda'] ?? 'UYU',
                'monto_no_facturable' => $datos['monto_no_facturable'] ?? 0,
                'monto_total' => $datos['monto_total'] ?? 0,
                'total_a_pagar' => $datos['total_a_pagar'] ?? 0,
                'referencias' => $datos['referencias'] ?? null,
                'adenda' => $datos['adenda'] ?? null,
                'archivo_pdf_path' => storage_path('app/' . $this->rutaArchivoTemporal),
                'tes_caja_concepto_id' => $this->cajaConceptoSeleccionado,
                'siif_distribucion_tipo_id' => $siifTipoId,
                'siif_distribucion_dependencia_id' => $this->siifDependenciaSeleccionado,
            ]);

            if (!empty($datos['items'])) {
                foreach ($datos['items'] as $index => $item) {
                    TesCfeItem::create([
                        'tes_cfe_id' => $cfe->id,
                        'detalle' => $item['detalle'] ?? '',
                        'descripcion' => $item['descripcion'] ?? null,
                        'cantidad' => $item['cantidad'] ?? 1,
                        'precio' => $item['precio'] ?? 0,
                        'descuento' => $item['descuento'] ?? 0,
                        'recargo' => $item['recargo'] ?? 0,
                        'importe' => $item['importe'] ?? 0,
                        'siif_distribucion_id' => !empty($this->itemDistribuciones[$index]) ? (int) $this->itemDistribuciones[$index] : null,
                    ]);
                }
            }

            if (!empty($datos['medios_pago'])) {
                foreach ($datos['medios_pago'] as $mp) {
                    TesCfeMedioPago::create([
                        'tes_cfe_id' => $cfe->id,
                        'medio_pago_tipo' => $mp['tipo'] ?? 'Desconocido',
                        'medio_pago_valor' => $mp['valor'] ?? 0,
                    ]);
                }
            }

            DB::commit();

            $this->cancelarCarga();

            $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'success',
                'title' => 'CFE Procesado',
                'text' => "El archivo {$this->nombreArchivoOriginal} ha sido procesado y guardado correctamente.",
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'error',
                'title' => 'Error al guardar',
                'text' => 'Hubo un problema guardando el CFE: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Cancela la carga y resetea el estado del modal de confirmación.
     */
    public function cancelarCarga(): void
    {
        $this->mostrarModalConfirmacion = false;
        $this->datosExtraidos = [];
        $this->nombreArchivoOriginal = '';
        $this->rutaArchivoTemporal = '';
        $this->cajaConceptoSeleccionado = null;
        $this->siifDependenciaSeleccionado = null;
        $this->itemDistribuciones = [];
        $this->dispatchBrowserEvent('cerrar-modal-confirmacion-cfe');
    }

    /**
     * Elimina un CFE y sus relaciones.
     */
    public function borrarCfe(int $cfeId): void
    {
        $cfe = TesCfe::findOrFail($cfeId);
        $cfe->delete();

        $this->dispatchBrowserEvent('swal:modal', [
            'type' => 'success',
            'title' => 'CFE eliminado',
            'text' => 'El CFE ha sido eliminado correctamente.',
        ]);
    }

    public function render()
    {
        $cfes = TesCfe::with(['items', 'mediosPago', 'cajaConcepto', 'siifDistribucionTipo', 'siifDistribucionDependencia'])
            ->where(function ($query) {
                $query->where('emisor_nombre', 'like', '%' . $this->search . '%')
                    ->orWhere('documento_numero', 'like', '%' . $this->search . '%')
                    ->orWhere('receptor_documento_ruc', 'like', '%' . $this->search . '%')
                    ->orWhere('receptor_nombre_denominacion', 'like', '%' . $this->search . '%');
            });
 
        if ($this->filtroConcepto) {
            $cfes->where('tes_caja_concepto_id', $this->filtroConcepto);
        }
 
        if ($this->filtroAno) {
            $cfes->whereYear('fecha', $this->filtroAno);
        }
 
        if (!empty($this->filtroMeses)) {
            $cfes->where(function ($query) {
                foreach ($this->filtroMeses as $mes) {
                    $query->orWhereMonth('fecha', (int) $mes);
                }
            });
        }
 
        $cfes = $cfes->orderBy('id', 'desc')
            ->paginate(15);

        $cajaConceptos = CajaConcepto::whereNull('deleted_at')
            ->ordenado()
            ->get();

        $siifDependencias = \App\Models\Tesoreria\SiifDistribucionDependencia::whereNull('deleted_at')
            ->orderBy('dependencia')
            ->get();
 
        $distribuciones = [];
        if ($this->cajaConceptoSeleccionado && $this->siifDependenciaSeleccionado) {
            $cajaConcepto = CajaConcepto::find($this->cajaConceptoSeleccionado);
            if ($cajaConcepto && $cajaConcepto->siif_distribucion_tipo_id) {
                $distribuciones = \App\Models\Tesoreria\SiifDistribucion::where('tipo_id', $cajaConcepto->siif_distribucion_tipo_id)
                    ->where('dependencia_id', $this->siifDependenciaSeleccionado)
                    ->whereNull('deleted_at')
                    ->get()
                    ->unique(function ($item) {
                        return $item->tipo_id . '-' . $item->dependencia_id . '-' . $item->concepto;
                    });
            }
        }
 
        $currentYear = (int) date('Y');
        $anosRegistrados = TesCfe::whereNotNull('fecha')
            ->selectRaw('YEAR(fecha) as ano')
            ->distinct()
            ->orderBy('ano', 'desc')
            ->pluck('ano')
            ->map(fn($year) => (int) $year)
            ->toArray();
 
        if (!in_array($currentYear, $anosRegistrados)) {
            array_unshift($anosRegistrados, $currentYear);
        }
 
        return view('livewire.tesoreria.gestion-cfe.index', compact('cfes', 'cajaConceptos', 'siifDependencias', 'distribuciones', 'anosRegistrados'))
            ->extends('layouts.app')
            ->section('content');
    }
}
