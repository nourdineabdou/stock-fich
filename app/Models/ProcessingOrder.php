<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessingOrder extends Model
{
    protected $fillable = ['client_id', 'arrival_id', 'reference', 'start_date', 'end_date', 'billing_method', 'price', 'status', 'notes', 'input_weight', 'output_weight', 'waste_weight', 'returned_weight', 'cart_distribution'];
    protected $casts = ['start_date' => 'datetime', 'end_date' => 'datetime', 'price' => 'decimal:2', 'input_weight' => 'decimal:2', 'output_weight' => 'decimal:2', 'waste_weight' => 'decimal:2', 'returned_weight' => 'decimal:2', 'cart_distribution' => 'array'];

    public function client() { return $this->belongsTo(Client::class); }
    public function arrival() { return $this->belongsTo(Arrival::class); }
    public function items() { return $this->hasMany(ProcessingItem::class); }
    public function invoice() { return $this->hasOne(Invoice::class); }
    public function cartAssignments() { return $this->hasMany(CartAssignment::class); }
    public function packagingOrders() { return $this->hasMany(PackagingOrder::class); }
    public function freezingCycles() { return $this->hasMany(FreezingCycle::class); }

    /**
     * Le cartonnage n'est possible qu'apres la sortie du tunnel : les chariots doivent avoir
     * ete affectes puis tous dissocies du tunnel. Retourne null si pret, sinon la raison.
     */
    public function packagingBlockReason(): ?string
    {
        $assignments = $this->relationLoaded('cartAssignments') ? $this->cartAssignments : $this->cartAssignments()->get();

        if ($assignments->isEmpty()) {
            return "Les chariots ne sont pas encore affectés ni passés au tunnel.";
        }

        $active = $assignments->where('status', 'active');
        $notPlaced = $active->whereNull('tunnel_id')->count();
        if ($notPlaced > 0) {
            return "{$notPlaced} chariot(s) pas encore mis dans un tunnel : placez-les (étape « Chariots dans les tunnels »), puis faites-les sortir avant le cartonnage.";
        }
        if ($active->count() > 0) {
            return "{$active->count()} chariot(s) encore dans le tunnel : faites-les sortir du tunnel avant le cartonnage.";
        }

        return null;
    }

    public function isReadyForPackaging(): bool
    {
        return $this->packagingBlockReason() === null;
    }
}
