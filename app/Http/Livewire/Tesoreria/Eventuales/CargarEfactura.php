<?php

namespace App\Http\Livewire\Tesoreria\Eventuales;

use Livewire\Component;
use Livewire\WithFileUploads;
use Smalot\PdfParser\Parser;
use App\Models\Tesoreria\Eventual;
use App\Models\Tesoreria\MedioDePago;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CargarEfactura extends Component
{
    use WithFileUploads;

    public $archivo;
    public $datosExtraidos = null;
    public $mensajeError = null;

    protected $rules = [
        'archivo' => 'required|mimes:pdf|max:10240', // 10MB max
    ];

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

            // Análisis del texto para extraer datos de eFactura
            $this->datosExtraidos = $this->parsearTextoEfactura($text);

            if (empty($this->datosExtraidos)) {
                $this->mensajeError = "No se pudieron extraer datos del archivo. Asegúrate de que es una eFactura válida.";
            }
        } catch (\Exception $e) {
            $this->mensajeError = "Error al procesar el PDF: " . $e->getMessage();
        }
    }

    private function parsearTextoEfactura($text)
    {
        $datos = [
            'titular' => '',
            'fecha' => '',
            'monto' => 0.0,
            'medio_de_pago' => '',
            'detalle' => '',
            'orden_cobro' => '',
            'recibo' => '',
        ];

        // 1. Serie y Número (Recibo)
        // Ejemplo: SERIENÚMERO ... \n A 2688
        if (preg_match('/SERIENÚMERO[^\n]*\n\s*([A-Z]+)\s+(\d+)/i', $text, $matches)) {
            $datos['recibo'] = $matches[1] . $matches[2];
        }

        // 2. Fecha
        if (preg_match('/FECHA\s+MONEDA\s*\n\s*(\d{2}\/\d{2}\/\d{4})/i', $text, $matches)) {
            $datos['fecha'] = $matches[1];
        }

        // 3. Titular (Nombre o Denominación)
        // Captura líneas entre "NOMBRE O DENOMINACIÓN DOMICILIO FISCAL" y "PERIODO" o "FECHA"
        if (preg_match('/NOMBRE O DENOMINACIÓN DOMICILIO FISCAL\s*\n\s*(.*?)(?=\s*\n\s*(?:PERIODO|FECHA|DETALLE DESCRIPCIÓN|$))/is', $text, $matches)) {
            $lines = explode("\n", trim($matches[1]));
            // Generalmente el nombre está en las primeras 2 líneas, luego sigue la dirección
            // Intentamos limpiar: si una línea empieza con calle/número o algo similar, paramos.
            // Para ser simple y efectivo, tomamos las 2 primeras líneas si existen y no lucen como dirección.
            $nombreLines = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                // Si la línea parece dirección (números al final o palabras como Apto, Esq, etc), paramos de capturar para el nombre
                if (preg_match('/\d+/', $line) && !preg_match('/[A-Z]{3,}/', $line)) break;
                if (count($nombreLines) >= 2) break;
                $nombreLines[] = $line;
            }
            $datos['titular'] = implode(' ', $nombreLines);
        }

        // 4. Medio de Pago y Monto
        // medio de pago será igual al texto que aparece debajo de TOTAL A PAGAR (sin los dos puntos finales) si existe
        if (preg_match('/TOTAL A PAGAR:\s*[\d\.,]+\s*\n\s*([^:]+):\s*([\d\.,]+)/i', $text, $matches)) {
            $datos['medio_de_pago'] = trim($matches[1]);
            $datos['monto'] = $matches[2];
        } else {
            // Si no existe texto específico debajo con monto, buscamos TOTAL A PAGAR
            if (preg_match('/TOTAL A PAGAR:\s*([\d\.,]+)/i', $text, $matches)) {
                $datos['monto'] = $matches[1];
            }

            // Y buscamos cualquier texto con "Transferencia" para medio_de_pago
            if (preg_match('/(Transferencia[^\n:]*)/i', $text, $matches)) {
                $datos['medio_de_pago'] = trim($matches[1]);
            }
        }

        // 5. Orden de Cobro
        // Referencia luego del texto eFactura, hasta el siguiente espacio en blanco
        // Buscamos específicamente debajo de REFERENCIAS
        if (preg_match('/REFERENCIAS:.*?(?:e-?Factura|eFactura)\s+([A-Z0-9]+)/is', $text, $matches)) {
            $datos['orden_cobro'] = $matches[1];
        }

        // 6. Detalle
        // Concatenación de ítems: detalle (descripción). Separado por /
        if (preg_match('/DETALLE DESCRIPCIÓN CANT\. PRECIO DESC\. REC\. IMPORTE\s*\n\s*(.*?)(?=\s*\n\s*(?:MONTO NO FACTURABLE|MONTO TOTAL|TOTAL A PAGAR|IVA|$))/is', $text, $matches)) {
            $detalleBlock = $matches[1];
            $lines = explode("\n", $detalleBlock);
            $items = [];
            $currentItem = "";
            $currentDesc = [];

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                // Patrón para detectar la línea que contiene los montos (CANT, PRECIO, IMPORTE)
                // Ejemplo: 32,000 (Hora) 72,74 2.327,68 o Aguinaldo Policias Eventuales 1,000 (Func) 6.387,65 25.550,60
                if (preg_match('/^(.*?)\s*([\d\.,]+\s*\([^\)]+\)\s*[\d\.,]+\s*[\d\.,]+)$/i', $line, $itemMatches)) {
                    $textBefore = trim($itemMatches[1]);

                    // Si hay texto en la misma línea que los montos, intentamos separar Detalle de Descripción
                    // Usualmente por tabulación o múltiples espacios
                    if (preg_match('/^(.*?)(?:\s{2,}|\t)(.*)$/', $textBefore, $parts)) {
                        $detalle = trim($parts[1]);
                        $descripcion = trim($parts[2]);
                    } else {
                        $detalle = $textBefore;
                        $descripcion = "";
                    }

                    if (empty($currentItem)) {
                        // Caso: Todo está en una sola línea
                        $itemFinal = $detalle . ($descripcion ? " ($descripcion)" : "");
                    } else {
                        // Caso: El ítem comenzó en líneas anteriores
                        $descAcumulada = implode(' ', $currentDesc);

                        // Combinamos lo recolectado
                        // Si hay $detalle, se suma a la descripción o se trata como parte del ítem
                        $descFull = trim($descAcumulada . " " . $detalle . " " . $descripcion);
                        $itemFinal = $currentItem . ($descFull ? " ($descFull)" : "");
                    }

                    if (!empty($itemFinal)) {
                        $items[] = trim($itemFinal);
                    }

                    // Resetear para el próximo ítem
                    $currentItem = "";
                    $currentDesc = [];
                } else {
                    // Texto que no contiene montos (puede ser el nombre del ítem o parte de la descripción)
                    if (empty($currentItem)) {
                        $currentItem = $line;
                    } else {
                        $currentDesc[] = $line;
                    }
                }
            }

            // Si quedó algo pendiente por procesar (raro pero posible)
            if (!empty($currentItem)) {
                $descFull = implode(' ', $currentDesc);
                $items[] = trim($currentItem . ($descFull ? " ($descFull)" : ""));
            }

            $datos['detalle'] = implode(' / ', $items);
        }

        return $datos;
    }

    public function guardar()
    {
        if (!$this->datosExtraidos) return;

        try {
            DB::beginTransaction();

            $monto = (float)str_replace(['.', ','], ['', '.'], $this->datosExtraidos['monto']);
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

            // Normalizar medio de pago
            $medioPago = mb_strtoupper($this->datosExtraidos['medio_de_pago'], 'UTF-8');
            if (empty($medioPago) || !str_contains($medioPago, 'TRANSFERENCIA')) {
                // Buscar predeterminado si es necesario
                $predeterminado = MedioDePago::activos()->where('nombre', 'like', '%TRANSFERENCIA%')->first();
                $medioPago = $predeterminado ? $predeterminado->nombre : 'TRANSFERENCIA BANCARIA';
            }

            $nuevoEventual = Eventual::create([
                'fecha' => $fecha,
                'ingreso' => null,
                'institucion' => null,
                'titular' => mb_strtoupper($this->datosExtraidos['titular'], 'UTF-8'),
                'monto' => $monto,
                'medio_de_pago' => $medioPago,
                'detalle' => mb_strtoupper($this->datosExtraidos['detalle'], 'UTF-8'),
                'orden_cobro' => $this->datosExtraidos['orden_cobro'],
                'recibo' => $this->datosExtraidos['recibo'],
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
    }

    public function render()
    {
        return view('livewire.tesoreria.eventuales.cargar-efactura');
    }
}
