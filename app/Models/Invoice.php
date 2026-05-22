<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = ['client_id', 'processing_order_id', 'invoice_type', 'invoice_number', 'invoice_date', 'due_date', 'tax_rate', 'notes', 'subtotal', 'tax', 'total', 'status'];
    protected $casts = ['invoice_date' => 'date', 'due_date' => 'date', 'tax_rate' => 'decimal:2', 'subtotal' => 'decimal:2', 'tax' => 'decimal:2', 'total' => 'decimal:2'];

    public function processingOrder() { return $this->belongsTo(ProcessingOrder::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function items() { return $this->hasMany(InvoiceItem::class); }

    public function getTotalAmountAttribute(): float {
        return (float) $this->total;
    }

    public function getStatusBadgeAttribute(): string {
        return match($this->status) {
            'paid'      => '<span class="badge-green">Payée</span>',
            'partial'   => '<span class="badge-yellow">Partielle</span>',
            'cancelled' => '<span class="badge-red">Annulée</span>',
            default     => '<span class="badge-gray">Brouillon</span>',
        };
    }
}
