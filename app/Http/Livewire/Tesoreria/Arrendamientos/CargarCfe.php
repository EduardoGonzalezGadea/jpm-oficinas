<?php

namespace App\Http\Livewire\Tesoreria\Arrendamientos;

use Livewire\Component;
use Livewire\WithFileUploads;
use Smalot\PdfParser\Parser;
use App\Models\Tesoreria\Arrendamiento;
use App\Models\Tesoreria\MedioDePago;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CargarCfe extends Component
{
    use WithFileUploads;

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
            $parser = new Parser();
            $pdf = $parser->parseFile($this->archivo->getRealPath());
            $text = $pdf->getText();

            $datos = $this->parsearTextoCfe($text);

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

    /**
     * Elimina acentos de un texto para comparación insensible.
     */
    private function quitarAcentos(string $text): string
    {
        $search  = ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', 'Ü'];
        $replace = ['a', 'e', 'i', 'o', 'u', 'n', 'u', 'a', 'e', 'i', 'o', 'u', 'n', 'u'];
        return str_replace($search, $replace, $text);
    }

    private function parsearTextoCfe($text)
    {
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        $datos = [
            'tipo_cfe' => 'No detectado',
            'serie' => '',
            'numero' => '',
            'fecha' => '',
            'cedula' => '',
            'nombre' => '',
            'telefono' => '',
            'monto' => 0.0,
            'monto_total' => 0.0,
            'moneda' => 'UYU',
            'detalle' => '',
            'orden_cobro' => '',
            'forma_pago' => 'SIN DATOS',
            'adenda' => '',
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

        // Fecha
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
        if (preg_match('/NOMBRE O DENOMINACIÓN DOMICILIO FISCAL\s*\n\s*(.*?)(?=\s*\n\s*(?:INFORMACION ADICIONAL|DETALLE DESCRIPCIÓN|PERIODO|FECHA|$))/isu', $text, $matches)) {
            $datos['nombre'] = trim(preg_replace('/\s+/', ' ', $matches[1]));
        } elseif (preg_match('/FISCAL\s*(.*?)(?=\s*(?:INFORMACION|DETALLE|FECHA|\d{2}\/\d{2}\/\d{4}|$))/isu', $text, $matches)) {
            $datos['nombre'] = trim(preg_replace('/\s+/', ' ', $matches[1]));
        }

        // Teléfono (de info adicional)
        if (preg_match('/(?:TEL\.|TELÉFONO|CEL\.)\s*([\d\s\-\/]+)/i', $text, $matches)) {
            $datos['telefono'] = trim($matches[1]);
        }

        // Monto Total
        if (preg_match('/TOTAL\s+A\s+PAGAR:\s*([\d\.,]+)/is', $text, $matches)) {
            $datos['monto_total'] = $matches[1];
            $datos['monto'] = floatval(str_replace(['.', ','], ['', '.'], $matches[1]));
        } elseif (preg_match('/MONTO\s+NO\s+FACTURABLE:\s*([\d\.,]+)/is', $text, $matches)) {
            $datos['monto_total'] = $matches[1];
            $datos['monto'] = floatval(str_replace(['.', ','], ['', '.'], $matches[1]));
        }

        // Moneda
        if (preg_match('/Peso uruguayo/i', $text)) {
            $datos['moneda'] = 'UYU';
        } elseif (preg_match('/Dólar/i', $text)) {
            $datos['moneda'] = 'USD';
        }

        // Medios de Pago (entre TOTAL A PAGAR y REFERENCIAS)
        if (preg_match('/TOTAL\s+A\s+PAGAR:[\s\t]*[\d\.,]+(.*?)(?=REFERENCIAS:)/isu', $text, $matches)) {
            $bloquePago = trim($matches[1]);
            if (!empty($bloquePago)) {
                $lineasPago = explode("\n", $bloquePago);
                $pagos = [];
                foreach ($lineasPago as $linea) {
                    $linea = trim($linea);
                    if (empty($linea)) continue;
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

        // Extracción de Detalle (concatenar todo el bloque de ítems)
        if (preg_match('/DETALLE\s+DESCRIPCI.N.*?IMPORTE\s*(.*?)(?=\s*MONTO\s+NO\s+FACTURABLE)/isu', $text, $matches)) {
            $bloqueItems = trim($matches[1]);
            $lineas = explode("\n", $bloqueItems);
            $bufferDetalle = [];

            foreach ($lineas as $linea) {
                $linea = trim($linea);
                if (empty($linea)) continue;

                // Remover las cantidades y montos del final de línea
                if (preg_match('/^(.*?)([\d\.,]+(?:\s*\(Unid\))?\s*[\d\.,]+\s+[\d\.,]+)$/i', $linea, $m)) {
                    $restoLinea = trim($m[1]);
                    if (!empty($restoLinea)) {
                        $bufferDetalle[] = $restoLinea;
                    }
                } else {
                    $bufferDetalle[] = $linea;
                }
            }

            if (!empty($bufferDetalle)) {
                $datos['detalle'] = trim(preg_replace('/\s+/', ' ', implode(' ', $bufferDetalle)));
            }
        }

        // Adenda
        if (preg_match('/ADENDA\s*\n(.*?)(?=\s*(?:Fecha\s+de|Puede\s+verificar|I\.V\.A\.|NÚMERO\s+DE\s+CAE|$))/isu', $text, $matches)) {
            $adendaRaw = trim($matches[1]);
            $lineas = explode("\n", $adendaRaw);
            $lineasLimpias = array_map(function ($linea) {
                $linea = trim($linea);
                $linea = preg_replace('/(\d)([A-Z])/u', '$1 $2', $linea);
                return $linea;
            }, $lineas);
            $lineasLimpias = array_filter($lineasLimpias, function ($linea) {
                return !empty($linea) && $linea !== '1';
            });
            $datos['adenda'] = implode("\n", $lineasLimpias);
        }

        // Extraer Orden de Cobro de la adenda
        if (!empty($datos['adenda'])) {
            $adendaSinAcentos = $this->quitarAcentos(mb_strtolower($datos['adenda'], 'UTF-8'));

            // Patrones: ORDEN DE COBRO, ORDEN COBRO, O.C., O/C, O.(C.
            if (preg_match('/(?:orden\s+de\s+cobro|orden\s+cobro|o\.\s*\(?c\.?|o\/c)\s*(\d+)/iu', $adendaSinAcentos, $ocMatch)) {
                $datos['orden_cobro'] = $ocMatch[1];
            } else {
                // Si hay solo un número en la adenda, es la orden de cobro
                $numerosEncontrados = [];
                if (preg_match_all('/\b(\d{3,})\b/', $datos['adenda'], $numMatches)) {
                    $numerosEncontrados = $numMatches[1];
                }
                if (count($numerosEncontrados) === 1) {
                    $datos['orden_cobro'] = $numerosEncontrados[0];
                }
            }
        }

        // Validación: Verificar que el detalle contenga "arrendamiento"
        $detalleNorm = $this->quitarAcentos(mb_strtolower($datos['detalle'], 'UTF-8'));
        if (strpos($detalleNorm, 'arrendamiento') === false && strpos($detalleNorm, 'arrendamientos') === false) {
            return [
                'error_validacion' => 'Este comprobante no corresponde a un Arrendamiento. No se encontró la palabra "ARRENDAMIENTO" en el detalle del CFE.'
            ];
        }

        // Si se pagó por transferencia, concatenar la información del pago al detalle
        if (stripos($datos['forma_pago'], 'Transferencia') !== false) {
            $datos['detalle'] .= ' - ' . $datos['forma_pago'];
        }

        return $datos;
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
