<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $fillable = ['client_id', 'reference', 'delivery_date', 'status', 'notes', 'receiver_name', 'receiver_id_number', 'receiver_phone', 'validated_at'];
    protected $casts = ['delivery_date' => 'datetime', 'validated_at' => 'datetime'];

    public function client() { return $this->belongsTo(Client::class); }
    public function items() { return $this->hasMany(DeliveryItem::class); }

    public function getTotalCartonsAttribute(): int {
        return $this->items->sum('cartons_count');
    }
    public function getTotalWeightAttribute(): float {
        return $this->items->sum('weight_kg');
    }
}
