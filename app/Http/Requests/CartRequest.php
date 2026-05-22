<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CartRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'tunnel_id'   => 'required|exists:tunnels,id',
            'code'        => 'required|string|max:100',
            'capacity_kg' => 'required|numeric|min:1',
            'status'      => 'required|in:empty,processing,frozen,completed',
        ];
    }
}
