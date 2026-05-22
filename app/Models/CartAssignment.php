<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartAssignment extends Model
{
    protected $fillable = ['cart_id', 'processing_order_id', 'tunnel_id', 'assigned_at', 'released_at', 'status'];
    protected $casts = ['assigned_at' => 'datetime', 'released_at' => 'datetime'];

    public function cart() { return $this->belongsTo(Cart::class); }
    public function processingOrder() { return $this->belongsTo(ProcessingOrder::class); }
    public function tunnel() { return $this->belongsTo(Tunnel::class); }
}
