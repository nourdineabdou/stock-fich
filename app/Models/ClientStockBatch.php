<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientStockBatch extends Model
{
    protected $fillable = ['packaging_order_id', 'client_id', 'fish_type_id', 'carton_type_id', 'cold_room_id', 'lot_number', 'entry_reference', 'cartons_count', 'weight_per_carton', 'total_weight', 'available_cartons', 'available_weight', 'reserved_cartons', 'reserved_weight', 'frozen_at', 'entry_date', 'status', 'notes'];
    protected $casts = ['frozen_at' => 'datetime', 'entry_date' => 'datetime', 'weight_per_carton' => 'decimal:2', 'total_weight' => 'decimal:2', 'available_weight' => 'decimal:2', 'reserved_weight' => 'decimal:2'];

    public function packagingOrder() { return $this->belongsTo(PackagingOrder::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function fishType() { return $this->belongsTo(FishType::class); }
    public function cartonType() { return $this->belongsTo(CartonType::class); }
    public function coldRoom() { return $this->belongsTo(ColdRoom::class); }
    public function deliveryItems() { return $this->hasMany(DeliveryItem::class); }
    public function storageEntries() { return $this->hasMany(StorageEntry::class); }
}
