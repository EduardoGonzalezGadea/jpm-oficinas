<?php

namespace App\Services\Tesoreria;

use Smalot\PdfParser\Parser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CfeUniversalParserService
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    public function parsePdf(string $rutaAbsoluta): array
    {
        $content = file_get_contents($rutaAbsoluta);
        if ($content === false) {
            Log::warning('CfeParser: No se pudo leer el archivo PDF', ['ruta' => $rutaAbsoluta]);
            return $this->datosVacios();
        }

        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        $trimmedContent = rtrim($content);
        if (str_ends_with($trimmedContent, '%%E')) {
            $content = $trimmedContent . 'OF';
        } elseif (str_ends_with($trimmedContent, '%%EO')) {
            $content = $trimmedContent . 'F';
        }

        try {
            $pdf = $this->parser->parseContent($content);
            $texto = $pdf->getText();
        } catch (\Exception $e) {
            Log::error('CfeParser: Error al parsear PDF', [
                'ruta' => $rutaAbsoluta,
                'error' => $e->getMessage(),
            ]);
            return $this->datosVacios();
        }

        $texto = $this->sanitizarTexto($texto);
        $datos = $this->extraerDatos($texto);

        // Extracción geométrica precisa para Receptor (separa Nombre y Domicilio que están en celdas adyacentes)
        try {
            $pages = $pdf->getPages();
            if (count($pages) > 0) {
                $dataTm = $pages[0]->getDataTm();
                
                $yTop = null;
                $yBottom = null;
                
                foreach ($dataTm as $tm) {
                    $text = trim($tm[1]);
                    $y = round($tm[0][5], 2);
                    
                    if (str_contains(str_replace('Ó', 'O', strtoupper($text)), 'NOMBRE O DENOMINACION')) {
                        $yTop = $y;
                    }
                }
                
                if ($yTop !== null) {
                    foreach ($dataTm as $tm) {
                        $text = trim($tm[1]);
                        $y = round($tm[0][5], 2);
                        
                        if (str_contains(strtoupper($text), 'INFORMACION ADICIONAL') || strtoupper($text) === 'PERIODO' || strtoupper($text) === 'FECHA' || strtoupper($text) === 'DETALLE') {
                            if ($y < $yTop) {
                                if ($yBottom === null || $y > $yBottom) {
                                    $yBottom = $y;
                                }
                            }
                        }
                    }
                    
                    if ($yBottom === null) {
                        $yBottom = $yTop - 100;
                    }
                    
                    $nombre = [];
                    $domicilio = [];
                    
                    foreach ($dataTm as $tm) {
                        $text = trim($tm[1]);
                        if ($text === '') continue;
                        $x = round($tm[0][4], 2);
                        $y = round($tm[0][5], 2);
                        
                        if ($y < $yTop && $y > $yBottom) {
                            if ($x >= 230 && $x < 390) {
                                $nombre[] = $text;
                            } elseif ($x >= 390) {
                                $domicilio[] = $text;
                            }
                        }
                    }
                    
                    if (!empty($nombre)) {
                        $datos['receptor_nombre_denominacion'] = implode(" ", $nombre);
                    }
                    if (!empty($domicilio)) {
                        $datos['receptor_domicilio_fiscal'] = implode(" ", $domicilio);
                    } else {
                        $datos['receptor_domicilio_fiscal'] = '';
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('CfeParser: Error en extracción geométrica, usando regex', [
                'error' => $e->getMessage(),
            ]);
        }

        $erroresValidacion = $this->validarDatos($datos);
        if (!empty($erroresValidacion)) {
            Log::warning('CfeParser: Extracción con advertencias', [
                'errores' => $erroresValidacion,
                'tipo_documento' => $datos['documento_tipo'] ?? 'desconocido',
                'numero_documento' => $datos['documento_numero'] ?? 'N/A',
            ]);
        }

        return $datos;
    }

    public function extraerDatos(string $texto): array
    {
        $datos = [
            'emisor_nombre' => 'Jefatura de Policía de Montevideo', // Suponemos constante o se puede mejorar
            'emisor_direccion' => '',
            'emisor_localidad' => '',
            'emisor_telefono' => '',
            'emisor_correo' => '',
            'emisor_ruc' => '',
            
            'documento_tipo' => '',
            'documento_serie' => '',
            'documento_numero' => '',
            'forma_pago' => '',
            'vencimiento' => null,
            'comprobante_tipo' => '',
            
            'receptor_documento_ruc' => '',
            'receptor_nombre_denominacion' => '',
            'receptor_domicilio_fiscal' => '',
            
            'periodo' => '',
            'nro_compra' => '',
            'fecha' => null,
            'moneda' => 'UYU',
            
            'items' => [],
            'medios_pago' => [],
            
            'monto_no_facturable' => 0.0,
            'monto_total' => 0.0,
            'total_a_pagar' => 0.0,
            
            'referencias' => '',
            'adenda' => ''
        ];

        // RUC Emisor
        if (preg_match('/(\d{12})\s+(?:e-Factura|e-Ticket|e-Boleta)/i', $texto, $m)) {
            $datos['emisor_ruc'] = trim($m[1]);
        }

        // Tipo de Comprobante / Documento
        if (preg_match('/(e-Factura|e-Ticket|e-Boleta)(?:\s+Cobranza)?/i', $texto, $m)) {
            $datos['documento_tipo'] = trim($m[0]);
        }

        if (preg_match('/Consumidor Final/i', $texto)) {
            $datos['comprobante_tipo'] = 'Consumidor Final';
        }

        // Serie, Número, Forma Pago, Vencimiento
        if (preg_match('/([A-Z])\s+(\d+)\s+(Contado|Cr.dito|Crédito|Credito)(?:\s+(\d{2}\/\d{2}\/\d{4}))?/iu', $texto, $m)) {
            $datos['documento_serie'] = trim($m[1]);
            $datos['documento_numero'] = trim($m[2]);
            $datos['forma_pago'] = trim($m[3]);
            if (isset($m[4]) && $m[4]) {
                try {
                    $datos['vencimiento'] = Carbon::createFromFormat('d/m/Y', $m[4])->format('Y-m-d');
                } catch (\Exception $e) {}
            }
        }

        // Emisor Telefono y Correo
        if (preg_match('/Tel\.:\s*(.*?)(?=\s+jpmonte|RUC COMPRADOR|$)/i', $texto, $m)) {
            $datos['emisor_telefono'] = trim($m[1]);
        }
        if (preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $texto, $m)) {
            $datos['emisor_correo'] = trim($m[1]);
        }
        
        if (preg_match('/VARELA JOSE PEDRO 3440\s*(.*?)(?=\s*Tel\.:)/is', $texto, $m)) {
             $datos['emisor_direccion'] = 'VARELA JOSE PEDRO 3440';
             $datos['emisor_localidad'] = trim(str_replace("\n", " ", $m[1]));
        }

        // Receptor (RUC / C.I.)
        if (preg_match('/C\.I\.\s*\(UY\)[\s:]*([\d\.-]+)/i', $texto, $m)) {
            $datos['receptor_documento_ruc'] = trim($m[1]);
        } elseif (preg_match('/RUC COMPRADOR[\s:]*(\d{12})/i', $texto, $m)) {
            $datos['receptor_documento_ruc'] = trim($m[1]);
        } elseif (preg_match('/DOCUMENTO RECEPTOR[\s:]*([\d\.-]+)/i', $texto, $m)) {
            $datos['receptor_documento_ruc'] = trim($m[1]);
        }

        // Nombre Receptor
        if (preg_match('/NOMBRE O DENOMINACI.N\s*\n?\s*DOMICILIO FISCAL\s+(.*?)(?=\s*(?:INFORMACION ADICIONAL|DETALLE DESCRIPCIÓN|PERIODO|FECHA|$))/isu', $texto, $m)) {
             $lines = explode("\n", trim($m[1]));
             $datos['receptor_nombre_denominacion'] = trim($lines[0]);
             if (count($lines) > 1) {
                  $datos['receptor_domicilio_fiscal'] = trim(implode(" ", array_slice($lines, 1)));
             }
        } elseif (preg_match('/(?:NOMBRE O DENOMINACI.N[\s\S]*?)?DOMICILIO\s+FISCAL\s+(.*?)(?=\s*(?:INFORMACION|DETALLE|PERIODO|FECHA|$))/isu', $texto, $m)) {
             // Otro posible formato
             $lines = explode("\n", trim($m[1]));
             $datos['receptor_nombre_denominacion'] = trim($lines[0]);
             if (count($lines) > 1) {
                  $datos['receptor_domicilio_fiscal'] = trim(implode(" ", array_slice($lines, 1)));
             }
        }

        // Periodo
        if (preg_match('/PERIODO\s*\n\s*(.*?)(?=\s*\n\s*(?:FECHA|DETALLE|$))/isu', $texto, $m)) {
             $datos['periodo'] = trim($m[1]);
        }

        // Fecha y Moneda
        if (preg_match('/FECHA\s*MONEDA\s*\n\s*(\d{2}\/\d{2}\/\d{4})\s*(.*?)(?=\s*\n\s*(?:DETALLE|$))/isu', $texto, $m)) {
            try {
                $datos['fecha'] = Carbon::createFromFormat('d/m/Y', $m[1])->format('Y-m-d');
            } catch (\Exception $e) {}
            if (stripos($m[2], 'Dólar') !== false) {
                 $datos['moneda'] = 'USD';
            }
        }

        // Totales
        if (preg_match('/MONTO NO FACTURABLE:\s*(-?[\d\.,]+)/i', $texto, $m)) {
             $datos['monto_no_facturable'] = $this->parseMonto($m[1]);
        }
        if (preg_match('/MONTO TOTAL\.:\s*(-?[\d\.,]+)/i', $texto, $m)) {
             $datos['monto_total'] = $this->parseMonto($m[1]);
        }
        if (preg_match('/TOTAL A PAGAR:\s*(-?[\d\.,]+)/i', $texto, $m)) {
             $datos['total_a_pagar'] = $this->parseMonto($m[1]);
        }

        // Items (Bloque entre DETALLE... y MONTO NO FACTURABLE / MONTO TOTAL)
        if (preg_match('/DETALLE DESCRIPCI.N CANT\. PRECIO DESC\. REC\. IMPORTE\s*\n(.*?)(?=\s*\n\s*MONTO (?:NO FACTURABLE|TOTAL))/isu', $texto, $m)) {
            $itemsBlock = trim($m[1]);
            $lines = explode("\n", $itemsBlock);

            // Patrón de línea de metadata (TRÁMITE, ING, O/C, REIMPRESION)
            $metaPattern = '/^(?:TR[\xc1A]M(?:ITE)?\.?|ING(?:RESO)?\.?(?:\s*N[\xb0\xba]?)?|O(?:RDEN)?[\s\/]?(?:DE\s+)?C(?:OBRO)?[\s\/]?\.?|REIMPRESI[\xd3O]N)/iu';

            $bufferDesc = [];  // líneas de descripción real
            $bufferMeta = [];  // líneas de metadata (se descartan del detalle/descripción)

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                // Línea que termina con el patrón de cantidades/precios
                if (preg_match('/^(.*?)(\d[\d\.,]*(?:\s*\([^)]+\))?\s+\d[\d\.,]*\s+\d[\d\.,]*)\s*$/u', $line, $im)) {
                    $prefix = trim($im[1]);

                        // El prefix puede ser: solo metadata, solo descripción, o "descripción + metadata" mezclados
                    if (!empty($prefix)) {
                        // Intentar separar: buscar dónde empieza la metadata dentro del prefix
                        if (preg_match('/^(.*?)\s+((?:TR[\xc1A]M(?:ITE)?\.?|ING(?:RESO)?\.?(?:\s*N[\xb0\xba]?)?|O(?:RDEN)?[\s\/]?(?:DE\s+)?C(?:OBRO)?[\s\/]?\.?|REIMPRESI[\xd3O]N).*)$/iu', $prefix, $split)) {
                            // Hay parte descriptiva antes de la metadata
                            if (!empty(trim($split[1]))) {
                                $bufferDesc[] = trim($split[1]);
                            }
                            $bufferMeta[] = trim($split[2]);
                        } elseif (preg_match($metaPattern, $prefix)) {
                            // Todo el prefix es metadata
                            $bufferMeta[] = $prefix;
                        } else {
                            // Todo el prefix es descripción real
                            $bufferDesc[] = $prefix;
                        }
                    }

                    $detalle     = trim(implode(' ', $bufferDesc));
                    $descripcion = trim(implode(' ', $bufferMeta));

                    // Parsear montos
                    $montos = preg_split('/\s+/', trim($im[2]));
                    $nMontos = count($montos);
                    $cantidad = $this->parseMonto($montos[0] ?? '0');
                    $precio   = $nMontos >= 2 ? $this->parseMonto($montos[$nMontos - 2]) : 0;
                    $importe  = $nMontos >= 3 ? $this->parseMonto($montos[$nMontos - 1]) : $precio;

                    $datos['items'][] = [
                        'detalle'     => $detalle,
                        'descripcion' => $descripcion,
                        'cantidad'    => $cantidad,
                        'precio'      => $precio,
                        'descuento'   => 0,
                        'recargo'     => 0,
                        'importe'     => $importe,
                    ];

                    $bufferDesc = [];
                    $bufferMeta = [];

                } elseif (preg_match($metaPattern, $line)) {
                    // Línea de metadata suelta: guardar para descripción
                    $bufferMeta[] = $line;
                } else {
                    // Línea de descripción real
                    $bufferDesc[] = $line;
                }
            }

            // Ítem incompleto sin línea de números
            if (!empty($bufferDesc)) {
                $datos['items'][] = [
                    'detalle'     => trim(implode(' ', $bufferDesc)),
                    'descripcion' => '',
                    'cantidad'    => 1,
                    'precio'      => 0,
                    'descuento'   => 0,
                    'recargo'     => 0,
                    'importe'     => 0,
                ];
            }
        }

        // Medios de pago
        if (preg_match('/TOTAL A PAGAR:[\s\d\.,]+\n(.*?)(?=\s*\n\s*REFERENCIAS:)/isu', $texto, $m)) {
            $bloqueMp = trim($m[1]);
            $lines = explode("\n", $bloqueMp);
            $lastLabel = '';
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                if (preg_match('/^(?:([^:]+):)?\s*(?:UYU|USD|\$)?\s*([\d\.,]+)$/ui', $line, $mp)) {
                    $tipo = !empty($mp[1]) ? trim($mp[1]) : (!empty($lastLabel) ? $lastLabel : 'Medio de pago');
                    $datos['medios_pago'][] = [
                        'tipo' => $tipo,
                        'valor' => $this->parseMonto($mp[2])
                    ];
                    $lastLabel = '';
                } elseif (preg_match('/^(.+?)\s+(?:UYU|USD|\$)?\s*([\d\.,]+)$/ui', $line, $mp)) {
                    $tipo = trim($mp[1]);
                    $datos['medios_pago'][] = [
                        'tipo' => $tipo,
                        'valor' => $this->parseMonto($mp[2])
                    ];
                    $lastLabel = '';
                } else {
                    $lastLabel = rtrim($line, ': ');
                }
            }
        }

        // El pie de página (número, Fecha de Vencimiento, Fecha emisor...) marca el fin del contenido útil
        $footerPattern = '\s*\n\s*(?:\d+\s*\n)?\s*(?:Fecha\s+de\s+Vencimiento|Fecha\s+emisor|Puede\s+verificar|I\.V\.A\.|NÚMERO\s+DE\s+CAE)';

        // Referencias: desde "REFERENCIAS:" hasta "ADENDA" o el pie de página
        if (preg_match('/REFERENCIAS:\s*\n(.*?)(?=\s*\n\s*ADENDA|' . $footerPattern . '|$)/isu', $texto, $m)) {
            $ref = trim($m[1]);
            // Limpiar líneas que ya pertenecen al pie (ej: "UYU 7.685,00", " 1")
            $ref = preg_replace('/\n?\s*(?:UYU|USD)[\d\s\.\,]+$/u', '', $ref);
            $datos['referencias'] = trim($ref);
        }

        // Adenda: desde "ADENDA" hasta el pie de página
        if (preg_match('/ADENDA\s*\n(.*?)(?=' . $footerPattern . '|$)/isu', $texto, $m)) {
            $datos['adenda'] = trim($m[1]);
        }

        return $datos;
    }

    private function parseMonto(string $monto): float
    {
        return (float) str_replace(['.', ','], ['', '.'], $monto);
    }

    /**
     * Elimina caracteres no imprimibles, de control y patrones sospechosos
     * del texto extraído del PDF antes de pasar a los extractores regex.
     */
    private function sanitizarTexto(string $texto): string
    {
        // Eliminar caracteres de control excepto tab, newline y carriage return
        $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $texto);

        // Eliminar secuencias de escape ANSI
        $texto = preg_replace('/\x1B\[[0-9;]*[a-zA-Z]/u', '', $texto);

        // Eliminar bytes nulos
        $texto = str_replace("\0", '', $texto);

        // Normalizar saltos de línea
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);

        // Eliminar múltiples espacios en blanco consecutivos (preservando saltos de línea)
        $texto = preg_replace('/[^\S\n]+/', ' ', $texto);

        // Eliminar líneas que solo contienen espacios en blanco
        $texto = preg_replace('/^\s+$/m', '', $texto);

        return trim($texto);
    }

    /**
     * Retorna un array con datos vacíos/por defecto cuando la extracción falla.
     */
    private function datosVacios(): array
    {
        return [
            'emisor_nombre' => '',
            'emisor_direccion' => '',
            'emisor_localidad' => '',
            'emisor_telefono' => '',
            'emisor_correo' => '',
            'emisor_ruc' => '',
            'documento_tipo' => '',
            'documento_serie' => '',
            'documento_numero' => '',
            'forma_pago' => '',
            'vencimiento' => null,
            'comprobante_tipo' => '',
            'receptor_documento_ruc' => '',
            'receptor_nombre_denominacion' => '',
            'receptor_domicilio_fiscal' => '',
            'periodo' => '',
            'nro_compra' => '',
            'fecha' => null,
            'moneda' => 'UYU',
            'items' => [],
            'medios_pago' => [],
            'monto_no_facturable' => 0.0,
            'monto_total' => 0.0,
            'total_a_pagar' => 0.0,
            'referencias' => '',
            'adenda' => '',
        ];
    }

    /**
     * Valida los datos extraídos del CFE y retorna errores encontrados.
     */
    public function validarDatos(array $datos): array
    {
        $errores = [];

        if (empty($datos['documento_tipo'])) {
            $errores[] = 'No se pudo detectar el tipo de documento CFE';
        }

        if (empty($datos['documento_numero'])) {
            $errores[] = 'No se pudo extraer el número de documento';
        }

        if (empty($datos['emisor_ruc']) || !preg_match('/^\d{12}$/', $datos['emisor_ruc'])) {
            $errores[] = 'RUC del emisor inválido o no detectado';
        }

        if ($datos['total_a_pagar'] <= 0 && $datos['monto_total'] <= 0) {
            $errores[] = 'No se pudo extraer el monto total a pagar';
        }

        $fecha = $datos['fecha'] ?? $datos['vencimiento'] ?? null;
        if ($fecha !== null && $fecha !== '') {
            try {
                // Intentar parsear en múltiples formatos
                if (\Carbon\Carbon::hasFormat($fecha, 'Y-m-d')) {
                    \Carbon\Carbon::createFromFormat('Y-m-d', $fecha);
                } elseif (\Carbon\Carbon::hasFormat($fecha, 'd/m/Y')) {
                    \Carbon\Carbon::createFromFormat('d/m/Y', $fecha);
                } else {
                    \Carbon\Carbon::parse($fecha);
                }
            } catch (\Exception $e) {
                $errores[] = 'Fecha extraída inválida: ' . $fecha;
            }
        }

        $serie = $datos['documento_serie'] ?? '';
        if (!empty($serie) && !preg_match('/^[A-Z]$/', $serie)) {
            $errores[] = 'Serie del documento inválida: ' . $serie;
        }

        $numero = $datos['documento_numero'] ?? '';
        if (!empty($numero) && !preg_match('/^\d+$/', $numero)) {
            $errores[] = 'Número del documento inválido: ' . $numero;
        }

        return $errores;
    }
}
