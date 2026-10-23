<?php

namespace App\Http\Controllers;

use App\Models\PackagingOrder;
use App\Models\PackagingItem;
use App\Models\ProcessingOrder;
use App\Models\FishType;
use App\Models\CartonType;
use App\Models\ColdRoom;
use App\Models\ClientStockBatch;
use App\Models\StorageEntry;
use App\Http\Requests\PackagingOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\CompanySetting;

class PackagingOrderController extends Controller
{
    private function syncPackagingStock(PackagingOrder $order, ProcessingOrder $processingOrder, iterable $items, int $coldRoomId, string $entryReference): void
    {
        $order->stockBatches()->delete();
        $order->storageEntries()->delete();

        foreach ($items as $item) {
            $lot = 'LOT-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));
            $totalWeight = $item->cartons_count * $item->weight_per_carton;

            $batch = ClientStockBatch::create([
                'client_id'          => $processingOrder->client_id,
                'fish_type_id'       => $item->fish_type_id,
                'carton_type_id'     => $item->carton_type_id,
                'cold_room_id'       => $coldRoomId,
                'packaging_order_id' => $order->id,
                'lot_number'         => $lot,
                'entry_reference'    => $entryReference,
                'cartons_count'      => $item->cartons_count,
                'weight_per_carton'  => $item->weight_per_carton,
                'total_weight'       => $totalWeight,
                'available_cartons'  => $item->cartons_count,
                'available_weight'   => $totalWeight,
                'reserved_cartons'   => 0,
                'reserved_weight'    => 0,
                'frozen_at'          => now(),
                'entry_date'         => now(),
                'status'             => 'available',
                'notes'              => 'Depuis cartonnage ' . $order->reference,
            ]);

            StorageEntry::create([
                'client_stock_batch_id' => $batch->id,
                'cold_room_id'          => $coldRoomId,
                'type'                  => 'in',
                'cartons_count'         => $batch->cartons_count,
                'weight_kg'             => $batch->total_weight,
                'reference'             => $order->reference,
                'entry_date'            => now(),
                'notes'                 => 'Transfert après traitement - ' . $order->reference,
            ]);
        }
    }

    public function index(Request $request)
    {
        $orders = PackagingOrder::with(['processingOrder.client', 'items'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('reference', 'like', "%{$request->search}%"))
            ->latest('date')
            ->paginate(10)
            ->withQueryString();
        return view('packaging.index', compact('orders'));
    }

    public function create()
    {
        if ($requested = ProcessingOrder::with(['cartAssignments', 'packagingOrders'])->find(request('processing_order_id'))) {
            if ($existing = $requested->packagingOrders->first()) {
                return redirect()->route('packaging.edit', $existing);
            }
            if ($reason = $requested->packagingBlockReason()) {
                return redirect()->route('processing.show', $requested)->with('warning', 'Cartonnage verrouillé : ' . $reason);
            }
        }

        // Seuls les traitements sortis du tunnel et pas encore cartonnes
        $processingOrders = ProcessingOrder::whereIn('status', ['processing', 'completed'])
            ->whereDoesntHave('packagingOrders')
            ->whereHas('cartAssignments')
            ->whereDoesntHave('cartAssignments', fn ($q) => $q->where('status', 'active'))
            ->with(['client', 'items.fishType'])
            ->latest()
            ->get();
        $fishTypes   = FishType::where('active', true)->orderBy('name')->get();
        $cartonTypes = CartonType::where('active', true)
            ->with(['fishTypes' => fn ($q) => $q->where('fish_type_carton_types.active', true)])
            ->orderBy('name')
            ->get();
        $coldRooms   = ColdRoom::where('active', true)->orderBy('name')->get();
        return view('packaging.create', compact('processingOrders', 'fishTypes', 'cartonTypes', 'coldRooms'));
    }

    public function store(PackagingOrderRequest $request)
    {
        $payload = $request->safe()->except('items');
        $items = collect($request->input('items', []))
            ->filter(fn ($item) => !empty($item['fish_type_id']) && !empty($item['carton_type_id']))
            ->values();

        if ($items->isEmpty()) {
            return back()->withErrors([
                'items' => 'Ajoutez au moins une ligne de cartonnage valide.',
            ])->withInput();
        }

        $reason = ProcessingOrder::findOrFail($payload['processing_order_id'])->packagingBlockReason();
        if ($reason) {
            return back()->withErrors(['processing_order_id' => 'Cartonnage verrouillé : ' . $reason])->withInput();
        }

        $result = DB::transaction(function () use ($payload, $items) {
            $processingOrder = ProcessingOrder::query()
                ->whereKey($payload['processing_order_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $existingOrder = PackagingOrder::query()
                ->where('processing_order_id', $payload['processing_order_id'])
                ->lockForUpdate()
                ->first();

            if ($existingOrder) {
                return ['existing' => $existingOrder];
            }

            $reference = 'PKG-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));
            $order = PackagingOrder::create(array_merge($payload, [
                'reference' => $reference,
                'status'    => 'pending',
            ]));

            // Only create items — stock created after finalization
            $order->items()->delete();
            foreach ($items->all() as $item) {
                $order->items()->create([
                    'fish_type_id'     => $item['fish_type_id'],
                    'carton_type_id'   => $item['carton_type_id'],
                    'cartons_count'    => $item['cartons_count'],
                    'weight_per_carton'=> $item['weight_per_carton'],
                    'lot_number'       => 'PKG-' . strtoupper(Str::random(6)),
                    'cold_room_id'     => $item['cold_room_id'] ?? null,
                    'produced_at'      => now(),
                ]);
            }

            return [
                'order'     => $order,
                'reference' => $reference,
            ];
        });

        if (isset($result['existing'])) {
            return redirect()
                ->route('packaging.edit', $result['existing'])
                ->with('warning', 'Un ordre de cartonnage existe deja pour cet ordre de traitement. Utilisez la mise a jour au lieu de creer un doublon.');
        }

        return redirect()->route('packaging.show', $result['order'])->with('success', 'Ordre de cartonnage cree: ' . $result['reference']);
    }

    public function show(PackagingOrder $packaging)
    {
        $packaging->load(['processingOrder.client', 'items.fishType', 'items.cartonType', 'items.coldRoom']);
        $packagingOrder = $packaging;
        $order = $packaging;
        return view('packaging.show', compact('packagingOrder', 'order'));
    }

    public function printSheet(PackagingOrder $packaging)
    {
        $packaging->load(['processingOrder.client', 'items.fishType', 'items.cartonType', 'items.coldRoom']);
        $order = $packaging;

        return view('packaging.print', compact('order'));
    }

    public function edit(PackagingOrder $packaging)
    {
        $packaging->load('items');
        $packagingOrder = $packaging;
        $processingOrders = ProcessingOrder::with(['client', 'items.fishType'])->latest()->get();
        $fishTypes   = FishType::where('active', true)->orderBy('name')->get();
        $cartonTypes = CartonType::where('active', true)
            ->with(['fishTypes' => fn ($q) => $q->where('fish_type_carton_types.active', true)])
            ->orderBy('name')
            ->get();
        $coldRooms   = ColdRoom::where('active', true)->orderBy('name')->get();
        return view('packaging.create', compact('packagingOrder', 'processingOrders', 'fishTypes', 'cartonTypes', 'coldRooms'));
    }

    public function update(PackagingOrderRequest $request, PackagingOrder $packaging)
    {
        DB::transaction(function () use ($request, $packaging) {
            $payload = $request->safe()->except('items');
            $items = collect($request->input('items', []))
                ->filter(fn ($item) => !empty($item['fish_type_id']) && !empty($item['carton_type_id']))
                ->values();

            if ($items->isEmpty()) {
                abort(422, 'Ajoutez au moins une ligne de cartonnage valide.');
            }

            $duplicateOrder = PackagingOrder::query()
                ->where('processing_order_id', $payload['processing_order_id'])
                ->whereKeyNot($packaging->id)
                ->lockForUpdate()
                ->first();

            if ($duplicateOrder) {
                abort(422, 'Un autre ordre de cartonnage existe deja pour cet ordre de traitement.');
            }

            // Keep current status (don't override with form value)
            $packaging->update(array_merge($payload, [
                'status' => $packaging->status,
            ]));

            // Update items only (stock managed via finalize)
            $packaging->items()->delete();
            foreach ($items->all() as $item) {
                $packaging->items()->create([
                    'fish_type_id'     => $item['fish_type_id'],
                    'carton_type_id'   => $item['carton_type_id'],
                    'cartons_count'    => $item['cartons_count'],
                    'weight_per_carton'=> $item['weight_per_carton'],
                    'lot_number'       => 'PKG-' . strtoupper(Str::random(6)),
                    'cold_room_id'     => $item['cold_room_id'] ?? null,
                    'produced_at'      => now(),
                ]);
            }
        });

        return redirect()->route('packaging.show', $packaging)->with('success', 'Mise à jour effectuée.');
    }

    public function finalize(Request $request, PackagingOrder $packaging)
    {
        abort_if($packaging->status !== 'pending', 403, 'Ce cartonnage est déjà finalisé.');

        $validated = $request->validate([
            'mode'        => 'required|in:store,pickup',
            'cold_room_id'=> 'required_if:mode,store|nullable|exists:cold_rooms,id',
        ]);

        if ($validated['mode'] === 'pickup') {
            $packaging->update(['status' => 'delivered']);
            return redirect()->route('packaging.show', $packaging)
                ->with('success', 'Cartons marqués comme récupérés directement par le client.');
        }

        // mode = store → create stock batches
        $coldRoomId = (int) $validated['cold_room_id'];
        $processingOrder = $packaging->processingOrder()->lockForUpdate()->firstOrFail();
        $entryReference = 'PKG-STK-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));

        DB::transaction(function () use ($packaging, $processingOrder, $coldRoomId, $entryReference) {
            $this->syncPackagingStock($packaging, $processingOrder, $packaging->items, $coldRoomId, $entryReference);
            $packaging->update(['status' => 'completed']);
        });

        return redirect()->route('packaging.show', $packaging)
            ->with('success', 'Cartons stockés avec succès dans la chambre froide.')
            ->with('pdf_url', route('stock.entry.show', $entryReference))
            ->with('pdf_label', 'Voir entrée stock');
    }

    public function destroy(PackagingOrder $packaging)
    {
        $packaging->delete();
        return redirect()->route('packaging.index')->with('success', 'Ordre de cartonnage supprimé.');
    }
}
