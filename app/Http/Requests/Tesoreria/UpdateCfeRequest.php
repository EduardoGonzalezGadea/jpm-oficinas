<?php

namespace App\Http\Requests\Tesoreria;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCfeRequest extends FormRequest
{
    use CfeValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge($this->cfeBaseRules(), [
            'fecha' => 'nullable|date',
        ]);
    }

    public function messages(): array
    {
        return $this->cfeBaseMessages();
    }
}
