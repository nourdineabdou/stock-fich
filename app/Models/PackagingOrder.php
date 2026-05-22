<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackagingOrder extends Model
{
    protected $fillable = ['processing_order_id', 'reference', 'date', 'status'];
    protected $casts = ['date' => 'datetime'];

    public function processingOrder() { return $this->belongsTo(ProcessingOrder::class); }
    public function items() { return $this->hasMany(PackagingItem::class); }
    public function stockBatches() { return $this->hasMany(ClientStockBatch::class); }
    public function storageEntries() { return $this->hasMany(StorageEntry::class); }
}
