<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FishTypeCartonType extends Model
{
    protected $table = 'fish_type_carton_types';
    protected $fillable = ['fish_type_id', 'carton_type_id', 'default_weight', 'min_cartons', 'max_cartons', 'price', 'is_default', 'active'];
    protected $casts = ['is_default' => 'boolean', 'active' => 'boolean', 'default_weight' => 'decimal:2', 'price' => 'decimal:2'];

    public function fishType() { return $this->belongsTo(FishType::class); }
    public function cartonType() { return $this->belongsTo(CartonType::class); }
}
