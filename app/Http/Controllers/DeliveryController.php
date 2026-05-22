<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\Client;
use App\Models\ClientStockBatch;
use App\Models\CompanySetting;
use App\Models\StockMovement;
use App\Models\StorageEntry;
use App\Http\Requests\DeliveryRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DeliveryController extends Controller
{
    public function index(Request $request)
    {
        $deliveries = Delivery::with(['client', 'items'])
            ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date_from, fn($q) => $q->whereDate('delivery_date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('delivery_date', '<=', $request->date_to))
            ->when($request->search, fn($q) => $q->where('reference', 'like', "%{$request->search}%"))
            ->latest('delivery_date')
            ->paginate(10)
            ->withQueryString();
        $clients = Client::where('active', true)->orderBy('name')->get();
        return view('deliveries.index', compact('deliveries', 'clients'));
    }

    public function create()
    {
        $clients = Client::where('active', true)->orderBy('name')->get();
        return view('deliveries.create', compact('clients'));
    }

    public function getClientStock(Request $request)
    {
        $batches = ClientStockBatch::where('client_id', $request->client_id)
            ->where('status', 'available')
            ->where('available_cartons', '>', 0)
            ->with(['fishType', 'cartonType', 'coldRoom'])
            ->get()
            ->map(fn($b) => [
                'id'                => $b->id,
                'lot_number'        => $b->lot_number,
                'fish_type'         => $b->fishType->name ?? '-',
                'carton_type'       => $b->cartonType->name ?? '-',
                'available_cartons' => $b->available_cartons,
                'available_weight'  => $b->available_weight,
                'weight_per_carton' => $b->weight_per_carton,
                'cold_room'         => $b->coldRoom->name ?? '-',
            ]);
        return response()->json($batches);
    }

    public function store(DeliveryRequest $request)
    {
        $prefix = CompanySetting::get('delivery_prefix', 'BL');
        $ref = $prefix . '-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));

        $delivery = DB::transaction(function () use ($request, $ref) {
            $delivery = Delivery::create(array_merge($request->safe()->except('items'), ['reference' => $ref]));

            foreach ($request->items ?? [] as $item) {
                if (empty($item['client_stock_batch_id'])) {
                    continue;
                }

                $batch = ClientStockBatch::findOrFail($item['client_stock_batch_id']);
                $requestedCartons = (int) ($item['cartons_count'] ?? 0);
                $cartons = min($requestedCartons, max((int) $batch->available_cartons, 0));
                if ($cartons <= 0) {
                    continue;
                }

                $weight  = $cartons * (float) $batch->weight_per_carton;

                DeliveryItem::create([
                    'delivery_id'           => $delivery->id,
                    'client_stock_batch_id' => $batch->id,
                    'fish_type_id'          => $batch->fish_type_id,
                    'carton_type_id'        => $batch->carton_type_id,
                    'cartons_count'         => $cartons,
                    'weight_kg'             => $weight,
                ]);
            }

            if ($delivery->status === 'delivered') {
                $this->assertReceiverIdentityProvided($delivery);
                $this->applyDeliveryStockMovement($delivery);
                $delivery->update(['validated_at' => now()]);
            }

            return $delivery;
        });

        return redirect()->route('deliveries.show', $delivery)
            ->with('success', 'Livraison creee: ' . $ref)
            ->with('pdf_url', route('deliveries.pdf', $delivery))
            ->with('pdf_label', 'Recu sortie PDF');
    }

    public function show(Delivery $delivery)
    {
        $delivery->load(['client', 'items.fishType', 'items.cartonType', 'items.stockBatch']);
        return view('deliveries.show', compact('delivery'));
    }

    public function edit(Delivery $delivery)
    {
        $delivery->load('items');
        $clients = Client::where('active', true)->orderBy('name')->get();
        return view('deliveries.edit', compact('delivery', 'clients'));
    }

    public function update(DeliveryRequest $request, Delivery $delivery)
    {
        $previousStatus = $delivery->status;
        $delivery->update($request->safe()->except('items'));

        if ($previousStatus !== 'delivered' && $delivery->status === 'delivered') {
            $this->assertReceiverIdentityProvided($delivery);
            $this->applyDeliveryStockMovement($delivery);
            $delivery->update(['validated_at' => now()]);
        }

        return redirect()->route('deliveries.show', $delivery)
            ->with('success', 'Livraison mise a jour.')
            ->with('pdf_url', route('deliveries.pdf', $delivery))
            ->with('pdf_label', 'Recu sortie PDF');
    }

    public function validateDelivery(Delivery $delivery)
    {
        if ($delivery->status === 'delivered') {
            return back()->with('info', 'Cette livraison est deja validee.');
        }

        $this->assertReceiverIdentityProvided($delivery);

        DB::transaction(function () use ($delivery) {
            $this->applyDeliveryStockMovement($delivery);
            $delivery->update(['status' => 'delivered', 'validated_at' => now()]);
        });

        return back()->with('success', 'Retrait client valide et stock decremente.')
            ->with('pdf_url', route('deliveries.pdf', $delivery))
            ->with('pdf_label', 'Recu sortie PDF');
    }

    public function destroy(Delivery $delivery)
    {
        $delivery->delete();
        return redirect()->route('deliveries.index')->with('success', 'Livraison supprimée.');
    }

    public function pdf(Delivery $delivery)
    {
        $delivery->load(['client', 'items.fishType', 'items.cartonType', 'items.stockBatch.coldRoom']);
        $settings = [
            'company_name'    => CompanySetting::get('company_name', 'FreezePro'),
            'company_address' => CompanySetting::get('company_address', ''),
            'company_phone'   => CompanySetting::get('company_phone', ''),
            'company_ice'     => CompanySetting::get('company_ice', ''),
        ];
        $pdf = Pdf::loadView('deliveries.pdf', compact('delivery', 'settings'))->setPaper('a4');
        return $pdf->stream('BL-' . $delivery->reference . '.pdf');
    }

    private function applyDeliveryStockMovement(Delivery $delivery): void
    {
        $delivery->loadMissing(['items.stockBatch', 'client']);

        $stockErrors = [];

        foreach ($delivery->items as $item) {
            if ($item->cartons_count <= 0) {
                continue;
            }

            $batch = ClientStockBatch::with(['fishType', 'cartonType', 'coldRoom'])
                ->whereKey($item->client_stock_batch_id)
                ->lockForUpdate()
                ->first();

            $requested = (int) $item->cartons_count;
            $available = (int) ($batch->available_cartons ?? 0);

            if (! $batch || $requested > $available) {
                $lot = $batch->lot_number ?? ('#' . $item->client_stock_batch_id);
                $fish = $batch->fishType->name ?? ($item->fishType->name ?? '-');
                $carton = $batch->cartonType->name ?? ($item->cartonType->name ?? '-');
                $stockErrors[] = $lot . ' (' . $fish . ' / ' . $carton . ') demande: ' . $requested . ', disponible: ' . $available;
            }
        }

        if (! empty($stockErrors)) {
            throw ValidationException::withMessages([
                'stock_validation' => 'Stock insuffisant pour valider le retrait: ' . implode(' | ', $stockErrors),
            ]);
        }

        foreach ($delivery->items as $item) {
            if ($item->cartons_count <= 0) {
                continue;
            }

            $batch = ClientStockBatch::with('coldRoom')
                ->whereKey($item->client_stock_batch_id)
                ->lockForUpdate()
                ->first();

            if (! $batch) {
                continue;
            }

            $cartons = (int) $item->cartons_count;

            $weight = $cartons * (float) $batch->weight_per_carton;

            $batch->decrement('available_cartons', $cartons);
            $batch->decrement('available_weight', $weight);

            $batch->refresh();
            $newStatus = $batch->available_cartons <= 0 ? 'empty' : 'partial';
            $batch->update(['status' => $newStatus]);

            StorageEntry::create([
                'client_stock_batch_id' => $batch->id,
                'cold_room_id' => $batch->cold_room_id,
                'type' => 'out',
                'cartons_count' => $cartons,
                'weight_kg' => $weight,
                'reference' => $delivery->reference,
                'entry_date' => now(),
                'notes' => 'Sortie client - livraison validee ' . $delivery->reference,
            ]);

            StockMovement::create([
                'client_id' => $delivery->client_id,
                'movement_type' => 'out',
                'reference_type' => 'delivery',
                'reference_id' => $delivery->id,
                'fish_type_id' => $item->fish_type_id,
                'quantity' => $cartons,
                'weight_kg' => $weight,
                'from_location' => $batch->coldRoom->name ?? 'Stock client',
                'to_location' => 'Client',
                'movement_date' => now(),
                'notes' => 'Retrait client valide (' . $delivery->reference . ')',
            ]);
        }
    }

    private function assertReceiverIdentityProvided(Delivery $delivery): void
    {
        if (blank($delivery->receiver_name) || blank($delivery->receiver_id_number) || blank($delivery->receiver_phone)) {
            throw ValidationException::withMessages([
                'stock_validation' => 'Renseignez nom, CIN/NIF et telephone du receptionnaire avant validation du retrait.',
            ]);
        }
    }
}
