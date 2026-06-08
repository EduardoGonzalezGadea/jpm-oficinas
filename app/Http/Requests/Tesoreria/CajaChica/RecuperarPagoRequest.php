<?php

namespace App\Http\Requests\Tesoreria\CajaChica;

use Illuminate\Foundation\Http\FormRequest;

class RecuperarPagoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'recuperarPagoData.fecha' => 'required|date',
            'recuperarPagoData.numero_ingreso' => 'required|string|max:255',
            'recuperarPagoData.monto_recuperado' => 'required|numeric|min:0.01|max:99999999.99',
        ];

        // Access the data if needed since this is in Livewire it depends if it's evaluated properly
        // In Livewire array contexts, conditional rules are usually built in the component.
        // But for structure we define the base rules.

        return $rules;
    }

    public function messages()
    {
        return [
            'recuperarPagoData.fecha.required' => 'La fecha es obligatoria.',
            'recuperarPagoData.numero_ingreso.required' => 'El número de ingreso es obligatorio.',
            'recuperarPagoData.numero_ingreso.max' => 'El número de ingreso no puede exceder los 255 caracteres.',
            'recuperarPagoData.numero_ingreso_bse.required' => 'El número de ingreso BSE es obligatorio para Banco de Seguros del Estado.',
            'recuperarPagoData.numero_ingreso_bse.max' => 'El número de ingreso BSE no puede exceder los 255 caracteres.',
            'recuperarPagoData.monto_recuperado.required' => 'El monto recuperado es obligatorio.',
            'recuperarPagoData.monto_recuperado.numeric' => 'El monto recuperado debe ser un número válido.',
            'recuperarPagoData.monto_recuperado.min' => 'El monto recuperado debe ser al menos 0.01.',
            'recuperarPagoData.monto_recuperado.max' => 'El monto recuperado no puede exceder 99,999,999.99.',
        ];
    }
}
