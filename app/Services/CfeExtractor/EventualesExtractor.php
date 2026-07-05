<?php

namespace App\Services\CfeExtractor;

use App\DTOs\CfeExtraccionDto;
use App\Exceptions\CfeExtraccionInvalidaException;
use App\Services\CfeExtractor\BaseExtractor;

/**
 * Extractor para CFE de Policías Eventuales (e-Factura Cobranza / e-Ticket Cobranza).
 */
class EventualesExtractor extends BaseExtractor
{
    /**
     * Tipos de CFE que este extractor soporta.
     */
    private const TIPOS_SOPORTADOS = [
        'eventual', 'aguinaldo', 'generico', 'e-factura', 'e-ticket', 'e-boleta',
    ];

    /**
     * Verifica si este extractor soporta el tipo dado.
     */
    public function soporta(string $tipo): bool
    {
        $tipoLower = mb_strtolower($tipo, 'UTF-8');

        foreach (self::TIPOS_SOPORTADOS as $soportado) {
            if (str_contains($tipoLower, $soportado)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retorna el nombre legible del módulo.
     */
    public function getNombreLegible(): string
    {
        return 'Eventuales (e-Factura)';
    }

    /**
     * Obtiene la versión del extractor.
     *
     * @return string
     */
    public function getExtractorVersion(): string
    {
        return '1.0.0';
    }

    /**
     * Extrae array de datos de eventuales.
     *
     * @param string $texto
     * @return array
     */
    protected function extraerArray(string $texto): array
    {
        $texto = $this->limpiarTexto($texto);

        $datos = $this->getEstructuraBase() + [
            'recibo'         => '',
            'titular'        => '',
            'ruc_receptor'   => '',
            'periodo'        => '',
            'vencimiento'    => '',
            'forma_pago_doc' => '',   // CRÉDITO / CONTADO del encabezado del CFE
            'monto'          => 0.0,
            'medio_de_pago'  => '',   // Transferencia Bancaria, etc.
            'ingreso'        => '',   // Número ING. de la adenda
            'detalle'        => '',   // Texto legible de los conceptos
            'items'          => [],   // Líneas del bloque DETALLE
            'orden_cobro'    => '',   // Referencia a e-Factura original
            'adenda'         => '',
            'referencias'    => '',
        ];

        // Tipo de CFE (e-Factura, e-Ticket, etc.)
        $datos['tipo_cfe'] = $this->extraerTipoCfe($texto);

        // --- ENCABEZADO: Serie, Número, Forma de pago del doc., Vencimiento ---
        // Patrón: "SERIENÚMERO FORMA DE PAGO VENCIMIENTO\nA 4788 CRÉDITO 31/03/2026"
        // La "Ú" puede aparecer como "Ú" o "U" según el PDF parser
        if (preg_match(
            '/SERIE\s*N[ÚU]MERO\b[^\n]*\n\s*([A-Z])\s+(\d+)\s+(\w+(?:\s+\w+)*?)\s+(\d{2}\/\d{2}\/\d{4})/iu',
            $texto,
            $m
        )) {
            $datos['serie']          = mb_strtoupper(trim($m[1]), 'UTF-8');
            $datos['numero']         = trim($m[2]);
            $datos['forma_pago_doc'] = mb_strtoupper(trim($m[3]), 'UTF-8');
            $datos['vencimiento']    = trim($m[4]);
            $datos['recibo']         = $datos['serie'] . '-' . $datos['numero'];
        } elseif (preg_match('/SERIE\s*N[ÚU]MERO[^\n]*\n\s*([A-Z])\s+(\d+)/iu', $texto, $m)) {
            // Fallback: sin forma de pago/vencimiento legibles
            $datos['serie']  = mb_strtoupper(trim($m[1]), 'UTF-8');
            $datos['numero'] = trim($m[2]);
            $datos['recibo'] = $datos['serie'] . '-' . $datos['numero'];
        }

        // --- FECHA y MONEDA ---
        // Patrón: "FECHA\tMONEDA\n22/05/2026 Peso uruguayo"
        if (preg_match('/FECHA\s+MONEDA\s*\n\s*(\d{2}\/\d{2}\/\d{4})/iu', $texto, $m)) {
            $datos['fecha'] = trim($m[1]);
        } elseif (preg_match('/FECHA\s*:?\s*(\d{2}\/\d{2}\/\d{4})/iu', $texto, $m)) {
            $datos['fecha'] = trim($m[1]);
        }

        $datos['moneda'] = $this->detectarMoneda($texto);

        // --- RUC COMPRADOR (receptor) ---
        if (preg_match('/RUC\s+COMPRADOR\s*\n\s*(\d[\d\s]*)/iu', $texto, $m)) {
            $datos['ruc_receptor'] = preg_replace('/\s+/', '', trim($m[1]));
        }

        // --- TITULAR (nombre receptor, multi-línea) ---
        $datos['titular'] = $this->extraerTitular($texto);
        $datos['receptor_nombre'] = $datos['titular'];
        $datos['receptor_documento'] = $datos['ruc_receptor'];

        // --- PERIODO ---
        if (preg_match('/PERIODO\s*\n\s*(\d{2}\/\d{2}\/\d{4}\s*-\s*\d{2}\/\d{2}\/\d{4})/iu', $texto, $m)) {
            $datos['periodo'] = trim($m[1]);
        }

        // --- ITEMS del bloque DETALLE ---
        $datos['items'] = $this->extraerItems($texto);

        // --- MONTO TOTAL A PAGAR ---
        // Puede ser negativo en notas de crédito: "TOTAL A PAGAR:  -13.774,32"
        if (preg_match('/TOTAL\s+A\s+PAGAR:\s*([-\d\.,]+)/iu', $texto, $m)) {
            $datos['monto'] = $this->parsearMonto($m[1]);
        } elseif (preg_match('/MONTO\s+NO\s+FACTURABLE:\s*([-\d\.,]+)/iu', $texto, $m)) {
            $datos['monto'] = $this->parsearMonto($m[1]);
        }

        // --- MEDIO DE PAGO y SUMA DE MONTOS DE MEDIOS DE PAGO ---
        $resultadoMedios = $this->extraerMediosYMontos($texto);
        $datos['medio_de_pago'] = $resultadoMedios['medio_de_pago'];
        
        if ($resultadoMedios['tiene_montos']) {
            $datos['monto'] = $resultadoMedios['suma_montos'];
        }

        // --- REFERENCIAS ---
        $datos['referencias'] = $this->extraerReferencias($texto);

        // --- ORDEN DE COBRO (e-Factura/e-Ticket de origen en REFERENCIAS) ---
        $datos['orden_cobro'] = $this->extraerOrdenCobro($datos['referencias']);

        // --- ADENDA ---
        $datos['adenda'] = $this->extraerAdenda($texto);

        // --- ING. (número de ingreso) desde la adenda ---
        $datos['ingreso'] = $this->extraerIngreso($datos['adenda']);
        $datos['ingreso_contabilidad'] = $datos['ingreso'];

        // --- DETALLE (texto legible construido desde los ítems) ---
        $detalle = $this->construirTextoDetalle($datos['items']);

        // --- DOMICILIO (dirección del receptor, extraída del bloque NOMBRE O DENOMINACIÓN) ---
        $domicilio = $this->extraerDomicilio($texto);
        if (!empty($domicilio)) {
            $detalle .= ' | Domicilio: ' . $domicilio;
        }

        $datos['detalle'] = $detalle;

        return $datos;
    }

    // ─── Extractores privados ──────────────────────────────────────────────────

    /**
     * Extrae el nombre del titular (receptor) desde el bloque NOMBRE O DENOMINACIÓN.
     * El bloque puede tener múltiples líneas antes de llegar a la dirección o PERIODO.
     */
    private function extraerTitular(string $texto): string
    {
        // Captura el bloque entre la etiqueta y la siguiente sección relevante
        if (!preg_match(
            '/NOMBRE\s+O\s+DENOMINACI[ÓO]N\s+DOMICILIO\s+FISCAL\s*\n(.*?)(?=PERIODO|FECHA\s+MONEDA)/isu',
            $texto,
            $m
        )) {
            return '';
        }

        $bloque = trim($m[1]);
        $lineas = explode("\n", $bloque);
        $partes = [];

        // Abreviaciones de vías públicas que indican inicio de dirección
        $abrevViasPublicas = '/\b(AVDA?\.?|CALLE|RAMBLA|BLVD\.?|BVAR\.?|BV\.?|RUTA|DIAGONAL)\b/iu';

        // Abreviaciones de títulos que indican continuación del nombre en la siguiente línea
        $abrevTitulos = '/\b(SR\.?|SRA\.?|DR\.?|DRA\.?|LIC\.?|PROF\.?|ING\.?|ARQ\.?|TEC\.?|CR\.?|SRITO\.?|SEC\.?)\s*$/iu';

        // Rastrear paréntesis abiertos (ej: "INISA (INSTITUTO\nNACIONAL DE\n...ADOLESCENTE)")
        $parentesisAbiertos = 0;

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (empty($linea)) {
                continue;
            }

            $parentesisAbiertos += substr_count($linea, '(') - substr_count($linea, ')');

            // Detener al llegar a la línea de dirección con "Apto:" (solo si no estamos en paréntesis)
            if ($parentesisAbiertos <= 0 && preg_match('/Apto\s*:/iu', $linea)) {
                break;
            }

            // Detener si la línea es únicamente numérica (CP, RUC suelto)
            if ($parentesisAbiertos <= 0 && preg_match('/^\d[\d\s\-\.]+$/', $linea)) {
                break;
            }

            // Detener si la línea contiene número de calle >= 3 dígitos (solo si fuera del paréntesis)
            if ($parentesisAbiertos <= 0 && !empty($partes) && preg_match('/\b\d{3,}\b/', $linea) && !preg_match('/\(/', $linea)) {
                break;
            }

            // Detener en nombres de calles/avenidas (solo fuera de paréntesis)
            if ($parentesisAbiertos <= 0 && !empty($partes) && preg_match($abrevViasPublicas, $linea)) {
                break;
            }

            // Detener ante nombre de ciudad corto en mayúsculas (ej: "MONTEVIDEO"), solo fuera de paréntesis
            // No detener si la línea anterior termina con abreviatura de título (DR., SR., etc.)
            $lineaAnteriorTerminaTitulo = !empty($partes) && preg_match($abrevTitulos, end($partes));
            if ($parentesisAbiertos <= 0 && !empty($partes) && !$lineaAnteriorTerminaTitulo && preg_match('/^[A-ZÁÉÍÓÚÑ\s,\-]{4,20}$/', $linea)) {
                break;
            }

            $partes[] = $linea;
        }

        // Unir y limpiar espacios redundantes
        return trim(preg_replace('/\s+/', ' ', implode(' ', $partes)));
    }

    /**
     * Extrae la dirección (domicilio) del receptor desde el bloque NOMBRE O DENOMINACIÓN.
     * Son las líneas posteriores al nombre y antes de PERIODO.
     */
    private function extraerDomicilio(string $texto): string
    {
        if (!preg_match(
            '/NOMBRE\s+O\s+DENOMINACI[ÓO]N\s+DOMICILIO\s+FISCAL\s*\n(.*?)(?=PERIODO|FECHA\s+MONEDA)/isu',
            $texto,
            $m
        )) {
            return '';
        }

        $bloque = trim($m[1]);
        $lineas = explode("\n", $bloque);
        $domicilioLineas = [];
        $parentesisAbiertos = 0;
        $empezado = false;

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (empty($linea)) {
                continue;
            }

            $parentesisAbiertos += substr_count($linea, '(') - substr_count($linea, ')');

            if ($empezado) {
                $domicilioLineas[] = $linea;
            } elseif ($parentesisAbiertos <= 0) {
                if (preg_match('/\b\d{3,}\b/', $linea) && !preg_match('/\(/', $linea)) {
                    $empezado = true;
                    $domicilioLineas[] = $linea;
                } elseif (preg_match('/Apto\s*:/iu', $linea)) {
                    $empezado = true;
                    $domicilioLineas[] = $linea;
                } elseif (preg_match('/\b(AVDA?\.?|CALLE|RAMBLA|BLVD\.?|BVAR\.?|BV\.?|RUTA|DIAGONAL)\b/iu', $linea)) {
                    $empezado = true;
                    $domicilioLineas[] = $linea;
                }
            }
        }

        return trim(preg_replace('/\s+/', ' ', implode(' ', $domicilioLineas)));
    }

    /**
     * Extrae las líneas de ítems del bloque DETALLE DESCRIPCIÓN.
     *
     * Formato del PDF (separado por tabs):
     *   Concepto\tDescripción CANT,DEC (Unidad) PRECIO_UNIT\tIMPORTE
     *
     * Ejemplos reales:
     *   "Nocturnidad\tPolicias eventuales 216,000 (Hora) 63,77\t13.774,32"
     *   "Sueldos\tPolicias eventuales 4,000 (Func) 81.612,12\t326.448,48"
     *   "Ajuste de Enero 2026 Policias eventuales 1,000 (func) 21.627,72\t21.627,72"  ← sin tab entre concepto y desc.
     *   "Nocturnidad\tPolicias eventuales 216,000 (Hora) 63,77\t-13.774,32"  ← importe negativo (NC)
     */
    private function extraerItems(string $texto): array
    {
        // Capturar el bloque entre el encabezado del detalle y MONTO NO FACTURABLE/MONTO TOTAL/TOTAL A PAGAR
        if (!preg_match(
            '/DETALLE\s+DESCRIPCI[ÓO]N\s+CANT\.?\s+PRECIO\b[^\n]*\n(.*?)(?=MONTO\s+NO\s+FACTURABLE|MONTO\s+TOTAL|TOTAL\s+A\s+PAGAR)/isu',
            $texto,
            $m
        )) {
            return [];
        }

        $bloque = $m[1];
        $lineas = array_filter(array_map('trim', explode("\n", $bloque)));
        $items  = [];

        foreach ($lineas as $linea) {
            if (empty($linea)) {
                continue;
            }

            $item = $this->parsearLineaItem($linea);
            if ($item !== null) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Parsea una línea de ítem del bloque DETALLE.
     * Retorna null si la línea no corresponde a un ítem válido.
     *
     * @return array{concepto:string, descripcion:string, cantidad:float, unidad:string, precio:float, importe:float}|null
     */
    private function parsearLineaItem(string $linea): ?array
    {
        // Patrón general: todo-antes-de-cantidad (CANT,DEC) [(Unidad)] precio\t±importe
        // La unidad entre paréntesis (Hora, Func, etc.) puede no estar presente.
        // La cantidad puede usar punto de miles y coma decimal: "216,000" = 216 o "4,000" = 4
        $patron = '/^(.*?)\s+([\d\.]+,\d+)(?:\s+\(([^)]+)\))?\s+([\d\.,]+)\s*\t\s*([-\d\.,]+)\s*$/u';

        if (!preg_match($patron, $linea, $m)) {
            return null;
        }

        $textoIzq    = trim($m[1]);
        $cantStr     = $m[2];
        $unidad      = isset($m[3]) ? trim($m[3]) : '';
        $precioStr   = $m[4];
        $importeStr  = $m[5];

        // Separar concepto y descripción aprovechando el tab si existe
        $concepto    = $textoIzq;
        $descripcion = '';

        if (str_contains($textoIzq, "\t")) {
            [$concepto, $descripcion] = explode("\t", $textoIzq, 2);
            $concepto    = trim($concepto);
            $descripcion = trim($descripcion);
        }

        return [
            'concepto'    => $concepto,
            'descripcion' => $descripcion,
            'cantidad'    => $this->parsearCantidad($cantStr),
            'unidad'      => $unidad,
            'precio'      => $this->parsearMonto($precioStr),
            'importe'     => $this->parsearMonto($importeStr),
        ];
    }

    /**
     * Convierte una cantidad con formato uruguayo ("216,000" → 216.0, "4,000" → 4.0).
     * La coma es separador decimal pero las cantidades de personas/funciones tienen ,000.
     */
    private function parsearCantidad(string $str): float
    {
        // "216,000" con punto de miles no presente → el número antes de la coma es la cantidad entera
        // "81.612,12" sería precio (no cantidad) – aquí str siempre viene de la posición de CANT
        // Estrategia: si la parte después de la coma son 3 ceros, es un entero con separador de mil
        if (preg_match('/^([\d\.]+),000$/', $str, $c)) {
            return (float) str_replace('.', '', $c[1]);
        }

        // Caso genérico: convertir como monto uruguayo (punto=miles, coma=decimal)
        return $this->parsearMonto($str);
    }

    /**
     * Extrae el medio de pago y calcula la suma de todos los montos de pago especificados.
     */
    private function extraerMediosYMontos(string $texto): array
    {
        $res = [
            'medio_de_pago' => '',
            'suma_montos'   => 0.0,
            'tiene_montos'  => false
        ];

        // Capturar el bloque entre TOTAL A PAGAR y REFERENCIAS (o ADENDA si no hay REFERENCIAS)
        $patron = '/TOTAL\s+A\s+PAGAR:\s*[-\d\.,]+\s*\n(.*?)(?=REFERENCIAS:|ADENDA\b)/isu';

        if (!preg_match($patron, $texto, $m)) {
            return $res;
        }

        $bloque = trim($m[1]);
        if (empty($bloque)) {
            return $res;
        }

        $lineas = array_filter(array_map('trim', explode("\n", $bloque)));
        $medios = [];
        $suma = 0.0;
        $tiene = false;

        foreach ($lineas as $linea) {
            if (empty($linea)) {
                continue;
            }

            // Línea con importe: "Transferencia Bancaria: 357.647,00" o "Transferencia Bancaria : 14.795,00"
            if (preg_match('/^(.+?)\s*:\s*([-\d\.,]+)\s*$/u', $linea, $lm)) {
                $nombreMedio = trim($lm[1]);
                $montoMedio = $this->parsearMonto($lm[2]);
                
                $medios[] = $nombreMedio;
                $suma += $montoMedio;
                $tiene = true;
            } elseif (!preg_match('/^\d{2}\/\d{2}\/\d{4}/', $linea)) {
                // Línea sin importe que no es una fecha → añadir tal cual
                $medios[] = $linea;
            }
        }

        $res['medio_de_pago'] = implode(', ', array_unique(array_filter($medios)));
        $res['suma_montos']   = $suma;
        $res['tiene_montos']  = $tiene;

        return $res;
    }

    /**
     * Extrae el bloque completo de REFERENCIAS como string limpio.
     */
    private function extraerReferencias(string $texto): string
    {
        if (preg_match('/REFERENCIAS:\s*\n(.*?)(?=ADENDA\b|Fecha\s+de\s+Vencimiento|$)/isu', $texto, $m)) {
            return trim(preg_replace('/\s+/', ' ', $m[1]));
        }

        return '';
    }

    /**
     * Extrae la referencia al CFE original (e-Factura/e-Ticket) desde el texto de REFERENCIAS.
     * Ejemplo: "e-Factura-A-3679 13/03/2026" → "e-Factura-A-3679"
     */
    private function extraerOrdenCobro(string $referencias): string
    {
        // Variante 1: "e-Factura-A-3679" (con guiones completos)
        if (preg_match('/\be-(?:Factura|Ticket|Boleta)-([A-Z])-?(\d+)\b/iu', $referencias, $m)) {
            return mb_strtoupper($m[1] . '-' . $m[2], 'UTF-8');
        }

        // Variante 2: "e-Factura A1707" or "e-Factura-A1707"
        if (preg_match('/\be-(?:Factura|Ticket|Boleta)[\s\-]*([A-Z])\s*(\d+)\b/iu', $referencias, $m)) {
            return mb_strtoupper($m[1] . '-' . $m[2], 'UTF-8');
        }

        return '';
    }

    /**
     * Extrae el número de ingreso (ING./INGRESO) desde el texto de la adenda.
     * Ejemplo: "Sueldos de Marzo-26. ING. 3020" → "3020"
     */
    private function extraerIngreso(string $adenda): string
    {
        if (preg_match('/\bING(?:\.|RESO)?\s*:?\s*(\d+)/iu', $adenda, $m)) {
            return trim($m[1]);
        }

        return '';
    }

    /**
     * Construye un texto de detalle legible desde los ítems extraídos.
     */
    private function construirTextoDetalle(array $items): string
    {
        if (empty($items)) {
            return '';
        }

        $partes = [];
        foreach ($items as $item) {
            $parte = $item['concepto'];
            if (!empty($item['descripcion'])) {
                $parte .= ' (' . $item['descripcion'] . ')';
            }
            $partes[] = $parte;
        }

        return implode(' / ', $partes);
    }

/**
     * Validación específica para CFEs de eventuales.
     *
     * @param CfeExtraccionDto $dto
     * @return void
     * @throws CfeExtraccionInvalidaException
     */
    public function validar(CfeExtraccionDto $dto): void
    {
        $errors = [];

        if (empty($dto->fecha)) {
            $errors[] = 'Fecha no detectada';
        }

        if (empty($dto->serie) || empty($dto->numero)) {
            $errors[] = 'Serie/Numero no detectado';
        }

        if (empty($dto->monto)) {
            $errors[] = 'Monto no valido';
        }

        if (!empty($errors)) {
            throw \App\Exceptions\CfeExtraccionInvalidaException::fromValidationErrors($errors);
        }

        $data = $dto->toArray();
        $warnings = [];

        // RUC e ING son deseables pero no obligatorios para todos los CFEs
        if (empty($data['ruc_receptor'])) {
            $warnings[] = 'RUC del receptor no detectado (opcional)';
        }

        if (empty($data['ingreso'])) {
            $warnings[] = 'Número de ingreso (ING.) no detectado en adenda (opcional)';
        }

        if (!empty($data['items'])) {
            $sumaItems = array_sum(array_column($data['items'], 'importe'));
            $monto     = (float) ($data['monto'] ?? 0);

            if ($monto != 0 && abs($monto - $sumaItems) > 1.0) {
                $warnings[] = sprintf(
                    'Posible inconsistencia: total declarado (%.2f) difiere de suma de ítems (%.2f)',
                    $monto,
                    $sumaItems
                );
            }
        }

        // Log warnings but don't throw exception
        if (!empty($warnings)) {
            // Log::channel('cfe_errors')->warning('Eventuales: advertencias de validación', $warnings);
        }
    }
}