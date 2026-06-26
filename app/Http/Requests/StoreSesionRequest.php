<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSesionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $textFields = ['microreto_titulo', 'centro_educativo', 'ciclo_formativo', 'curso', 'grupo', 'notas'];

        $sanitized = [];
        foreach ($textFields as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $sanitized[$field] = strip_tags($this->input($field));
            }
        }

        if ($sanitized) {
            $this->merge($sanitized);
        }
    }

    public function rules(): array
    {
        return [
            'microreto_titulo' => 'required|string|max:500',
            'fecha'            => 'required|date',
            'microreto_id'     => 'nullable',
            'centro_educativo' => 'nullable|string|max:255',
            'ciclo_formativo'  => 'nullable|string|max:255',
            'curso'            => 'nullable|string|max:10',
            'grupo'            => 'nullable|string|max:10',
            'num_alumnos'      => 'nullable|integer|min:1|max:999',
            'notas'            => 'nullable|string|max:5000',
        ];
    }
}
