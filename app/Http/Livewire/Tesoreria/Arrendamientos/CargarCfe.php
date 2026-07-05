<?php

namespace App\Http\Livewire\Tesoreria\Arrendamientos;

use Livewire\Component;
use Livewire\WithFileUploads;
use Smalot\PdfParser\Parser;
use App\Models\Tesoreria\Arrendamiento;
use App\Models\Tesoreria\MedioDePago;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Traits\WithOrdenCobroValidation;

class CargarCfe extends Component
{
    use WithFileUploads, WithOrdenCobroValidation, \App\Traits\WithAnulacionCfe;

    public $archivo;
    public $datosExtraidos = null;
    public $mensajeError = null;

    protected $rules = [
        'archivo' => 'required|mimes:pdf|max:10240', // 10MB max
    ];

    public function mount()
    {
        // 1. Intentar cargar desde Caché (método de la extensión del navegador)
        $prefillId = request()->query('prefill_id');
        if ($prefillId && Cache::has('cfe_prefill_' . $prefillId)) {
            $cacheData = Cache::get('cfe_prefill_' . $prefillId);
            $tipo = $cacheData['tipo'] ?? '';
            if (in_array($tipo, ['arrendamientos', 'Arrendamientos'])) {
                $this->datosExtraidos = $cacheData['datos'];
                Cache::forget('cfe_prefill_' . $prefillId);
                return;
            }
        }

        // 2. Fallback: sesión
        if (session()->has('cfe_datos_precargados') && session('cfe_tipo') === 'arrendamientos') {
            $this->datosExtraidos = session('cfe_datos_precargados');
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
            $processor = app(\App\Services\CfeProcessorService::class);
            $text = $processor->parsearPdf($this->archivo->getRealPath());

            // Debug: log first 1500 chars of PDF text for diagnosis
            \Illuminate\Support\Facades\Log::channel('cfe_errors')->info('Arrendamientos: texto PDF (primeros 1500 caracteres)', [
                'texto' => substr($text, 0, 1500)
            ]);

$extractor = new \App\Services\CfeExtractor\ArrendamientosExtractor();
            /** @var \App\DTOs\CfeExtraccionDto $dto */
            $dto = $extractor->extraer($text);
            $extractor->validar($dto);

            $datos = $dto->toArray();

            // Validar campos críticos
            if (empty($datos['fecha']) || empty($datos['serie']) || empty($datos['numero']) || empty($datos['monto'])) {
                $this->mensajeError = "Datos incompletos del CFE. Faltan campos obligatorios (fecha, serie, número, monto).";
                \Illuminate\Support\Facades\Log::warning('Arrendamientos: campos críticos vacíos', $datos);
                return;
            }

            // Debug: log extracted data
            \Illuminate\Support\Facades\Log::info('Arrendamientos: datos extraídos', $datos);

            $this->datosExtraidos = $datos;
        } catch (\App\Exceptions\CfeExtraccionInvalidaException $e) {
            $resultado = $this->handleMontoAnulacion($text, $e->getMessage());
            if ($resultado === 'confirmar') {
                return;
            }
            if ($resultado === 'inexistente') {
                $this->mensajeError = 'Posible anulación: la e-Factura/e-Ticket referenciada (' . $this->ultimaRefEncontrada . ') no está registrada en el sistema.';
                return;
            }
            \Illuminate\Support\Facades\Log::warning('Arrendamientos: validación fallida', [
                'errores' => $e->errores,
                'texto_preview' => substr($text ?? '', 0, 500)
            ]);
            $this->mensajeError = $e->getMessage();
            $this->dispatchBrowserEvent('swal:modal-error', [
                'title' => 'Comprobante No Válido',
                'text' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Arrendamientos: error procesando PDF', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
$this->mensajeError = "Error al procesar el PDF: " . $e->getMessage();
        }
    }

    protected function getModelClassForAnulacion(): string
    {
        return \App\Models\Tesoreria\Arrendamiento::class;
    }

    /**
     * Elimina acentos de un texto para comparación insensible.
     */
    private function quitarAcentos(string $text): string
    {
        $search  = ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', 'Ü'];
        $replace = ['a', 'e', 'i', 'o', 'u', 'n', 'u', 'a', 'e', 'i', 'o', 'u', 'n', 'u'];
        return str_replace($search, $replace, $text);
    }



    public function guardarRegistro()
    {
        if (!$this->datosExtraidos) {
            $this->mensajeError = "No hay datos del CFE para guardar.";
            return;
        }

        try {
            DB::beginTransaction();

            $monto = $this->datosExtraidos['monto'];
            if (is_string($monto)) {
                $monto = floatval(str_replace(['.', ','], ['', '.'], $monto));
            }

            $fecha = \Carbon\Carbon::createFromFormat('d/m/Y', $this->datosExtraidos['fecha']);
            $recibo = $this->datosExtraidos['serie'] . '-' . $this->datosExtraidos['numero'];

            // Verificar duplicados por recibo y fecha
            $existe = Arrendamiento::where('recibo', $recibo)
                ->whereDate('fecha', $fecha->format('Y-m-d'))
                ->exists();

            if ($existe) {
                $this->dispatchBrowserEvent('swal:toast-error', [
                    'text' => "El recibo {$recibo} ya fue cargado el día {$this->datosExtraidos['fecha']}."
                ]);
                DB::rollBack();
                return;
            }

            // Validar que la orden de cobro no esté duplicada
            $ordenCobro = $this->datosExtraidos['orden_cobro'] ?? '';
            if (!empty($ordenCobro) && !$this->validarOrdenCobroUnica(Arrendamiento::class, $ordenCobro, null, 'recibo')) {
                DB::rollBack();
                return;
            }

            // Determinar medio de pago
            $medioPago = $this->datosExtraidos['forma_pago'] ?? 'SIN DATOS';
            // Intentar mapear a medio de pago del sistema
            if (stripos($medioPago, 'Transferencia') !== false) {
                $medioPago = $this->getDefaultMedioDePago('Transferencia');
            } elseif (stripos($medioPago, 'Efectivo') !== false) {
                $medioPago = $this->getDefaultMedioDePago('Efectivo');
            }

            // Determinar detalle
            $detalle = mb_strtoupper($this->datosExtraidos['detalle'], 'UTF-8');

            Arrendamiento::create([
                'fecha' => $fecha->format('Y-m-d'),
                'nombre' => mb_strtoupper($this->datosExtraidos['nombre'], 'UTF-8'),
                'cedula' => $this->datosExtraidos['cedula'],
                'telefono' => $this->datosExtraidos['telefono'] ?? null,
                'monto' => $monto,
                'detalle' => $detalle,
                'orden_cobro' => $this->datosExtraidos['orden_cobro'] ?? null,
                'recibo' => $recibo,
                'medio_de_pago' => $medioPago,
            ]);

            DB::commit();

            Cache::flush();
            $this->datosExtraidos = null;
            $this->archivo = null;

            session()->flash('message', 'Arrendamiento cargado exitosamente desde CFE.');
            return redirect()->route('tesoreria.arrendamientos.index');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->mensajeError = "Error al guardar el registro: " . $e->getMessage();
        }
    }

    /**
     * Busca un medio de pago activo que coincida con el término dado.
     */
    private function getDefaultMedioDePago(string $termino): string
    {
        $medio = MedioDePago::activos()
            ->where('nombre', 'like', "%{$termino}%")
            ->first();
        return $medio ? $medio->nombre : $termino;
    }

    public function limpiar()
    {
        $this->archivo = null;
        $this->datosExtraidos = null;
        $this->mensajeError = null;
        $this->limpiarAnulacion();
    }

    public function render()
    {
        return view('livewire.tesoreria.arrendamientos.cargar-cfe');
    }
}
