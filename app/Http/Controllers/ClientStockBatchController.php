<?php

namespace App\Http\Controllers;

use App\Models\ClientStockBatch;
use App\Models\Client;
use App\Models\FishType;
use App\Models\CartonType;
use App\Models\ColdRoom;
use App\Models\StorageEntry;
use App\Models\StockMovement;
use App\Models\CompanySetting;
use App\Exports\ClientStatementExport;
use App\Exports\StockListExport;
use App\Exports\TransactionReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ClientStockBatchController extends Controller
{
    public function index(Request $request)
    {
        $allBatches = ClientStockBatch::with(['client', 'fishType', 'cartonType', 'coldRoom', 'packagingOrder'])
            ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
            ->when($request->fish_type_id, fn($q) => $q->where('fish_type_id', $request->fish_type_id))
            ->when($request->cold_room_id, fn($q) => $q->where('cold_room_id', $request->cold_room_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where(function ($sq) use ($request) {
                $sq->where('lot_number', 'like', "%{$request->search}%")
                   ->orWhere('entry_reference', 'like', "%{$request->search}%");
            }))
            ->when($request->origin, function ($q) use ($request) {
                if ($request->origin === 'direct') {
                    $q->whereNull('packaging_order_id');
                } elseif ($request->origin === 'processing') {
                    $q->whereNotNull('packaging_order_id');
                } elseif ($request->origin === 'empty') {
                    $q->where('available_cartons', 0)->where('cartons_count', '>', 0);
                }
            })
            ->latest('entry_date')
            ->get();

        // Group by entry_reference; batches without one are their own group
        $grouped = $allBatches
            ->groupBy(fn($b) => $b->entry_reference ?? ('b_' . $b->id))
            ->sortByDesc(fn($group) => $group->max('entry_date'));

        $page    = max(1, (int) $request->get('page', 1));
        $perPage = 10;
        $total   = $grouped->count();
        $items   = $grouped->forPage($page, $perPage);

        $entries = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $clients   = Client::where('active', true)->orderBy('name')->get();
        $fishTypes = FishType::where('active', true)->orderBy('name')->get();
        $coldRooms = ColdRoom::where('active', true)->orderBy('name')->get();

        $totals = ClientStockBatch::where('status', 'available')
            ->selectRaw('SUM(available_cartons) as cartons, SUM(available_weight) as weight')
            ->first();

        return view('stock.index', compact('entries', 'clients', 'fishTypes', 'coldRooms', 'totals'));
    }

    public function entryShow(string $reference)
    {
        $batches = ClientStockBatch::with(['client', 'fishType', 'cartonType', 'coldRoom', 'storageEntries', 'deliveryItems.delivery'])
            ->where('entry_reference', $reference)
            ->orderBy('id')
            ->get();

        abort_if($batches->isEmpty(), 404);

        $firstBatch = $batches->first();

        return view('stock.entry-show', compact('batches', 'firstBatch', 'reference'));
    }

    public function create()
    {
        $clients = Client::where('active', true)->orderBy('name')->get();
        $fishTypes = FishType::where('active', true)->orderBy('name')->get();
        $cartonTypes = CartonType::where('active', true)->orderBy('name')->get();
        $coldRooms = ColdRoom::where('active', true)->orderBy('name')->get();

        return view('stock.create', compact('clients', 'fishTypes', 'cartonTypes', 'coldRooms'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'cold_room_id' => 'nullable|exists:cold_rooms,id',
            'entry_date' => 'required|date',
            'frozen_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.fish_type_id' => 'required|exists:fish_types,id',
            'items.*.carton_type_id' => 'required|exists:carton_types,id',
            'items.*.cartons_count' => 'required|integer|min:1',
            'items.*.weight_per_carton' => 'required|numeric|min:0.01',
        ]);

        $createdBatches = [];
        $createdEntries = [];
        $entryDate = $data['entry_date'];
        $frozenAt = $data['frozen_at'] ?? now();

        $entryReference = 'ENT-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));

        DB::transaction(function () use ($data, $entryDate, $frozenAt, $entryReference, &$createdBatches, &$createdEntries) {
            foreach ($data['items'] as $item) {
                $totalWeight = (float) $item['cartons_count'] * (float) $item['weight_per_carton'];
                $lotNumber = 'DIR-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));

                $batch = ClientStockBatch::create([
                    'client_id' => $data['client_id'],
                    'fish_type_id' => $item['fish_type_id'],
                    'carton_type_id' => $item['carton_type_id'],
                    'cold_room_id' => $data['cold_room_id'] ?? null,
                    'lot_number' => $lotNumber,
                    'entry_reference' => $entryReference,
                    'cartons_count' => $item['cartons_count'],
                    'weight_per_carton' => $item['weight_per_carton'],
                    'total_weight' => $totalWeight,
                    'available_cartons' => $item['cartons_count'],
                    'available_weight' => $totalWeight,
                    'reserved_cartons' => 0,
                    'reserved_weight' => 0,
                    'frozen_at' => $frozenAt,
                    'entry_date' => $entryDate,
                    'status' => 'available',
                    'notes' => $data['notes'] ?? 'Entrée directe sans traitement',
                ]);

                $entry = StorageEntry::create([
                    'client_stock_batch_id' => $batch->id,
                    'cold_room_id' => $batch->cold_room_id,
                    'type' => 'in',
                    'cartons_count' => $batch->cartons_count,
                    'weight_kg' => $batch->total_weight,
                    'reference' => $batch->lot_number,
                    'entry_date' => $batch->entry_date ?? now(),
                    'notes' => 'Entrée directe client',
                ]);

                $createdBatches[] = $batch->id;
                $createdEntries[] = $entry->id;
            }
        });

        $firstBatchId = $createdBatches[0] ?? null;
        $message = count($createdBatches) > 1
            ? count($createdBatches) . ' lignes d\'entrée enregistrées.'
            : 'Entree directe enregistree.';

        return $firstBatchId
            ? redirect()->route('stock.show', ['clientStockBatch' => $firstBatchId])
                ->with('success', $message)
                ->with('pdf_url', route('stock.receipt', ['clientStockBatch' => $firstBatchId]))
                ->with('pdf_label', 'Recu entree PDF')
            : redirect()->route('stock.index')->with('success', $message);
    }

    public function show(ClientStockBatch $clientStockBatch)
    {
        $clientStockBatch->load(['client', 'fishType', 'cartonType', 'coldRoom', 'deliveryItems.delivery', 'storageEntries']);
        return view('stock.show', compact('clientStockBatch'));
    }

    public function entryReceiptPdf(ClientStockBatch $clientStockBatch)
    {
        $clientStockBatch->load(['client', 'coldRoom']);

        $entryBatches = ClientStockBatch::query()
            ->with(['fishType', 'cartonType', 'coldRoom'])
            ->where('client_id', $clientStockBatch->client_id)
            ->when($clientStockBatch->entry_reference, fn ($q) => $q->where('entry_reference', $clientStockBatch->entry_reference), fn ($q) => $q->whereKey($clientStockBatch->id))
            ->orderBy('id')
            ->get();

        $settings = [
            'company_name' => CompanySetting::get('company_name', 'FreezePro'),
            'company_address' => CompanySetting::get('company_address', ''),
            'company_phone' => CompanySetting::get('company_phone', ''),
            'company_ice' => CompanySetting::get('company_ice', ''),
        ];

        $pdf = Pdf::loadView('stock.entry_receipt_pdf', compact('clientStockBatch', 'entryBatches', 'settings'))->setPaper('a4');

        return $pdf->stream('Recu-Entree-' . $clientStockBatch->lot_number . '.pdf');
    }

    public function transfer(Request $request, ClientStockBatch $clientStockBatch)
    {
        $validated = $request->validate([
            'cold_room_id'  => 'required|exists:cold_rooms,id',
            'cartons_count' => 'required|integer|min:1|max:' . $clientStockBatch->available_cartons,
        ]);

        $cartons    = (int) $validated['cartons_count'];
        $weight     = $cartons * $clientStockBatch->weight_per_carton;
        $newColdRoom = ColdRoom::findOrFail($validated['cold_room_id']);
        $fromColdRoomName = $clientStockBatch->coldRoom->name ?? 'Non défini';

        if ((int) $clientStockBatch->cold_room_id === (int) $newColdRoom->id) {
            return back()->withErrors([
                'cold_room_id' => 'Le lot est déjà dans cette chambre froide.',
            ]);
        }

        DB::transaction(function () use ($clientStockBatch, $cartons, $weight, $newColdRoom, $fromColdRoomName) {
            $transferReference = 'TRF-' . now()->format('Ymd-His') . '-' . $clientStockBatch->id;
            $destinationBatch = $clientStockBatch;

            if ($cartons === (int) $clientStockBatch->available_cartons) {
                $clientStockBatch->update([
                    'cold_room_id' => $newColdRoom->id,
                ]);
            } else {
                $clientStockBatch->decrement('available_cartons', $cartons);
                $clientStockBatch->decrement('available_weight', $weight);

                $clientStockBatch->refresh();
                $sourceStatus = (int) $clientStockBatch->available_cartons <= 0 ? 'empty' : 'partial';
                $clientStockBatch->update(['status' => $sourceStatus]);

                $destinationBatch = ClientStockBatch::create([
                    'packaging_order_id' => $clientStockBatch->packaging_order_id,
                    'client_id'         => $clientStockBatch->client_id,
                    'fish_type_id'      => $clientStockBatch->fish_type_id,
                    'carton_type_id'    => $clientStockBatch->carton_type_id,
                    'cold_room_id'      => $newColdRoom->id,
                    'lot_number'        => $clientStockBatch->lot_number . '-T' . now()->format('His'),
                    'cartons_count'     => $cartons,
                    'weight_per_carton' => $clientStockBatch->weight_per_carton,
                    'total_weight'      => $weight,
                    'available_cartons' => $cartons,
                    'available_weight'  => $weight,
                    'reserved_cartons'  => 0,
                    'reserved_weight'   => 0,
                    'frozen_at'         => $clientStockBatch->frozen_at,
                    'entry_date'        => now(),
                    'status'            => 'available',
                    'notes'             => 'Lot généré depuis transfert interne ' . $clientStockBatch->lot_number,
                ]);
            }

            StorageEntry::create([
                'client_stock_batch_id' => $destinationBatch->id,
                'cold_room_id' => $newColdRoom->id,
                'type' => 'transfer',
                'cartons_count' => $cartons,
                'weight_kg' => $weight,
                'reference' => $transferReference,
                'entry_date' => now(),
                'notes' => 'Transfert interne: ' . $fromColdRoomName . ' -> ' . $newColdRoom->name,
            ]);

            StockMovement::create([
                'client_id' => $clientStockBatch->client_id,
                'movement_type' => 'transfer',
                'reference_type' => 'stock_batch',
                'reference_id' => $destinationBatch->id,
                'fish_type_id' => $clientStockBatch->fish_type_id,
                'quantity' => $cartons,
                'weight_kg' => $weight,
                'from_location' => $fromColdRoomName,
                'to_location' => $newColdRoom->name,
                'movement_date' => now(),
                'notes' => 'Transfert de lot ' . $clientStockBatch->lot_number,
            ]);
        });

        return redirect()->route('stock.index')->with('success', "$cartons cartons transférés vers {$newColdRoom->name}.");
    }

    public function destroy(ClientStockBatch $clientStockBatch)
    {
        $clientStockBatch->delete();
        return redirect()->route('stock.index')->with('success', 'Lot supprimé.');
    }

    public function clientStatement(Request $request, Client $client)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $batches = ClientStockBatch::with(['fishType', 'cartonType', 'coldRoom'])
            ->where('client_id', $client->id)
            ->orderByDesc('entry_date')
            ->get();

        $stockByType = ClientStockBatch::query()
            ->with(['fishType', 'cartonType', 'coldRoom'])
            ->where('client_id', $client->id)
            ->where('available_cartons', '>', 0)
            ->get()
            ->groupBy(function ($batch) {
                return implode('|', [
                    $batch->fish_type_id,
                    $batch->carton_type_id,
                    $batch->cold_room_id,
                ]);
            })
            ->map(function ($rows) {
                $first = $rows->first();
                return [
                    'fish' => $first->fishType->name ?? '-',
                    'carton' => $first->cartonType->name ?? '-',
                    'cold_room' => $first->coldRoom->name ?? '-',
                    'lots' => $rows->count(),
                    'cartons' => (int) $rows->sum('available_cartons'),
                    'weight' => (float) $rows->sum('available_weight'),
                ];
            })
            ->sortByDesc('cartons')
            ->values();

        $query = StorageEntry::query()
            ->with(['stockBatch.fishType', 'stockBatch.cartonType', 'stockBatch.packagingOrder', 'coldRoom'])
            ->whereHas('stockBatch', fn ($q) => $q->where('client_id', $client->id))
            ->orderByDesc('entry_date');

        if ($startDate) {
            $query->whereDate('entry_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('entry_date', '<=', $endDate);
        }

        $transactions = $query->limit(500)->get();

        $deliveries = $client->deliveries()
            ->with(['items.stockBatch.fishType', 'items.stockBatch.cartonType'])
            ->orderByDesc('delivery_date')
            ->limit(20)
            ->get();

        $summary = [
            'total_lots' => $batches->count(),
            'total_cartons' => (int) $batches->sum('cartons_count'),
            'available_cartons' => (int) $batches->sum('available_cartons'),
            'delivered_cartons' => (int) ($batches->sum('cartons_count') - $batches->sum('available_cartons')),
            'available_weight' => (float) $batches->sum('available_weight'),
        ];

        return view('stock.client_statement', compact('client', 'batches', 'deliveries', 'summary', 'stockByType', 'transactions', 'startDate', 'endDate'));
    }

    public function clientStatementPdf(Request $request, Client $client)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $batches = ClientStockBatch::with(['fishType', 'cartonType', 'coldRoom'])
            ->where('client_id', $client->id)
            ->orderByDesc('entry_date')
            ->get();

        $deliveries = $client->deliveries()
            ->with(['items.stockBatch.fishType', 'items.stockBatch.cartonType'])
            ->orderByDesc('delivery_date')
            ->limit(30)
            ->get();

        $summary = [
            'total_lots' => $batches->count(),
            'total_cartons' => (int) $batches->sum('cartons_count'),
            'available_cartons' => (int) $batches->sum('available_cartons'),
            'delivered_cartons' => (int) ($batches->sum('cartons_count') - $batches->sum('available_cartons')),
            'available_weight' => (float) $batches->sum('available_weight'),
        ];

        $settings = [
            'company_name' => CompanySetting::get('company_name', 'FreezePro'),
            'company_address' => CompanySetting::get('company_address', ''),
            'company_phone' => CompanySetting::get('company_phone', ''),
        ];

        $pdf = Pdf::loadView('stock.client_statement_pdf', compact('client', 'batches', 'deliveries', 'summary', 'settings', 'startDate', 'endDate'))->setPaper('a4');
        return $pdf->stream('Suivi-Stock-' . $client->name . '.pdf');
    }

    public function clientStatementExcel(Request $request, Client $client)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $fileName = 'Suivi-Stock-' . $client->name . '-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new ClientStatementExport($client, $startDate, $endDate), $fileName);
    }

    public function stockListPdf(Request $request)
    {
        $batches = ClientStockBatch::with(['client', 'fishType', 'cartonType', 'coldRoom', 'packagingOrder'])
            ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
            ->when($request->fish_type_id, fn($q) => $q->where('fish_type_id', $request->fish_type_id))
            ->when($request->cold_room_id, fn($q) => $q->where('cold_room_id', $request->cold_room_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('lot_number', 'like', "%{$request->search}%"))
            ->when($request->origin, function($q) use ($request) {
                if ($request->origin === 'direct') {
                    $q->whereNull('packaging_order_id');
                } elseif ($request->origin === 'processing') {
                    $q->whereNotNull('packaging_order_id');
                } elseif ($request->origin === 'empty') {
                    $q->where('available_cartons', 0)->where('cartons_count', '>', 0);
                }
            })
            ->latest('entry_date')
            ->get();

        $clients = Client::where('active', true)->orderBy('name')->get();
        $fishTypes = FishType::where('active', true)->orderBy('name')->get();
        $coldRooms = ColdRoom::where('active', true)->orderBy('name')->get();

        $totals = ClientStockBatch::where('status', 'available')
            ->selectRaw('SUM(available_cartons) as cartons, SUM(available_weight) as weight')
            ->first();

        $settings = [
            'company_name' => CompanySetting::get('company_name', 'FreezePro'),
            'company_address' => CompanySetting::get('company_address', ''),
            'company_phone' => CompanySetting::get('company_phone', ''),
        ];

        $pdf = Pdf::loadView('stock.list_pdf', compact('batches', 'clients', 'fishTypes', 'coldRooms', 'totals', 'settings', 'request'))->setPaper('a4', 'landscape');
        return $pdf->stream('Liste-Stock-' . now()->format('Y-m-d') . '.pdf');
    }

    public function stockListExcel(Request $request)
    {
        $fileName = 'Liste-Stock-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new StockListExport($request), $fileName);
    }

    public function transactionReport(Request $request)
    {
        $clients = Client::where('active', true)->orderBy('name')->get();

        $query = StorageEntry::query()
            ->with(['stockBatch.client', 'stockBatch.fishType', 'stockBatch.cartonType', 'stockBatch.packagingOrder', 'coldRoom'])
            ->when($request->client_id, function ($q) use ($request) {
                $q->whereHas('stockBatch', fn ($sq) => $sq->where('client_id', $request->client_id));
            })
            ->when($request->movement_type, function ($q) use ($request) {
                if ($request->movement_type === 'processing_in') {
                    $q->where('type', 'in')
                        ->whereHas('stockBatch', fn ($sq) => $sq->whereNotNull('packaging_order_id'));
                    return;
                }

                $q->where('type', $request->movement_type);
            })
            ->when($request->start_date, fn ($q) => $q->whereDate('entry_date', '>=', $request->start_date))
            ->when($request->end_date, fn ($q) => $q->whereDate('entry_date', '<=', $request->end_date))
            ->orderByDesc('entry_date');

        $transactions = (clone $query)->paginate(10)->withQueryString();
        $allFiltered = (clone $query)->get();

        $summary = [
            'total_transactions' => $allFiltered->count(),
            'in_cartons' => (int) $allFiltered->where('type', 'in')->sum('cartons_count'),
            'out_cartons' => (int) $allFiltered->where('type', 'out')->sum('cartons_count'),
            'transfer_cartons' => (int) $allFiltered->where('type', 'transfer')->sum('cartons_count'),
            'in_weight' => (float) $allFiltered->where('type', 'in')->sum('weight_kg'),
            'out_weight' => (float) $allFiltered->where('type', 'out')->sum('weight_kg'),
            'transfer_weight' => (float) $allFiltered->where('type', 'transfer')->sum('weight_kg'),
            'processing_in_cartons' => (int) $allFiltered
                ->filter(fn ($tx) => $tx->type === 'in' && optional($tx->stockBatch)->packaging_order_id)
                ->sum('cartons_count'),
            'processing_in_weight' => (float) $allFiltered
                ->filter(fn ($tx) => $tx->type === 'in' && optional($tx->stockBatch)->packaging_order_id)
                ->sum('weight_kg'),
        ];

        return view('stock.transactions', compact('transactions', 'clients', 'summary'));
    }

    public function transactionReportExcel(Request $request)
    {
        $fileName = 'Suivi-Transactions-Clients-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new TransactionReportExport($request), $fileName);
    }
}
