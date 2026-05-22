<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'client_id'     => 'required|exists:clients,id',
            'delivery_date' => 'required|date',
            'status'        => 'required|in:pending,delivered,cancelled',
            'notes'         => 'nullable|string',
            'receiver_name' => 'nullable|string|max:255',
            'receiver_id_number' => 'nullable|string|max:100',
            'receiver_phone' => 'nullable|string|max:50',
            'items'         => 'required|array|min:1',
            'items.*.client_stock_batch_id' => 'required|exists:client_stock_batches,id',
            'items.*.cartons_count'         => 'required|integer|min:1',
        ];
    }
}
