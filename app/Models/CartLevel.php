<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartLevel extends Model
{
    protected $fillable = ['cart_id', 'level_number', 'capacity_kg'];
    protected $casts = ['capacity_kg' => 'decimal:2'];

    public function cart() { return $this->belongsTo(Cart::class); }
}
