<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryItem extends Model
{
    protected $fillable = ['delivery_id', 'client_stock_batch_id', 'fish_type_id', 'carton_type_id', 'cartons_count', 'weight_kg'];
    protected $casts = ['weight_kg' => 'decimal:2'];

    public function delivery() { return $this->belongsTo(Delivery::class); }
    public function stockBatch() { return $this->belongsTo(ClientStockBatch::class, 'client_stock_batch_id'); }
    public function fishType() { return $this->belongsTo(FishType::class); }
    public function cartonType() { return $this->belongsTo(CartonType::class); }
}
