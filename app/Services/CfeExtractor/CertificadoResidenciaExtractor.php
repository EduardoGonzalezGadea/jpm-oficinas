<?php

namespace App\Services\CfeExtractor;

use App\DTOs\CfeExtraccionDto;
use App\Exceptions\CfeExtraccionInvalidaException;

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
     * Obtiene la versión del extractor.
     *
     * @return string
     */
    public function getExtractorVersion(): string
    {
        return '1.0.0';
    }

    /**
     * Extrae array de datos de certificado de residencia.
     *
     * @param string $texto
     * @return array
     */
    protected function extraerArray(string $texto): array
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
        $datos['nombre_titular'] = '';
        $datos['retira_es_titular'] = true;
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

        // Adenda
        $datos['adenda'] = $this->extraerAdenda($texto);

        // Items (Detalle + Descripcion)
        $this->extraerItems($texto, $datos);

        // Fallback: si no se detectó cédula en el encabezado, buscar en detalle/descripción
        if (empty($datos['cedula_receptor'])) {
            $cedula = $this->extraerCedulaDesdeTexto($datos['detalle']);
            if (!empty($cedula)) {
                $datos['cedula_receptor'] = $cedula;
            }
        }
        if (empty($datos['cedula_receptor'])) {
            $cedula = $this->extraerCedulaDesdeTexto($datos['descripcion']);
            if (!empty($cedula)) {
                $datos['cedula_receptor'] = $cedula;
            }
        }
        if (empty($datos['cedula_receptor'])) {
            $cedula = $this->extraerCedulaDesdeTexto($datos['adenda'] ?? '');
            if (!empty($cedula)) {
                $datos['cedula_receptor'] = $cedula;
            }
        }

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
        $camposBusqueda = ['descripcion', 'detalle'];
        foreach ($camposBusqueda as $campo) {
            if (!empty($datos[$campo])) {
                if (preg_match('/(?:C\.I\.|CI|CÉDULA|CEDULA|DOCUMENTO)\s*[:\-\s]*([\d][\d\.\-]{4,}[\d])/iu', $datos[$campo], $ciMatch)) {
                    $ciLimpia = preg_replace('/[^0-9]/', '', $ciMatch[1]);
                    if (strlen($ciLimpia) >= 6 && strlen($ciLimpia) <= 10) {
                        $datos['cedula_titular'] = $ciMatch[1];
                        $datos['retira_es_titular'] = false;

                        if (!empty($datos['descripcion'])) {
                            if (preg_match('/(?:NOMBRE|TITULAR)\s*[:\-\s]+([A-Za-zÀ-ÿÑñ\s\.]+?)(?=(?:\s*(?:C\.I\.|CI|CÉDULA|CEDULA|TEL|$)))/iu', $datos['descripcion'], $nomMatch)) {
                                $datos['nombre_titular'] = trim(preg_replace('/\s+/', ' ', $nomMatch[1]));
                            } elseif (preg_match('/' . preg_quote($ciMatch[1], '/') . '\s*[:\-\s]*([A-Za-zÀ-ÿÑñ\s\.]+?)(?=(?:\s*(?:CORRESPONDE|$)))/iu', $datos['descripcion'], $nomFallback)) {
                                $datos['nombre_titular'] = trim(preg_replace('/\s+/', ' ', $nomFallback[1]));
                            }
                        }
                        return;
                    }
                }
            }
        }

        $datos['cedula_titular'] = $datos['cedula_receptor'];
        $datos['nombre_titular'] = $datos['nombre_receptor'] ?? '';
    }

    /**
     * Busca una cédula de identidad en un texto, reconociendo prefijos
     * como CI, C.I., Cédula, Cedula, CÉDULA, CEDULA, etc.
     */
    private function extraerCedulaDesdeTexto(string $texto): string
    {
        if (empty($texto)) {
            return '';
        }

        // Patrón: prefijo opcional (CI, C.I., Cédula, etc.) + separador + número
        if (preg_match('/(?:C\.?\s*I\.?|C(?:E|É)DULA|CEDULA)\s*[:\-]?\s*([\d][\d\.\s\-]{4,}[\d])/iu', $texto, $m)) {
            return trim($m[1]);
        }

        // Fallback: cualquier número de 6-8 dígitos con formato de cédula (con puntos)
        if (preg_match('/([\d][\d\.]{4,}[\d])/u', $texto, $m)) {
            $ciLimpia = preg_replace('/[^0-9]/', '', $m[1]);
            if (strlen($ciLimpia) >= 6 && strlen($ciLimpia) <= 8) {
                return $m[1];
            }
        }

        return '';
    }

    /**
     * Validación específica para certificados de residencia.
     *
     * @param CfeExtraccionDto $dto
     * @return void
     * @throws CfeExtraccionInvalidaException
     */
    public function validar(CfeExtraccionDto $dto): void
    {
        parent::validar($dto);

        $errors = [];
        $data = $dto->toArray();

        if (empty($data['cedula_receptor'])) {
            $errors[] = 'Cédula del receptor no detectada';
        }

        if (empty($data['nombre_receptor'])) {
            $errors[] = 'Nombre del receptor no detectado';
        }

        if (!empty($errors)) {
            throw CfeExtraccionInvalidaException::fromValidationErrors($errors);
        }
    }
}