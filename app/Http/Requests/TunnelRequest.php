<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TunnelRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'cold_room_id'       => 'required|exists:cold_rooms,id',
            'name'               => 'required|string|max:255',
            'capacity_chariots'  => 'required|integer|min:1',
            'temperature'        => 'nullable|numeric',
            'active'             => 'boolean',
        ];
    }
}
