<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArrivalRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'client_id'    => 'required|exists:clients,id',
            'arrival_date' => 'required|date',
            'status'       => 'required|in:pending,processing,completed',
            'notes'        => 'nullable|string',
            'items'        => 'required|array|min:1',
            'items.*.weight_kg'    => 'required|numeric|min:0.01',
            'items.*.quantity'     => 'nullable|integer|min:1',
            'items.*.notes'        => 'nullable|string',
        ];
    }
}
