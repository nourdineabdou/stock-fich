<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorageEntry extends Model
{
    protected $fillable = ['packaging_order_id', 'client_stock_batch_id', 'cold_room_id', 'type', 'cartons_count', 'weight_kg', 'reference', 'entry_date', 'notes'];
    protected $casts = ['entry_date' => 'datetime', 'weight_kg' => 'decimal:2'];

    public function packagingOrder() { return $this->belongsTo(PackagingOrder::class); }
    public function stockBatch() { return $this->belongsTo(ClientStockBatch::class, 'client_stock_batch_id'); }
    public function coldRoom() { return $this->belongsTo(ColdRoom::class); }
}
