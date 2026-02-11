<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcesarCfeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Anyone can process a CFE for now
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'pdf_file' => 'required|file|mimes:pdf|max:10240', // 10MB max
            'source_url' => 'nullable|url',
            'user_id' => 'nullable|exists:users,id',
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'pdf_file.required' => 'El archivo PDF es requerido.',
            'pdf_file.file' => 'El archivo debe ser un PDF.',
            'pdf_file.mimes' => 'El archivo debe tener una extensión de PDF.',
            'pdf_file.max' => 'El archivo no puede superar los 10MB.',
        ];
    }
}
