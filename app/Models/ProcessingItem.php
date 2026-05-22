<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessingItem extends Model
{
    protected $fillable = ['processing_order_id', 'fish_type_id', 'input_weight', 'output_weight', 'waste_weight', 'returned_weight', 'price_per_kg', 'total'];
    protected $casts = ['input_weight' => 'decimal:2', 'output_weight' => 'decimal:2', 'waste_weight' => 'decimal:2', 'returned_weight' => 'decimal:2', 'price_per_kg' => 'decimal:2', 'total' => 'decimal:2'];

    public function processingOrder() { return $this->belongsTo(ProcessingOrder::class); }
    public function fishType() { return $this->belongsTo(FishType::class); }

    public function getYieldPercentageAttribute(): float {
        if ($this->input_weight <= 0) return 0;
        return round(($this->output_weight / $this->input_weight) * 100, 2);
    }
}
