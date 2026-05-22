<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->map(function ($item) {
                if (!is_array($item)) {
                    return $item;
                }

                if (empty($item['service_type'])) {
                    $item['service_type'] = 'other';
                }

                return $item;
            })
            ->all();

        $this->merge(['items' => $items]);
    }

    public function rules(): array
    {
        return [
            'client_id'    => 'required|exists:clients,id',
            'processing_order_id' => 'nullable|exists:processing_orders,id',
            'invoice_date' => 'required|date',
            'due_date'     => 'nullable|date|after_or_equal:invoice_date',
            'status'       => 'required|in:draft,sent,paid,partial,cancelled',
            'tax_rate'     => 'required|numeric|min:0|max:100',
            'notes'        => 'nullable|string',
            'items'        => 'nullable|array',
            'items.*.description' => 'required|string',
            'items.*.service_type'=> 'required|in:storage,processing,packaging,freezing,delivery,other',
            'items.*.quantity'    => 'required|numeric|min:0',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ];
    }
}
