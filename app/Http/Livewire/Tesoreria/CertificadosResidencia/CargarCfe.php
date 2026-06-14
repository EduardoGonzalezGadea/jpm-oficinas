<?php

namespace App\Http\Livewire\Tesoreria\CertificadosResidencia;

use Livewire\Component;
use Livewire\WithFileUploads;
use Smalot\PdfParser\Parser;
use App\Models\Tesoreria\CertificadoResidencia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
            $text = app(\App\Services\CfeProcessorService::class)->parsearPdf($this->archivo->getRealPath());

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
            $this->prefilarNuevoCertificado();
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
            // Titular extraído de la descripción del CFE
            'cedula_titular' => '',
            'nombre_titular' => '',
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

        // Teléfono: Buscar solo en INFORMACION ADICIONAL
        if (preg_match('/INFORMACION ADICIONAL\s*(.*?)(?=\s*(?:PERIODO|FECHA|DETALLE|$))/isu', $text, $matchesInfo) &&
            preg_match('/(?:TEL\.?|TEL(?:E|É)FONO|CEL\.?)[\s:]*([\d][\d\s\-\/\.]{5,})/iu', $matchesInfo[1], $matchesTel)) {
            $datos['telefono'] = trim($matchesTel[1]);
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

        // --- Extraer TITULAR de la descripción (CÉDULA + NOMBRE) ---
        // Puede venir en formatos como:
        //   "CORRESPONDE A: C.I. 1234567-8 NOMBRE: JUAN PEREZ"
        //   "C.I. 1234567-8 TITULAR: JUAN PEREZ"
        //   "C.I. 1234567-8 - JUAN PEREZ"
        //   "CÉDULA: 1234567-8 NOMBRE: JUAN PEREZ"
        if (!empty($datos['descripcion'])) {
            $descripcion = $datos['descripcion'];

            // Intentar extraer CÉDULA del titular desde la descripción
            $ciTitularDesc = null;
            if (preg_match('/(?:C\.I\.|CI|CÉDULA|CEDULA|DOCUMENTO)\s*[:\-\s]*([\d][\d\.\-]{4,}[\d])/iu', $descripcion, $ciMatch)) {
                $ciLimpia = preg_replace('/[^0-9]/', '', $ciMatch[1]);
                if (strlen($ciLimpia) >= 6 && strlen($ciLimpia) <= 10) {
                    $ciTitularDesc = $ciMatch[1];
                }
            }

            if ($ciTitularDesc) {
                $datos['cedula_titular'] = $ciTitularDesc;
                $datos['retira_es_titular'] = false;

                // Intentar extraer el NOMBRE del titular desde la descripción
                // Buscar patrones como: NOMBRE: X, TITULAR: X, - NOMBRE X
                $nombreTitularDesc = null;
                if (preg_match('/(?:NOMBRE|TITULAR)\s*[:\-\s]+([A-Za-zÀ-ÿÑñ\s\.]+?)(?=(?:\s*(?:C\.I\.|CI|CÉDULA|CEDULA|TEL|$)))/iu', $descripcion, $nomMatch)) {
                    $nombreTitularDesc = trim(preg_replace('/\s+/', ' ', $nomMatch[1]));
                }
                // Fallback: buscar texto después de la CI hasta el final
                elseif (preg_match('/' . preg_quote($ciMatch[1], '/') . '\s*[:\-\s]*([A-Za-zÀ-ÿÑñ\s\.]+?)(?=(?:\s*(?:CORRESPONDE|$)))/iu', $descripcion, $nomFallback)) {
                    $nombreTitularDesc = trim(preg_replace('/\s+/', ' ', $nomFallback[1]));
                }

                if ($nombreTitularDesc && strlen($nombreTitularDesc) > 2) {
                    $datos['nombre_titular'] = mb_strtoupper($nombreTitularDesc, 'UTF-8');
                }
            }
        }

        // Si no se detectó titular en la descripción, el receptor del CFE es el titular
        if ($datos['retira_es_titular']) {
            $datos['cedula_titular'] = $datos['cedula_receptor'];
            $datos['nombre_titular'] = $datos['nombre_receptor'];
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

        $ciLimpia = preg_replace('/[^0-9]/', '', $this->datosExtraidos['cedula_titular']);

        $certificados = CertificadoResidencia::where('estado', 'Recibido')
            ->where(function ($q) use ($ciLimpia) {
                $q->whereRaw("REPLACE(REPLACE(titular_nro_documento, '.', ''), '-', '') LIKE ?", ["%{$ciLimpia}%"]);
            })
            ->orderBy('fecha_recibido', 'desc')
            ->get()
            ->toArray();

        $this->certificadosEncontrados = $certificados;

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

            // Separar nombre del receptor del CFE en nombre y apellido (quien retira)
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
                'monto' => $this->parsearMontoCfe($this->datosExtraidos['monto_total']),
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
     * Parsea el monto extraído del CFE a float
     */
    private function parsearMontoCfe($monto): ?float
    {
        if (empty($monto)) {
            return null;
        }
        $monto = str_replace('.', '', $monto);
        $monto = str_replace(',', '.', $monto);
        return (float) $monto;
    }

    /**
     * Intenta separar un nombre completo en nombre y apellido.
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

        // Formato del CFE de Jefatura suele ser "APELLIDO NOMBRE"
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

    /**
     * Datos del certificado a crear (cuando no se encuentra uno existente)
     */
    public $nuevoCertificado = [
        'fecha_recibido' => '',
        'titular_nombre' => '',
        'titular_apellido' => '',
        'titular_tipo_documento' => 'Cédula',
        'titular_nro_documento' => '',
    ];

    public function mount()
    {
        $this->nuevoCertificado['fecha_recibido'] = date('Y-m-d');

        // 1. Intentar cargar desde Caché (método de la extensión del navegador)
        $prefillId = request()->query('prefill_id');
        if ($prefillId && Cache::has('cfe_prefill_' . $prefillId)) {
            $cacheData = Cache::get('cfe_prefill_' . $prefillId);
            $tipo = $cacheData['tipo'] ?? '';
            if (in_array($tipo, ['certificado_residencia', 'Certificado de Residencia', 'certificados_residencia'])) {
                $this->datosExtraidos = $cacheData['datos'];
                Cache::forget('cfe_prefill_' . $prefillId);
                $this->buscarCertificadoCoincidente();
                $this->prefilarNuevoCertificado();
                return;
            }
        }

        // 2. Fallback: sesión
        if (session()->has('cfe_datos_precargados') && session('cfe_tipo') === 'certificados_residencia') {
            $this->datosExtraidos = session('cfe_datos_precargados');
            session()->forget(['cfe_datos_precargados', 'cfe_tipo', 'cfe_filepath']);
            $this->buscarCertificadoCoincidente();
            $this->prefilarNuevoCertificado();
        }
    }

    /**
     * Pre-fill the new certificate form fields from the extracted CFE data.
     * - Si la descripción tenía C.I. + NOMBRE del titular, se usan esos.
     * - Si no, se usan los datos del receptor del CFE como titular.
     */
    private function prefilarNuevoCertificado()
    {
        if (!$this->datosExtraidos) {
            return;
        }

        $this->nuevoCertificado['titular_nro_documento'] = $this->datosExtraidos['cedula_titular'] ?? '';
        $this->nuevoCertificado['titular_tipo_documento'] = 'Cédula';

        // Si hay nombre_titular extraído de la descripción, usarlo
        if (!empty($this->datosExtraidos['nombre_titular']) && !$this->datosExtraidos['retira_es_titular']) {
            $nombreCompleto = mb_strtoupper($this->datosExtraidos['nombre_titular'], 'UTF-8');
            $partes = $this->separarNombreApellido($nombreCompleto);
            $this->nuevoCertificado['titular_nombre'] = $partes['nombre'];
            $this->nuevoCertificado['titular_apellido'] = $partes['apellido'];
        } else {
            // Si es el mismo titular, usar el nombre del receptor del CFE
            $nombreCompleto = mb_strtoupper($this->datosExtraidos['nombre_receptor'] ?? '', 'UTF-8');
            $partes = $this->separarNombreApellido($nombreCompleto);
            $this->nuevoCertificado['titular_nombre'] = $partes['nombre'];
            $this->nuevoCertificado['titular_apellido'] = $partes['apellido'];
        }
    }

    /**
     * Crea un nuevo certificado en estado "Recibido" y lo marca como entregado en un solo paso.
     */
    public function guardarNuevoCertificadoYEntrega()
    {
        if (!$this->datosExtraidos) {
            $this->mensajeError = "No hay datos del CFE para procesar.";
            return;
        }

        $this->validate([
            'nuevoCertificado.fecha_recibido' => 'required|date',
            'nuevoCertificado.titular_nombre' => 'required|string|max:255',
            'nuevoCertificado.titular_apellido' => 'required|string|max:255',
            'nuevoCertificado.titular_tipo_documento' => 'required|in:Cédula,Cédula Extranjera,Pasaporte,Otro',
            'nuevoCertificado.titular_nro_documento' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('tes_certificados_residencia', 'titular_nro_documento')
                    ->where('fecha_recibido', $this->nuevoCertificado['fecha_recibido'])
                    ->whereNull('deleted_at')
            ],
        ], [
            'nuevoCertificado.titular_nombre.required' => 'El nombre del titular es obligatorio.',
            'nuevoCertificado.titular_apellido.required' => 'El apellido del titular es obligatorio.',
            'nuevoCertificado.titular_nro_documento.required' => 'El documento del titular es obligatorio.',
            'nuevoCertificado.titular_nro_documento.unique' => 'Ya existe un certificado de residencia recibido para este documento en la fecha seleccionada.',
        ]);

        try {
            // Parsear fecha del CFE
            $fechaEntrega = \Carbon\Carbon::createFromFormat('d/m/Y', $this->datosExtraidos['fecha']);

            // Separar nombre del receptor del CFE en nombre y apellido (quien retira)
            $nombreCompleto = mb_strtoupper($this->datosExtraidos['nombre_receptor'], 'UTF-8');
            $partesNombre = $this->separarNombreApellido($nombreCompleto);

            $recibo = $this->datosExtraidos['serie'] . '-' . $this->datosExtraidos['numero'];

            // Verificar duplicado por número de recibo
            $existeRecibo = CertificadoResidencia::where('numero_recibo', $recibo)->exists();
            if ($existeRecibo) {
                $this->dispatchBrowserEvent('swal:toast-error', [
                    'text' => "El recibo {$recibo} ya fue utilizado en otro certificado."
                ]);
                return;
            }

            DB::beginTransaction();

            // 1. Crear el certificado en estado Recibido
            $certificado = CertificadoResidencia::create([
                'fecha_recibido' => $this->nuevoCertificado['fecha_recibido'],
                'receptor_id' => Auth::id(),
                'titular_nombre' => $this->nuevoCertificado['titular_nombre'],
                'titular_apellido' => $this->nuevoCertificado['titular_apellido'],
                'titular_tipo_documento' => $this->nuevoCertificado['titular_tipo_documento'],
                'titular_nro_documento' => $this->nuevoCertificado['titular_nro_documento'],
                'estado' => 'Recibido',
            ]);

            // 2. Marcar como entregado inmediatamente con los datos del CFE
            $certificado->update([
                'fecha_entregado' => $fechaEntrega->format('Y-m-d'),
                'entregador_id' => Auth::id(),
                'retira_nombre' => $partesNombre['nombre'],
                'retira_apellido' => $partesNombre['apellido'],
                'retira_tipo_documento' => 'Cédula',
                'retira_nro_documento' => $this->datosExtraidos['cedula_receptor'],
                'retira_telefono' => $this->datosExtraidos['telefono'] ?? '',
                'numero_recibo' => $recibo,
                'monto' => $this->parsearMontoCfe($this->datosExtraidos['monto_total']),
                'estado' => 'Entregado',
            ]);

            DB::commit();

            Cache::flush();
            $this->datosExtraidos = null;
            $this->archivo = null;
            $this->certificadosEncontrados = [];
            $this->certificadoSeleccionadoId = null;

            session()->flash('message', 'Certificado de Residencia creado y entregado exitosamente.');
            return redirect()->route('tesoreria.certificados-residencia.index');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->mensajeError = "Error al crear y entregar el certificado: " . $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.tesoreria.certificados-residencia.cargar-cfe');
    }
}