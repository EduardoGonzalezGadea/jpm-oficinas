<?php

namespace App\Helpers;

use Carbon\Carbon;

class FormatHelper
{
    /**
     * Formatea un valor numérico como moneda Uruguay
     * Ejemplo: 1234.56 -> $ 1.234,56
     */
    public static function moneyUyu($value): string
    {
        if (is_null($value)) {
            return '$ 0,00';
        }

        return '$ ' . number_format((float) $value, 2, ',', '.');
    }

    /**
     * Formatea una fecha al formato Uruguay
     * Ejemplo: 2026-02-26 -> 26/02/2026
     */
    public static function dateUy($value): string
    {
        if (is_null($value)) {
            return '';
        }

        return Carbon::parse($value)->format('d/m/Y');
    }

    /**
     * Formatea un número con separador de miles Uruguay
     * Ejemplo: 1234 -> 1.234
     */
    public static function numberUy($value, int $decimals = 0): string
    {
        return number_format((float) $value, $decimals, ',', '.');
    }
}
