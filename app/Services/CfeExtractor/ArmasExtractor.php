<?php

namespace App\Services\CfeExtractor;

use App\DTOs\CfeExtraccionDto;
use App\Exceptions\CfeExtraccionInvalidaException;

/**
 * Extractor para CFE de Armas (Porte y Tenencia).
 */
class ArmasExtractor extends BaseExtractor
{
    private const TRAMITE_PATTERN = '/(?:TR[ÁA]?M(?:ITE)?\.?(?:\s+DE)?)\s*(?:N[º°]?|N\.?|Nro\.?|NUM\.?|N)?[\s:\-.,]*\s*([\d][\d\/\-]+)/iu';
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

        return strpos($tipoSinAcentos, 'porte') !== false
            || strpos($tipoSinAcentos, 'tenencia') !== false
            || strpos($tipoSinAcentos, 'arma') !== false
            || strpos($tipoSinAcentos, 'tahta') !== false;
    }

    /**
     * Retorna el nombre legible.
     *
     * @return string
     */
    public function getNombreLegible(): string
    {
        return 'Armas';
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
     * Extrae array de datos de armas.
     *
     * @param string $texto
     * @return array
     */
    protected function extraerArray(string $texto): array
    {
        $texto = $this->limpiarTexto($texto);

        $datos = $this->getEstructuraBase();
        $datos['emisor_rut'] = '';
        $datos['emisor_nombre'] = '';
        $datos['receptor_documento'] = '';
        $datos['receptor_nombre'] = '';
        $datos['monto'] = 0.0;
        $datos['subtotal'] = 0.0;
        $datos['iva'] = 0.0;
        $datos['detalle'] = '';
        $datos['orden_cobro'] = '';
        $datos['tramite'] = '';
        $datos['ingreso_contabilidad'] = '';
        $datos['telefono'] = '';
        $datos['adenda'] = '';

        // Tipo de CFE
        $datos['tipo_cfe'] = $this->extraerTipoCfe($texto);

        // Serie y Numero
        $serieNumero = $this->extraerSerieNumero($texto);
        $datos['serie'] = $serieNumero['serie'];
        $datos['numero'] = $serieNumero['numero'];

        // Fecha
        $datos['fecha'] = $this->extraerFecha($texto);

        // RUC Emisor
        $datos['emisor_rut'] = $this->extraerEmisorRut($texto);

        // Receptor
        $datos['receptor_documento'] = $this->extraerReceptorDocumento($texto);
        $datos['receptor_nombre'] = $this->extraerReceptorNombre($texto);

        // Razon Social Emisor
        if (preg_match('/^([^\n]+)\n([^\n]+)/', ltrim($texto), $matches)) {
            $datos['emisor_nombre'] = trim($matches[1] . ' ' . $matches[2]);
        }

        // Montos
        if (preg_match('/TOTAL A PAGAR:\s*([\d\.,]+)/i', $texto, $matches)) {
            $datos['monto'] = $this->parsearMonto($matches[1]);
            $datos['subtotal'] = $datos['monto'];
        } elseif (preg_match('/MONTO NO FACTURABLE:\s*([\d\.,]+)/i', $texto, $matches)) {
            $datos['monto'] = $this->parsearMonto($matches[1]);
            $datos['subtotal'] = $datos['monto'];
        }

        // Moneda
        $datos['moneda'] = $this->detectarMoneda($texto);

        // Telefono
        $datos['telefono'] = $this->extraerTelefono($texto);

        // Datos adicionales de Jefatura
        if (preg_match('/(?:ING\.?|ING:|INGRESO|ING)(?:\s*N.?)?[:\s\t-]*(\d+)/iu', $texto, $matches)) {
            $datos['ingreso_contabilidad'] = $matches[1];
        }

        if (preg_match('/(?:ORDEN\s+DE\s+COBRO|ORDEN\s+COBRO|O\.C\.|O\/C|O\.\(?C\.)(?:\s*N.?)?[:\s\t-]*(\d+)/iu', $texto, $matches)) {
            $datos['orden_cobro'] = $matches[1];
        }

        // Adenda
        $datos['adenda'] = $this->extraerAdenda($texto);

        // Items (Detalle + Tramite via parsing de items)
        $this->extraerItems($texto, $datos);

        // Fallback: si no se parsearon items, extraer detalle crudo
        if (empty($datos['detalle'])) {
            if (preg_match('/DETALLE\s+DESCRIPCI[^\n]*\n\s*(.*?)(?=\s*(?:IMPORTE|MONTO|$))/isu', $texto, $matches)) {
                $datos['detalle'] = trim(preg_replace('/\s+/', ' ', $matches[1]));
            }
        }

        // Tramite fallback: si no se encontró en items, buscar en texto completo o adenda
        if (empty($datos['tramite'])) {
            if (preg_match(self::TRAMITE_PATTERN, $texto, $matches)) {
                $datos['tramite'] = $matches[1];
            } elseif (!empty($datos['adenda']) && preg_match(self::TRAMITE_PATTERN, $datos['adenda'], $matches)) {
                $datos['tramite'] = $matches[1];
            }
        }

        // Corregir partición detalle/descripción según concepto de caja conocido
        $this->corregirDetallePorConcepto($datos);

        // Orden de Cobro desde adenda
        if (empty($datos['orden_cobro']) && !empty($datos['adenda'])) {
            $datos['orden_cobro'] = $this->extraerOrdenCobroAdenda($datos['adenda']);
        }

        // Ingreso desde adenda
        if (empty($datos['ingreso_contabilidad']) && !empty($datos['adenda'])) {
            $adendaNorm = $this->quitarAcentos(mb_strtolower($datos['adenda'], 'UTF-8'));
            if (preg_match('/(?:ing\.?|ing:|ingreso|ing)(?:\s*n.?)?[:\s\t-]*(\d+)/iu', $adendaNorm, $matches)) {
                $datos['ingreso_contabilidad'] = $matches[1];
            }
        }

        return $datos;
    }

    /**
     * Extrae la orden de cobro de la adenda.
     *
     * @param string $adenda
     * @return string
     */
    private function extraerOrdenCobroAdenda(string $adenda): string
    {
        $adendaNorm = $this->quitarAcentos(mb_strtolower($adenda, 'UTF-8'));

        if (preg_match('/(?:orden\s+de\s+cobro|orden\s+cobro|o\.\s*\(?c\.?|o\/c)\s*(\d+)/iu', $adendaNorm, $matches)) {
            return $matches[1];
        }

        // Buscar un numero de 4 a 6 digitos que este solo o con separadores
        $numeros = [];
        if (preg_match_all('/\b(\d{4,6})\b/', $adenda, $numMatches)) {
            $numeros = $numMatches[1];
        }

        if (count($numeros) === 1) {
            return $numeros[0];
        }

        return '';
    }

    /**
     * Parsea los items del detalle, separando descripción de cantidades/precios
     * y extrayendo el número de trámite de las descripciones.
     *
     * Maneja dos estructuras reales del PDF:
     *   A) TRAMITE / ING en líneas propias, luego línea solo con números
     *   B) TRAMITE pegado a los números en la misma línea
     */
    private function extraerItems(string $texto, array &$datos): void
    {
        if (!preg_match('/DETALLE\s+DESCRIPCI[^\n]*\n\s*(.*?)(?=\s*(?:MONTO|TOTAL|$))/isu', $texto, $matches)) {
            return;
        }

        $bloqueItems = trim($matches[1]);
        $lineas      = explode("\n", $bloqueItems);
        $descripciones = [];
        $bufferDesc    = [];   // solo líneas de descripción real

        // Patrón que identifica una línea de metadata (trámite, ingreso, orden de cobro, anotaciones)
        $metaPattern = '/^(?:TR[ÁA]M(?:ITE)?\.?|ING(?:RESO)?\.?(?:\s*N[°º]?)?|O(?:RDEN)?[\s\/]?(?:DE\s+)?C(?:OBRO)?[\s\/]?\.?|REIMPRESI[ÓO]N)[\s:\-]*(.*)$/iu';

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (empty($linea)) continue;

            // Línea que termina con el patrón de cantidades/precios
            if (preg_match('/^(.*?)(\d[\d\.,]*(?:\s*\(Unid\))?\s+[\d\.,]+\s+[\d\.,]+)\s*$/i', $linea, $m)) {
                $restoDesc = trim($m[1]);

                // El "resto" antes de los números puede contener el trámite (Estructura B)
                if (!empty($restoDesc)) {
                    if (preg_match($metaPattern, $restoDesc, $metaM)) {
                        // Es metadata: extraer trámite si aplica
                        if (empty($datos['tramite']) && preg_match(self::TRAMITE_PATTERN, $restoDesc, $tramM)) {
                            $datos['tramite'] = $tramM[1];
                        }
                    } else {
                        $bufferDesc[] = $restoDesc;
                    }
                }

                $fullDesc = trim(preg_replace('/\s+/', ' ', implode(' ', $bufferDesc)));
                if (!empty($fullDesc)) {
                    $descripciones[] = $fullDesc;
                }

                $bufferDesc = [];

            } elseif (preg_match($metaPattern, $linea, $metaM)) {
                // Línea de metadata suelta (Estructura A): extraer trámite/ingreso/OC
                if (empty($datos['tramite']) && preg_match(self::TRAMITE_PATTERN, $linea, $tramM)) {
                    $datos['tramite'] = $tramM[1];
                }
                // ING suelto
                if (empty($datos['ingreso_contabilidad']) && preg_match('/^ING(?:RESO)?\.?(?:\s*N[°º]?)?[:\s\-]*(\d+)/iu', $linea, $ingM)) {
                    $datos['ingreso_contabilidad'] = $ingM[1];
                }

            } else {
                // Línea de descripción normal
                $bufferDesc[] = $linea;
            }
        }

        // Buffer sin cerrar (item sin línea de números)
        if (!empty($bufferDesc)) {
            $fullDesc = trim(preg_replace('/\s+/', ' ', implode(' ', $bufferDesc)));
            if (!empty($fullDesc)) {
                $descripciones[] = $fullDesc;
            }
        }

        if (!empty($descripciones)) {
            $datos['detalle'] = implode(' | ', $descripciones);
        }
    }

    /**
     * Extrae el número de trámite de la adenda.
     *
     * @param string $adenda
     * @return string
     */
    private function extraerTramiteAdenda(string $adenda): string
    {
        if (preg_match('/(?:TR[ÁA]?M(?:ITE)?\.?(?:\s+DE)?)\s*(?:N[º°]?|N\.?|Nro\.?|N)?[\s:\-.,]*\s*([\d][\d\/\-]+)/iu', $adenda, $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * Corrige la particion detalle/descripcion separando el tramite del detalle.
     * El patron TRAMITE/TRÁMITE actúa como delimitador entre el detalle real y la descripción.
     */
    private function corregirDetallePorConcepto(array &$datos): void
    {
        $detalle = $datos['detalle'] ?? '';
        if (empty($detalle)) return;

        // Separar en el punto donde aparece el patrón de trámite
        if (preg_match('/^(.*?)\s+(?:TR[ÁA]M(?:ITE)?\.?(?:\s+(?:DE|NRO?\.?|N[°º]?))*\s*(?:[:\-]?\s*)?[\d\/\-]+.*)/iu', $detalle, $m)) {
            $parteDetalle = trim($m[1]);
            $parteDesc    = trim(mb_substr($detalle, mb_strlen($parteDetalle, 'UTF-8'), null, 'UTF-8'));

            if ($parteDetalle !== '') {
                $datos['detalle'] = $parteDetalle;
                if ($parteDesc !== '' && empty($datos['descripcion'])) {
                    $datos['descripcion'] = $parteDesc;
                }
            }
        }
    }

    /**
     * Determina si es Porte o Tenencia basandose en el texto.
     *
     * @param string $texto
     * @return string 'porte_armas' o 'tenencia_armas'
     */
    public function determinarTipoArma(string $texto): string
    {
        $textoSinAcentos = $this->quitarAcentos(mb_strtolower($texto, 'UTF-8'));

        if (strpos($textoSinAcentos, 'tenencia') !== false || strpos($textoSinAcentos, 'tahta') !== false) {
            return 'tenencia_armas';
        }

        if (strpos($textoSinAcentos, 'porte') !== false) {
            return 'porte_armas';
        }

        // Por defecto, si tiene 'arma' pero no es especifico
        return 'tenencia_armas';
    }

    /**
     * Validación específica para CFEs de armas.
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

        if (empty($data['receptor_documento'])) {
            $errors[] = 'Documento del receptor no detectado';
        }

        if (empty($data['receptor_nombre'])) {
            $errors[] = 'Nombre del receptor no detectado';
        }

        if (empty($data['ingreso_contabilidad']) && empty($data['orden_cobro'])) {
            $errors[] = 'Ningún número de ingreso/orden de cobro detectado';
        }

        if (!empty($errors)) {
            throw CfeExtraccionInvalidaException::fromValidationErrors($errors);
        }
    }
}