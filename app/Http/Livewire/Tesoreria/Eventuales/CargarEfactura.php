<?php

namespace App\Http\Livewire\Tesoreria\Eventuales;

use Livewire\Component;
use Livewire\WithFileUploads;
use Smalot\PdfParser\Parser;
use App\Models\Tesoreria\Eventual;
use App\Models\Tesoreria\EventualInstitucion;
use App\Models\Tesoreria\MedioDePago;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Traits\WithOrdenCobroValidation;

class CargarEfactura extends Component
{
    use WithFileUploads, WithOrdenCobroValidation;

    public $archivo;
    public $datosExtraidos = null;
    public $mensajeError = null;
    public $anulacionPendiente = null;

    protected $rules = [
        'archivo' => 'required|mimes:pdf|max:10240', // 10MB max
    ];

    public function mount()
    {
        // 1. Intentar cargar desde CACHÉ (Nuevo método más seguro)
        $prefillId = request()->query('prefill_id');
        if ($prefillId && \Illuminate\Support\Facades\Cache::has('cfe_prefill_' . $prefillId)) {
            $cacheData = \Illuminate\Support\Facades\Cache::get('cfe_prefill_' . $prefillId);
            $data = $cacheData['datos'];

            $this->datosExtraidos = [
                'titular' => $data['titular'] ?? ($data['receptor_nombre'] ?? ''),
                'fecha' => $data['fecha'] ?? '',
                'monto' => $data['monto'] ?? 0.0,
                'medio_de_pago' => $this->normalizarMedioPago($data['medio_de_pago'] ?? ''),
                'detalle' => $data['detalle'] ?? '',
                'orden_cobro' => $data['orden_cobro'] ?? '',
                'recibo' => $data['recibo'] ?? (($data['serie'] ?? '') . '-' . ($data['numero'] ?? '')),
                'ingreso' => $data['ingreso'] ?? $data['ingreso_contabilidad'] ?? '',
                'institucion' => $data['institucion'] ?? $this->detectarInstitucion($data['titular'] ?? ($data['receptor_nombre'] ?? '')),
            ];

            \Illuminate\Support\Facades\Cache::forget('cfe_prefill_' . $prefillId);
            return;
        }

        // 2. Fallback: Verificar si hay datos pre-cargados desde la sesión
        if (session()->has('cfe_datos_precargados') && session('cfe_tipo') === 'eventuales') {
            $data = session('cfe_datos_precargados');

            // Mapear campos genéricos a lo que espera este Livewire
            $this->datosExtraidos = [
                'titular' => $data['receptor_nombre'] ?? $data['titular'] ?? '',
                'fecha' => $data['fecha'] ?? '',
                'monto' => $data['monto'] ?? 0.0,
                'medio_de_pago' => $this->normalizarMedioPago($data['medio_de_pago'] ?? ''),
                'detalle' => $data['detalle'] ?? '',
                'orden_cobro' => $data['orden_cobro'] ?? '',
                'recibo' => $data['recibo'] ?? (($data['serie'] ?? '') . '-' . ($data['numero'] ?? '')),
                'ingreso' => $data['ingreso'] ?? $data['ingreso_contabilidad'] ?? '',
                'institucion' => $data['institucion'] ?? $this->detectarInstitucion($data['receptor_nombre'] ?? $data['titular'] ?? ''),
            ];

            // Limpiar sesión después de cargar
            session()->forget(['cfe_datos_precargados', 'cfe_tipo', 'cfe_filepath']);
        }
    }

    public function updatedArchivo()
    {
        $this->validate();
        $this->procesarArchivo();
    }

    public function procesarArchivo()
    {
        $this->datosExtraidos = null;
        $this->mensajeError = null;

        try {
            $text = app(\App\Services\CfeProcessorService::class)->parsearPdf($this->archivo->getRealPath());

            // Usar el extractor especializado para obtener datos robustos
            $extractor = new \App\Services\CfeExtractor\EventualesExtractor();
            /** @var \App\DTOs\CfeExtraccionDto $dto */
            $dto = $extractor->extraer($text);
            $extractor->validar($dto);

            $data = $dto->toArray();

            // Construir recibo si no existe (serie + numero)
            $recibo = $data['recibo'] ?? '';
            if (empty($recibo) && !empty($data['serie']) && !empty($data['numero'])) {
                $recibo = $data['serie'] . '-' . $data['numero'];
            }

            // Usar titular o construir desde serie/numero
            $titular = $data['titular'] ?? $data['receptor_nombre'] ?? '';
            if (empty($titular) && !empty($recibo)) {
                $titular = $recibo;
            }

            // Validar campos críticos
            if (empty($data['fecha']) || empty($data['monto']) || empty($recibo)) {
                $this->mensajeError = "Datos incompletos del CFE. Faltan campos obligatorios (fecha, monto, recibo).";
                \Illuminate\Support\Facades\Log::channel('cfe_errors')->warning('Eventuales: campos críticos vacíos', array_merge($data, ['recibo_construido' => $recibo]));
                return;
            }

            $this->datosExtraidos = [
                'titular' => $titular,
                'fecha' => $data['fecha'] ?? '',
                'monto' => $data['monto'] ?? 0.0,
                'medio_de_pago' => $this->normalizarMedioPago($data['medio_de_pago'] ?? ''),
                'detalle' => $data['detalle'] ?? '',
                'orden_cobro' => $data['orden_cobro'] ?? '',
                'recibo' => $recibo,
                'ingreso' => $data['ingreso'] ?? $data['ingreso_contabilidad'] ?? '',
                'institucion' => $this->detectarInstitucion($titular),
            ];
        } catch (\App\Exceptions\CfeExtraccionInvalidaException $e) {
            if (str_contains($e->getMessage(), 'Monto no valido')) {
                $resultado = $this->detectarAnulacion($text);
                if ($resultado === 'confirmar') {
                    return;
                }
                if ($resultado === 'inexistente') {
                    $this->mensajeError = 'Nota de crédito: la e-Factura/e-Ticket referenciada (' . $this->ultimaRefEncontrada . ') no está registrada en el sistema, no es necesario eliminar nada.';
                    return;
                }
            }

            $this->mensajeError = $e->getMessage();
            $this->dispatchBrowserEvent('swal:modal-error', [
                'title' => 'Comprobante No Válido',
                'text' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->mensajeError = "Error al procesar el PDF: " . $e->getMessage();
        }
    }

    /**
     * Normaliza el medio de pago asociándolo con un registro activo de la base de datos.
     */
    private function normalizarMedioPago(string $medioTexto): string
    {
        $texto = mb_strtoupper(trim($medioTexto), 'UTF-8');
        if (empty($texto)) {
            $predeterminado = MedioDePago::activos()->where('nombre', 'like', '%Transferencia%')->first();
            return $predeterminado ? mb_strtoupper($predeterminado->nombre, 'UTF-8') : 'TRANSFERENCIA';
        }

        $medios = MedioDePago::activos()->get();

        // 1. Coincidencia exacta con nombre
        foreach ($medios as $medio) {
            $nombreUpper = mb_strtoupper($medio->nombre, 'UTF-8');
            if ($texto === $nombreUpper) {
                return $nombreUpper;
            }
        }

        // 2. Coincidencia por subcadena de nombre
        foreach ($medios as $medio) {
            $nombreUpper = mb_strtoupper($medio->nombre, 'UTF-8');
            if (str_contains($texto, $nombreUpper) || str_contains($nombreUpper, $texto)) {
                return $nombreUpper;
            }
        }

        // 3. Coincidencia por descripción
        foreach ($medios as $medio) {
            $descUpper = mb_strtoupper($medio->descripcion ?? '', 'UTF-8');
            if (!empty($descUpper) && (str_contains($texto, $descUpper) || str_contains($descUpper, $texto))) {
                return mb_strtoupper($medio->nombre, 'UTF-8');
            }
        }

        // Fallback predeterminado
        $predeterminado = MedioDePago::activos()->where('nombre', 'like', '%Transferencia%')->first();
        return $predeterminado ? mb_strtoupper($predeterminado->nombre, 'UTF-8') : 'TRANSFERENCIA';
    }

    /**
     * Detecta la institución activa asociada a partir del nombre del titular.
     */
    private function detectarInstitucion(string $titular): ?string
    {
        $titularUpper = mb_strtoupper($titular, 'UTF-8');
        if (empty($titularUpper)) {
            return null;
        }

        if (str_contains($titularUpper, 'ESPAÑOL') || str_contains($titularUpper, 'ASSE') || str_contains($titularUpper, 'SERVICIOS DE SALUD DEL ESTADO')) {
            return 'ASSE';
        }
        if (str_contains($titularUpper, 'INISA') || str_contains($titularUpper, 'INAU') || str_contains($titularUpper, 'NIÑO Y ADOLESCENTE')) {
            return 'INAU';
        }
        if (str_contains($titularUpper, 'MIDES') || str_contains($titularUpper, 'DESARROLLO SOCIAL')) {
            return 'MIDES';
        }
        if (str_contains($titularUpper, 'CLINICAS') || str_contains($titularUpper, 'CLÍNICAS')) {
            return 'HOSPITAL CLÍNICAS';
        }
        if (str_contains($titularUpper, 'IMM') || str_contains($titularUpper, 'INTENDENCIA') || str_contains($titularUpper, 'MONTEVIDEO')) {
            return 'IMM';
        }
        if (str_contains($titularUpper, 'MGAP') || str_contains($titularUpper, 'GANADERIA') || str_contains($titularUpper, 'GANADERÍA')) {
            return 'MGAP';
        }

        // Intentar buscar coincidencia general con nombres de instituciones activas
        $instituciones = EventualInstitucion::activas()->get();
        foreach ($instituciones as $inst) {
            $nombreUpper = mb_strtoupper($inst->nombre, 'UTF-8');
            if (str_contains($titularUpper, $nombreUpper) || str_contains($nombreUpper, $titularUpper)) {
                return $nombreUpper;
            }
        }

        return null;
    }

    private string $ultimaRefEncontrada = '';

    /**
     * Detecta si el CFE es una nota de crédito (monto negativo) que referencia
     * una factura existente en el sistema.
     * @return string 'confirmar' si existe y montos coinciden, 'inexistente' si la ref. no está registrada, '' si no aplica.
     */
    private function detectarAnulacion(string $text): string
    {
        if (!preg_match('/TOTAL\s+A\s+PAGAR:\s*-\s*([\d\.,]+)/iu', $text, $mMonto)) {
            return '';
        }

        // Extraer el bloque REFERENCIAS para buscar la referencia, evitando
        // falsos positivos con la serie/número del propio documento en el encabezado.
        if (!preg_match('/REFERENCIAS:\s*\n(.*?)(?=ADENDA\b|Fecha\s+de\s+Vencimiento|$)/isu', $text, $refBlock)) {
            return '';
        }

        $refText = $refBlock[1];
        if (!preg_match('/e-(?:Factura|Ticket|Boleta)[\s\-]*([A-Z])\s*-?\s*(\d+)/iu', $refText, $mRef)) {
            return '';
        }

        $refRecibo = mb_strtoupper($mRef[1] . '-' . $mRef[2], 'UTF-8');
        $refReciboNoSep = mb_strtoupper($mRef[1] . $mRef[2], 'UTF-8');
        $this->ultimaRefEncontrada = $refRecibo;

        // Si la referencia en REFERENCIAS es al propio documento (autoreferencia),
        // no es una anulación — es un CFE normal con total negativo.
        $propioRecibo = '';
        if (preg_match('/SERIE\s*N[ÚU]MERO\b[^\n]*\n\s*([A-Z])\s+(\d+)/iu', $text, $mPropio)) {
            $propioRecibo = mb_strtoupper($mPropio[1] . '-' . $mPropio[2], 'UTF-8');
        }
        if (!empty($propioRecibo) && ($refRecibo === $propioRecibo || $refReciboNoSep === str_replace('-', '', $propioRecibo))) {
            return '';
        }

        // Buscar por recibo (serie+numero con que se registró originalmente),
        // no por orden_cobro (que es una referencia a otro CFE distinto).
        // Soportar ambos formatos (con/sin guión) por compatibilidad con registros
        // creados antes de que se estandarizara el formato "A-4788".
        $existing = Eventual::where('recibo', $refRecibo)
            ->orWhere('recibo', $refReciboNoSep)
            ->first();
        if (!$existing) {
            return 'inexistente';
        }

        $montoNota = (float)str_replace(['.', ','], ['', '.'], $mMonto[1]);

        // Verificar que los montos coincidan (abs de la nota == monto del registro)
        if (abs(abs($montoNota) - (float)$existing->monto) > 1.0) {
            $this->mensajeError = 'Nota de crédito: el monto (' . number_format($montoNota, 2, ',', '.') . ') no coincide con el registro referenciado ' . $refRecibo . ' (' . number_format($existing->monto, 2, ',', '.') . ').';
            return '';
        }

        $this->anulacionPendiente = [
            'orden_cobro' => $refRecibo,
            'record_id' => $existing->id,
            'titular' => $existing->titular,
            'fecha' => $existing->fecha instanceof \Carbon\Carbon
                ? $existing->fecha->format('d/m/Y')
                : $existing->fecha,
            'monto' => $existing->monto,
            'monto_nota' => $montoNota,
        ];

        return 'confirmar';
    }

    public function confirmarAnulacion()
    {
        if (!$this->anulacionPendiente) return;

        $record = Eventual::find($this->anulacionPendiente['record_id']);
        if ($record) {
            $record->delete();
            $this->clearCache();
            session()->flash('message', 'Registro ' . $this->anulacionPendiente['orden_cobro'] . ' eliminado exitosamente.');
        }

        $this->anulacionPendiente = null;
        $this->archivo = null;
        $this->datosExtraidos = null;
    }

    public function cancelarAnulacion()
    {
        $this->anulacionPendiente = null;
        $this->archivo = null;
        $this->datosExtraidos = null;
        $this->mensajeError = 'No se cargó el comprobante. El monto negativo no es válido para una recaudación de eventuales.';
    }

    public function guardar()
    {
        if (!$this->datosExtraidos) return;

        try {
            DB::beginTransaction();

            $monto = (float)$this->datosExtraidos['monto'];
            $fecha = Carbon::createFromFormat('d/m/Y', $this->datosExtraidos['fecha'])->format('Y-m-d');

            // Verificar si ya existe el recibo en esa fecha
            $existe = Eventual::where('recibo', $this->datosExtraidos['recibo'])
                ->whereDate('fecha', $fecha)
                ->exists();

            if ($existe) {
                $this->dispatchBrowserEvent('swal:toast-error', [
                    'text' => "El recibo {$this->datosExtraidos['recibo']} ya fue cargado el día {$this->datosExtraidos['fecha']}."
                ]);
                DB::rollBack();
                return;
            }

            // Validar que la orden de cobro no esté duplicada
            $ordenCobro = $this->datosExtraidos['orden_cobro'] ?? '';
            if (!empty($ordenCobro) && !$this->validarOrdenCobroUnica(Eventual::class, $ordenCobro, null, 'recibo')) {
                DB::rollBack();
                return;
            }

            $nuevoEventual = Eventual::create([
                'fecha' => $fecha,
                'ingreso' => !empty($this->datosExtraidos['ingreso']) ? (int)$this->datosExtraidos['ingreso'] : null,
                'institucion' => !empty($this->datosExtraidos['institucion']) ? mb_strtoupper($this->datosExtraidos['institucion'], 'UTF-8') : null,
                'titular' => mb_strtoupper($this->datosExtraidos['titular'], 'UTF-8'),
                'monto' => $monto,
                'medio_de_pago' => mb_strtoupper($this->datosExtraidos['medio_de_pago'], 'UTF-8'),
                'detalle' => mb_strtoupper($this->datosExtraidos['detalle'], 'UTF-8'),
                'orden_cobro' => !empty($this->datosExtraidos['orden_cobro']) ? mb_strtoupper($this->datosExtraidos['orden_cobro'], 'UTF-8') : null,
                'recibo' => mb_strtoupper($this->datosExtraidos['recibo'], 'UTF-8'),
                'confirmado' => false,
            ]);

            DB::commit();
            $this->clearCache();
            session()->flash('message', 'eFactura cargada como eventual exitosamente.');
            session()->flash('edit_eventual_id', $nuevoEventual->id);
            return redirect()->route('tesoreria.eventuales.index', ['edit_id' => $nuevoEventual->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->mensajeError = "Error al guardar el registro: " . $e->getMessage();
        }
    }

    private function clearCache()
    {
        $version = Cache::get('eventuales_version', 1);
        Cache::put('eventuales_version', $version + 1, now()->addYear());
    }

    public function limpiar()
    {
        $this->archivo = null;
        $this->datosExtraidos = null;
        $this->mensajeError = null;
        $this->anulacionPendiente = null;
    }

    public function render()
    {
        return view('livewire.tesoreria.eventuales.cargar-efactura');
    }
}
