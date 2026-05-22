<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = ['client_id', 'movement_type', 'reference_type', 'reference_id', 'fish_type_id', 'quantity', 'weight_kg', 'from_location', 'to_location', 'movement_date', 'notes'];
    protected $casts = ['movement_date' => 'datetime', 'quantity' => 'decimal:2', 'weight_kg' => 'decimal:2'];

    public function client() { return $this->belongsTo(Client::class); }
    public function fishType() { return $this->belongsTo(FishType::class); }
}
