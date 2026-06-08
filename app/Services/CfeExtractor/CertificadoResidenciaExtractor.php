<?php

namespace App\Services\CfeExtractor;

/**
 * Extractor para CFE de Certificados de Residencia.
 */
class CertificadoResidenciaExtractor extends BaseExtractor
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

        return strpos($tipoSinAcentos, 'certificado') !== false
            && strpos($tipoSinAcentos, 'residencia') !== false;
    }

    /**
     * Retorna el nombre legible.
     *
     * @return string
     */
    public function getNombreLegible(): string
    {
        return 'Certificado de Residencia';
    }

    /**
     * Extrae los datos del CFE de certificado de residencia.
     *
     * @param string $texto
     * @return array
     */
    public function extraer(string $texto): array
    {
        $texto = $this->limpiarTexto($texto);

        $datos = $this->getEstructuraBase();
        $datos['cedula_receptor'] = '';
        $datos['nombre_receptor'] = '';
        $datos['monto'] = 0.0;
        $datos['monto_total'] = 0.0;
        $datos['telefono'] = '';
        $datos['forma_pago'] = 'SIN DATOS';
        $datos['detalle'] = '';
        $datos['descripcion'] = '';
        $datos['cedula_titular'] = '';
        $datos['retira_es_titular'] = true;

        // Tipo de CFE
        $datos['tipo_cfe'] = $this->extraerTipoCfe($texto);

        // Serie y Numero
        $serieNumero = $this->extraerSerieNumero($texto);
        $datos['serie'] = $serieNumero['serie'];
        $datos['numero'] = $serieNumero['numero'];

        // Fecha
        $datos['fecha'] = $this->extraerFecha($texto);

        // Receptor
        $datos['cedula_receptor'] = $this->extraerReceptorDocumento($texto);
        $datos['nombre_receptor'] = $this->extraerReceptorNombre($texto);

        // Monto
        $datos['monto'] = $this->extraerMonto($texto);
        $datos['monto_total'] = $datos['monto'];

        // Medios de Pago
        $datos['forma_pago'] = $this->extraerMediosPago($texto);

        // Telefono
        $datos['telefono'] = $this->extraerTelefono($texto);

        // Moneda
        $datos['moneda'] = $this->detectarMoneda($texto);

        // Items (Detalle + Descripcion)
        $this->extraerItems($texto, $datos);

        // Detectar cedula del titular
        $this->detectarCedulaTitular($datos);

        return $datos;
    }

    /**
     * Extrae los items del CFE.
     *
     * @param string $texto
     * @param array $datos
     */
    private function extraerItems(string $texto, array &$datos): void
    {
        if (!preg_match('/DETALLE\s+DESCRIPCI.N.*?IMPORTE\s*(.*?)(?=\s*MONTO\s+NO\s+FACTURABLE)/isu', $texto, $matches)) {
            return;
        }

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

    /**
     * Detecta si la descripcion contiene una cedula del titular.
     *
     * @param array $datos
     */
    private function detectarCedulaTitular(array &$datos): void
    {
        if (!empty($datos['descripcion'])) {
            if (preg_match('/([\d][\d\.]{4,}[\d])/u', $datos['descripcion'], $ciMatch)) {
                $ciLimpia = preg_replace('/[^0-9]/', '', $ciMatch[1]);
                if (strlen($ciLimpia) >= 6 && strlen($ciLimpia) <= 10) {
                    $datos['cedula_titular'] = $ciMatch[1];
                    $datos['retira_es_titular'] = false;
                    return;
                }
            }
        }

        // Si no se detecto CI en descripcion, el receptor es el titular
        $datos['cedula_titular'] = $datos['cedula_receptor'];
    }
}