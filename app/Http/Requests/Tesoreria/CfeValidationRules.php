<?php

namespace App\Http\Requests\Tesoreria;

trait CfeValidationRules
{
    public function cfeBaseRules(): array
    {
        return [
            'tes_caja_concepto_id' => 'required|integer|min:1|exists:tes_caja_conceptos,id',
            'siif_dependencia_id' => 'nullable|integer|exists:siif_distribucion_dependencias,id',
        ];
    }

    public function cfeBaseMessages(): array
    {
        return [
            'tes_caja_concepto_id.required' => 'Debe seleccionar un concepto de caja.',
            'tes_caja_concepto_id.min' => 'Debe seleccionar un concepto de caja válido.',
            'tes_caja_concepto_id.exists' => 'El concepto de caja seleccionado no existe.',
            'siif_dependencia_id.exists' => 'La dependencia de distribución SIIF seleccionada no existe.',
        ];
    }
}
