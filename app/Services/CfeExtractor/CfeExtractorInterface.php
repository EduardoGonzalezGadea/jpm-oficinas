<?php

namespace App\Services\CfeExtractor;

/**
 * Interfaz para extractores de datos de CFE.
 * Cada tipo de comprobante tiene su propia implementacion.
 */
interface CfeExtractorInterface
{
    /**
     * Verifica si este extractor soporta el tipo de CFE dado.
     *
     * @param string $tipo Tipo de CFE detectado
     * @return bool
     */
    public function soporta(string $tipo): bool;

    /**
     * Extrae los datos del texto del PDF.
     *
     * @param string $texto Texto extraido del PDF
     * @return array Datos extraidos
     */
    public function extraer(string $texto): array;

    /**
     * Valida que los datos extraidos sean consistentes.
     *
     * @param array $datos Datos extraidos
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validar(array $datos): array;

    /**
     * Retorna el nombre legible del tipo de CFE.
     *
     * @return string
     */
    public function getNombreLegible(): string;
}