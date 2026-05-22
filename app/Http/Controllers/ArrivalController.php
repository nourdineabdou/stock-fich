<?php

namespace App\Http\Controllers;

use App\Models\Arrival;
use App\Models\ArrivalItem;
use App\Models\Client;
use App\Models\FishType;
use App\Models\CompanySetting;
use App\Http\Requests\ArrivalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArrivalController extends Controller
{
    public function index(Request $request)
    {
        $query = Arrival::with(['client', 'items.fishType'])
            ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
            ->when($request->status,    fn($q) => $q->where('status', $request->status))
            ->when($request->date_from, fn($q) => $q->whereDate('arrival_date', '>=', $request->date_from))
            ->when($request->date_to,   fn($q) => $q->whereDate('arrival_date', '<=', $request->date_to))
            ->when($request->search,    fn($q) => $q->where('reference', 'like', "%{$request->search}%"));

        $arrivals = $query->latest('arrival_date')->paginate(10)->withQueryString();
        $clients  = Client::where('active', true)->orderBy('name')->get();
        return view('arrivals.index', compact('arrivals', 'clients'));
    }

    public function create()
    {
        $clients   = Client::where('active', true)->orderBy('name')->get();
        return view('arrivals.create', compact('clients'));
    }

    public function store(ArrivalRequest $request)
    {
        $prefix = CompanySetting::get('arrival_prefix', 'ARR');
        $ref = $prefix . '-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));

        $arrival = Arrival::create(array_merge($request->safe()->except('items'), [
            'reference' => $ref,
        ]));

        $grossWeight = 0;
        $netWeight   = 0;
        foreach ($request->items as $item) {
            if (empty($item['weight_kg'])) continue;
            ArrivalItem::create([
                'arrival_id'   => $arrival->id,
                'fish_type_id' => null,
                'weight_kg'    => $item['weight_kg'],
                'quantity'     => $item['quantity'] ?? 1,
                'notes'        => $item['notes'] ?? null,
            ]);
            $grossWeight += $item['weight_kg'];
            $netWeight   += $item['weight_kg'];
        }

        $arrival->update(['gross_weight' => $grossWeight, 'net_weight' => $netWeight]);
        return redirect()->route('arrivals.show', $arrival)->with('success', 'Arrivage enregistré: ' . $ref);
    }

    public function show(Arrival $arrival)
    {
        $arrival->load([
            'client',
            'items',
            'processingOrders' => fn ($query) => $query->latest('id')->limit(1),
            'processingOrders.items.fishType',
        ]);

        return view('arrivals.show', compact('arrival'));
    }

    public function edit(Arrival $arrival)
    {
        $arrival->load('items');
        $clients   = Client::where('active', true)->orderBy('name')->get();
        return view('arrivals.edit', compact('arrival', 'clients'));
    }

    public function update(ArrivalRequest $request, Arrival $arrival)
    {
        $arrival->update($request->safe()->except('items'));

        $arrival->items()->delete();
        $grossWeight = 0;
        foreach ($request->items as $item) {
            if (empty($item['weight_kg'])) continue;
            ArrivalItem::create([
                'arrival_id'   => $arrival->id,
                'fish_type_id' => null,
                'weight_kg'    => $item['weight_kg'],
                'quantity'     => $item['quantity'] ?? 1,
                'notes'        => $item['notes'] ?? null,
            ]);
            $grossWeight += $item['weight_kg'];
        }
        $arrival->update(['gross_weight' => $grossWeight, 'net_weight' => $grossWeight]);
        return redirect()->route('arrivals.show', $arrival)->with('success', 'Arrivage mis à jour.');
    }

    public function destroy(Arrival $arrival)
    {
        $arrival->delete();
        return redirect()->route('arrivals.index')->with('success', 'Arrivage supprimé.');
    }
}
