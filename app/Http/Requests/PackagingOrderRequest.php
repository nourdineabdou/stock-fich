<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PackagingOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'processing_order_id'         => 'required|exists:processing_orders,id',
            'date'                         => 'required|date',
            'status'                       => 'nullable|in:pending,completed',
            'items'                        => 'nullable|array',
            'items.*.fish_type_id'         => 'required|exists:fish_types,id',
            'items.*.carton_type_id'       => 'required|exists:carton_types,id',
            'items.*.cartons_count'        => 'required|integer|min:1',
            'items.*.weight_per_carton'    => 'required|numeric|min:0',
            'items.*.cold_room_id'         => 'nullable|exists:cold_rooms,id',
        ];
    }
}
