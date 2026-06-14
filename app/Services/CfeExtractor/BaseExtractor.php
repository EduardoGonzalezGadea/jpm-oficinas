<?php

namespace App\Services\CfeExtractor;

/**
 * Clase base para extractores de CFE con funcionalidad comun.
 */
abstract class BaseExtractor implements CfeExtractorInterface
{
    /**
     * Patrones de expresiones regulares comunes.
     */
    protected const PATTERNS = [
        'serie_numero' => '/([A-Z])[\s\t]+(\d+)[\s\t]+(?:Contado|Cr.dito)/i',
        'fecha' => '/FECHA[\s:]+(?:MONEDA[\s:]+)?(\d{2}\/\d{2}\/\d{4})/i',
        'fecha_alternativa' => '/(\d{2}\/\d{2}\/\d{4})/i',
        'cedula_rut' => '/(?:C\.I\.|RUT).*?:\s*([\d\.-]+)/is',
        'monto_total' => '/TOTAL\s+A\s+PAGAR:\s*([\d\.,]+)/is',
        'monto_no_facturable' => '/MONTO\s+NO\s+FACTURABLE:\s*([\d\.,]+)/is',
        'telefono' => '/(?:TEL\.?|TEL(?:E|É)FONO|CEL\.?)[\s:]*([\d][\d\s\-\/\.]{5,})/iu',
        'nombre_receptor' => '/NOMBRE O DENOMINACIÓN DOMICILIO FISCAL\s*\n\s*(.*?)(?=\s*\n\s*(?:INFORMACION ADICIONAL|DETALLE DESCRIPCIÓN|PERIODO|FECHA|$))/isu',
        'nombre_receptor_alt' => '/FISCAL\s*(.*?)(?=\s*(?:INFORMACION|DETALLE|FECHA|\d{2}\/\d{2}\/\d{4}|$))/isu',
        'emisor_rut' => '/(\d{12})\s+(?:e-Factura|e-Ticket|e-Boleta)/i',
        'tipo_cfe' => '/(e-Factura|e-Ticket|e-Boleta)(?:\s+Cobranza)?/i',
        'peso_uruguayo' => '/Peso uruguayo/i',
        'dolar' => '/Dólar/i',
    ];

    /**
     * Estructura base de datos extraidos.
     */
    protected function getEstructuraBase(): array
    {
        return [
            'tipo_cfe' => 'No detectado',
            'serie' => '',
            'numero' => '',
            'fecha' => '',
            'moneda' => 'UYU',
        ];
    }

    /**
     * Elimina acentos de un texto para comparacion insensible.
     *
     * @param string $texto
     * @return string
     */
    protected function quitarAcentos(string $texto): string
    {
        $search  = ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', 'Ü'];
        $replace = ['a', 'e', 'i', 'o', 'u', 'n', 'u', 'a', 'e', 'i', 'o', 'u', 'n', 'u'];
        return str_replace($search, $replace, $texto);
    }

    /**
     * Extrae serie y numero del CFE.
     *
     * @param string $texto
     * @return array ['serie' => string, 'numero' => string]
     */
    protected function extraerSerieNumero(string $texto): array
    {
        if (preg_match(self::PATTERNS['serie_numero'], $texto, $matches)) {
            return [
                'serie' => $matches[1],
                'numero' => $matches[2]
            ];
        }
        return ['serie' => '', 'numero' => ''];
    }

    /**
     * Extrae la fecha del CFE.
     *
     * @param string $texto
     * @return string
     */
    protected function extraerFecha(string $texto): string
    {
        if (preg_match(self::PATTERNS['fecha'], $texto, $matches)) {
            return $matches[1];
        }
        if (preg_match(self::PATTERNS['fecha_alternativa'], $texto, $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * Extrae el monto total.
     *
     * @param string $texto
     * @return float
     */
    protected function extraerMonto(string $texto): float
    {
        if (preg_match(self::PATTERNS['monto_total'], $texto, $matches)) {
            return (float)str_replace(['.', ','], ['', '.'], $matches[1]);
        }
        if (preg_match(self::PATTERNS['monto_no_facturable'], $texto, $matches)) {
            return (float)str_replace(['.', ','], ['', '.'], $matches[1]);
        }
        return 0.0;
    }

    /**
     * Detecta la moneda del CFE.
     *
     * @param string $texto
     * @return string
     */
    protected function detectarMoneda(string $texto): string
    {
        if (preg_match(self::PATTERNS['peso_uruguayo'], $texto)) {
            return 'UYU';
        }
        if (preg_match(self::PATTERNS['dolar'], $texto)) {
            return 'USD';
        }
        return 'UYU';
    }

    /**
     * Extrae el tipo de CFE (e-Factura, e-Ticket, e-Boleta).
     *
     * @param string $texto
     * @return string
     */
    protected function extraerTipoCfe(string $texto): string
    {
        if (preg_match(self::PATTERNS['tipo_cfe'], $texto, $matches)) {
            return $matches[0];
        }
        return 'No detectado';
    }

    /**
     * Extrae el RUT del emisor.
     *
     * @param string $texto
     * @return string
     */
    protected function extraerEmisorRut(string $texto): string
    {
        if (preg_match(self::PATTERNS['emisor_rut'], $texto, $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * Extrae la cedula o RUT del receptor.
     *
     * @param string $texto
     * @return string
     */
    protected function extraerReceptorDocumento(string $texto): string
    {
        if (preg_match(self::PATTERNS['cedula_rut'], $texto, $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * Extrae el nombre del receptor.
     *
     * @param string $texto
     * @return string
     */
    protected function extraerReceptorNombre(string $texto): string
    {
        if (preg_match(self::PATTERNS['nombre_receptor'], $texto, $matches)) {
            return trim(preg_replace('/\s+/', ' ', $matches[1]));
        }
        if (preg_match(self::PATTERNS['nombre_receptor_alt'], $texto, $matches)) {
            return trim(preg_replace('/\s+/', ' ', $matches[1]));
        }
        return '';
    }

    /**
     * Extrae el telefono solo si se encuentra en la información adicional o adenda.
     *
     * @param string $texto
     * @return string
     */
    protected function extraerTelefono(string $texto): string
    {
        // 1. Buscar en INFORMACION ADICIONAL
        if (preg_match('/INFORMACION ADICIONAL\s*(.*?)(?=\s*(?:PERIODO|FECHA|DETALLE|$))/isu', $texto, $matchesInfo)) {
            if (preg_match(self::PATTERNS['telefono'], $matchesInfo[1], $matchesTel)) {
                return trim($matchesTel[1]);
            }
        }

        // 2. Buscar en ADENDA
        $adenda = $this->extraerAdenda($texto);
        if (!empty($adenda)) {
            if (preg_match(self::PATTERNS['telefono'], $adenda, $matchesTel)) {
                return trim($matchesTel[1]);
            }
        }

        // Si no se encuentra en las secciones adicionales, se devuelve vacío
        // para no capturar erróneamente el teléfono de la institución emisora.
        return '';
    }

    /**
     * Convierte un monto string a float.
     *
     * @param mixed $monto
     * @return float
     */
    protected function parsearMonto($monto): float
    {
        if (is_float($monto) || is_int($monto)) {
            return (float)$monto;
        }
        return (float)str_replace(['.', ','], ['', '.'], (string)$monto);
    }

    /**
     * Limpia caracteres invalidos UTF-8.
     *
     * @param string $texto
     * @return string
     */
    protected function limpiarTexto(string $texto): string
    {
        return mb_convert_encoding($texto, 'UTF-8', 'UTF-8');
    }

    /**
     * Extrae el bloque de medios de pago.
     *
     * @param string $texto
     * @return string
     */
    protected function extraerMediosPago(string $texto): string
    {
        if (preg_match('/TOTAL\s+A\s+PAGAR:[\s\t]*[\d\.,]+(.*?)(?=REFERENCIAS:)/isu', $texto, $matches)) {
            $bloque = trim($matches[1]);
            if (!empty($bloque)) {
                $lineas = explode("\n", $bloque);
                $pagos = [];
                $lastLabel = '';
                foreach ($lineas as $linea) {
                    $linea = trim($linea);
                    if (empty($linea)) continue;
                    if (preg_match('/^(?:([^:]+):)?\s*(?:UYU|USD|\$)?\s*([\d\.,]+)$/ui', $linea, $mpm)) {
                        $tipo = !empty($mpm[1]) ? trim($mpm[1]) : (!empty($lastLabel) ? $lastLabel : 'Medio de pago');
                        $pagos[] = $tipo . ": " . trim($mpm[2]);
                        $lastLabel = '';
                    } else {
                        $lastLabel = rtrim($linea, ': ');
                    }
                }
                if (!empty($pagos)) {
                    return implode(' / ', $pagos);
                }
            }
        }
        return 'SIN DATOS';
    }

    /**
     * Extrae la adenda del CFE.
     *
     * @param string $texto
     * @return string
     */
    protected function extraerAdenda(string $texto): string
    {
        if (preg_match('/ADENDA\s*\n(.*?)(?=\s*(?:Fecha\s+de|Puede\s+verificar|I\.V\.A\.|NÚMERO\s+DE\s+CAE|$))/isu', $texto, $matches)) {
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
            return implode("\n", $lineasLimpias);
        }
        return '';
    }

    /**
     * Validacion base de datos extraidos.
     *
     * @param array $datos
     * @return array
     */
    public function validar(array $datos): array
    {
        $errors = [];

        if (empty($datos['fecha'])) {
            $errors[] = 'Fecha no detectada';
        }

        if (empty($datos['serie']) || empty($datos['numero'])) {
            $errors[] = 'Serie/Numero no detectado';
        }

        if (empty($datos['monto']) || $datos['monto'] <= 0) {
            $errors[] = 'Monto no valido';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}