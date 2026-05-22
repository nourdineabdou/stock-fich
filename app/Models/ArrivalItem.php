<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArrivalItem extends Model
{
    protected $fillable = ['arrival_id', 'fish_type_id', 'weight_kg', 'quantity', 'notes'];
    protected $casts = ['weight_kg' => 'decimal:2'];

    public function arrival() { return $this->belongsTo(Arrival::class); }
    public function fishType() { return $this->belongsTo(FishType::class); }
}
