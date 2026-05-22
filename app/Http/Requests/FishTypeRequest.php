<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FishTypeRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name'            => 'required|string|max:255',
            'scientific_name' => 'nullable|string|max:255',
            'description'     => 'nullable|string',
            'active'          => 'boolean',
        ];
    }
}
