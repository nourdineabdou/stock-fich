<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = ['tunnel_id', 'code', 'capacity_kg', 'status'];
    protected $casts = ['capacity_kg' => 'decimal:2'];

    public function tunnel() { return $this->belongsTo(Tunnel::class); }
    public function levels() { return $this->hasMany(CartLevel::class); }
    public function assignments() { return $this->hasMany(CartAssignment::class); }

    public function getStatusColorAttribute(): string {
        return match($this->status) {
            'empty'      => 'green',
            'processing' => 'yellow',
            'frozen'     => 'blue',
            'completed'  => 'gray',
            default      => 'gray',
        };
    }
}
