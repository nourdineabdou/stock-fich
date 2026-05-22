<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Client;
use App\Models\ProcessingOrder;
use App\Models\PricingRule;
use App\Models\CompanySetting;
use App\Services\StorageBillingService;
use App\Http\Requests\InvoiceRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class InvoiceController extends Controller
{
    public function __construct(private readonly StorageBillingService $storageBillingService)
    {
    }

    public function index(Request $request)
    {
        $invoices = Invoice::with(['client', 'processingOrder'])
            ->where('invoice_type', 'processing')
            ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date_from, fn($q) => $q->whereDate('invoice_date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('invoice_date', '<=', $request->date_to))
            ->when($request->search, function ($q) use ($request) {
                $search = trim((string) $request->search);

                $q->where(function ($sub) use ($search) {
                    $sub->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('processingOrder', fn ($po) => $po->where('reference', 'like', "%{$search}%"));
                });
            })
            ->latest('invoice_date')
            ->paginate(10)
            ->withQueryString();

        $clients = Client::where('active', true)->orderBy('name')->get();
        $base = Invoice::where('invoice_type', 'processing');
        $totals  = [
            'unpaid'     => (clone $base)->whereIn('status', ['draft', 'partial'])->sum('total'),
            'paid'       => (clone $base)->where('status', 'paid')->sum('total'),
            'this_month' => (clone $base)->whereMonth('invoice_date', now()->month)->sum('total'),
        ];
        return view('invoices.index', compact('invoices', 'clients', 'totals'));
    }

    public function create()
    {
        $selectedClientId = request('client_id');
        $selectedProcessingOrderId = request('processing_order_id');

        $clients = Client::where('active', true)->orderBy('name')->get();
        $processingOrders = ProcessingOrder::with(['client', 'items.fishType'])
            ->when($selectedClientId, fn ($q) => $q->where('client_id', $selectedClientId))
            ->latest('start_date')
            ->get();

        if ($selectedProcessingOrderId && !$processingOrders->contains('id', (int) $selectedProcessingOrderId)) {
            $forcedOrder = ProcessingOrder::with(['client', 'items.fishType'])->find((int) $selectedProcessingOrderId);
            if ($forcedOrder) {
                $processingOrders->prepend($forcedOrder);
                if (!$selectedClientId) {
                    $selectedClientId = $forcedOrder->client_id;
                }
            }
        }

        $selectedProcessingOrder = $selectedProcessingOrderId
            ? $processingOrders->firstWhere('id', (int) $selectedProcessingOrderId)
            : $processingOrders->first();

        $tax = CompanySetting::get('tax_rate', 20);
        $invoiceItems = $this->buildInvoiceItemsFromProcessing($selectedProcessingOrder);
        $subtotal = collect($invoiceItems)->sum(fn ($item) => $item['total']);
        $taxAmount = $subtotal * ($tax / 100);
        $total = $subtotal + $taxAmount;

        return view('invoices.create', compact('clients', 'tax', 'processingOrders', 'selectedProcessingOrder', 'invoiceItems', 'subtotal', 'taxAmount', 'total'));
    }

    public function store(InvoiceRequest $request)
    {
        $invoiceData = $this->buildInvoiceDataFromRequest($request);

        if (!empty($invoiceData['invoice']['processing_order_id'])) {
            $existing = Invoice::where('processing_order_id', $invoiceData['invoice']['processing_order_id'])->first();
            if ($existing) {
                return redirect()->route('invoices.edit', $existing)->with('info', 'Une facture existe deja pour ce traitement. Modifiez-la au lieu d en creer une autre.');
            }
        }

        $invoice = DB::transaction(function () use ($invoiceData) {
            $invoiceNumber = $this->nextInvoiceNumber();

            $invoice = Invoice::create(array_merge($invoiceData['invoice'], ['invoice_number' => $invoiceNumber]));

            foreach ($invoiceData['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id'     => $invoice->id,
                    'service_type'   => $item['service_type'],
                    'reference_type' => $item['reference_type'] ?? null,
                    'reference_id'   => $item['reference_id'] ?? null,
                    'description'    => $item['description'],
                    'quantity'       => $item['quantity'],
                    'unit_price'     => $item['unit_price'],
                    'total'          => $item['total'],
                ]);
            }

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Facture créée: ' . $invoice->invoice_number);
    }

    public function generateStorageInvoice(Request $request, Client $client)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'billing_method' => 'nullable|in:kg,carton,day,week,month',
            'charge_unit' => 'nullable|in:kg,carton',
            'billing_frequency' => 'nullable|in:day,week,month',
            'price' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'])->endOfDay();

        $chargeUnit = $data['charge_unit']
            ?? (in_array(($data['billing_method'] ?? ''), ['kg', 'carton'], true) ? $data['billing_method'] : null)
            ?? 'kg';

        $rule = PricingRule::query()
            ->where('active', true)
            ->where('service_type', 'storage')
            ->where('charge_unit', $chargeUnit)
            ->where(function ($q) use ($client) {
                $q->whereNull('client_id')->orWhere('client_id', $client->id);
            })
            ->orderByRaw('CASE WHEN client_id = ? THEN 0 ELSE 1 END', [$client->id])
            ->first();

        $unitPrice = (float) ($data['price'] ?? ($rule?->price ?? 0));
        if ($unitPrice <= 0) {
            return back()->withErrors([
                'price' => 'Definissez un tarif chambre froide (regle de prix active ou prix manuel).',
            ]);
        }
        $taxRate = (float) ($data['tax_rate'] ?? CompanySetting::get('tax_rate', 20));
        $invoice = $this->storageBillingService->generateForPeriod(
            $client,
            $start,
            $end,
            $chargeUnit,
            $unitPrice,
            $taxRate,
            7,
            (string) ($data['billing_frequency'] ?? 'manual')
        );

        if (! $invoice) {
            return back()->withErrors([
                'start_date' => 'Aucun stock facturable pour cette periode ou facture deja generee.',
            ]);
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Facture chambre froide generee: ' . $invoice->invoice_number);
    }

    public function saveStorageBillingConfig(Request $request, Client $client)
    {
        $data = $request->validate([
            'price' => 'required|numeric|min:0.01',
            'charge_unit' => 'required|in:kg,carton',
            'billing_frequency' => 'required|in:day,week,month',
            'auto_generate' => 'nullable|boolean',
            'week_start_day' => 'nullable|integer|min:0|max:6',
            'month_day' => 'nullable|integer|min:1|max:28',
            'due_days' => 'nullable|integer|min:0|max:90',
            'active' => 'nullable|boolean',
        ]);

        PricingRule::updateOrCreate(
            [
                'client_id' => $client->id,
                'service_type' => 'storage',
            ],
            [
                'billing_method' => $data['charge_unit'],
                'charge_unit' => $data['charge_unit'],
                'billing_frequency' => $data['billing_frequency'],
                'auto_generate' => (bool) ($data['auto_generate'] ?? false),
                'week_start_day' => (int) ($data['week_start_day'] ?? 1),
                'month_day' => (int) ($data['month_day'] ?? 1),
                'due_days' => (int) ($data['due_days'] ?? 7),
                'price' => (float) $data['price'],
                'active' => (bool) ($data['active'] ?? true),
            ]
        );

        return back()->with('success', 'Parametrage de facturation chambre froide mis a jour.');
    }

    public function preview(Request $request)
    {
        $data = $request->validate([
            'processing_order_id' => 'required|exists:processing_orders,id',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $processing = ProcessingOrder::with(['client', 'items.fishType'])->findOrFail($data['processing_order_id']);
        $taxRate = (float) ($data['tax_rate'] ?? CompanySetting::get('tax_rate', 20));
        $invoiceItems = $this->buildInvoiceItemsFromProcessing($processing);
        $subtotal = collect($invoiceItems)->sum('total');
        $taxAmount = $subtotal * ($taxRate / 100);
        $total = $subtotal + $taxAmount;

        $invoice = (object) [
            'invoice_number' => 'PROFORMA-' . $processing->reference,
            'invoice_date' => now(),
            'due_date' => null,
            'tax_rate' => $taxRate,
            'status' => 'draft',
            'notes' => 'Aperçu non enregistré basé sur ' . $processing->reference,
            'client' => $processing->client,
            'items' => collect($invoiceItems)->map(fn ($item) => (object) $item),
            'subtotal' => $subtotal,
            'tax' => $taxAmount,
            'total' => $total,
            'total_amount' => $total,
        ];

        $settings = [
            'company_name' => CompanySetting::get('company_name', 'FreezePro'),
            'company_address' => CompanySetting::get('company_address', ''),
            'company_phone' => CompanySetting::get('company_phone', ''),
            'company_ice' => CompanySetting::get('company_ice', ''),
            'company_nif' => CompanySetting::get('company_nif', ''),
            'currency' => CompanySetting::get('currency', 'MRU'),
        ];

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice', 'settings'))->setPaper('a4');
        return $pdf->stream('Apercu-Facture-' . $processing->reference . '.pdf');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client', 'items']);
        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        $invoice->load('items');
        $clients = Client::where('active', true)->orderBy('name')->get();
        $tax     = CompanySetting::get('tax_rate', 20);
        $processingOrders = ProcessingOrder::with(['client', 'items.fishType'])->where('client_id', $invoice->client_id)->latest('start_date')->get();
        $selectedProcessingOrder = $invoice->processingOrder()->with(['client', 'items.fishType'])->first();
        $invoiceItems = $invoice->items->map(function ($item) {
            return [
                'description' => $item->description,
                'service_type' => $item->service_type,
                'reference_type' => $item->reference_type,
                'reference_id' => $item->reference_id,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total' => (float) $item->total,
            ];
        })->all();
        return view('invoices.edit', compact('invoice', 'clients', 'tax', 'processingOrders', 'selectedProcessingOrder', 'invoiceItems'));
    }

    public function update(InvoiceRequest $request, Invoice $invoice)
    {
        $invoiceData = $this->buildInvoiceDataFromRequest($request);

        DB::transaction(function () use ($invoice, $invoiceData) {
            $invoice->update($invoiceData['invoice']);
            $invoice->items()->delete();

            foreach ($invoiceData['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id'     => $invoice->id,
                    'service_type'   => $item['service_type'],
                    'reference_type' => $item['reference_type'] ?? null,
                    'reference_id'   => $item['reference_id'] ?? null,
                    'description'    => $item['description'],
                    'quantity'       => $item['quantity'],
                    'unit_price'     => $item['unit_price'],
                    'total'          => $item['total'],
                ]);
            }
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Facture mise à jour.');
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return redirect()->route('invoices.index')->with('success', 'Facture supprimée.');
    }

    public function pdf(Invoice $invoice)
    {
        $invoice->load(['client', 'items']);
        $settings = [
            'company_name'    => CompanySetting::get('company_name', 'FreezePro'),
            'company_address' => CompanySetting::get('company_address', ''),
            'company_phone'   => CompanySetting::get('company_phone', ''),
            'company_ice'     => CompanySetting::get('company_ice', ''),
            'company_nif'     => CompanySetting::get('company_nif', ''),
            'currency'        => CompanySetting::get('currency', 'MRU'),
        ];
        $pdf = Pdf::loadView('invoices.pdf', compact('invoice', 'settings'))->setPaper('a4');
        return $pdf->stream('Facture-' . $invoice->invoice_number . '.pdf');
    }

    private function buildInvoiceItemsFromProcessing(?ProcessingOrder $processing): array
    {
        if (! $processing) {
            return [];
        }

        $defaultPrice = (float) ($processing->price ?: 0);

        return $processing->items
            ->map(function ($item) use ($processing, $defaultPrice) {
                $quantity = (float) $item->output_weight;
                $unitPrice = (float) ($item->price_per_kg ?: $defaultPrice);

                return [
                    'service_type' => 'processing',
                    'reference_type' => 'processing_item',
                    'reference_id' => $item->id,
                    'description' => trim(($item->fishType->name ?? 'Poisson') . ' - ' . $processing->reference),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $quantity * $unitPrice,
                ];
            })
            ->filter(fn ($item) => $item['quantity'] > 0)
            ->values()
            ->all();
    }

    private function buildInvoiceDataFromRequest(InvoiceRequest $request): array
    {
        $processing = null;
        if ($request->filled('processing_order_id')) {
            $processing = ProcessingOrder::with(['client', 'items.fishType'])->find($request->processing_order_id);
        }

        $items = collect($request->input('items', []));
        if ($items->isEmpty() && $processing) {
            $items = collect($this->buildInvoiceItemsFromProcessing($processing));
        } else {
            $items = $items->map(function ($item) {
                $quantity = (float) ($item['quantity'] ?? 0);
                $unitPrice = (float) ($item['unit_price'] ?? 0);

                return [
                    'service_type' => $item['service_type'] ?? 'other',
                    'reference_type' => $item['reference_type'] ?? null,
                    'reference_id' => $item['reference_id'] ?? null,
                    'description' => $item['description'] ?? '',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $quantity * $unitPrice,
                ];
            })->filter(fn ($item) => $item['description'] !== '' && $item['quantity'] > 0)->values();
        }

        if ($items->isEmpty()) {
            abort(422, 'Aucune ligne de facture valide n a ete trouvee.');
        }

        $subtotal = $items->sum('total');
        $taxRate = (float) $request->input('tax_rate', CompanySetting::get('tax_rate', 20));
        $tax = $subtotal * ($taxRate / 100);
        $total = $subtotal + $tax;

        return [
            'invoice' => [
                'client_id' => $request->client_id,
                'processing_order_id' => $request->input('processing_order_id'),
                'invoice_type' => $request->input('processing_order_id') ? 'processing' : ($request->input('invoice_type', 'processing')),
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->input('due_date'),
                'tax_rate' => $taxRate,
                'notes' => $request->input('notes'),
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'status' => $request->status ?? 'draft',
            ],
            'items' => $items->all(),
        ];
    }

    private function nextInvoiceNumber(): string
    {
        $prefix = CompanySetting::get('invoice_prefix', 'FAC');
        $num = str_pad(Invoice::count() + 1, 5, '0', STR_PAD_LEFT);

        return $prefix . '-' . now()->format('Y') . '-' . $num;
    }

    public function markPaid(Invoice $invoice)
    {
        $invoice->update(['status' => 'paid']);
        return back()->with('success', 'Facture marquée comme payée.');
    }
}

