<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Cualquiera autenticado puede intentar crear, la autorización real se delega a Spatie Permissions
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'cedula' => 'nullable|string|unique:users,cedula|max:15',
            'telefono' => 'nullable|string|max:30',
            'direccion' => 'nullable|string|max:500',
            'modulo_id' => 'nullable|exists:modulos,id',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
        ];
    }

    /**
     * Get the custom validation messages for the defined rules.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'nombre.required' => 'El nombre es obligatorio',
            'apellido.required' => 'El apellido es obligatorio',
            'email.required' => 'El email es obligatorio',
            'email.unique' => 'Ya existe un usuario con este email',
            'cedula.unique' => 'Ya existe un usuario con esta cédula',
            'roles.required' => 'Debe seleccionar al menos un rol',
        ];
    }
}