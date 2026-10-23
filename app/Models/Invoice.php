<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = ['client_id', 'processing_order_id', 'invoice_type', 'invoice_number', 'invoice_date', 'due_date', 'tax_rate', 'notes', 'subtotal', 'tax', 'total', 'paid_amount', 'status'];
    protected $casts = ['invoice_date' => 'date', 'due_date' => 'date', 'tax_rate' => 'decimal:2', 'subtotal' => 'decimal:2', 'tax' => 'decimal:2', 'total' => 'decimal:2', 'paid_amount' => 'decimal:2'];

    public function processingOrder() { return $this->belongsTo(ProcessingOrder::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function items() { return $this->hasMany(InvoiceItem::class); }
    public function payments() { return $this->hasMany(InvoicePayment::class)->orderBy('paid_at'); }

    public function getBalanceAttribute(): float {
        return max(0, round((float) $this->total - (float) $this->paid_amount, 2));
    }

    /** Recalcule le montant encaisse et le statut (brouillon / partielle / payee) a partir des encaissements. */
    public function refreshPaymentStatus(): void {
        if ($this->status === 'cancelled') {
            return;
        }

        $paid = round((float) $this->payments()->sum('amount'), 2);
        $status = match (true) {
            $paid <= 0 => 'draft',
            $paid + 0.009 >= (float) $this->total => 'paid',
            default => 'partial',
        };

        $this->update(['paid_amount' => $paid, 'status' => $status]);
    }

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
