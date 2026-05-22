<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartonType extends Model
{
    protected $fillable = ['name', 'weight_kg', 'description', 'active'];
    protected $casts = ['active' => 'boolean', 'weight_kg' => 'decimal:2'];

    public function fishTypes() {
        return $this->belongsToMany(FishType::class, 'fish_type_carton_types')
            ->withPivot('default_weight', 'min_cartons', 'max_cartons', 'price', 'is_default', 'active')
            ->withTimestamps();
    }
    public function packagingItems() { return $this->hasMany(PackagingItem::class); }
    public function stockBatches() { return $this->hasMany(ClientStockBatch::class); }
    public function deliveryItems() { return $this->hasMany(DeliveryItem::class); }
    public function pricingRules() { return $this->hasMany(PricingRule::class); }
}
