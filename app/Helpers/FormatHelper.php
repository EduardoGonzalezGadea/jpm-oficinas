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

    private static array $coloresTipo = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark'];

    public static function colorTipo($tipoId): string
    {
        if (is_null($tipoId)) {
            return 'secondary';
        }
        return self::$coloresTipo[((int) $tipoId - 1) % count(self::$coloresTipo)];
    }

    public static function renderTipo(string $label, $tipoId): string
    {
        $color = self::colorTipo($tipoId);
        $display = preg_replace('/^Recaudación\b/', 'Rec.', $label);
        $escaped = htmlspecialchars($display, ENT_QUOTES, 'UTF-8');
        return '<span class="d-inline-block rounded-circle bg-'.$color.' mr-1" style="width: 10px; height: 10px; vertical-align: middle;"></span><span style="vertical-align: middle;">'.$escaped.'</span>';
    }
}
