<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FreezingCycle extends Model
{
    protected $fillable = ['tunnel_id', 'processing_order_id', 'started_at', 'ended_at', 'start_temperature', 'end_temperature', 'status', 'notes'];
    protected $casts = ['started_at' => 'datetime', 'ended_at' => 'datetime', 'start_temperature' => 'decimal:2', 'end_temperature' => 'decimal:2'];

    public function tunnel() { return $this->belongsTo(Tunnel::class); }
    public function processingOrder() { return $this->belongsTo(ProcessingOrder::class); }

    public function getDurationMinutesAttribute(): ?int {
        if (!$this->started_at || !$this->ended_at) return null;
        return $this->started_at->diffInMinutes($this->ended_at);
    }
}
