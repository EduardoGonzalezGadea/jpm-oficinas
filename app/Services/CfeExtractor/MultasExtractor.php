<?php

namespace App\Services\CfeExtractor;

use App\DTOs\CfeExtraccionDto;
use App\Exceptions\CfeExtraccionInvalidaException;

/**
 * Extractor para CFE de Multas Cobradas.
 */
class MultasExtractor extends BaseExtractor
{
    /**
     * Tipos de CFE que este extractor soporta.
     */
    private const TIPOS_SOPORTADOS = ['multas_cobradas', 'multa', 'infraccion'];

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

        foreach (self::TIPOS_SOPORTADOS as $tipoSoportado) {
            if (strpos($tipoSinAcentos, $tipoSoportado) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retorna el nombre legible.
     *
     * @return string
     */
    public function getNombreLegible(): string
    {
        return 'Multas Cobradas';
    }

    /**
     * Obtiene la versión del extractor.
     *
     * @return string
     */
    public function getExtractorVersion(): string
    {
        return '1.1.0';
    }

    /**
     * Extrae array de datos de multas.
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
        $datos['domicilio'] = '';
        $datos['monto_total'] = 0.0;
        $datos['detalle_completo'] = '';
        $datos['adicional'] = '';
        $datos['adenda'] = '';
        $datos['forma_pago'] = 'SIN DATOS';
        $datos['referencias'] = '';
        $datos['items'] = [];

        // Tipo de CFE
        $datos['tipo_cfe'] = $this->extraerTipoCfe($texto);

        // Serie y Numero
        $serieNumero = $this->extraerSerieNumero($texto);
        $datos['serie'] = $serieNumero['serie'];
        $datos['numero'] = $serieNumero['numero'];

        // Fecha
        $datos['fecha'] = $this->extraerFecha($texto);

        // Receptor (Cedula o RUT)
        $datos['cedula'] = $this->extraerReceptorDocumento($texto);

        // Nombre Receptor
        $datos['nombre'] = $this->extraerReceptorNombre($texto);

        // Monto Total
        $datos['monto_total'] = $this->extraerMonto($texto);

        // Medios de Pago
        $datos['forma_pago'] = $this->extraerMediosPago($texto);

        // Moneda
        $datos['moneda'] = $this->detectarMoneda($texto);

        // Informacion Adicional
        if (preg_match('/INFORMACION\s+ADICIONAL\s*\n(.*?)(?=\s*FECHA\s+MONEDA)/isu', $texto, $matches)) {
            $datos['adicional'] = preg_replace('/\s+/', ' ', trim($matches[1]));
        }

        // Adenda
        $datos['adenda'] = $this->extraerAdenda($texto);

        // Referencias
        if (preg_match('/REFERENCIAS:(.*?)(?=\s*(?:ADENDA|Fecha\s+de|$))/isu', $texto, $matches)) {
            $datos['referencias'] = preg_replace('/\s+/', ' ', trim($matches[1]));
        }

        // Items
        $datos['items'] = $this->extraerItems($texto);
        $datos['detalle_completo'] = $this->extraerDetalleCompleto($texto);

        return $datos;
    }

    /**
     * Extrae los items del CFE.
     *
     * @param string $texto
     * @return array
     */
    private function extraerItems(string $texto): array
    {
        $items = [];

        if (!preg_match('/DETALLE\s+DESCRIPCI.N.*?IMPORTE\s*(.*?)(?=\s*MONTO\s+NO\s+FACTURABLE)/isu', $texto, $matches)) {
            return $items;
        }

        $bloqueItems = $matches[1];
        $lineas = explode("\n", $bloqueItems);
        $bufferItem = [];

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (empty($linea)) continue;

            // Detectar linea de cierre de item
            if (preg_match('/^(.*?)([\d\.,]+(?:\s*\(Unid\))?[\s\t]*[\d\.,]+[\s\t]+([\d\.,]+))$/i', $linea, $m)) {
                $restoLinea = trim($m[1]);
                $importe = $m[3];

                if (!empty($restoLinea)) {
                    $bufferItem[] = $restoLinea;
                }

                $itemActual = [
                    'detalle' => '',
                    'descripcion' => '',
                    'importe' => $this->parsearMonto($importe)
                ];

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
                    }
                }

                $items[] = $itemActual;
                $bufferItem = [];
            } else {
                $bufferItem[] = $linea;
            }
        }

        return $items;
    }

    /**
     * Extrae el detalle completo.
     *
     * @param string $texto
     * @return string
     */
    private function extraerDetalleCompleto(string $texto): string
    {
        if (preg_match('/DETALLE\s+DESCRIPCI.N.*?IMPORTE\s*(.*?)(?=\s*MONTO\s+NO\s+FACTURABLE)/isu', $texto, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }

    /**
     * Validacion especifica para multas.
     *
     * @param CfeExtraccionDto $dto
     * @return void
     * @throws CfeExtraccionInvalidaException
     */
    public function validar(CfeExtraccionDto $dto): void
    {
        parent::validar($dto);

        $errors = [];

        // Validar que haya items
        if (empty($dto->items)) {
            $errors[] = 'No se detectaron items en el CFE';
        }

        // Validar consistencia entre monto total y suma de items
        if (!empty($dto->items) && $dto->montoTotal !== null) {
            $sumaItems = array_sum(array_column($dto->items, 'importe'));
            $montoTotal = $this->parsearMonto($dto->montoTotal);

            if (abs($montoTotal - $sumaItems) > 0.1) {
                $errors[] = sprintf(
                    'Inconsistencia: total (%s) no coincide con suma de items (%s)',
                    number_format($montoTotal, 2),
                    number_format($sumaItems, 2)
                );
            }
        }

        if (!empty($errors)) {
            throw CfeExtraccionInvalidaException::fromValidationErrors($errors);
        }
    }
}