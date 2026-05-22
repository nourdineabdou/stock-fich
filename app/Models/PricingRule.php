<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingRule extends Model
{
    protected $fillable = ['client_id', 'service_type', 'billing_method', 'charge_unit', 'billing_frequency', 'auto_generate', 'week_start_day', 'month_day', 'due_days', 'price', 'fish_type_id', 'carton_type_id', 'active'];
    protected $casts = ['active' => 'boolean', 'auto_generate' => 'boolean', 'price' => 'decimal:2'];

    public function client() { return $this->belongsTo(Client::class); }
    public function fishType() { return $this->belongsTo(FishType::class); }
    public function cartonType() { return $this->belongsTo(CartonType::class); }
}
