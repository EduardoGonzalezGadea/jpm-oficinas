<?php

namespace App\Services\Tesoreria;

use App\Models\Tesoreria\TesCfeItem;
use App\Models\Tesoreria\TesPlanillaEr;
use Illuminate\Support\Facades\DB;

class AnularPlanillaService
{
    public function anular(TesPlanillaEr $planilla, string $motivo): void
    {
        if ($planilla->confirmada) {
            throw new \RuntimeException('No se puede anular una planilla que ha sido confirmada.');
        }

        DB::transaction(function () use ($planilla, $motivo) {
            foreach ($planilla->items as $item) {
                $duplicado = $item->replicate();

                $item->update(['planilla_er_id' => null]);

                $duplicado->planilla_er_id = $planilla->id;
                $duplicado->detalle = ($duplicado->detalle ?? '') . ' (PLANILLA ANULADA)';
                $duplicado->created_by = auth()->id();
                $duplicado->updated_by = auth()->id();
                $duplicado->save();

                $duplicado->delete();
            }

            $planilla->update(['motivo_anulacion' => $motivo]);
            $planilla->delete();
        });
    }
}
