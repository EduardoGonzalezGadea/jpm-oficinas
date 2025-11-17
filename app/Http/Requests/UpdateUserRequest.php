<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Cualquiera autenticado puede intentar actualizar, la autorización real se delega a Spatie Permissions
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $userId = $this->route('usuario')->id;

        return [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($userId),
            ],
            'cedula' => [
                'nullable',
                'string',
                'max:15',
                Rule::unique('users')->ignore($userId),
            ],
            'telefono' => 'nullable|string|max:30',
            'direccion' => 'nullable|string|max:500',
            'modulo_id' => 'nullable|exists:modulos,id',
            'roles' => 'required|exists:roles,name',
            'activo' => 'boolean',
        ];
    }
}