<?php

namespace App\Traits;

trait ConvertirMayusculas
{
    /**
     * Convierte un texto a mayúsculas respetando acentos y símbolos
     */
    protected function toUpper($value)
    {
        if (is_string($value)) {
            return mb_strtoupper($value, 'UTF-8');
        }

        return $value;
    }

    /**
     * Convierte múltiples campos a mayúsculas
     */
    protected function convertirCamposAMayusculas(array $campos, array $datos)
    {
        foreach ($campos as $campo) {
            if (isset($datos[$campo])) {
                $datos[$campo] = $this->toUpper($datos[$campo]);
            }
        }
        return $datos;
    }
}
