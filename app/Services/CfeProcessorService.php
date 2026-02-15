<?php

namespace App\Services;

use App\Models\TesCfePendiente;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;

class CfeProcessorService
{
    /**
     * Process a PDF file and extract CFE data.
     *
     * @param  \Illuminate\Http\UploadedFile  $pdf
     * @param  string|null  $sourceUrl
     * @param  int|null  $userId
     * @return TesCfePendiente
     */
    public function procesarPdf($pdf, ?string $sourceUrl = null, ?int $userId = null): TesCfePendiente
    {
        // Store the PDF
        $pdfPath = $pdf->store('cfe-pendientes');

        // Parse the PDF
        $parser = new Parser();
        $pdfContent = $parser->parseFile(Storage::path($pdfPath));
        $text = $pdfContent->getText();

        // Determine CFE type
        $tipoCfe = $this->detectarTipoCfe($text);

        // Extract data
        $datosExtraidos = $this->extraerDatos($text, $tipoCfe);

        // Create the pending record
        $cfePendiente = TesCfePendiente::create([
            'tipo_cfe' => $tipoCfe,
            'serie' => $datosExtraidos['serie'] ?? null,
            'numero' => $datosExtraidos['numero'] ?? null,
            'fecha' => $datosExtraidos['fecha'] ?? null,
            'monto' => (float)str_replace(['.', ','], ['', '.'], $datosExtraidos['monto'] ?? 0),
            'moneda' => $datosExtraidos['moneda'] ?? 'UYU',
            'datos_extraidos' => $datosExtraidos,
            'pdf_path' => $pdfPath,
            'source_url' => $sourceUrl,
            'user_id' => $userId,
            'estado' => 'pendiente',
        ]);

        return $cfePendiente;
    }

    /**
     * Detect the type of CFE based on keywords in the text.
     *
     * @param  string  $text
     * @return string
     */
    protected function detectarTipoCfe(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');

        // Versión sin acentos del texto para detección insensible a acentos
        $textSinAcentos = $this->quitarAcentos($text);

        if (Str::contains($textSinAcentos, 'certificado de residencia') || Str::contains($textSinAcentos, 'certificado residencia')) {
            return 'certificado_residencia';
        }

        if (Str::contains($textSinAcentos, 'multa') || Str::contains($textSinAcentos, 'infraccion') || Str::contains($textSinAcentos, 'transito')) {
            return 'multas_cobradas';
        }

        // Detección de arrendamientos - buscar en el detalle (antes de eventuales para evitar falso positivo)
        if (Str::contains($textSinAcentos, 'arrendamiento') || Str::contains($textSinAcentos, 'arrendamientos')) {
            return 'arrendamientos';
        }

        if (Str::contains($text, 'aguinaldo') || Str::contains($text, 'policias eventuales') || Str::contains($text, 'eventuales')) {
            return 'eventuales';
        }

        if (Str::contains($textSinAcentos, 'tenencia') || Str::contains($textSinAcentos, 'tahta')) {
            return 'tenencia_armas';
        }

        if (Str::contains($textSinAcentos, 'porte')) {
            return 'porte_armas';
        }

        if (Str::contains($textSinAcentos, 'arma')) {
            // Si dice 'arma' pero no es tenencia ni porte, podría ser cualquiera de los dos, 
            // pero por lo visto en los documentos, suele ser parte de TAHTA.
            return 'tenencia_armas';
        }

        if (Str::contains($text, 'e-factura') || Str::contains($text, 'e-ticket') || Str::contains($text, 'e-boleta')) {
            return 'generico';
        }

        return 'desconocido';
    }

    /**
     * Elimina acentos de un texto para comparación insensible.
     */
    protected function quitarAcentos(string $text): string
    {
        $search  = ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', 'Ü'];
        $replace = ['a', 'e', 'i', 'o', 'u', 'n', 'u', 'a', 'e', 'i', 'o', 'u', 'n', 'u'];
        return str_replace($search, $replace, $text);
    }

    /**
     * Extract data from a CFE based on its text.
     *
     * @param  string  $text
     * @param  string  $tipoCfe
     * @return array
     */
    protected function extraerDatos(string $text, string $tipoCfe): array
    {
        // Si es CFE de armas, usar lógica específica
        if (in_array($tipoCfe, ['porte_armas', 'tenencia_armas'])) {
            return $this->extraerDatosArmas($text);
        }

        // Si es CFE de multas, usar lógica específica
        if ($tipoCfe === 'multas_cobradas') {
            return $this->extraerDatosMultas($text);
        }

        // Si es CFE de certificado de residencia, usar lógica específica
        if ($tipoCfe === 'certificado_residencia') {
            return $this->extraerDatosCertificadoResidencia($text);
        }

        // Si es CFE de arrendamientos, usar lógica específica
        if ($tipoCfe === 'arrendamientos') {
            return $this->extraerDatosArrendamientos($text);
        }

        // Si es CFE de eventuales o genérico (e-factura), usar lógica específica
        if (in_array($tipoCfe, ['eventuales', 'generico'])) {
            return $this->extraerDatosEventuales($text);
        }

        // Lógica genérica para otros tipos de CFE
        $datos = [
            'tipo_cfe' => $tipoCfe,
            'serie' => '',
            'numero' => '',
            'fecha' => '',
            'monto' => 0.0,
            'moneda' => 'UYU',
            'receptor_nombre' => '',
            'receptor_documento' => '',
            'emisor_nombre' => '',
            'emisor_rut' => '',
            'detalle' => '',
            'items' => []
        ];

        // 1. Serie y Número
        if (preg_match('/SERIE\s+NÚMERO[^\n]+\n\s*([A-Z]+)\s+(\d+)/i', $text, $matches)) {
            $datos['serie'] = $matches[1];
            $datos['numero'] = $matches[2];
        } elseif (preg_match('/([A-Z])[\s\t]+(\d+)[\s\t]+(?:Contado|Cr.dito)/i', $text, $matches)) {
            $datos['serie'] = $matches[1];
            $datos['numero'] = $matches[2];
        }

        // 2. Fecha
        if (preg_match('/FECHA\s+MONEDA\s*\n\s*(\d{2}\/\d{2}\/\d{4})/i', $text, $matches)) {
            $datos['fecha'] = $matches[1];
        } elseif (preg_match('/FECHA[\s:]+(?:MONEDA[\s:]+)?(\d{2}\/\d{2}\/\d{4})/i', $text, $matches)) {
            $datos['fecha'] = $matches[1];
        }

        // 3. Emisor
        if (preg_match('/(\d{12})\s+(?:e-Factura|e-Ticket|e-Boleta)/i', $text, $matches)) {
            $datos['emisor_rut'] = $matches[1];
        }

        if (preg_match('/^([^\n]+)\n([^\n]+)/', ltrim($text), $matches)) {
            $datos['emisor_nombre'] = trim($matches[1] . ' ' . $matches[2]);
        }

        // 4. Receptor
        if (preg_match('/(C\.I\.|RUT)\s*\(?[^\)]*\)?:\s*([\d\.-]+)/i', $text, $matches)) {
            $datos['receptor_documento'] = $matches[2];
        }

        if (preg_match('/NOMBRE O DENOMINACIÓN DOMICILIO FISCAL\s*\n\s*(.*?)(?=\s*\n\s*(?:INFORMACION ADICIONAL|DETALLE DESCRIPCIÓN|PERIODO|FECHA|$))/is', $text, $matches)) {
            $datos['receptor_nombre'] = trim(preg_replace('/\s+/', ' ', $matches[1]));
        } elseif (preg_match('/FISCAL\s*(.*?)(?=\s*(?:INFORMACION|DETALLE|FECHA|\d{2}\/\d{2}\/\d{4}|$))/isu', $text, $matches)) {
            $datos['receptor_nombre'] = trim(preg_replace('/\s+/', ' ', $matches[1]));
        }

        // 5. Monto
        if (preg_match('/TOTAL.*?:\s*([\d\.,]+)/i', $text, $matches)) {
            $datos['monto'] = floatval(str_replace(['.', ','], ['', '.'], $matches[1]));
        } elseif (preg_match('/MONTO\s+NO\s+FACTURABLE:\s*([\d\.,]+)/is', $text, $matches)) {
            $datos['monto'] = floatval(str_replace(['.', ','], ['', '.'], $matches[1]));
        }

        // 6. Moneda
        if (preg_match('/Peso uruguayo/i', $text)) {
            $datos['moneda'] = 'UYU';
        } elseif (preg_match('/Dólar/i', $text)) {
            $datos['moneda'] = 'USD';
        }

        // 7. Detalle
        if (preg_match('/DETALLE DESCRIPCIÓN[^\n]+\n\s*([^\n]+(?:\n\s*[^\n,]+)*)/i', $text, $matches)) {
            $datos['detalle'] = trim(preg_replace('/\s+/', ' ', $matches[1]));
        }

        return $datos;
    }

    /**
     * Extract data from CFE for Armas (Porte/Tenencia).
     *
     * @param  string  $text
     * @return array
     */
    protected function extraerDatosArmas(string $text): array
    {
        $datos = [
            'tipo_cfe' => 'No detectado',
            'serie' => '',
            'numero' => '',
            'fecha' => '',
            'emisor_rut' => '',
            'emisor_nombre' => '',
            'receptor_documento' => '',
            'receptor_nombre' => '',
            'monto' => 0.0,
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
        if (preg_match('/SERIE\s*N.MERO.*?\n\s*([A-Z]+)[\s\t]+(\d+)/iu', $text, $matches)) {
            $datos['serie'] = $matches[1];
            $datos['numero'] = $matches[2];
        } elseif (preg_match('/([A-Z])[\s\t]+(\d+)[\s\t]+(?:Contado|Cr.dito)/i', $text, $matches)) {
            $datos['serie'] = $matches[1];
            $datos['numero'] = $matches[2];
        }

        // Fecha
        if (preg_match('/FECHA\s*MONEDA.*?(?:\n|\t|\s)+(\d{2}\/\d{2}\/\d{4})/iu', $text, $matches)) {
            $datos['fecha'] = $matches[1];
        } elseif (preg_match('/FECHA[\s:]+(?:MONEDA[\s:]+)?(\d{2}\/\d{2}\/\d{4})/i', $text, $matches)) {
            $datos['fecha'] = $matches[1];
        } elseif (preg_match('/(\d{2}\/\d{2}\/\d{4})/', $text, $matches)) {
            $datos['fecha'] = $matches[1];
        }

        // RUC Emisor
        if (preg_match('/(\d{12})\s+(?:e-Factura|e-Ticket|e-Boleta)/i', $text, $matches)) {
            $datos['emisor_rut'] = $matches[1];
        }

        // Receptor (C.I. o Rut)
        if (preg_match('/(C\.I\.|RUT)\s*\(?[^\)]*\)?:\s*([\d\.-]+)/i', $text, $matches)) {
            $datos['receptor_documento'] = $matches[2];
        }

        // Razon Social Emisor
        if (preg_match('/^([^\n]+)\n([^\n]+)/', ltrim($text), $matches)) {
            $datos['emisor_nombre'] = trim($matches[1] . ' ' . $matches[2]);
        }

        // Nombre Receptor
        if (preg_match('/NOMBRE O DENOMINACIÓN DOMICILIO FISCAL\s*\n\s*(.*?)(?=\s*\n\s*(?:INFORMACION ADICIONAL|DETALLE DESCRIPCIÓN|PERIODO|FECHA|$))/is', $text, $matches)) {
            $datos['receptor_nombre'] = trim(preg_replace('/\s+/', ' ', $matches[1]));
        }

        // Montos
        if (preg_match('/TOTAL A PAGAR:\s*([\d\.,]+)/i', $text, $matches)) {
            $datos['monto'] = floatval(str_replace(['.', ','], ['', '.'], $matches[1]));
            $datos['subtotal'] = $datos['monto'];
        } elseif (preg_match('/MONTO NO FACTURABLE:\s*([\d\.,]+)/i', $text, $matches)) {
            $datos['monto'] = floatval(str_replace(['.', ','], ['', '.'], $matches[1]));
            $datos['subtotal'] = $datos['monto'];
        }

        // Moneda
        if (preg_match('/Peso uruguayo/i', $text)) {
            $datos['moneda'] = 'UYU';
        } elseif (preg_match('/Dólar/i', $text)) {
            $datos['moneda'] = 'USD';
        }

        // Datos adicionales de Jefatura (Ingreso y O/C)
        if (preg_match('/(?:ING\.?|ING:|INGRESO|ING)(?:\s*N.?)?[:\s\t-]*(\d+)/iu', $text, $matches)) {
            $datos['ingreso_contabilidad'] = $matches[1];
        }

        if (preg_match('/(?:ORDEN\s+DE\s+COBRO|ORDEN\s+COBRO|O\.C\.|O\/C|O\.\(?C\.)(?:\s*N.?)?[:\s\t-]*(\d+)/iu', $text, $matches)) {
            $datos['orden_cobro'] = $matches[1];
        }

        // Trámite
        if (preg_match('/(?:TRÁMITE|TRAMITE)(?:\s*N.?)?[:\s\t-]*([\d\/]+)/iu', $text, $matches)) {
            $datos['tramite'] = $matches[1];
        }

        // Teléfono
        if (preg_match('/(?:TEL\.?|TELÉFONO|CEL\.)\s*([\d\s\-\/]+)/iu', $text, $matches)) {
            $datos['telefono'] = trim($matches[1]);
        }

        // Adenda
        if (preg_match('/ADENDA\s*\n(.*?)(?=\s*(?:Fecha\s+de|Puede\s+verificar|I\.V\.A\.|NÚMERO\s+DE\s+CAE|$))/isu', $text, $matches)) {
            $adendaRaw = trim($matches[1]);
            $lineas = explode("\n", $adendaRaw);
            $lineasLimpias = array_map(function ($linea) {
                $linea = trim($linea);
                // Separar números de letras pegadas si ocurre
                $linea = preg_replace('/(\d)([A-Z])/u', '$1 $2', $linea);
                return $linea;
            }, $lineas);
            $lineasLimpias = array_filter($lineasLimpias, function ($linea) {
                return !empty($linea) && $linea !== '1';
            });
            $datos['adenda'] = implode("\n", $lineasLimpias);
        }

        // Orden de Cobro y otros de la adenda
        if (!empty($datos['adenda'])) {
            $adendaNorm = $this->quitarAcentos(mb_strtolower($datos['adenda'], 'UTF-8'));

            // Si después de los regex anteriores todavía no tenemos O/C, buscar en la adenda
            if (empty($datos['orden_cobro'])) {
                if (preg_match('/(?:orden\s+de\s+cobro|orden\s+cobro|o\.\s*\(?c\.?|o\/c)\s*(\d+)/iu', $adendaNorm, $ocMatch)) {
                    $datos['orden_cobro'] = $ocMatch[1];
                } else {
                    // Buscar un número de 4 a 6 dígitos que esté solo o con separadores
                    $numeros = [];
                    if (preg_match_all('/\b(\d{4,6})\b/', $datos['adenda'], $numMatches)) {
                        $numeros = $numMatches[1];
                    }
                    if (count($numeros) === 1) {
                        $datos['orden_cobro'] = $numeros[0];
                    }
                }
            }

            // Si no tenemos ingreso, buscar en adenda
            if (empty($datos['ingreso_contabilidad'])) {
                if (preg_match('/(?:ing\.?|ing:|ingreso|ing)(?:\s*n.?)?[:\s\t-]*(\d+)/iu', $adendaNorm, $ingMatch)) {
                    $datos['ingreso_contabilidad'] = $ingMatch[1];
                }
            }
        }

        // Detalle descriptivo
        if (preg_match('/DETALLE DESCRIPCIÓN[^\n]+\n\s*([^\n]+(?:\n\s*[^\n,]+)*)/i', $text, $matches)) {
            $datos['detalle'] = trim(preg_replace('/\s+/', ' ', $matches[1]));
        }

        return $datos;
    }

    /**
     * Extract data from CFE for Multas.
     *
     * @param  string  $text
     * @return array
     */
    /**
     * Extract data from CFE for Multas.
     *
     * @param  string  $text
     * @return array
     */
    protected function extraerDatosMultas(string $text): array
    {
        // Limpiar caracteres no válidos para UTF-8
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
        if (preg_match('/FISCAL\s*(.*?)(?=\s*(?:INFORMACION|DETALLE|FECHA|\d{2}\/\d{2}\/\d{4}|$))/isu', $text, $matches)) {
            $datos['nombre'] = trim(preg_replace('/\s+/', ' ', $matches[1]));
        }

        // Monto Total
        if (preg_match('/TOTAL\s+A\s+PAGAR:\s*([\d\.,]+)/is', $text, $matches)) {
            $datos['monto_total'] = $matches[1];
        } elseif (preg_match('/MONTO\s+NO\s+FACTURABLE:\s*([\d\.,]+)/is', $text, $matches)) {
            $datos['monto_total'] = $matches[1];
        }

        // Extracción de Medios de Pago
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

        // Información Adicional
        if (preg_match('/INFORMACION\s+ADICIONAL\s*\n(.*?)(?=\s*FECHA\s+MONEDA)/isu', $text, $matches)) {
            $adicionalRaw = trim($matches[1]);
            $datos['adicional'] = preg_replace('/\s+/', ' ', $adicionalRaw);
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

        // Referencias
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

                // Detectar línea de cierre de item
                if (preg_match('/^(.*?)([\d\.,]+(?:\s*\(Unid\))?[\s\t]*[\d\.,]+[\s\t]+([\d\.,]+))$/i', $linea, $m)) {
                    $restoLinea = trim($m[1]);
                    $importe = $m[3];

                    if (!empty($restoLinea)) {
                        $bufferItem[] = $restoLinea;
                    }

                    $itemActual['importe'] = (float)str_replace(['.', ','], ['', '.'], $importe);

                    if (!empty($bufferItem)) {
                        $fullText = implode(' ', $bufferItem);
                        $fullText = trim(preg_replace('/\s+/', ' ', $fullText));

                        $separator = 'CORRESPONDE A';
                        $pos = mb_stripos($fullText, $separator);

                        if ($pos !== false) {
                            $itemActual['detalle'] = trim(mb_substr($fullText, 0, $pos));
                            $itemActual['descripcion'] = trim(mb_substr($fullText, $pos));
                        } else {
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

        return $datos;
    }

    /**
     * Extract data from CFE for Eventuales (e-Factura).
     *
     * @param  string  $text
     * @return array
     */
    protected function extraerDatosEventuales(string $text): array
    {
        // Limpiar caracteres no válidos para UTF-8
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        $datos = [
            'tipo_cfe' => 'eventuales',
            'recibo' => '',
            'fecha' => '',
            'titular' => '',
            'monto' => 0.0,
            'medio_de_pago' => '',
            'detalle' => '',
            'orden_cobro' => '',
        ];

        // 1. Serie y Número (Recibo)
        if (preg_match('/SERIENÚMERO[^\n]*\n\s*([A-Z]+)\s+(\d+)/i', $text, $matches)) {
            $datos['recibo'] = $matches[1] . $matches[2];
        }

        // 2. Fecha
        if (preg_match('/FECHA\s+MONEDA\s*\n\s*(\d{2}\/\d{2}\/\d{4})/i', $text, $matches)) {
            $datos['fecha'] = $matches[1];
        }

        // 3. Titular (Nombre o Denominación)
        if (preg_match('/NOMBRE O DENOMINACIÓN DOMICILIO FISCAL\s*\n\s*(.*?)(?=\s*\n\s*(?:PERIODO|FECHA|DETALLE DESCRIPCIÓN|$))/is', $text, $matches)) {
            $lines = explode("\n", trim($matches[1]));
            $nombreLines = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                if (preg_match('/\d+/', $line) && !preg_match('/[A-Z]{3,}/', $line)) break;
                if (count($nombreLines) >= 2) break;
                $nombreLines[] = $line;
            }
            $datos['titular'] = implode(' ', $nombreLines);
        }

        // 4. Medio de Pago y Monto
        if (preg_match('/TOTAL A PAGAR:\s*[\d\.,]+\s*\n\s*([^:]+):\s*([\d\.,]+)/i', $text, $matches)) {
            $datos['medio_de_pago'] = trim($matches[1]);
            $datos['monto'] = (float)str_replace(['.', ','], ['', '.'], $matches[2]);
        } else {
            if (preg_match('/TOTAL A PAGAR:\s*([\d\.,]+)/i', $text, $matches)) {
                $datos['monto'] = (float)str_replace(['.', ','], ['', '.'], $matches[1]);
            }
            if (preg_match('/(Transferencia[^\n:]*)/i', $text, $matches)) {
                $datos['medio_de_pago'] = trim($matches[1]);
            }
        }

        // 5. Orden de Cobro
        if (preg_match('/REFERENCIAS:.*?(?:e-?Factura|eFactura)\s+([A-Z0-9]+)/is', $text, $matches)) {
            $datos['orden_cobro'] = $matches[1];
        }

        // 6. Detalle (Concatenación de ítems)
        if (preg_match('/DETALLE DESCRIPCIÓN CANT\. PRECIO DESC\. REC\. IMPORTE\s*\n\s*(.*?)(?=\s*\n\s*(?:MONTO NO FACTURABLE|MONTO TOTAL|TOTAL A PAGAR|IVA|$))/is', $text, $matches)) {
            $detalleBlock = $matches[1];
            $lines = explode("\n", $detalleBlock);
            $items = [];
            $currentItem = "";
            $currentDesc = [];

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                if (preg_match('/^(.*?)\s*([\d\.,]+\s*\([^\)]+\)\s*[\d\.,]+\s*[\d\.,]+)$/i', $line, $itemMatches)) {
                    $textBefore = trim($itemMatches[1]);

                    if (preg_match('/^(.*?)(?:\s{2,}|\t)(.*)$/', $textBefore, $parts)) {
                        $detalle = trim($parts[1]);
                        $descripcion = trim($parts[2]);
                    } else {
                        $detalle = $textBefore;
                        $descripcion = "";
                    }

                    if (empty($currentItem)) {
                        $currentItem = $detalle . ($descripcion ? " ($descripcion)" : "");
                    } else {
                        $descAcumulada = implode(' ', $currentDesc);
                        $descFull = trim($descAcumulada . " " . $detalle . " " . $descripcion);
                        $itemFinal = $currentItem . ($descFull ? " ($descFull)" : "");
                        $items[] = $itemFinal;
                        $currentItem = "";
                        $currentDesc = [];

                        // Si es el inicio de uno nuevo en la misma lógica...
                        $currentItem = $detalle . ($descripcion ? " ($descripcion)" : "");
                    }

                    // Simple logic for the loop: add current item
                    if (!empty($currentItem)) {
                        $items[] = $currentItem;
                        $currentItem = "";
                        $currentDesc = [];
                    }
                } else {
                    if (empty($currentItem)) {
                        $currentItem = $line;
                    } else {
                        $currentDesc[] = $line;
                    }
                }
            }
            if (!empty($items)) {
                $datos['detalle'] = implode(" / ", $items);
            }
        }

        return $datos;
    }

    /**
     * Extract data from CFE for Certificado de Residencia.
     *
     * @param  string  $text
     * @return array
     */
    protected function extraerDatosCertificadoResidencia(string $text): array
    {
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        $datos = [
            'tipo_cfe' => 'No detectado',
            'serie' => '',
            'numero' => '',
            'fecha' => '',
            'cedula_receptor' => '',
            'nombre_receptor' => '',
            'monto' => 0.0,
            'monto_total' => 0.0,
            'moneda' => 'UYU',
            'telefono' => '',
            'forma_pago' => 'SIN DATOS',
            'detalle' => '',
            'descripcion' => '',
            'cedula_titular' => '',
            'retira_es_titular' => true,
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

        // Receptor (CI del CFE)
        if (preg_match('/(?:C\.I\.|RUT).*?:\s*([\d\.-]+)/is', $text, $matches)) {
            $datos['cedula_receptor'] = $matches[1];
        }

        // Nombre Receptor
        if (preg_match('/NOMBRE O DENOMINACIÓN DOMICILIO FISCAL\s*\n\s*(.*?)(?=\s*\n\s*(?:INFORMACION ADICIONAL|DETALLE DESCRIPCIÓN|PERIODO|FECHA|$))/isu', $text, $matches)) {
            $datos['nombre_receptor'] = trim(preg_replace('/\s+/', ' ', $matches[1]));
        } elseif (preg_match('/FISCAL\s*(.*?)(?=\s*(?:INFORMACION|DETALLE|FECHA|\d{2}\/\d{2}\/\d{4}|$))/isu', $text, $matches)) {
            $datos['nombre_receptor'] = trim(preg_replace('/\s+/', ' ', $matches[1]));
        }

        // Monto Total
        if (preg_match('/TOTAL\s+A\s+PAGAR:\s*([\d\.,]+)/is', $text, $matches)) {
            $datos['monto_total'] = $matches[1];
            $datos['monto'] = floatval(str_replace(['.', ','], ['', '.'], $matches[1]));
        } elseif (preg_match('/MONTO\s+NO\s+FACTURABLE:\s*([\d\.,]+)/is', $text, $matches)) {
            $datos['monto_total'] = $matches[1];
            $datos['monto'] = floatval(str_replace(['.', ','], ['', '.'], $matches[1]));
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

        // Teléfono
        if (preg_match('/(?:TEL\.|TELÉFONO|CEL\.)\s*([\d\s\-\/]+)/i', $text, $matches)) {
            $datos['telefono'] = trim($matches[1]);
        }

        // Moneda
        if (preg_match('/Peso uruguayo/i', $text)) {
            $datos['moneda'] = 'UYU';
        } elseif (preg_match('/Dólar/i', $text)) {
            $datos['moneda'] = 'USD';
        }

        // Extracción de Items (Detalle + Descripción)
        if (preg_match('/DETALLE\s+DESCRIPCI.N.*?IMPORTE\s*(.*?)(?=\s*MONTO\s+NO\s+FACTURABLE)/isu', $text, $matches)) {
            $bloqueItems = trim($matches[1]);
            $lineas = explode("\n", $bloqueItems);
            $bufferItem = [];

            foreach ($lineas as $linea) {
                $linea = trim($linea);
                if (empty($linea)) continue;

                if (preg_match('/^(.*?)([\d\.,]+(?:\s*\(Unid\))?\s*[\d\.,]+\s+([\d\.,]+))$/i', $linea, $m)) {
                    $restoLinea = trim($m[1]);
                    if (!empty($restoLinea)) {
                        $bufferItem[] = $restoLinea;
                    }

                    $fullText = implode(' ', $bufferItem);
                    $fullText = trim(preg_replace('/\s+/', ' ', $fullText));

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

        // Detectar si la descripción contiene una cédula (del titular del certificado)
        if (!empty($datos['descripcion'])) {
            if (preg_match('/([\d][\d\.]{4,}[\d])/u', $datos['descripcion'], $ciMatch)) {
                $ciLimpia = preg_replace('/[^0-9]/', '', $ciMatch[1]);
                if (strlen($ciLimpia) >= 6 && strlen($ciLimpia) <= 10) {
                    $datos['cedula_titular'] = $ciMatch[1];
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
     * Extract data from CFE for Arrendamientos.
     *
     * @param  string  $text
     * @return array
     */
    protected function extraerDatosArrendamientos(string $text): array
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
        // Búsqueda insensible a mayúsculas y acentos
        if (!empty($datos['adenda'])) {
            $adendaSinAcentos = $this->quitarAcentos(mb_strtolower($datos['adenda'], 'UTF-8'));

            // Patrones para orden de cobro: ORDEN DE COBRO, ORDEN COBRO, O.C., O/C, O.(C.
            if (preg_match('/(?:orden\s+de\s+cobro|orden\s+cobro|o\.\s*\(?c\.?|o\/c)\s*(\d+)/iu', $adendaSinAcentos, $ocMatch)) {
                $datos['orden_cobro'] = $ocMatch[1];
            } else {
                // Si hay solamente un número en la adenda, es el número de orden de cobro
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

    protected function extraerItemsMultas(string $text): array
    {
        $items = [];
        if (preg_match('/DETALLE\s+DESCRIPCI.N.*?IMPORTE\s*(.*?)(?=\s*MONTO\s+NO\s+FACTURABLE)/isu', $text, $matches)) {
            $bloqueItems = $matches[1];
            $lineas = explode("\n", $bloqueItems);
            $itemActual = ['detalle' => '', 'descripcion' => '', 'importe' => 0];
            $bufferItem = [];

            foreach ($lineas as $linea) {
                $linea = trim($linea);
                if (empty($linea)) continue;

                if (preg_match('/^(.*?)([\d\.,]+(?:\s*\(Unid\))?[\s\t]*[\d\.,]+[\s\t]+([\d\.,]+))$/i', $linea, $m)) {
                    $restoLinea = trim($m[1]);
                    $importe = $m[3];
                    if (!empty($restoLinea)) $bufferItem[] = $restoLinea;

                    $itemActual['importe'] = (float)str_replace(['.', ','], ['', '.'], $importe);
                    $fullText = trim(preg_replace('/\s+/', ' ', implode(' ', $bufferItem)));

                    $separator = 'CORRESPONDE A';
                    $pos = mb_stripos($fullText, $separator);
                    if ($pos !== false) {
                        $itemActual['detalle'] = trim(mb_substr($fullText, 0, $pos));
                        $itemActual['descripcion'] = trim(mb_substr($fullText, $pos));
                    } else {
                        $itemActual['detalle'] = $fullText;
                    }

                    $items[] = $itemActual;
                    $itemActual = ['detalle' => '', 'descripcion' => '', 'importe' => 0];
                    $bufferItem = [];
                } else {
                    $bufferItem[] = $linea;
                }
            }
        }
        return $items;
    }

    /**
     * Analizar un PDF desde una ruta local.
     *
     * @param  string  $filepath
     * @return array
     */
    public function analizarPdf(string $filepath): array
    {
        try {
            if (!file_exists($filepath)) {
                throw new \Exception("El archivo no existe en la ruta: " . $filepath);
            }

            $parser = new Parser();
            $pdfContent = $parser->parseFile($filepath);
            $text = $pdfContent->getText();

            // Detectar tipo de CFE
            $tipoCfe = $this->detectarTipoCfe($text);

            if ($tipoCfe === 'desconocido') {
                return [
                    'es_cfe' => false,
                    'mensaje' => 'Este PDF no contiene un CFE reconocido por el sistema.',
                    'tipo_cfe' => null,
                    'datos' => []
                ];
            }

            // Extraer datos generales y específicos
            $datosExtraidos = $this->extraerDatos($text, $tipoCfe);

            // Nombres amigables para los tipos
            $nombresAmigables = [
                'certificado_residencia' => 'Certificado de Residencia',
                'multas_cobradas' => 'Multas Cobradas',
                'arrendamientos' => 'Arrendamientos',
                'porte_armas' => 'Porte de Armas',
                'tenencia_armas' => 'Tenencia de Armas',
                'eventuales' => 'Eventuales (e-Factura)',
                'generico' => 'CFE Genérico',
            ];

            return [
                'es_cfe' => true,
                'tipo_cfe' => $nombresAmigables[$tipoCfe] ?? $tipoCfe,
                'tipo_cfe_codigo' => $tipoCfe,
                'datos' => $datosExtraidos,
                'mensaje' => 'CFE detectado: ' . ($nombresAmigables[$tipoCfe] ?? $tipoCfe)
            ];
        } catch (\Exception $e) {
            return [
                'es_cfe' => false,
                'mensaje' => 'Error al leer el PDF: ' . $e->getMessage(),
                'tipo_cfe' => null,
                'datos' => []
            ];
        }
    }

    /**
     * Crear un registro en el módulo correspondiente desde los datos analizados.
     *
     * @param  string  $tipoCfe
     * @param  array  $datos
     * @param  string|null  $filepath
     * @return array
     */
    public function crearRegistroDesdeAnalisis(string $tipoCfe, array $datos, ?string $filepath = null): array
    {
        try {
            // Determinar qué módulo usar basándose en el tipo de CFE
            $redirectUrl = null;
            $mensaje = '';

            switch ($tipoCfe) {
                case 'Multas Cobradas':
                case 'multas_cobradas':
                    // Redirigir al módulo de multas cobradas (pestaña cargar-cfe)
                    $redirectUrl = url('tesoreria/multas-cobradas/cargar-cfe');
                    $mensaje = 'Registro preparado. Por favor confirme los datos en el módulo de Multas Cobradas.';
                    break;

                case 'Eventuales (e-Factura)':
                case 'eventuales':
                    $redirectUrl = url('tesoreria/eventuales/cargar-efactura');
                    $mensaje = 'Registro preparado para el módulo de Eventuales.';
                    break;

                case 'Porte de Armas':
                case 'porte_armas':
                case 'Tenencia de Armas':
                case 'tenencia_armas':
                    $redirectUrl = url('tesoreria/armas/cargar-cfe');
                    $mensaje = 'Registro preparado para el módulo de Armas.';
                    break;

                case 'Certificado de Residencia':
                case 'certificado_residencia':
                    $redirectUrl = url('tesoreria/certificados-residencia/cargar-cfe');
                    $mensaje = 'Registro preparado para el módulo de Certificados de Residencia.';
                    break;

                case 'Arrendamientos':
                case 'arrendamientos':
                    $redirectUrl = url('tesoreria/arrendamientos/cargar-cfe');
                    $mensaje = 'Registro preparado para el módulo de Arrendamientos.';
                    break;

                default:
                    return [
                        'success' => false,
                        'mensaje' => 'Tipo de CFE no soportado para creación automática: ' . $tipoCfe
                    ];
            }

            // Guardar los datos en CACHÉ en lugar de sesión
            if (!empty($datos)) {
                $prefillId = Str::random(40);
                Cache::put('cfe_prefill_' . $prefillId, [
                    'datos' => $datos,
                    'tipo' => $tipoCfe,
                    'filepath' => $filepath
                ], now()->addMinutes(15));

                // Añadir el ID a la URL de redirección
                $separator = strpos($redirectUrl, '?') !== false ? '&' : '?';
                $redirectUrl .= $separator . 'prefill_id=' . $prefillId;
            }

            return [
                'success' => true,
                'mensaje' => $mensaje,
                'redirect_url' => $redirectUrl,
                'tipo_cfe' => $tipoCfe
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'mensaje' => 'Error al preparar el registro: ' . $e->getMessage()
            ];
        }
    }
}
