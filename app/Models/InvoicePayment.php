<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoicePayment extends Model
{
    public const METHODS = [
        'cash' => 'Espèces',
        'transfer' => 'Virement',
        'cheque' => 'Chèque',
        'mobile' => 'Paiement mobile',
    ];

    protected $fillable = ['invoice_id', 'client_id', 'receipt_number', 'amount', 'paid_at', 'method', 'reference', 'notes', 'received_by'];
    protected $casts = ['amount' => 'decimal:2', 'paid_at' => 'datetime'];

    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function receiver() { return $this->belongsTo(User::class, 'received_by'); }

    public function getMethodLabelAttribute(): string
    {
        return self::METHODS[$this->method] ?? $this->method;
    }
}
