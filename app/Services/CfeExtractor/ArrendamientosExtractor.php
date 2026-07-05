<?php

namespace App\Services\CfeExtractor;

use App\DTOs\CfeExtraccionDto;
use App\Exceptions\CfeExtraccionInvalidaException;

/**
 * Extractor para CFE de Arrendamientos.
 */
class ArrendamientosExtractor extends BaseExtractor
{
    /**
     * Verifica si este extractor soporta el tipo dado.
     *
     * @param string $tipo
     * @return bool
     */
    public function soporta(string $tipo): bool
    {
        $tipoLower = mb_strtolower($tipo, 'UTF-8');
        $tipoSinAcentos = $this->quitarAcentos($tipoLower);

        return strpos($tipoSinAcentos, 'arrendamiento') !== false;
    }

    /**
     * Retorna el nombre legible.
     *
     * @return string
     */
    public function getNombreLegible(): string
    {
        return 'Arrendamientos';
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
     * Extrae array de datos de arrendamientos.
     *
     * @param string $texto
     * @return array
     */
    protected function extraerArray(string $texto): array
    {
        $texto = $this->limpiarTexto($texto);

        $datos = $this->getEstructuraBase();
        $datos['cedula'] = '';
        $datos['nombre'] = '';
        $datos['telefono'] = '';
        $datos['monto'] = 0.0;
        $datos['monto_total'] = 0.0;
        $datos['detalle'] = '';
        $datos['orden_cobro'] = '';
        $datos['forma_pago'] = 'SIN DATOS';
        $datos['adenda'] = '';

        // Tipo de CFE
        $datos['tipo_cfe'] = $this->extraerTipoCfe($texto);

        // Serie y Numero
        $serieNumero = $this->extraerSerieNumero($texto);
        $datos['serie'] = $serieNumero['serie'];
        $datos['numero'] = $serieNumero['numero'];

        // Fecha
        $datos['fecha'] = $this->extraerFecha($texto);

        // Receptor y Domicilio
        $datos['cedula'] = $this->extraerReceptorDocumento($texto);
        
        $bloqueNombre = '';
        if (preg_match('/NOMBRE O DENOMINACIÓN\s*\n?\s*DOMICILIO FISCAL\s+(.*?)(?=\s*(?:INFORMACION ADICIONAL|DETALLE DESCRIPCIÓN|PERIODO|FECHA|$))/isu', $texto, $matches)) {
            $bloqueNombre = $matches[1];
        } elseif (preg_match('/(?:NOMBRE O DENOMINACIÓN[\s\S]*?)?DOMICILIO\s+FISCAL\s+(.*?)(?=\s*(?:INFORMACION|DETALLE|FECHA|\d{2}\/\d{2}\/\d{4}|$))/isu', $texto, $matches)) {
            $bloqueNombre = $matches[1];
        }

        $domicilio = '';
        if (!empty($bloqueNombre)) {
            $lineas = array_values(array_filter(array_map('trim', explode("\n", $bloqueNombre))));
            $nombreLines = [];
            $domicilioLines = [];
            $domicilioEmpezado = false;

            foreach ($lineas as $k => $linea) {
                if ($domicilioEmpezado) {
                    $domicilioLines[] = $linea;
                } else {
                    $esDomicilio = false;
                    
                    // Si la línea contiene dígitos o palabras clave de dirección
                    if (preg_match('/\d/', $linea) || preg_match('/\b(s\/n|cno|ruta|calle|av\.?|avda|blvr|bvar|apto|block|esq\.?|piso|of\.?)\b/iu', $linea)) {
                        $esDomicilio = true;
                    } 
                    // Si no tiene números, pero la SIGUIENTE línea empieza con números (ej: calle en una línea, número en la otra)
                    elseif (isset($lineas[$k + 1]) && preg_match('/^\d+/u', trim($lineas[$k + 1]))) {
                        // Evitar tomar la primera línea como domicilio si es la única antes del número
                        if ($k > 0) {
                            $esDomicilio = true;
                        }
                    }

                    if ($esDomicilio) {
                        // Si es la primera línea y ya tiene números (todo en una sola línea)
                        if ($k === 0 && count($lineas) === 1) {
                            if (preg_match('/^(.*?)\s+(\d.*)$/u', $linea, $m)) {
                                $nombreLines[] = trim($m[1]);
                                $domicilioLines[] = trim($m[2]);
                            } else {
                                $nombreLines[] = $linea; // Fallback, dejar todo en el nombre
                            }
                        } else {
                            $domicilioLines[] = $linea;
                        }
                        $domicilioEmpezado = true;
                    } else {
                        $nombreLines[] = $linea;
                    }
                }
            }

            $datos['nombre'] = implode(' ', $nombreLines);
            $domicilio = trim(preg_replace('/\s+/', ' ', implode(' ', $domicilioLines)));
        } else {
            $datos['nombre'] = $this->extraerReceptorNombre($texto);
        }

        // Telefono
        $datos['telefono'] = $this->extraerTelefonoArrendamiento($texto);

        // Monto Total
        $datos['monto'] = $this->extraerMonto($texto);
        $datos['monto_total'] = $datos['monto'];

        // Moneda
        $datos['moneda'] = $this->detectarMoneda($texto);

        // Medios de Pago
        $datos['forma_pago'] = $this->extraerMediosPago($texto);

        // Detalle
        $datos['detalle'] = $this->extraerDetalle($texto);

        // Adenda
        $datos['adenda'] = $this->extraerAdenda($texto);

        // Orden de Cobro: buscar en detalle y adenda
        $datos['orden_cobro'] = $this->extraerOrdenCobro($datos['detalle'], $datos['adenda']);

        // Validacion de tipo (buscar en detalle, texto completo o adenda)
        $detalleNorm = $this->quitarAcentos(mb_strtolower($datos['detalle'], 'UTF-8'));
        $textoNorm = $this->quitarAcentos(mb_strtolower($texto, 'UTF-8'));
        $adendaNorm = $this->quitarAcentos(mb_strtolower($datos['adenda'], 'UTF-8'));

        $tienePalabraClave = strpos($detalleNorm, 'arrendamiento') !== false
            || strpos($detalleNorm, 'arrendamientos') !== false
            || strpos($textoNorm, 'arrendamiento') !== false
            || strpos($textoNorm, 'arrendamientos') !== false
            || strpos($adendaNorm, 'arrendamiento') !== false
            || strpos($adendaNorm, 'arrendamientos') !== false;

        if (!$tienePalabraClave) {
            throw CfeExtraccionInvalidaException::fromValidationErrors([
                'Este comprobante no corresponde a un Arrendamiento. No se encontró la palabra "ARRENDAMIENTO" en el CFE.'
            ]);
        }

        // Quitar palabra ARRENDAMIENTO(S) del detalle ya que esta implícito
        $datos['detalle'] = preg_replace('/ARRENDAMIENTOS?/iu', '', $datos['detalle']);

        // Quitar O/C del detalle (se almacena en su propio campo orden_cobro)
        if (!empty($datos['orden_cobro'])) {
            $datos['detalle'] = preg_replace(
                '/[-\x{2013}\x{2014}]?\s*(?:O\.?\s*\(?C\.?\)?|O\/C|ORDEN\s+DE\s+COBRO)\s*' . preg_quote($datos['orden_cobro'], '/') . '/iu',
                '',
                $datos['detalle']
            );
        }

        $datos['detalle'] = trim(preg_replace('/\s+/', ' ', $datos['detalle']), " -.,:");

        // Agregar datos adicionales del CFE no almacenados en otros campos
        $datosExtra = [];

        $referencias = $this->extraerReferencias($texto);
        if (!empty($referencias)) {
            $refNorm = $this->quitarAcentos(mb_strtolower(trim($referencias)));
            if ($refNorm !== 'cancelacion de factura') {
                $datosExtra[] = 'REF: ' . $referencias;
            }
        }

        if (!empty($datos['adenda'])) {
            $adendaSinOC = preg_replace(
                '/(?:ORDEN\s+DE\s+COBRO|ORDEN\s+COBRO|O\.\s*\(?C\.?\)?|O\/C)\s*\d+/iu',
                '',
                $datos['adenda']
            );
            $adendaSinOC = trim(preg_replace('/\s+/', ' ', $adendaSinOC), " -.,:");
            if (!empty($adendaSinOC)) {
                $datosExtra[] = $adendaSinOC;
            }
        }

        if (!empty($datosExtra)) {
            $extra = implode(' - ', $datosExtra);
            $datos['detalle'] = empty($datos['detalle']) ? $extra : $datos['detalle'] . ' - ' . $extra;
        }

        // Colocar el domicilio al principio del detalle
        if (!empty($domicilio)) {
            $datos['detalle'] = empty($datos['detalle']) ? $domicilio : $domicilio . ' - ' . $datos['detalle'];
        }

        return $datos;
    }

    /**
     * Extrae el detalle del CFE.
     *
     * @param string $texto
     * @return string
     */
    private function extraerDetalle(string $texto): string
    {
        $patrones = [
            // 1. Formato estándar con encabezado completo (con o sin tilde)
            '/DETALLE\s+DESCRIPCI[ÓO]N.*?IMPORTE\s*(.*?)(?=\s*(?:TOTAL\s+A\s+PAGAR|MONTO\s+NO\s+FACTURABLE))/isu',
            // 2. DETALLE DESCRIPCIÓN sin IMPORTE
            '/DETALLE\s+DESCRIPCI[ÓO]N.*?\n(.*?)(?=\s*(?:TOTAL\s+A\s+PAGAR|MONTO\s+NO\s+FACTURABLE))/isu',
            // 3. DETALLE a secas hasta TOTAL
            '/DETALLE\s*\n(.*?)(?=\s*(?:TOTAL\s+A\s+PAGAR|MONTO\s+NO\s+FACTURABLE))/isu',
            // 4. DETALLE hasta REFERENCIAS o ADENDA
            '/DETALLE\s*\n(.+?)(?=\s*(?:REFERENCIAS|ADENDA|N[ÚU]MERO\s+DE\s+CAE))/isu',
        ];

        $bloqueItems = '';
        foreach ($patrones as $patron) {
            if (preg_match($patron, $texto, $matches)) {
                $bloqueItems = trim($matches[1]);
                if (!empty($bloqueItems)) {
                    break;
                }
            }
        }

        if (empty($bloqueItems)) {
            return '';
        }

        $lineas = explode("\n", $bloqueItems);
        $bufferDetalle = [];

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (empty($linea)) continue;

            // Separar texto descriptivo de columnas tabulares (qty, precio, importe)
            // usando el primer gap de 3+ espacios como separador
            $partes = preg_split('/[ \t]{3,}/', $linea, 2);
            $textoLinea = trim($partes[0]);
            $tieneColumnasNumericas = count($partes) > 1;

            if ($tieneColumnasNumericas) {
                $bufferDetalle[] = $textoLinea;
            } elseif (!empty($textoLinea)) {
                // Ignorar paréntesis (unidades como "(Unid)") al buscar texto real
                $textoSinParens = preg_replace('/\([^)]*\)/', '', $textoLinea);
                if (preg_match('/[a-zA-Z]/', $textoSinParens)
                    || preg_match('/^\d{8,}$/', $textoLinea)
                    || preg_match('/^\d{2}\/\d{2}\/\d{2,4}$/', $textoLinea)
                ) {
                    $bufferDetalle[] = $textoLinea;
                }
            }
        }

        if (!empty($bufferDetalle)) {
            return trim(preg_replace('/\s+/', ' ', implode(' ', $bufferDetalle)));
        }

        return '';
    }

    /**
     * Extrae la orden de cobro del detalle y/o adenda.
     *
     * @param string $detalle
     * @param string $adenda
     * @return string
     */
    private function extraerOrdenCobro(string $detalle, string $adenda): string
    {
        $patronOC = '/(?:orden\s+de\s+cobro|orden\s+|o\.\s*\(?c\.?\)?|o\/c)\s*(\d+)/iu';

        // 1. Buscar en el detalle
        if (!empty($detalle)) {
            $detalleSinAcentos = $this->quitarAcentos(mb_strtolower($detalle, 'UTF-8'));
            if (preg_match($patronOC, $detalleSinAcentos, $ocMatch)) {
                return $ocMatch[1];
            }
        }

        // 2. Buscar en la adenda
        if (empty($adenda)) {
            return '';
        }

        $adendaSinAcentos = $this->quitarAcentos(mb_strtolower($adenda, 'UTF-8'));

        if (preg_match($patronOC, $adendaSinAcentos, $ocMatch)) {
            return $ocMatch[1];
        }

        // 3. Si hay solo un numero en la adenda, asumir que es la orden de cobro
        $numerosEncontrados = [];
        if (preg_match_all('/\b(\d{3,})\b/', $adenda, $numMatches)) {
            $numerosEncontrados = $numMatches[1];
        }

        if (count($numerosEncontrados) === 1) {
            return $numerosEncontrados[0];
        }

        return '';
    }

    /**
     * Extrae el contenido de REFERENCIAS del CFE.
     *
     * @param string $texto
     * @return string
     */
    private function extraerReferencias(string $texto): string
    {
        if (!preg_match('/REFERENCIAS:\s*(.*)/isu', $texto, $matches)) {
            return '';
        }

        $bloque = $matches[1];
        $lineas = explode("\n", $bloque);
        $resultado = [];

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (empty($linea)) continue;
            if (preg_match('/^\d{1,2}$/', $linea)) break;
            if (preg_match('/^Fecha\s+de/iu', $linea)) break;
            if (preg_match('/^Puede\s+verificar/iu', $linea)) break;
            if (preg_match('/^I\.V\.A\./iu', $linea)) break;
            if (preg_match('/^N[\x{00DA}U]MERO\s+DE\s+CAE/iu', $linea)) break;
            if (preg_match('/^ADENDA/iu', $linea)) break;

            $resultado[] = $linea;
        }

        return trim(implode(' ', $resultado));
    }

    /**
     * Extrae el telefono flexibilizando el prefijo.
     *
     * @param string $texto
     * @return string
     */
    private function extraerTelefonoArrendamiento(string $texto): string
    {
        // 1. Buscar en INFORMACION ADICIONAL
        if (preg_match('/INFORMACION ADICIONAL\s*(.*?)(?=\s*(?:PERIODO|FECHA|DETALLE|$))/isu', $texto, $matchesInfo)) {
            $info = $matchesInfo[1];
            if (preg_match('/(?:TEL\.?|TEL(?:E|É)FONO|CEL\.?)?[\s:]*(\d[\d\s\-]{7,})/iu', $info, $matchesTel)) {
                $numero = trim($matchesTel[1]);
                $digitos = preg_replace('/[^\d]/', '', $numero);
                if (strlen($digitos) >= 8) {
                    return $numero;
                }
            }
        }

        // 2. Buscar en ADENDA
        $adenda = $this->extraerAdenda($texto);
        if (!empty($adenda)) {
            if (preg_match('/(?:TEL\.?|TEL(?:E|É)FONO|CEL\.?)?[\s:]*(\d[\d\s\-]{7,})/iu', $adenda, $matchesTel)) {
                $numero = trim($matchesTel[1]);
                $digitos = preg_replace('/[^\d]/', '', $numero);
                if (strlen($digitos) >= 8) {
                    return $numero;
                }
            }
        }

        return $this->extraerTelefono($texto);
    }

    /**
     * Validación específica para arrendamientos.
     *
     * @param CfeExtraccionDto $dto
     * @return void
     * @throws CfeExtraccionInvalidaException
     */
    public function validar(CfeExtraccionDto $dto): void
    {
        $data = $dto->toArray();

        if (isset($data['error_validacion'])) {
            throw CfeExtraccionInvalidaException::fromValidationErrors([$data['error_validacion']]);
        }

        parent::validar($dto);
    }
}