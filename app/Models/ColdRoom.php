<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColdRoom extends Model
{
    protected $fillable = ['name', 'type', 'temperature', 'capacity_kg', 'description', 'active'];
    protected $casts = ['active' => 'boolean', 'temperature' => 'decimal:2', 'capacity_kg' => 'decimal:2'];

    public function tunnels() { return $this->hasMany(Tunnel::class); }
    public function packagingItems() { return $this->hasMany(PackagingItem::class); }
    public function stockBatches() { return $this->hasMany(ClientStockBatch::class); }
    public function storageEntries() { return $this->hasMany(StorageEntry::class); }
}
