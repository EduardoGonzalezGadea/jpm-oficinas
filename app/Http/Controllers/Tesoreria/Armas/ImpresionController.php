<?php

namespace App\Http\Controllers\Tesoreria\Armas;

use App\Http\Controllers\Controller;
use App\Models\Tesoreria\TesTenenciaArmas;
use App\Models\Tesoreria\TesPorteArmas;
use App\Models\Tesoreria\TesPorteArmasPlanilla;
use App\Models\Tesoreria\TesTenenciaArmasPlanilla;

class ImpresionController extends Controller
{
    /**
     * Muestra la vista imprimible para un recibo de Tenencia de Armas.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function imprimirTenencia($id)
    {
        $tenencia = TesTenenciaArmas::findOrFail($id);

        // Calcular el monto en letras
        $montoEnLetras = $this->numeroALetras($tenencia->monto);

        return view('tesoreria.armas.imprimir.tenencia-recibo', compact('tenencia', 'montoEnLetras'));
    }

    /**
     * Muestra la vista imprimible para un recibo de Porte de Armas.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function imprimirPorte($id)
    {
        $porte = TesPorteArmas::findOrFail($id);

        // Calcular el monto en letras
        $montoEnLetras = $this->numeroALetras($porte->monto);

        return view('tesoreria.armas.imprimir.porte-recibo', compact('porte', 'montoEnLetras'));
    }

    /**
     * Muestra la vista imprimible para una planilla de Porte de Armas.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function imprimirPlanillaPorte($id)
    {
        $planilla = TesPorteArmasPlanilla::with(['porteArmas', 'createdBy'])->findOrFail($id);
        return view('tesoreria.armas.imprimir.porte-planilla', compact('planilla'));
    }

    /**
     * Muestra la vista imprimible para una planilla de Tenencia de Armas.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function imprimirPlanillaTenencia($id)
    {
        $planilla = TesTenenciaArmasPlanilla::with(['tenenciaArmas', 'createdBy'])->findOrFail($id);
        return view('tesoreria.armas.imprimir.tenencia-planilla', compact('planilla'));
    }

    // --- Función para convertir números a letras ---
    private function numeroALetras($num)
    {
        $num = floatval($num); // Asegurar que sea un número
        if ($num == 0) return 'CERO PESOS 00/100 MN';

        // Formatear a dos decimales
        $num = number_format($num, 2, '.', '');
        $partes = explode('.', $num);
        $entero = intval($partes[0]);
        $decimal = $partes[1] ?? '00';

        $enteroEnLetras = $this->convertirGrupo($entero);

        // Manejar singular/plural de "PESO URUGUAYO"
        $moneda = ($entero == 1) ? 'PESO URUGUAYO' : 'PESOS URUGUAYOS';

        // Si el número es negativo, agregar "MENOS"
        if ($num < 0) {
            $enteroEnLetras = 'MENOS ' . $enteroEnLetras;
        }
        // Formatear el resultado final
        if ($decimal == '00') {
            return trim($enteroEnLetras) . ' ' . $moneda;
        }

        return trim($enteroEnLetras) . ' ' . $moneda . ' CON ' . $decimal . ' CENTÉSIMOS';
    }

    private function convertirGrupo($n)
    {
        // Listas de palabras
        $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $especiales = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
        $decenas = ['', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $centenas = ['', 'CIEN', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        if ($n >= 1000000) {
            $millones = intval($n / 1000000);
            $resto = $n % 1000000;
            $textoMillones = $this->convertirGrupo($millones) . ' MILLONES';
            return ($resto > 0) ? $textoMillones . ' ' . $this->convertirGrupo($resto) : $textoMillones;
        }
        if ($n >= 1000) {
            $miles = intval($n / 1000);
            $resto = $n % 1000;
            $textoMiles = ($miles == 1) ? 'MIL' : $this->convertirGrupo($miles) . ' MIL';
            return ($resto > 0) ? $textoMiles . ' ' . $this->convertirGrupo($resto) : $textoMiles;
        }
        if ($n >= 100) {
            if ($n == 100) return 'CIEN';
            return $centenas[intval($n / 100)] . ' ' . $this->convertirGrupo($n % 100);
        }
        if ($n >= 20) {
            $decena = intval($n / 10);
            $unidad = $n % 10;
            if ($unidad == 0) {
                return $decenas[$decena];
            } else {
                // Manejar "VEINTIUNO", "VEINTIDÓS", etc.
                if ($decena == 2) {
                    $veinti = ['VEINTI', 'VEINTIUNO', 'VEINTIDÓS', 'VEINTITRÉS', 'VEINTICUATRO', 'VEINTICINCO', 'VEINTISÉIS', 'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE'];
                    return $veinti[$unidad];
                }
                return $decenas[$decena] . ' Y ' . $unidades[$unidad];
            }
        }
        if ($n >= 10) {
            return $especiales[$n - 10];
        }
        return $unidades[$n];
    }
    // --- Fin de la función para convertir números a letras ---
}
