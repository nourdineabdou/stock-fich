<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tunnel extends Model
{
    protected $fillable = ['cold_room_id', 'name', 'capacity_chariots', 'temperature', 'active'];
    protected $casts = ['active' => 'boolean', 'temperature' => 'decimal:2'];

    public function coldRoom() { return $this->belongsTo(ColdRoom::class); }
    public function carts() { return $this->hasMany(Cart::class); }
    public function freezingCycles() { return $this->hasMany(FreezingCycle::class); }
    public function cartAssignments() { return $this->hasMany(CartAssignment::class); }
}
