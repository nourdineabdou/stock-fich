<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Services\PaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments)
    {
    }

    /** Encaissement sur une facture (partiel ou total). */
    public function store(Request $request, Invoice $invoice)
    {
        $data = $this->validated($request);
        $receipt = $this->payments->payInvoice($invoice, (float) $data['amount'], $data, $request->user()?->id);

        return back()
            ->with('success', 'Encaissement de ' . number_format($data['amount'], 2, ',', ' ') . ' MRU enregistré (' . $receipt . ').')
            ->with('pdf_url', route('payments.receipt', $receipt))
            ->with('pdf_label', 'Imprimer le reçu');
    }

    /** Encaissement global sur le compte froid d'un client, reparti sur les factures les plus anciennes. */
    public function storeForClient(Request $request, Client $client)
    {
        $data = $this->validated($request);
        $receipt = $this->payments->payClientAccount($client, 'storage', (float) $data['amount'], $data, $request->user()?->id);

        return back()
            ->with('success', 'Encaissement froid de ' . number_format($data['amount'], 2, ',', ' ') . ' MRU enregistré (' . $receipt . ').')
            ->with('pdf_url', route('payments.receipt', $receipt))
            ->with('pdf_label', 'Imprimer le reçu');
    }

    public function destroy(InvoicePayment $payment)
    {
        $this->payments->cancel($payment);

        return back()->with('success', 'Encaissement ' . $payment->receipt_number . ' annulé.');
    }

    public function receipt(string $receipt)
    {
        $payments = InvoicePayment::with(['invoice', 'client', 'receiver'])
            ->where('receipt_number', $receipt)
            ->orderBy('id')
            ->get();
        abort_if($payments->isEmpty(), 404);

        $client = $payments->first()->client;
        $invoiceType = $payments->first()->invoice->invoice_type ?? 'processing';
        $remaining = (float) Invoice::where('client_id', $client->id)
            ->where('invoice_type', $invoiceType)
            ->whereIn('status', ['draft', 'partial'])
            ->get()
            ->sum(fn (Invoice $i) => $i->balance);

        $settings = [
            'company_name' => CompanySetting::get('company_name', 'FreezePro'),
            'company_address' => CompanySetting::get('company_address', ''),
            'company_phone' => CompanySetting::get('company_phone', ''),
            'currency' => CompanySetting::get('currency', 'MRU'),
        ];

        return Pdf::loadView('payments.receipt_pdf', compact('payments', 'client', 'receipt', 'remaining', 'invoiceType', 'settings'))
            ->setPaper('a5', 'landscape')
            ->stream('Recu-' . $receipt . '.pdf');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'paid_at' => 'nullable|date',
            'method' => ['required', Rule::in(array_keys(InvoicePayment::METHODS))],
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);
    }
}
