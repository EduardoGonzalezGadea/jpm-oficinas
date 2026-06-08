<?php

namespace App\Services\CfeExtractor;

/**
 * Extractor para CFE de Prendas.
 */
class PrendasExtractor extends BaseExtractor
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

        return strpos($tipoSinAcentos, 'prenda') !== false
            || strpos($tipoSinAcentos, 'prendas') !== false;
    }

    /**
     * Retorna el nombre legible.
     *
     * @return string
     */
    public function getNombreLegible(): string
    {
        return 'Prendas';
    }

    /**
     * Extrae los datos del CFE de prendas.
     * Nota: La estructura es similar a Arrendamientos.
     *
     * @param string $texto
     * @return array
     */
    public function extraer(string $texto): array
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

        // Receptor
        $datos['cedula'] = $this->extraerReceptorDocumento($texto);
        $datos['nombre'] = $this->extraerReceptorNombre($texto);

        // Telefono
        $datos['telefono'] = $this->extraerTelefono($texto);

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

        // Orden de Cobro desde adenda
        $datos['orden_cobro'] = $this->extraerOrdenCobro($datos['adenda']);

        // Validacion de tipo
        $detalleNorm = $this->quitarAcentos(mb_strtolower($datos['detalle'], 'UTF-8'));
        if (strpos($detalleNorm, 'prenda') === false && strpos($detalleNorm, 'prendas') === false) {
            return [
                'error_validacion' => 'Este comprobante no corresponde a Prendas. No se encontro la palabra "PRENDA" en el detalle del CFE.'
            ];
        }

        // Si se pago por transferencia, concatenar al detalle
        if (stripos($datos['forma_pago'], 'Transferencia') !== false) {
            $datos['detalle'] .= ' - ' . $datos['forma_pago'];
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
        if (!preg_match('/DETALLE\s+DESCRIPCI.N.*?IMPORTE\s*(.*?)(?=\s*MONTO\s+NO\s+FACTURABLE)/isu', $texto, $matches)) {
            return '';
        }

        $bloqueItems = trim($matches[1]);
        $lineas = explode("\n", $bloqueItems);
        $bufferDetalle = [];

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (empty($linea)) continue;

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
            return trim(preg_replace('/\s+/', ' ', implode(' ', $bufferDetalle)));
        }

        return '';
    }

    /**
     * Extrae la orden de cobro de la adenda.
     *
     * @param string $adenda
     * @return string
     */
    private function extraerOrdenCobro(string $adenda): string
    {
        if (empty($adenda)) {
            return '';
        }

        $adendaSinAcentos = $this->quitarAcentos(mb_strtolower($adenda, 'UTF-8'));

        if (preg_match('/(?:orden\s+de\s+cobro|orden\s+cobro|o\.\s*\(?c\.?|o\/c)\s*(\d+)/iu', $adendaSinAcentos, $ocMatch)) {
            return $ocMatch[1];
        }

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
     * Validacion especifica para prendas.
     *
     * @param array $datos
     * @return array
     */
    public function validar(array $datos): array
    {
        $result = parent::validar($datos);

        if (isset($datos['error_validacion'])) {
            $result['errors'][] = $datos['error_validacion'];
            $result['valid'] = false;
        }

        return $result;
    }
}