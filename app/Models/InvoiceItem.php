<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = ['invoice_id', 'service_type', 'reference_type', 'reference_id', 'description', 'quantity', 'unit_price', 'total'];
    protected $casts = ['quantity' => 'decimal:2', 'unit_price' => 'decimal:2', 'total' => 'decimal:2'];

    public function invoice() { return $this->belongsTo(Invoice::class); }
}
