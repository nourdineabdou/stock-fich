<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProcessingOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'client_id'      => 'required|exists:clients,id',
            'arrival_id'     => 'nullable|exists:arrivals,id',
            'start_date'     => 'required|date',
            'end_date'       => 'nullable|date|after_or_equal:start_date',
            'billing_method' => 'required|in:kg,carton,day,fixed',
            'price'          => 'nullable|numeric|min:0',
            'status'         => 'required|in:pending,processing,completed,cancelled',
            'notes'          => 'nullable|string',
            'input_weight'   => 'nullable|numeric|min:0',
            'output_weight'  => 'nullable|numeric|min:0',
            'waste_weight'   => 'nullable|numeric|min:0',
            'returned_weight'=> 'nullable|numeric|min:0',
            'items'          => 'nullable|array',
            'items.*.fish_type_id'    => 'nullable|exists:fish_types,id',
            'items.*.input_weight'    => 'nullable|numeric|min:0',
            'items.*.output_weight'   => 'nullable|numeric|min:0',
            'items.*.waste_weight'    => 'nullable|numeric|min:0',
            'items.*.returned_weight' => 'nullable|numeric|min:0',
            'items.*.price_per_kg'    => 'nullable|numeric|min:0',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $inputWeight = $this->toNullableFloat($this->input('input_weight'));
            $outputWeight = $this->toNullableFloat($this->input('output_weight')) ?? 0;
            $wasteWeight = $this->toNullableFloat($this->input('waste_weight')) ?? 0;
            $returnedWeight = $this->toNullableFloat($this->input('returned_weight')) ?? 0;

            if ($inputWeight === null) {
                return;
            }

            if (($outputWeight + $wasteWeight + $returnedWeight) > $inputWeight) {
                $validator->errors()->add(
                    'returned_weight',
                    'Le total stockable + dechets + retour client ne peut pas depasser le poids recu.'
                );
            }

            $detailOutputTotal = 0.0;
            foreach ($this->input('items', []) as $index => $item) {
                $fishTypeId = $item['fish_type_id'] ?? null;
                $detailOutput = $this->toNullableFloat($item['output_weight'] ?? null);
                $detailPrice = $this->toNullableFloat($item['price_per_kg'] ?? null);

                if ($fishTypeId === null && $detailOutput === null && $detailPrice === null) {
                    continue;
                }
                $detailOutputTotal += max(0, (float) $detailOutput);
            }
        });
    }

    private function toNullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}
