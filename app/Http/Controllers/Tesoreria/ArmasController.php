<?php

namespace App\Http\Controllers\Tesoreria;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArmasController extends Controller
{
    private function getAniosDisponibles()
    {
        // Obtener años únicos de ambas tablas
        $aniosPorte = DB::table('tes_porte_armas')
            ->selectRaw('DISTINCT YEAR(fecha) as anio')
            ->whereNotNull('fecha')
            ->pluck('anio');

        $aniosTenencia = DB::table('tes_tenencia_armas')
            ->selectRaw('DISTINCT YEAR(fecha) as anio')
            ->whereNotNull('fecha')
            ->pluck('anio');

        // Combinar y ordenar descendentemente
        $anios = $aniosPorte->merge($aniosTenencia)
            ->unique()
            ->sort()
            ->reverse()
            ->values();

        // Si no hay años, devolver al menos el año actual
        if ($anios->isEmpty()) {
            $anios = collect([date('Y')]);
        }

        return $anios;
    }

    public function index()
    {
        $aniosDisponibles = $this->getAniosDisponibles();
        return view('tesoreria.armas.index', compact('aniosDisponibles'));
    }

    public function porte()
    {
        $aniosDisponibles = $this->getAniosDisponibles();
        return view('tesoreria.armas.porte', compact('aniosDisponibles'));
    }

    public function tenencia()
    {
        $aniosDisponibles = $this->getAniosDisponibles();
        return view('tesoreria.armas.tenencia', compact('aniosDisponibles'));
    }
}
