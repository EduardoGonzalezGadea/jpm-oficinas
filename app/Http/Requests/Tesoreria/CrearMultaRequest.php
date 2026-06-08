<?php

namespace App\Http\Requests\Tesoreria;

use Illuminate\Foundation\Http\FormRequest;

class CrearMultaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'recibo'                   => 'required|string|max:255',
            'fecha'                    => 'required|date',
            'monto'                    => 'required|numeric|min:0',
            'cedula'                   => 'nullable|string|max:20',
            'nombre'                   => 'nullable|string|max:255',
            'domicilio'                => 'nullable|string|max:255',
            'forma_pago'               => 'nullable|string',
            'referencias'              => 'nullable|string',
            'adenda'                   => 'nullable|string',
            'items_form'               => 'required|array|min:1',
            'items_form.*.detalle'     => 'required|string|max:255',
            'items_form.*.importe'     => 'required|numeric|min:0',
        ];
    }
}
