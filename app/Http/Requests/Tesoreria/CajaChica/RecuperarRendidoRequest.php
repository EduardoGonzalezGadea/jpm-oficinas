<?php

namespace App\Http\Requests\Tesoreria\CajaChica;

use Illuminate\Foundation\Http\FormRequest;

class RecuperarRendidoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'recuperarRendidoData.fecha' => 'required|date',
            'recuperarRendidoData.documentos' => 'nullable|string|max:255',
            'recuperarRendidoData.monto_recuperado' => 'required|numeric|min:0.01|max:99999999.99',
        ];
    }

    public function messages()
    {
        return [
            'recuperarRendidoData.fecha.required' => 'La fecha es obligatoria.',
            'recuperarRendidoData.documentos.max' => 'Los documentos no pueden exceder los 255 caracteres.',
            'recuperarRendidoData.monto_recuperado.required' => 'El monto recuperado es obligatorio.',
            'recuperarRendidoData.monto_recuperado.numeric' => 'El monto recuperado debe ser un número válido.',
            'recuperarRendidoData.monto_recuperado.min' => 'El monto recuperado debe ser al menos 0.01.',
            'recuperarRendidoData.monto_recuperado.max' => 'El monto recuperado no puede exceder 99,999,999.99.',
        ];
    }
}
