<?php

namespace App\Http\Requests\Tesoreria;

use Illuminate\Foundation\Http\FormRequest;

class StoreCfeRequest extends FormRequest
{
    use CfeValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge($this->cfeBaseRules(), [
            'documento_tipo' => 'required|string|max:50',
            'documento_serie' => 'nullable|string|max:10',
            'documento_numero' => 'required|string|max:20',
            'fecha' => 'required|date',
            'receptor_nombre_denominacion' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.detalle' => 'required|string|max:500',
            'items.*.importe' => 'required|numeric|min:0',
        ]);
    }

    public function messages(): array
    {
        return array_merge($this->cfeBaseMessages(), [
            'documento_tipo.required' => 'El tipo de documento es obligatorio.',
            'documento_numero.required' => 'El número de documento es obligatorio.',
            'fecha.required' => 'La fecha es obligatoria.',
            'receptor_nombre_denominacion.required' => 'El nombre del receptor es obligatorio.',
            'items.required' => 'Debe agregar al menos un ítem.',
            'items.*.detalle.required' => 'El detalle del ítem es obligatorio.',
            'items.*.importe.required' => 'El importe del ítem es obligatorio.',
        ]);
    }
}
