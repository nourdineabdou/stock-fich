<?php

namespace App\Http\Controllers;

use App\Models\CartonType;
use App\Models\Client;
use App\Models\ClientStockBatch;
use App\Models\FishType;
use App\Models\StorageEntry;
use App\Http\Requests\ClientRequest;
use App\Services\StorageBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::withCount(['arrivals', 'invoices', 'stockBatches', 'deliveries'])
            ->withSum('invoices as total_invoiced', 'total')
            ->withSum('stockBatches as stock_cartons', 'available_cartons')
            ->withSum('stockBatches as stock_weight', 'available_weight');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('ice_number', 'like', "%{$request->search}%")
                  ->orWhere('address', 'like', "%{$request->search}%");
            });
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('active', $request->status);
        }

        $clients = $query->orderBy('name')->paginate(10)->withQueryString();
        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        return view('clients.create');
    }

    public function store(ClientRequest $request)
    {
        $client = Client::create($request->validated());
        return redirect()->route('clients.show', $client)->with('success', 'Client créé avec succès.');
    }

    public function show(Request $request, Client $client, StorageBillingService $billingService)
    {
        $filters = $request->validate([
            'fish_type_id' => 'nullable|integer|exists:fish_types,id',
            'carton_type_id' => 'nullable|integer|exists:carton_types,id',
            'date_mode' => 'nullable|in:range,exact',
            'date' => 'nullable|date',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'movement' => 'nullable|in:in,out,transfer',
        ]);
        $exactDate = ($filters['date_mode'] ?? 'range') === 'exact' ? ($filters['date'] ?? null) : null;
        $filterBatch = function ($q) use ($filters) {
            $q->when($filters['fish_type_id'] ?? null, fn ($sq, $id) => $sq->where('fish_type_id', $id))
              ->when($filters['carton_type_id'] ?? null, fn ($sq, $id) => $sq->where('carton_type_id', $id));
        };

        $stockSummary = ClientStockBatch::with(['fishType', 'cartonType'])
            ->where('client_id', $client->id)
            ->where('available_cartons', '>', 0)
            ->tap($filterBatch)
            ->get()
            ->groupBy(fn ($b) => $b->fish_type_id . '|' . $b->carton_type_id)
            ->map(function ($rows) {
                $first = $rows->first();
                return [
                    'fish' => $first->fishType->name ?? '-',
                    'carton' => $first->cartonType->name ?? '-',
                    'carton_weight' => (float) $first->weight_per_carton,
                    'lots' => $rows->count(),
                    'cartons' => (int) $rows->sum('available_cartons'),
                    'weight' => (float) $rows->sum('available_weight'),
                ];
            })
            ->sortBy([['fish', 'asc'], ['carton_weight', 'desc']])
            ->values();

        $historyQuery = StorageEntry::query()
            ->whereHas('stockBatch', fn ($q) => $q->where('client_id', $client->id)->tap($filterBatch))
            ->when($filters['movement'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($exactDate, fn ($q, $d) => $q->whereDate('entry_date', $d))
            ->when(! $exactDate && ($filters['date_from'] ?? null), fn ($q) => $q->whereDate('entry_date', '>=', $filters['date_from']))
            ->when(! $exactDate && ($filters['date_to'] ?? null), fn ($q) => $q->whereDate('entry_date', '<=', $filters['date_to']));

        $historyTotals = (clone $historyQuery)
            ->selectRaw("type, SUM(cartons_count) as cartons, SUM(weight_kg) as weight")
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $history = $historyQuery
            ->with(['stockBatch.fishType', 'stockBatch.cartonType', 'coldRoom'])
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'hist_page')
            ->withQueryString()
            ->fragment('historique');

        $fishTypes = FishType::orderBy('name')->get(['id', 'name']);
        $cartonTypes = CartonType::orderBy('name')->get(['id', 'name', 'weight_kg']);

        $client->loadCount(['arrivals', 'invoices', 'deliveries', 'stockBatches']);
        $client->load([
            'arrivals' => fn($q) => $q->latest()->limit(5),
            'invoices' => fn($q) => $q->latest()->limit(5),
            'deliveries' => fn($q) => $q->latest()->limit(5),
            'stockBatches' => fn($q) => $q->where('status', '!=', 'empty')->with(['fishType', 'cartonType', 'coldRoom']),
        ]);

        $storageRule = $client->pricingRules()
            ->where('service_type', 'storage')
            ->latest('id')
            ->first();

        $paymentSummary = [
            'paid' => (float) $client->invoices()->where('status', '!=', 'cancelled')->sum('paid_amount'),
            'unpaid' => (float) $client->invoices()->whereIn('status', ['draft', 'partial'])->sum(DB::raw('total - paid_amount')),
            'storage_unpaid' => (float) $client->invoices()->where('invoice_type', 'storage')->whereIn('status', ['draft', 'partial'])->sum(DB::raw('total - paid_amount')),
            'processing_unpaid' => (float) $client->invoices()->where('invoice_type', 'processing')->whereIn('status', ['draft', 'partial'])->sum(DB::raw('total - paid_amount')),
        ];

        $coldFees = $billingService->weeklyBreakdown($client, $storageRule?->active ? $storageRule : null);

        return view('clients.show', compact(
            'client', 'storageRule', 'paymentSummary',
            'stockSummary', 'history', 'historyTotals', 'fishTypes', 'cartonTypes', 'filters', 'coldFees'
        ));
    }

    public function edit(Client $client)
    {
        return view('clients.edit', compact('client'));
    }

    public function update(ClientRequest $request, Client $client)
    {
        $client->update($request->validated());
        return redirect()->route('clients.show', $client)->with('success', 'Client mis à jour.');
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return redirect()->route('clients.index')->with('success', 'Client supprimé.');
    }
}
