<?php

namespace App\Helpers;

class TextoHelper
{
    public static function normalizarTexto(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        $from = ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ', 'à', 'è', 'ì', 'ò', 'ù', 'â', 'ê', 'î', 'ô', 'û'];
        $to   = ['a', 'e', 'i', 'o', 'u', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u'];
        return str_replace($from, $to, $texto);
    }

    public static function quitarAcentos(string $texto): string
    {
        $search  = ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', 'Ü'];
        $replace = ['a', 'e', 'i', 'o', 'u', 'n', 'u', 'A', 'E', 'I', 'O', 'U', 'N', 'U'];
        return str_replace($search, $replace, $texto);
    }

    public static function normalizarConcepto(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');

        $from = ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ', 'à', 'è', 'ì', 'ò', 'ù', 'â', 'ê', 'î', 'ô', 'û'];
        $to   = ['a', 'e', 'i', 'o', 'u', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u'];
        $texto = str_replace($from, $to, $texto);

        $texto = preg_replace('/\s*\([^)]*\)/u', '', $texto);

        $texto = preg_replace('/\b(de|del|la|el|las|los|y)\b/u', '', $texto);

        $plurales = [
            'armas'        => 'arma',
            'alarmas'      => 'alarma',
            'prendas'      => 'prenda',
            'certificados' => 'certificado',
            'multas'       => 'multa',
            'tenencias'    => 'tenencia',
            'titulos'      => 'titulo',
            'vehiculos'    => 'vehiculo',
            'servicios'    => 'servicio',
            'eventuales'   => 'eventual',
            'arrendamientos' => 'arrendamiento',
            'habilitaciones' => 'habilitacion',
            'documentos'   => 'documento',
            'notificaciones' => 'notificacion',
            'sanciones'    => 'sancion',
        ];
        foreach ($plurales as $plural => $singular) {
            $texto = preg_replace('/\b' . $plural . '\b/u', $singular, $texto);
        }

        $texto = preg_replace('/\s+/u', ' ', $texto);
        $texto = trim($texto);

        return $texto;
    }
}
