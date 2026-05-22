<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'phone'       => 'nullable|string|max:30',
            'email'       => 'nullable|email|max:255',
            'address'     => 'nullable|string|max:500',
            'ice_number'  => 'nullable|string|max:50',
            'active'      => 'boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'       => 'Nom',
            'phone'      => 'Téléphone',
            'email'      => 'Email',
            'address'    => 'Adresse',
            'ice_number' => 'ICE',
        ];
    }
}
