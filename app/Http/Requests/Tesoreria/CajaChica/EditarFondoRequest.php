<?php

namespace App\Http\Requests\Tesoreria\CajaChica;

use Illuminate\Foundation\Http\FormRequest;

class EditarFondoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'editandoFondo.monto' => 'required|numeric|min:0|max:99999999.99',
        ];
    }

    public function messages()
    {
        return [
            'editandoFondo.monto.required' => 'El monto es obligatorio.',
            'editandoFondo.monto.numeric'  => 'El monto debe ser un número válido.',
            'editandoFondo.monto.min'      => 'El monto no puede ser negativo.',
            'editandoFondo.monto.max'      => 'El monto no puede exceder 99,999,999.99.',
        ];
    }
}
