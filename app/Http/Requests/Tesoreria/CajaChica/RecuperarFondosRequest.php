<?php

namespace App\Http\Requests\Tesoreria\CajaChica;

use Illuminate\Foundation\Http\FormRequest;

class RecuperarFondosRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'recuperacion.fecha' => 'required|date',
            'recuperacion.numero_ingreso' => 'required|string|max:50',
            'itemsSeleccionados' => 'required|array|min:1',
        ];
    }

    public function messages()
    {
        return [
            'recuperacion.fecha.required' => 'La fecha de recuperación es obligatoria.',
            'recuperacion.numero_ingreso.required' => 'El número de ingreso es obligatorio.',
            'itemsSeleccionados.required' => 'Debe seleccionar al menos un ítem para recuperar.',
            'itemsSeleccionados.min' => 'Debe seleccionar al menos un ítem para recuperar.',
        ];
    }
}
