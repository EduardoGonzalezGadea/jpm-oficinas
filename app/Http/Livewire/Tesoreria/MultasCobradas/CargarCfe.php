<?php

namespace App\Http\Livewire\Tesoreria\MultasCobradas;

use Livewire\Component;
use Livewire\WithFileUploads;
use Smalot\PdfParser\Parser;
use App\Models\Tesoreria\TesMultasCobradas;
use App\Models\Tesoreria\TesMultasItems;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CargarCfe extends Component
{
    use WithFileUploads;

    public $archivo;
    public $datosExtraidos = null;
    public $mensajeError = null;
    public $sugerenciaItem = null;

    protected $rules = [
        'archivo' => 'required|mimes:pdf|max:10240', // 10MB max
    ];

    public function mount()
    {
        // 1. Intentar cargar desde Caché (Nuevo método más seguro para la extensión)
        $prefillId = request()->query('prefill_id');
        if ($prefillId && Cache::has('cfe_prefill_' . $prefillId)) {
            $cacheData = Cache::get('cfe_prefill_' . $prefillId);
            if (in_array($cacheData['tipo'] ?? '', ['multas_cobradas', 'Multas Cobradas'])) {
                $this->datosExtraidos = $cacheData['datos'];
                Cache::forget('cfe_prefill_' . $prefillId); // Limpiar
                return;
            }
        }

        // 2. Fallback: Verificar si hay datos pre-cargados desde la sesión (Antiguo método)
        if (session()->has('cfe_datos_precargados') && session('cfe_tipo') === 'multas_cobradas') {
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
        $this->sugerenciaItem = null;

        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($this->archivo->getRealPath());
            $text = $pdf->getText();

            // Análisis básico del texto para extraer datos de CFE
            $datos = $this->parsearTextoCfe($text);

            // Verificar si hay un error de validación
            if (isset($datos['error_validacion'])) {
                $this->mensajeError = $datos['error_validacion'];
                $this->dispatchBrowserEvent('swal:modal-error', [
                    'title' => 'Comprobante No Válido',
                    'text' => $datos['error_validacion']
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

    private function parsearTextoCfe($text)
    {
        // Limpiar caracteres no válidos para UTF-8 para evitar errores en Livewire/JSON
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        $datos = [
            'tipo_cfe' => 'No detectado',
            'serie' => '',
            'numero' => '',
            'fecha' => '',
            'cedula' => '',
            'nombre' => '',
            'domicilio' => '',
            'monto_total' => 0.0,
            'moneda' => 'UYU',
            'detalle_completo' => '',
            'adicional' => '',
            'adenda' => '',
            'forma_pago' => 'SIN DATOS',
            'items' => []
        ];

        // Tipo de CFE
        if (preg_match('/(e-Factura|e-Ticket|e-Boleta)(?:\s+Cobranza)?/is', $text, $matches)) {
            $datos['tipo_cfe'] = $matches[0];
        }

        // Serie y Número
        if (preg_match('/([A-Z])[\s\t]+(\d+)[\s\t]+(?:Contado|Cr.dito)/i', $text, $matches)) {
            $datos['serie'] = $matches[1];
            $datos['numero'] = $matches[2];
        }

        // Fecha - Priorizar la etiqueta FECHA para evitar capturar el inicio de un periodo
        // Se añade soporte para casos donde aparece "MONEDA" entre "FECHA" y el valor (común en algunos CFE)
        if (preg_match('/FECHA[\s:]+(?:MONEDA[\s:]+)?(\d{2}\/\d{2}\/\d{4})/i', $text, $matches)) {
            $datos['fecha'] = $matches[1];
        } elseif (preg_match('/(\d{2}\/\d{2}\/\d{4})/i', $text, $matches)) {
            $datos['fecha'] = $matches[1];
        }

        // Receptor (Cédula o RUT)
        if (preg_match('/(?:C\.I\.|RUT).*?:\s*([\d\.-]+)/is', $text, $matches)) {
            $datos['cedula'] = $matches[1];
        }

        // Nombre Receptor
        if (preg_match('/FISCAL\s*(.*?)(?=\s*(?:INFORMACION|DETALLE|FECHA|\d{2}\/\d{2}\/\d{4}|$))/isu', $text, $matches)) {
            $datos['nombre'] = trim(preg_replace('/\s+/', ' ', $matches[1]));
        }

        // Monto Total
        if (preg_match('/TOTAL\s+A\s+PAGAR:\s*([\d\.,]+)/is', $text, $matches)) {
            $datos['monto_total'] = $matches[1];
        } elseif (preg_match('/MONTO\s+NO\s+FACTURABLE:\s*([\d\.,]+)/is', $text, $matches)) {
            $datos['monto_total'] = $matches[1];
        }

        // Extracción de Medios de Pago (entre TOTAL A PAGAR y REFERENCIAS)
        if (preg_match('/TOTAL\s+A\s+PAGAR:[\s\t]*[\d\.,]+(.*?)(?=REFERENCIAS:)/isu', $text, $matches)) {
            $bloquePago = trim($matches[1]);
            if (!empty($bloquePago)) {
                $lineasPago = explode("\n", $bloquePago);
                $pagos = [];
                foreach ($lineasPago as $linea) {
                    $linea = trim($linea);
                    if (empty($linea)) continue;
                    // Detectar formato Clave: Valor
                    if (preg_match('/^(.*?):[\s\t]*([\d\.,]+)$/u', $linea, $mpm)) {
                        $pagos[] = trim($mpm[1]) . ": " . trim($mpm[2]);
                    } elseif (!empty($linea)) {
                        $pagos[] = $linea;
                    }
                }
                if (!empty($pagos)) {
                    $datos['forma_pago'] = implode(' / ', $pagos);
                }
            }
        }

        // Extracción de INFORMACIÓN ADICIONAL (teléfono u otros datos)
        if (preg_match('/INFORMACION\s+ADICIONAL\s*\n(.*?)(?=\s*FECHA\s+MONEDA)/isu', $text, $matches)) {
            $adicionalRaw = trim($matches[1]);
            $datos['adicional'] = preg_replace('/\s+/', ' ', $adicionalRaw);
        }

        // Extracción de ADENDA - Está después de "ADENDA" y antes de información del CAE
        // Mantener saltos de línea pero limpiar espacios excesivos en cada línea
        if (preg_match('/ADENDA\s*\n(.*?)(?=\s*(?:Fecha\s+de|Puede\s+verificar|I\.V\.A\.|NÚMERO\s+DE\s+CAE|$))/isu', $text, $matches)) {
            $adendaRaw = trim($matches[1]);
            // Dividir en líneas, limpiar cada línea y volver a unir
            $lineas = explode("\n", $adendaRaw);
            $lineasLimpias = array_map(function ($linea) {
                $linea = trim($linea);

                // Agregar espacio entre números y letras cuando están pegados
                // Ejemplo: "357282SLF" -> "357282 SLF"
                $linea = preg_replace('/(\d)([A-Z])/u', '$1 $2', $linea);

                return $linea;
            }, $lineas);

            // Filtrar líneas vacías y el número "1" solitario
            $lineasLimpias = array_filter($lineasLimpias, function ($linea) {
                return !empty($linea) && $linea !== '1';
            });

            $datos['adenda'] = implode("\n", $lineasLimpias);
        }

        // Extracción de REFERENCIAS
        if (preg_match('/REFERENCIAS:(.*?)(?=\s*(?:ADENDA|Fecha\s+de|$))/isu', $text, $matches)) {
            $referenciasRaw = trim($matches[1]);
            $datos['referencias'] = preg_replace('/\s+/', ' ', $referenciasRaw);
        }

        // Extracción de Items
        if (preg_match('/DETALLE\s+DESCRIPCI.N.*?IMPORTE\s*(.*?)(?=\s*MONTO\s+NO\s+FACTURABLE)/isu', $text, $matches)) {
            $bloqueItems = $matches[1];
            $datos['detalle_completo'] = trim($bloqueItems);

            $lineas = explode("\n", $bloqueItems);
            $itemActual = ['detalle' => '', 'descripcion' => '', 'importe' => 0];
            $bufferItem = [];

            foreach ($lineas as $linea) {
                $linea = trim($linea);
                if (empty($linea)) continue;

                // Detectar línea de cierre de item (cantidades y montos)
                // Refactorizado para aceptar líneas sin "(Unid)" explícito, asumiendo unidad por defecto
                // Regex mejorada: Busca números al final de la línea que parezcan [Cantidad] [PrecioUnitario] [ImporteTotal]
                // Soporta opcionalmente (Unid) o espacios entre ellos
                if (preg_match('/^(.*?)([\d\.,]+(?:\s*\(Unid\))?[\s\t]*[\d\.,]+[\s\t]+([\d\.,]+))$/i', $linea, $m)) {
                    $restoLinea = trim($m[1]);
                    $importe = $m[3];

                    if (!empty($restoLinea)) {
                        $bufferItem[] = $restoLinea;
                    }

                    $itemActual['importe'] = (float)str_replace(['.', ','], ['', '.'], $importe);

                    if (!empty($bufferItem)) {
                        // Unir todas las líneas en un solo texto limpiando espacios extra
                        $fullText = implode(' ', $bufferItem);
                        $fullText = trim(preg_replace('/\s+/', ' ', $fullText));

                        // Buscar separador inteligente "CORRESPONDE A"
                        // El usuario indicó que la descripción suele empezar con esto
                        $separator = 'CORRESPONDE A';
                        $pos = mb_stripos($fullText, $separator);

                        if ($pos !== false) {
                            $itemActual['detalle'] = trim(mb_substr($fullText, 0, $pos));
                            $itemActual['descripcion'] = trim(mb_substr($fullText, $pos));
                        } else {
                            // Si no hay separador claro, todo es detalle (según feedback del usuario)
                            $itemActual['detalle'] = $fullText;
                            $itemActual['descripcion'] = '';
                        }
                    }

                    $datos['items'][] = $itemActual;
                    $itemActual = ['detalle' => '', 'descripcion' => '', 'importe' => 0];
                    $bufferItem = [];
                } else {
                    $bufferItem[] = $linea;
                }
            }
        }

        // Validación: Verificar que al menos un ítem contenga la palabra "multa"
        if (!empty($datos['items'])) {
            $contieneMulta = false;
            foreach ($datos['items'] as $item) {
                $textoCompleto = strtolower($item['detalle'] . ' ' . $item['descripcion']);
                if (strpos($textoCompleto, 'multa') !== false) {
                    $contieneMulta = true;
                    break;
                }
            }

            if (!$contieneMulta) {
                // Rechazar el CFE si no contiene la palabra "multa"
                return [
                    'tipo_cfe' => 'No válido',
                    'serie' => '',
                    'numero' => '',
                    'fecha' => '',
                    'cedula' => '',
                    'nombre' => '',
                    'domicilio' => '',
                    'monto_total' => 0.0,
                    'moneda' => 'UYU',
                    'detalle_completo' => '',
                    'adicional' => '',
                    'adenda' => '',
                    'referencias' => '',
                    'forma_pago' => 'SIN DATOS',
                    'items' => [],
                    'error_validacion' => 'Este comprobante no corresponde a un pago de multas de tránsito. No se encontró la palabra "MULTA" en ningún ítem.'
                ];
            }
        }

        return $datos;
    }

    public function guardarRegistro()
    {
        if (!$this->datosExtraidos || empty($this->datosExtraidos['items'])) {
            $this->mensajeError = "No se detectaron ítems válidos para guardar.";
            return;
        }

        try {
            DB::beginTransaction();

            $montoTotal = (float)str_replace(['.', ','], ['', '.'], $this->datosExtraidos['monto_total']);

            // --- VALIDACIÓN DE CONSISTENCIA DE TOTALES ---
            $sumaItems = collect($this->datosExtraidos['items'])->sum('importe');
            // Usamos un delta pequeño para evitar errores de precisión flotante
            if (abs($montoTotal - $sumaItems) > 0.1) {
                $montoFormat = number_format($montoTotal, 2, ',', '.');
                $sumaFormat = number_format($sumaItems, 2, ',', '.');
                $diffFormat = number_format(abs($montoTotal - $sumaItems), 2, ',', '.');

                $this->mensajeError = "ERROR DE CONSISTENCIA: El total del comprobante ($ {$montoFormat}) NO coincide con la suma de los ítems detectados ($ {$sumaFormat}). Diferencia: $ {$diffFormat}. No se puede guardar.";
                $this->dispatchBrowserEvent('swal:modal-error', [
                    'title' => 'Error de Consistencia',
                    'text' => $this->mensajeError
                ]);
                DB::rollBack();
                return;
            }
            // ---------------------------------------------

            $fecha = \Carbon\Carbon::createFromFormat('d/m/Y', $this->datosExtraidos['fecha']);
            $recibo = $this->datosExtraidos['serie'] . '-' . $this->datosExtraidos['numero'];

            // Verificar duplicados
            $existe = TesMultasCobradas::where('recibo', $recibo)
                ->whereDate('fecha', $fecha->format('Y-m-d'))
                ->exists();

            if ($existe) {
                $this->dispatchBrowserEvent('swal:toast-error', ['text' => "El recibo {$recibo} ya fue cargado el día {$this->datosExtraidos['fecha']}."]);
                DB::rollBack();
                return;
            }

            $cobro = TesMultasCobradas::create([
                'fecha' => $fecha->format('Y-m-d'),
                'recibo' => $recibo,
                'monto' => $montoTotal,
                'nombre' => mb_strtoupper($this->datosExtraidos['nombre'], 'UTF-8'),
                'cedula' => $this->datosExtraidos['cedula'],
                'adicional' => $this->datosExtraidos['adicional'] ?? null,
                'adenda' => $this->datosExtraidos['adenda'] ?? null,
                'referencias' => $this->datosExtraidos['referencias'] ?? null,
                'forma_pago' => $this->datosExtraidos['forma_pago'] ?? 'SIN DATOS',
                'created_by' => auth()->id(),
            ]);

            foreach ($this->datosExtraidos['items'] as $itemData) {
                $cobro->items()->create([
                    'detalle' => mb_strtoupper($itemData['detalle'], 'UTF-8'),
                    'descripcion' => mb_strtoupper($itemData['descripcion'], 'UTF-8'),
                    'importe' => $itemData['importe'],
                    'created_by' => auth()->id(),
                ]);
            }

            DB::commit();

            Cache::flush();
            $this->datosExtraidos = null;
            $this->archivo = null;

            session()->flash('message', 'Multa y sus ' . count($cobro->items) . ' ítems cargados exitosamente.');
            return redirect()->route('tesoreria.multas-cobradas.index');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->mensajeError = "Error al guardar el registro: " . $e->getMessage();
        }
    }

    public function limpiar()
    {
        $this->archivo = null;
        $this->datosExtraidos = null;
        $this->mensajeError = null;
    }

    public function render()
    {
        $items = TesMultasItems::orderBy('detalle')->get();
        return view('livewire.tesoreria.multas-cobradas.cargar-cfe', [
            'items' => $items
        ]);
    }
}
