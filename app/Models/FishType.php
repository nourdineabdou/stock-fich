<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FishType extends Model
{
    protected $fillable = ['name', 'scientific_name', 'description', 'active'];
    protected $casts = ['active' => 'boolean'];

    public function cartonTypes() {
        return $this->belongsToMany(CartonType::class, 'fish_type_carton_types')
            ->withPivot('default_weight', 'min_cartons', 'max_cartons', 'price', 'is_default', 'active')
            ->withTimestamps();
    }
    public function arrivalItems() { return $this->hasMany(ArrivalItem::class); }
    public function processingItems() { return $this->hasMany(ProcessingItem::class); }
    public function packagingItems() { return $this->hasMany(PackagingItem::class); }
    public function stockBatches() { return $this->hasMany(ClientStockBatch::class); }
    public function stockMovements() { return $this->hasMany(StockMovement::class); }
    public function pricingRules() { return $this->hasMany(PricingRule::class); }
}
