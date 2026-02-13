<?php

namespace App\Http\Livewire\Tesoreria\CertificadosResidencia;

use Livewire\Component;
use Livewire\WithFileUploads;
use Smalot\PdfParser\Parser;
use App\Models\Tesoreria\CertificadoResidencia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CargarCfe extends Component
{
    use WithFileUploads;

    public $archivo;
    public $datosExtraidos = null;
    public $mensajeError = null;

    // Certificados encontrados que coinciden con la búsqueda
    public $certificadosEncontrados = [];
    public $certificadoSeleccionadoId = null;

    protected $rules = [
        'archivo' => 'required|mimes:pdf|max:10240',
    ];

    public function mount()
    {
        // 1. Intentar cargar desde Caché (método de la extensión del navegador)
        $prefillId = request()->query('prefill_id');
        if ($prefillId && Cache::has('cfe_prefill_' . $prefillId)) {
            $cacheData = Cache::get('cfe_prefill_' . $prefillId);
            $tipo = $cacheData['tipo'] ?? '';
            if (in_array($tipo, ['certificado_residencia', 'Certificado de Residencia', 'certificados_residencia'])) {
                $this->datosExtraidos = $cacheData['datos'];
                Cache::forget('cfe_prefill_' . $prefillId);
                $this->buscarCertificadoCoincidente();
                return;
            }
        }

        // 2. Fallback: sesión
        if (session()->has('cfe_datos_precargados') && session('cfe_tipo') === 'certificados_residencia') {
            $this->datosExtraidos = session('cfe_datos_precargados');
            session()->forget(['cfe_datos_precargados', 'cfe_tipo', 'cfe_filepath']);
            $this->buscarCertificadoCoincidente();
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
        $this->certificadosEncontrados = [];
        $this->certificadoSeleccionadoId = null;

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
            $this->buscarCertificadoCoincidente();
        } catch (\Exception $e) {
            $this->mensajeError = "Error al procesar el PDF: " . $e->getMessage();
        }
    }

    private function parsearTextoCfe($text)
    {
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        $datos = [
            'tipo_cfe' => 'No detectado',
            'serie' => '',
            'numero' => '',
            'fecha' => '',
            'cedula_receptor' => '',
            'nombre_receptor' => '',
            'monto_total' => 0.0,
            'moneda' => 'UYU',
            'telefono' => '',
            'forma_pago' => 'SIN DATOS',
            'detalle' => '',
            'descripcion' => '',
            'cedula_titular' => '',     // CI del titular del certificado (de la descripción)
            'retira_es_titular' => true, // Por defecto se asume que es el mismo titular
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

        // Receptor (CI del CFE - quien retira o el titular)
        if (preg_match('/(?:C\.I\.|RUT).*?:\s*([\d\.-]+)/is', $text, $matches)) {
            $datos['cedula_receptor'] = $matches[1];
        }

        // Nombre Receptor del CFE
        if (preg_match('/NOMBRE O DENOMINACIÓN DOMICILIO FISCAL\s*\n\s*(.*?)(?=\s*\n\s*(?:INFORMACION ADICIONAL|DETALLE DESCRIPCIÓN|PERIODO|FECHA|$))/isu', $text, $matches)) {
            $datos['nombre_receptor'] = trim(preg_replace('/\s+/', ' ', $matches[1]));
        } elseif (preg_match('/FISCAL\s*(.*?)(?=\s*(?:INFORMACION|DETALLE|FECHA|\d{2}\/\d{2}\/\d{4}|$))/isu', $text, $matches)) {
            $datos['nombre_receptor'] = trim(preg_replace('/\s+/', ' ', $matches[1]));
        }

        // Monto Total
        if (preg_match('/TOTAL\s+A\s+PAGAR:\s*([\d\.,]+)/is', $text, $matches)) {
            $datos['monto_total'] = $matches[1];
        } elseif (preg_match('/MONTO\s+NO\s+FACTURABLE:\s*([\d\.,]+)/is', $text, $matches)) {
            $datos['monto_total'] = $matches[1];
        }

        // Medios de Pago
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

        // Teléfono (de info adicional)
        if (preg_match('/(?:TEL\.|TELÉFONO|CEL\.)\s*([\d\s\-\/]+)/i', $text, $matches)) {
            $datos['telefono'] = trim($matches[1]);
        }

        // Extracción de Items (Detalle + Descripción)
        if (preg_match('/DETALLE\s+DESCRIPCI.N.*?IMPORTE\s*(.*?)(?=\s*MONTO\s+NO\s+FACTURABLE)/isu', $text, $matches)) {
            $bloqueItems = trim($matches[1]);

            $lineas = explode("\n", $bloqueItems);
            $bufferItem = [];

            foreach ($lineas as $linea) {
                $linea = trim($linea);
                if (empty($linea)) continue;

                // Detectar línea con cantidades y montos al final
                if (preg_match('/^(.*?)([\d\.,]+(?:\s*\(Unid\))?\s*[\d\.,]+\s+([\d\.,]+))$/i', $linea, $m)) {
                    $restoLinea = trim($m[1]);
                    if (!empty($restoLinea)) {
                        $bufferItem[] = $restoLinea;
                    }

                    $fullText = implode(' ', $bufferItem);
                    $fullText = trim(preg_replace('/\s+/', ' ', $fullText));

                    // Buscar separador entre detalle y descripción
                    // Los separadores pueden ser: tab, "CORRESPONDE A", o varios espacios
                    if (preg_match('/^(.*?)\t+(.*?)$/u', $fullText, $parts)) {
                        $datos['detalle'] = trim($parts[1]);
                        $datos['descripcion'] = trim($parts[2]);
                    } elseif (mb_stripos($fullText, 'CORRESPONDE A') !== false) {
                        $pos = mb_stripos($fullText, 'CORRESPONDE A');
                        $datos['detalle'] = trim(mb_substr($fullText, 0, $pos));
                        $datos['descripcion'] = trim(mb_substr($fullText, $pos));
                    } else {
                        $datos['detalle'] = $fullText;
                        $datos['descripcion'] = '';
                    }

                    $bufferItem = [];
                } else {
                    $bufferItem[] = $linea;
                }
            }
        }

        // Validación: Verificar que el detalle contenga "certificado" y "residencia"
        $detalleMinusculas = strtolower($datos['detalle'] . ' ' . $datos['descripcion']);
        if (strpos($detalleMinusculas, 'certificado') === false || strpos($detalleMinusculas, 'residencia') === false) {
            return [
                'error_validacion' => 'Este comprobante no corresponde a un Certificado de Residencia. No se encontró "CERTIFICADO DE RESIDENCIA" en el detalle del CFE.'
            ];
        }

        // Detectar si la descripción contiene una cédula (del titular del certificado)
        // Esto indica que otra persona retira el certificado
        if (!empty($datos['descripcion'])) {
            if (preg_match('/([\d][\d\.]{4,}[\d])/u', $datos['descripcion'], $ciMatch)) {
                $ciLimpia = preg_replace('/[^0-9]/', '', $ciMatch[1]);
                if (strlen($ciLimpia) >= 6 && strlen($ciLimpia) <= 10) {
                    $datos['cedula_titular'] = $ciMatch[1]; // Mantener formato original
                    $datos['retira_es_titular'] = false;
                }
            }
        }

        // Si no se detectó CI en la descripción, el receptor del CFE es el titular
        if ($datos['retira_es_titular']) {
            $datos['cedula_titular'] = $datos['cedula_receptor'];
        }

        return $datos;
    }

    /**
     * Busca certificados en estado "Recibido" que coincidan con la cédula del titular
     */
    public function buscarCertificadoCoincidente()
    {
        if (!$this->datosExtraidos || empty($this->datosExtraidos['cedula_titular'])) {
            $this->certificadosEncontrados = [];
            return;
        }

        // Limpiar la CI para búsqueda (remover puntos y guiones)
        $ciLimpia = preg_replace('/[^0-9]/', '', $this->datosExtraidos['cedula_titular']);

        $certificados = CertificadoResidencia::where('estado', 'Recibido')
            ->where(function ($q) use ($ciLimpia) {
                // Buscar CI con o sin puntos/guiones
                $q->whereRaw("REPLACE(REPLACE(titular_nro_documento, '.', ''), '-', '') LIKE ?", ["%{$ciLimpia}%"]);
            })
            ->orderBy('fecha_recibido', 'desc')
            ->get()
            ->toArray();

        $this->certificadosEncontrados = $certificados;

        // Si hay exactamente uno, seleccionarlo automáticamente
        if (count($this->certificadosEncontrados) === 1) {
            $this->certificadoSeleccionadoId = $this->certificadosEncontrados[0]['id'];
        }
    }

    public function seleccionarCertificado($id)
    {
        $this->certificadoSeleccionadoId = $id;
    }

    public function confirmarEntrega()
    {
        if (!$this->datosExtraidos) {
            $this->mensajeError = "No hay datos del CFE para procesar.";
            return;
        }

        if (!$this->certificadoSeleccionadoId) {
            $this->mensajeError = "Debe seleccionar un certificado para marcar como entregado.";
            return;
        }

        try {
            $certificado = CertificadoResidencia::find($this->certificadoSeleccionadoId);

            if (!$certificado || $certificado->estado !== 'Recibido') {
                $this->mensajeError = "El certificado seleccionado no existe o ya fue entregado/devuelto.";
                return;
            }

            // Verificar duplicado por número de recibo
            $recibo = $this->datosExtraidos['serie'] . '-' . $this->datosExtraidos['numero'];
            $existeRecibo = CertificadoResidencia::where('numero_recibo', $recibo)
                ->where('id', '!=', $certificado->id)
                ->exists();

            if ($existeRecibo) {
                $this->dispatchBrowserEvent('swal:toast-error', [
                    'text' => "El recibo {$recibo} ya fue utilizado en otro certificado."
                ]);
                return;
            }

            // Parsear fecha del CFE
            $fechaEntrega = \Carbon\Carbon::createFromFormat('d/m/Y', $this->datosExtraidos['fecha']);

            // Separar nombre del receptor del CFE en nombre y apellido
            $nombreCompleto = mb_strtoupper($this->datosExtraidos['nombre_receptor'], 'UTF-8');
            $partesNombre = $this->separarNombreApellido($nombreCompleto);

            // Preparar datos de entrega
            $updateData = [
                'fecha_entregado' => $fechaEntrega->format('Y-m-d'),
                'entregador_id' => Auth::id(),
                'retira_nombre' => $partesNombre['nombre'],
                'retira_apellido' => $partesNombre['apellido'],
                'retira_tipo_documento' => 'Cédula',
                'retira_nro_documento' => $this->datosExtraidos['cedula_receptor'],
                'retira_telefono' => $this->datosExtraidos['telefono'] ?? '',
                'numero_recibo' => $recibo,
                'estado' => 'Entregado',
            ];

            $certificado->update($updateData);

            Cache::flush();
            $this->datosExtraidos = null;
            $this->archivo = null;
            $this->certificadosEncontrados = [];
            $this->certificadoSeleccionadoId = null;

            session()->flash('message', 'Certificado de Residencia marcado como entregado exitosamente.');
            return redirect()->route('tesoreria.certificados-residencia.index');
        } catch (\Exception $e) {
            $this->mensajeError = "Error al registrar la entrega: " . $e->getMessage();
        }
    }

    /**
     * Intenta separar un nombre completo en nombre y apellido.
     * Asume formato "APELLIDO1 APELLIDO2 NOMBRE1..." o "NOMBRE APELLIDO" (2 palabras)
     */
    private function separarNombreApellido(string $nombreCompleto): array
    {
        $partes = preg_split('/\s+/', trim($nombreCompleto));

        if (count($partes) <= 1) {
            return ['nombre' => $nombreCompleto, 'apellido' => ''];
        }

        if (count($partes) == 2) {
            return ['nombre' => $partes[0], 'apellido' => $partes[1]];
        }

        // 3+ palabras: últimas como nombre, primeras como apellido
        // Patrón típico uruguayo: APELLIDO1 APELLIDO2 NOMBRE
        // Pero también puede ser NOMBRE APELLIDO1 APELLIDO2
        // Dificil de determinar automáticamente, dejamos apellido=primera palabra, nombre=resto
        // ya que el formato del CFE de Jefatura suele ser "APELLIDO NOMBRE"
        $apellido = array_shift($partes);
        $nombre = implode(' ', $partes);

        return ['nombre' => $nombre, 'apellido' => $apellido];
    }

    public function limpiar()
    {
        $this->archivo = null;
        $this->datosExtraidos = null;
        $this->mensajeError = null;
        $this->certificadosEncontrados = [];
        $this->certificadoSeleccionadoId = null;
    }

    public function render()
    {
        return view('livewire.tesoreria.certificados-residencia.cargar-cfe');
    }
}
