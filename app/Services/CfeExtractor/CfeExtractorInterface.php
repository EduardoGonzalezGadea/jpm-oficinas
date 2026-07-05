<?php

namespace App\Services\CfeExtractor;

use App\DTOs\CfeExtraccionDto;

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
     * Extrae los datos del texto del PDF y retorna un DTO validado.
     *
     * @param string $texto Texto extraido del PDF
     * @return CfeExtraccionDto Datos extraidos validados
     * @throws \App\Exceptions\CfeExtraccionInvalidaException Si la validación falla
     */
    public function extraer(string $texto): CfeExtraccionDto;

    /**
     * Valida que los datos extraidos sean consistentes.
     * Lanza excepción si no son válidos.
     *
     * @param CfeExtraccionDto $dto Datos extraidos
     * @return void
     * @throws \App\Exceptions\CfeExtraccionInvalidaException
     */
    public function validar(CfeExtraccionDto $dto): void;

    /**
     * Retorna el nombre legible del tipo de CFE.
     *
     * @return string
     */
    public function getNombreLegible(): string;

    /**
     * Retorna la versión del extractor.
     *
     * @return string
     */
    public function getExtractorVersion(): string;
}