<?php

namespace App\Services\CfeExtractor;

use App\DTOs\CfeExtraccionDto;
use App\Exceptions\CfeExtraccionInvalidaException;

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
     * Obtiene la versión del extractor.
     *
     * @return string
     return string
     */
    public function getExtractorVersion(): string
    {
        return '1.0.0';
    }

    /**
     * Extrae array de datos de prendas.
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

        // Validacion de tipo (buscar en detalle, texto completo o adenda)
        $detalleNorm = $this->quitarAcentos(mb_strtolower($datos['detalle'], 'UTF-8'));
        $textoNorm = $this->quitarAcentos(mb_strtolower($texto, 'UTF-8'));
        $adendaNorm = $this->quitarAcentos(mb_strtolower($datos['adenda'], 'UTF-8'));

        $tienePalabraClave = strpos($detalleNorm, 'prenda') !== false
            || strpos($detalleNorm, 'prendas') !== false
            || strpos($textoNorm, 'prenda') !== false
            || strpos($textoNorm, 'prendas') !== false
            || strpos($adendaNorm, 'prenda') !== false
            || strpos($adendaNorm, 'prendas') !== false;

        if (!$tienePalabraClave) {
            throw CfeExtraccionInvalidaException::fromValidationErrors([
                'Este comprobante no corresponde a Prendas. No se encontró la palabra "PRENDA" en el CFE.'
            ]);
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
        $patrones = [
            '/DETALLE\s+DESCRIPCI[ÓO]N.*?IMPORTE\s*(.*?)(?=\s*(?:MONTO\s+NO\s+FACTURABLE|TOTAL\s+A\s+PAGAR))/isu',
            '/DETALLE\s+DESCRIPCI[ÓO]N.*?\n(.*?)(?=\s*(?:MONTO\s+NO\s+FACTURABLE|TOTAL\s+A\s+PAGAR))/isu',
            '/DETALLE\s*\n(.*?)(?=\s*(?:MONTO\s+NO\s+FACTURABLE|TOTAL\s+A\s+PAGAR))/isu',
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

            $partes = preg_split('/[ \t]{3,}/', $linea, 2);
            $textoLinea = trim($partes[0]);
            $tieneColumnasNumericas = count($partes) > 1;

            if ($tieneColumnasNumericas) {
                $bufferDetalle[] = $textoLinea;
            } elseif (!empty($textoLinea)) {
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
     * Validación específica para prendas.
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