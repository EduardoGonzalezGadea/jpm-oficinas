<?php
// app/Http/Controllers/Tesoreria/CajaChica/ImpresionController.php

namespace App\Http\Controllers\Tesoreria\CajaChica;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tesoreria\Pendiente;
use App\Models\Tesoreria\Pago;

class ImpresionController extends Controller
{
    /**
     * Muestra la vista imprimible para un Pendiente.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function imprimirPendiente($id)
    {
        $pendiente = Pendiente::with(['cajaChica', 'dependencia'])->findOrFail($id);

        // Calcular el monto en letras
        $montoEnLetras = $this->numeroALetras($pendiente->montoPendientes);

        return view('tesoreria.caja-chica.imprimir.pendiente', compact('pendiente', 'montoEnLetras'));
    }

    /**
     * Muestra la vista imprimible para un Pago Directo.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function imprimirPago($id)
    {
        $pago = Pago::with(['cajaChica', 'acreedor'])->findOrFail($id);

        return view('tesoreria.caja-chica.imprimir.pago', compact('pago'));
    }

    // --- Función para convertir números a letras ---
    // Esta es una versión simplificada. Para producción, considera usar una librería más completa.
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

        // Manejar singular/plural de "PESO"
        $moneda = ($entero == 1) ? 'PESO' : 'PESOS';

        return trim($enteroEnLetras) . ' ' . $moneda . ' ' . $decimal . '/100 MN';
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
