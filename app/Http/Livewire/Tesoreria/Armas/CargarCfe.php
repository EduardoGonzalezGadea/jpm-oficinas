<?php

namespace App\Http\Livewire\Tesoreria\Armas;

use Livewire\Component;
use Livewire\WithFileUploads;
use Smalot\PdfParser\Parser;

class CargarCfe extends Component
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

            if (empty($datos)) {
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
        $datos = [
            'tipo_cfe' => 'No detectado',
            'serie' => '',
            'numero' => '',
            'fecha' => '',
            'rut_emisor' => '',
            'razon_social_emisor' => '',
            'rut_receptor' => '',
            'razon_social_receptor' => '',
            'monto_total' => 0.0,
            'moneda' => 'UYU',
            'subtotal' => 0.0,
            'iva' => 0.0,
            'detalle' => '',
            'orden_cobro' => '',
            'tramite' => '',
            'ingreso_contabilidad' => '',
            'telefono' => ''
        ];

        // Tipo de CFE
        if (preg_match('/(e-Factura|e-Ticket|e-Boleta)(?:\s+Cobranza)?/i', $text, $matches)) {
            $datos['tipo_cfe'] = $matches[0];
        }

        // Serie y Número
        if (preg_match('/SERIE\s+NÚMERO[^\n]+\n\s*([A-Z]+)\s+(\d+)/i', $text, $matches)) {
            $datos['serie'] = $matches[1];
            $datos['numero'] = $matches[2];
        }

        // Fecha (FECHA\tMONEDA\nDD/MM/AAAA)
        if (preg_match('/FECHA\s+MONEDA\s*\n\s*(\d{2}\/\d{2}\/\d{4})/i', $text, $matches)) {
            $datos['fecha'] = $matches[1];
        }

        // RUC Emisor
        if (preg_match('/(\d{12})\s+(?:e-Factura|e-Ticket|e-Boleta)/i', $text, $matches)) {
            $datos['rut_emisor'] = $matches[1];
        }

        // Receptor (C.I. o Rut)
        if (preg_match('/(C\.I\.|RUT)\s*\(?[^\)]*\)?:\s*([\d\.-]+)/i', $text, $matches)) {
            $datos['rut_receptor'] = $matches[2];
        }

        // Razon Social Emisor (Suele estar al inicio)
        if (preg_match('/^([^\n]+)\n([^\n]+)/', ltrim($text), $matches)) {
            $datos['razon_social_emisor'] = trim($matches[1] . ' ' . $matches[2]);
        }

        // Nombre Receptor - Captura todo hasta el siguiente bloque de encabezado (usualmente INFORMACION ADICIONAL)
        if (preg_match('/NOMBRE O DENOMINACIÓN DOMICILIO FISCAL\s*\n\s*(.*?)(?=\s*\n\s*(?:INFORMACION ADICIONAL|DETALLE DESCRIPCIÓN|PERIODO|FECHA|$))/is', $text, $matches)) {
            $datos['razon_social_receptor'] = trim(preg_replace('/\s+/', ' ', $matches[1]));
        }

        // Montos
        // Para "e-Ticket Cobranza", el total suele estar en "TOTAL A PAGAR" o "MONTO NO FACTURABLE"
        if (preg_match('/TOTAL A PAGAR:\s*([\d\.,]+)/i', $text, $matches)) {
            $datos['monto_total'] = $matches[1];
            $datos['subtotal'] = $matches[1];
        } elseif (preg_match('/MONTO NO FACTURABLE:\s*([\d\.,]+)/i', $text, $matches)) {
            $datos['monto_total'] = $matches[1];
            $datos['subtotal'] = $matches[1];
        }

        // Moneda
        if (preg_match('/Peso uruguayo/i', $text)) {
            $datos['moneda'] = 'UYU';
        } elseif (preg_match('/Dólar/i', $text)) {
            $datos['moneda'] = 'USD';
        }

        // Datos adicionales (específicos de Jefatura - Adenda)
        // Soporta formatos como: "ORDEN DE COBRO 14821" o "ING. 39 - O/C 15639"
        if (preg_match('/ING\.\s*(\d+)/i', $text, $matches)) {
            $datos['ingreso_contabilidad'] = $matches[1];
        } elseif (preg_match('/INGRESO\s*(\d+)/i', $text, $matches)) {
            $datos['ingreso_contabilidad'] = $matches[1];
        }

        if (preg_match('/ORDEN DE COBRO\s*(\d+)/i', $text, $matches)) {
            $datos['orden_cobro'] = $matches[1];
        } elseif (preg_match('/O\/C\s*(\d+)/i', $text, $matches)) {
            $datos['orden_cobro'] = $matches[1];
        }

        if (preg_match('/TRÁMITE\s*([\d\/]+)/i', $text, $matches)) {
            $datos['tramite'] = $matches[1];
        }

        // Teléfono
        if (preg_match('/(?:TEL\.|TELÉFONO|CEL\.)\s*([\d\s\-\/]+)/i', $text, $matches)) {
            $datos['telefono'] = trim($matches[1]);
        }

        // Detalle descriptivo
        if (preg_match('/DETALLE DESCRIPCIÓN[^\n]+\n\s*([^\n]+(?:\n\s*[^\n,]+)*)/i', $text, $matches)) {
            $datos['detalle'] = trim(preg_replace('/\s+/', ' ', $matches[1]));
        }

        // Validación: Verificar que el detalle contenga "porte" o "tenencia"
        if (!empty($datos['detalle'])) {
            $detalleMinusculas = strtolower($datos['detalle']);
            $contienePorteOTenencia = (strpos($detalleMinusculas, 'porte') !== false) ||
                (strpos($detalleMinusculas, 'tenencia') !== false);

            if (!$contienePorteOTenencia) {
                // Rechazar el CFE si no contiene "porte" o "tenencia"
                return [
                    'tipo_cfe' => 'No válido',
                    'serie' => '',
                    'numero' => '',
                    'fecha' => '',
                    'rut_emisor' => '',
                    'razon_social_emisor' => '',
                    'rut_receptor' => '',
                    'razon_social_receptor' => '',
                    'monto_total' => 0.0,
                    'moneda' => 'UYU',
                    'subtotal' => 0.0,
                    'iva' => 0.0,
                    'detalle' => '',
                    'orden_cobro' => '',
                    'tramite' => '',
                    'ingreso_contabilidad' => '',
                    'telefono' => '',
                    'error_validacion' => 'Este comprobante no corresponde a un pago de Porte o Tenencia de Armas. No se encontró "PORTE" ni "TENENCIA" en el detalle.'
                ];
            }
        }

        return $datos;
    }

    public function render()
    {
        return view('livewire.tesoreria.armas.cargar-cfe');
    }

    public function limpiar()
    {
        $this->archivo = null;
        $this->datosExtraidos = null;
        $this->mensajeError = null;
    }

    public function guardarRegistro()
    {
        if (!$this->datosExtraidos) {
            return;
        }

        try {
            $tipoMódulo = '';
            if (preg_match('/Tenencia|TAHTA/i', $this->datosExtraidos['detalle'])) {
                $tipoMódulo = 'tenencia';
            } elseif (preg_match('/Porte/i', $this->datosExtraidos['detalle'])) {
                $tipoMódulo = 'porte';
            }

            if (!$tipoMódulo) {
                $this->mensajeError = "No se pudo determinar si el registro es para Tenencia o Porte de Armas desde el detalle.";
                return;
            }

            $monto = (float)str_replace(['.', ','], ['', '.'], $this->datosExtraidos['monto_total']);
            $fecha = \Carbon\Carbon::createFromFormat('d/m/Y', $this->datosExtraidos['fecha']);
            $recibo = $this->datosExtraidos['serie'] . '-' . $this->datosExtraidos['numero'];

            // Verificar si el recibo ya existe en esa fecha en cualquiera de las tablas
            $existeTenencia = \App\Models\Tesoreria\TesTenenciaArmas::where('recibo', $recibo)
                ->whereDate('fecha', $fecha->format('Y-m-d'))
                ->exists();
            $existePorte = \App\Models\Tesoreria\TesPorteArmas::where('recibo', $recibo)
                ->whereDate('fecha', $fecha->format('Y-m-d'))
                ->exists();

            if ($existeTenencia || $existePorte) {
                $moduloExistente = $existeTenencia ? 'Tenencia' : 'Porte';
                $this->dispatchBrowserEvent('swal:toast-error', ['text' => "El recibo {$recibo} ya fue cargado el día {$this->datosExtraidos['fecha']} en el módulo de {$moduloExistente}."]);
                return;
            }

            $data = [
                'fecha' => $fecha->format('Y-m-d'),
                'orden_cobro' => $this->datosExtraidos['orden_cobro'] ?: '',
                'numero_tramite' => $this->datosExtraidos['tramite'] ?: '',
                'ingreso_contabilidad' => $this->datosExtraidos['ingreso_contabilidad'] ?: '',
                'recibo' => $recibo,
                'monto' => $monto,
                'titular' => mb_strtoupper($this->datosExtraidos['razon_social_receptor'], 'UTF-8'),
                'cedula' => $this->datosExtraidos['rut_receptor'],
                'telefono' => $this->datosExtraidos['telefono'] ?: '',
            ];

            $nuevoRegistro = \Illuminate\Support\Facades\DB::transaction(function () use ($tipoMódulo, $data) {
                if ($tipoMódulo === 'tenencia') {
                    return \App\Models\Tesoreria\TesTenenciaArmas::create($data);
                } else {
                    return \App\Models\Tesoreria\TesPorteArmas::create($data);
                }
            });

            \Illuminate\Support\Facades\Cache::flush();
            $this->datosExtraidos = null;
            $this->archivo = null;

            session()->flash('message', 'Registro de ' . ucfirst($tipoMódulo) . ' cargado exitosamente.');
            session()->flash('edit_id', $nuevoRegistro->id);

            // Redirigir a la pestaña correspondiente
            return redirect()->route('tesoreria.armas.' . ($tipoMódulo === 'tenencia' ? 'tenencia' : 'porte'), [
                'anio' => $fecha->year,
                'edit_id' => $nuevoRegistro->id
            ]);
        } catch (\Exception $e) {
            $this->mensajeError = "Error al guardar el registro: " . $e->getMessage();
        }
    }
}
