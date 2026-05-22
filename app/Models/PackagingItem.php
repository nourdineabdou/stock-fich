<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackagingItem extends Model
{
    protected $fillable = ['packaging_order_id', 'fish_type_id', 'carton_type_id', 'cartons_count', 'weight_per_carton', 'lot_number', 'cold_room_id', 'produced_at'];
    protected $casts = ['produced_at' => 'datetime', 'weight_per_carton' => 'decimal:2'];

    public function packagingOrder() { return $this->belongsTo(PackagingOrder::class); }
    public function fishType() { return $this->belongsTo(FishType::class); }
    public function cartonType() { return $this->belongsTo(CartonType::class); }
    public function coldRoom() { return $this->belongsTo(ColdRoom::class); }

    public function getTotalWeightAttribute(): float {
        return $this->cartons_count * $this->weight_per_carton;
    }
}
