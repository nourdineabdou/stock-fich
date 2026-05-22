<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Arrival extends Model
{
    protected $fillable = ['client_id', 'reference', 'arrival_date', 'gross_weight', 'net_weight', 'status', 'notes'];
    protected $casts = ['arrival_date' => 'datetime', 'gross_weight' => 'decimal:2', 'net_weight' => 'decimal:2'];

    public function client() { return $this->belongsTo(Client::class); }
    public function items() { return $this->hasMany(ArrivalItem::class); }
    public function processingOrders() { return $this->hasMany(ProcessingOrder::class); }
}
