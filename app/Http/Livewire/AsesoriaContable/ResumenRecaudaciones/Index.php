<?php

namespace App\Http\Livewire\AsesoriaContable\ResumenRecaudaciones;

use App\Models\Tesoreria\SiifDistribucionTipo;
use App\Models\Tesoreria\TesCfe;
use Livewire\Component;

class Index extends Component
{
    public $tabActivo = null;

    public $search = '';
    public array $filtroMeses = [];
    public $filtroAno = null;

    public function mount()
    {
        $this->filtroAno = (int) date('Y');
        $this->filtroMeses = [(int) date('m')];
        $this->tabActivo = null;
    }

    public function updatedSearch()
    {
    }

    public function updatedFiltroMeses()
    {
    }

    public function updatedFiltroAno()
    {
    }

    public function cambiarTab($tipoId): void
    {
        $this->tabActivo = $tipoId;
    }

    public function limpiarFiltroMeses(): void
    {
        $this->filtroMeses = [];
    }

    public function resetearBusqueda(): void
    {
        $this->search = '';
        $this->filtroMeses = [];
        $this->filtroAno = (int) date('Y');
    }

    public function render()
    {
        $tiposConDatos = TesCfe::whereNotNull('siif_distribucion_tipo_id')
            ->distinct()
            ->pluck('siif_distribucion_tipo_id');

        $tiposDistribucion = SiifDistribucionTipo::whereIn('id', $tiposConDatos)
            ->ordenado()
            ->get();

        if (!$this->tabActivo || !$tiposDistribucion->contains('id', $this->tabActivo)) {
            $primerTipo = $tiposDistribucion->first();
            $this->tabActivo = $primerTipo?->id;
        }

        $anosRegistrados = TesCfe::whereNotNull('siif_distribucion_tipo_id')
            ->whereNotNull('fecha')
            ->selectRaw('YEAR(fecha) as year')
            ->distinct()
            ->pluck('year')
            ->sort()
            ->values()
            ->toArray();

        $currentYear = (int) date('Y');
        if (!in_array($currentYear, $anosRegistrados)) {
            array_unshift($anosRegistrados, $currentYear);
        }

        $grupos = collect();
        $totalGeneral = 0;
        $totalGeneralPorMedioPago = collect();

        if ($this->tabActivo) {
            $cfes = TesCfe::with(['siifDistribucionTipo', 'siifDistribucionDependencia', 'mediosPago', 'cajaConcepto'])
                ->where('siif_distribucion_tipo_id', $this->tabActivo)
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('documento_numero', 'like', '%' . $this->search . '%')
                          ->orWhere('receptor_nombre_denominacion', 'like', '%' . $this->search . '%');
                    });
                })
                ->when($this->filtroAno, fn($q) => $q->whereYear('fecha', $this->filtroAno))
                ->when(!empty($this->filtroMeses), function ($q) {
                    $q->where(function ($query) {
                        foreach ($this->filtroMeses as $mes) {
                            $query->orWhereMonth('fecha', (int) $mes);
                        }
                    });
                })
                ->orderBy('fecha', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            $grupos = $cfes->groupBy(function ($cfe) {
                return $cfe->fecha?->format('Y-m-d') ?? 'Sin fecha';
            })->sortKeysDesc()->map(function ($items, $fecha) {
                $subtotal = $items->sum('total_a_pagar');
                $mediosPagoSubtotal = $items->flatMap->mediosPago
                    ->groupBy('medio_pago_tipo')
                    ->map(fn($mps) => $mps->sum('medio_pago_valor'));

                $conceptos = $items->groupBy(function ($cfe) {
                    return $cfe->cajaConcepto?->caja_concepto ?? 'Sin concepto';
                })->map(function ($cfes, $concepto) {
                    $conceptoSubtotal = $cfes->sum('total_a_pagar');
                    $conceptoMediosPago = $cfes->flatMap->mediosPago
                        ->groupBy('medio_pago_tipo')
                        ->map(fn($mps) => $mps->sum('medio_pago_valor'));
                    return (object) [
                        'concepto' => $concepto,
                        'items' => $cfes,
                        'subtotal' => $conceptoSubtotal,
                        'mediosPago' => $conceptoMediosPago,
                    ];
                })->sortKeys()->values();

                return (object) [
                    'fecha' => $fecha,
                    'conceptos' => $conceptos,
                    'subtotal' => $subtotal,
                    'mediosPago' => $mediosPagoSubtotal,
                ];
            })->values();

            $totalGeneral = $cfes->sum('total_a_pagar');
            $totalGeneralPorMedioPago = $cfes->flatMap->mediosPago
                ->groupBy('medio_pago_tipo')
                ->map(fn($mps) => $mps->sum('medio_pago_valor'));
        }

        return view('livewire.asesoria-contable.resumen-recaudaciones', compact(
            'tiposDistribucion',
            'anosRegistrados',
            'grupos',
            'totalGeneral',
            'totalGeneralPorMedioPago'
        ))->extends('layouts.app')->section('content');
    }
}
