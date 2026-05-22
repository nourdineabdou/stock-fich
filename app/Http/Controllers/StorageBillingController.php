<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\PricingRule;
use App\Services\StorageBillingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StorageBillingController extends Controller
{
    public function __construct(private readonly StorageBillingService $billingService)
    {
    }

    public function index(Request $request)
    {
        // Dropdown list: all active clients
        $allClients = Client::where('active', true)->orderBy('name')->get(['id', 'name']);

        // ── Client detail mode ──────────────────────────────────────────
        $selectedClient  = null;
        $clientRule      = null;
        $clientInvoices  = collect();
        $cumulativeUnpaid = 0.0;
        $totalPaid       = 0.0;
        $canGenerate     = false;

        if ($request->client_id) {
            $selectedClient = Client::with([
                'pricingRules' => fn ($q) => $q->where('service_type', 'storage')->where('active', true)->latest('id'),
            ])->find($request->client_id);

            if ($selectedClient) {
                $clientRule     = $selectedClient->pricingRules->first();
                $clientInvoices = Invoice::where('client_id', $selectedClient->id)
                    ->where('invoice_type', 'storage')
                    ->with('items')
                    ->orderByDesc('invoice_date')
                    ->get();

                $cumulativeUnpaid = (float) $clientInvoices->whereIn('status', ['draft', 'partial'])->sum('total');
                $totalPaid        = (float) $clientInvoices->where('status', 'paid')->sum('total');
                $canGenerate      = $clientRule && $this->isCurrentPeriodUnbilled($clientRule, $clientInvoices->first());
            }
        }

        // ── Overview mode (no client selected) ─────────────────────────
        $overview = collect();
        if (! $selectedClient) {
            $clientsWithStorage = Client::where('active', true)
                ->where(function ($q) {
                    $q->whereHas('pricingRules', fn ($r) => $r->where('service_type', 'storage'))
                      ->orWhereHas('invoices', fn ($i) => $i->where('invoice_type', 'storage'));
                })
                ->with(['pricingRules' => fn ($q) => $q->where('service_type', 'storage')->where('active', true)->latest('id')])
                ->orderBy('name')
                ->get();

            $overview = $clientsWithStorage->map(function ($client) {
                $rule        = $client->pricingRules->first();
                $invoices    = Invoice::where('client_id', $client->id)
                    ->where('invoice_type', 'storage')
                    ->orderByDesc('invoice_date')
                    ->get();
                $unpaid      = $invoices->whereIn('status', ['draft', 'partial']);
                $lastInvoice = $invoices->first();
                $isOverdue   = $this->isCurrentPeriodUnbilled($rule, $lastInvoice);

                return [
                    'client'       => $client,
                    'rule'         => $rule,
                    'total_unpaid' => (float) $unpaid->sum('total'),
                    'nb_unpaid'    => $unpaid->count(),
                    'last_invoice' => $lastInvoice,
                    'is_overdue'   => $isOverdue,
                    'period_label' => $rule ? $this->currentPeriodLabel($rule) : null,
                ];
            })->sortByDesc('total_unpaid')->values();
        }

        $totals = [
            'total_unpaid' => (float) Invoice::where('invoice_type', 'storage')->whereIn('status', ['draft', 'partial'])->sum('total'),
            'total_paid'   => (float) Invoice::where('invoice_type', 'storage')->where('status', 'paid')->sum('total'),
            'nb_overdue'   => $overview->filter(fn ($r) => $r['is_overdue'])->count(),
            'nb_clients'   => $overview->count(),
        ];

        return view('storage_billing.index', compact(
            'allClients', 'overview', 'totals',
            'selectedClient', 'clientRule', 'clientInvoices',
            'cumulativeUnpaid', 'totalPaid', 'canGenerate'
        ));
    }

    public function generate(Request $request, Client $client)
    {
        $rule = PricingRule::where('client_id', $client->id)
            ->where('service_type', 'storage')
            ->where('active', true)
            ->first();

        if (! $rule) {
            return back()->withErrors(['rule' => 'Aucune règle de facturation congélation pour ce client.']);
        }

        [$start, $end] = $this->getCurrentPeriodDates($rule);

        $invoice = $this->billingService->generateForPeriod(
            $client,
            $start,
            $end,
            $rule->charge_unit ?? 'kg',
            (float) $rule->price,
            (float) CompanySetting::get('tax_rate', 20),
            (int) ($rule->due_days ?? 7),
            (string) ($rule->billing_frequency ?? 'week')
        );

        if (! $invoice) {
            return redirect()->route('storage-billing.index', ['client_id' => $client->id])
                ->with('warning', 'Aucun stock facturable pour cette période, ou facture déjà générée.');
        }

        return redirect()->route('storage-billing.index', ['client_id' => $client->id])
            ->with('success', 'Facture congélation générée : ' . $invoice->invoice_number);
    }

    public function pay(Invoice $invoice)
    {
        abort_if($invoice->invoice_type !== 'storage', 403);
        abort_if($invoice->status === 'paid', 422, 'Facture déjà payée.');

        $invoice->update(['status' => 'paid']);

        return redirect()->route('storage-billing.index', ['client_id' => $invoice->client_id])
            ->with('success', 'Paiement enregistré — ' . $invoice->invoice_number)
            ->with('pdf_url', route('invoices.pdf', $invoice));
    }

    // ──────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────

    private function isCurrentPeriodUnbilled(?PricingRule $rule, ?Invoice $lastInvoice): bool
    {
        if (! $rule || ! $rule->auto_generate) {
            return false;
        }

        if (! $lastInvoice) {
            return true;
        }

        $lastDate  = Carbon::parse($lastInvoice->invoice_date);
        $frequency = $rule->billing_frequency ?? 'week';
        $now       = Carbon::now();

        return match ($frequency) {
            'day'   => $lastDate->diffInDays($now) >= 1,
            'week'  => $lastDate->diffInDays($now) >= 7,
            'month' => $lastDate->diffInMonths($now) >= 1,
            default => false,
        };
    }

    private function getCurrentPeriodDates(PricingRule $rule): array
    {
        $now       = Carbon::now();
        $frequency = $rule->billing_frequency ?? 'week';

        return match ($frequency) {
            'day'   => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            default => [$now->copy()->startOfWeek(Carbon::MONDAY), $now->copy()->endOfWeek(Carbon::SUNDAY)],
        };
    }

    private function currentPeriodLabel(PricingRule $rule): string
    {
        [$start, $end] = $this->getCurrentPeriodDates($rule);

        return match ($rule->billing_frequency ?? 'week') {
            'day'   => 'Aujourd\'hui ' . $start->format('d/m/Y'),
            'month' => 'Mois de ' . $start->format('m/Y'),
            default => 'Sem. ' . $start->format('d/m') . ' – ' . $end->format('d/m/Y'),
        };
    }
}
