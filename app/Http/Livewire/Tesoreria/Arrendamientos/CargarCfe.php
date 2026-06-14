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
    use WithFileUploads, WithOrdenCobroValidation;

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

            $extractor = new \App\Services\CfeExtractor\ArrendamientosExtractor();
            $datos = $extractor->extraer($text);
            $validacion = $extractor->validar($datos);

            if (!$validacion['valid']) {
                $errores = implode(", ", $validacion['errors']);
                $this->mensajeError = $errores;
                $this->dispatchBrowserEvent('swal:modal-error', [
                    'title' => 'Comprobante No Válido',
                    'text' => $errores
                ]);
                return;
            }

            if (empty($datos) || !$datos['numero']) {
                $this->mensajeError = "No se pudieron extraer datos del archivo. Asegúrate de que es un CFE válido.";
                return;
            }

            $this->datosExtraidos = $datos;
        } catch (\Exception $e) {
            $this->mensajeError = "Error al procesar el PDF: " . $e->getMessage();
        }
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
    }

    public function render()
    {
        return view('livewire.tesoreria.arrendamientos.cargar-cfe');
    }
}
