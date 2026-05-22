<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ColdRoomRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'type'        => 'required|in:freezing,cold,ambient',
            'temperature' => 'nullable|numeric',
            'capacity_kg' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'active'      => 'boolean',
        ];
    }
}
