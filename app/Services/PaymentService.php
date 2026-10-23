<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    /**
     * Encaisse un montant sur une facture (paiement partiel ou total).
     * Retourne le numero de recu.
     */
    public function payInvoice(Invoice $invoice, float $amount, array $details, ?int $userId): string
    {
        return DB::transaction(function () use ($invoice, $amount, $details, $userId) {
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $this->guard($invoice, $amount);

            $receipt = $this->nextReceiptNumber();
            $this->record($invoice, $amount, $receipt, $details, $userId);

            return $receipt;
        });
    }

    /**
     * Encaisse un montant global pour un client : il est reparti sur ses factures impayees
     * de ce type, de la plus ancienne a la plus recente. Un seul recu pour l'ensemble.
     */
    public function payClientAccount(Client $client, string $invoiceType, float $amount, array $details, ?int $userId): string
    {
        return DB::transaction(function () use ($client, $invoiceType, $amount, $details, $userId) {
            $invoices = Invoice::where('client_id', $client->id)
                ->where('invoice_type', $invoiceType)
                ->whereIn('status', ['draft', 'partial'])
                ->orderBy('invoice_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->filter(fn (Invoice $i) => $i->balance > 0);

            $due = round($invoices->sum(fn (Invoice $i) => $i->balance), 2);
            if ($amount <= 0 || $amount > $due + 0.009) {
                throw ValidationException::withMessages([
                    'amount' => 'Montant invalide : le reste à payer du client est de ' . number_format($due, 2, ',', ' ') . ' MRU.',
                ]);
            }

            $receipt = $this->nextReceiptNumber();
            $left = $amount;
            foreach ($invoices as $invoice) {
                if ($left <= 0.009) {
                    break;
                }
                $part = round(min($left, $invoice->balance), 2);
                $this->record($invoice, $part, $receipt, $details, $userId);
                $left = round($left - $part, 2);
            }

            return $receipt;
        });
    }

    public function cancel(InvoicePayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $invoice = $payment->invoice;
            $payment->delete();
            $invoice->refreshPaymentStatus();
        });
    }

    private function guard(Invoice $invoice, float $amount): void
    {
        if ($invoice->status === 'cancelled') {
            throw ValidationException::withMessages(['amount' => 'Cette facture est annulée.']);
        }

        if ($amount <= 0 || $amount > $invoice->balance + 0.009) {
            throw ValidationException::withMessages([
                'amount' => 'Le montant doit être entre 0,01 et le reste à payer (' . number_format($invoice->balance, 2, ',', ' ') . ' MRU).',
            ]);
        }
    }

    private function record(Invoice $invoice, float $amount, string $receipt, array $details, ?int $userId): void
    {
        InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'receipt_number' => $receipt,
            'amount' => $amount,
            'paid_at' => $details['paid_at'] ?? now(),
            'method' => $details['method'] ?? 'cash',
            'reference' => $details['reference'] ?? null,
            'notes' => $details['notes'] ?? null,
            'received_by' => $userId,
        ]);

        $invoice->refreshPaymentStatus();
    }

    private function nextReceiptNumber(): string
    {
        $year = now()->format('Y');
        $count = InvoicePayment::where('receipt_number', 'like', "REC-{$year}-%")->distinct()->count('receipt_number');

        return sprintf('REC-%s-%05d', $year, $count + 1);
    }
}
