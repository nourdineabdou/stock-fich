<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name', 'phone', 'email', 'address', 'ice_number', 'active',
    ];

    protected $casts = ['active' => 'boolean'];

    public function arrivals() { return $this->hasMany(Arrival::class); }
    public function processingOrders() { return $this->hasMany(ProcessingOrder::class); }
    public function stockBatches() { return $this->hasMany(ClientStockBatch::class); }
    public function deliveries() { return $this->hasMany(Delivery::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function pricingRules() { return $this->hasMany(PricingRule::class); }
    public function stockMovements() { return $this->hasMany(StockMovement::class); }
}
