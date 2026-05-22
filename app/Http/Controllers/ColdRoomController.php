<?php

namespace App\Http\Controllers;

use App\Models\ColdRoom;
use App\Models\ClientStockBatch;
use App\Http\Requests\ColdRoomRequest;
use Illuminate\Http\Request;

class ColdRoomController extends Controller
{
    public function index(Request $request)
    {
        $coldRooms = ColdRoom::query()
            ->withCount([
                'stockBatches as active_lots_count' => fn ($q) => $q->where('available_cartons', '>', 0),
            ])
            ->withSum([
                'stockBatches as available_cartons_sum' => fn ($q) => $q->where('available_cartons', '>', 0),
            ], 'available_cartons')
            ->withSum([
                'stockBatches as available_weight_sum' => fn ($q) => $q->where('available_weight', '>', 0),
            ], 'available_weight')
            ->addSelect([
                'carton_types_count' => ClientStockBatch::query()
                    ->selectRaw('COUNT(DISTINCT carton_type_id)')
                    ->whereColumn('cold_room_id', 'cold_rooms.id')
                    ->where('available_cartons', '>', 0),
            ])
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
        return view('cold-rooms.index', compact('coldRooms'));
    }

    public function create()
    {
        return view('cold-rooms.create');
    }

    public function store(ColdRoomRequest $request)
    {
        ColdRoom::create($request->validated());
        return redirect()->route('cold-rooms.index')->with('success', 'Chambre froide créée.');
    }

    public function show(ColdRoom $coldRoom)
    {
        $coldRoom->load([
            'stockBatches' => fn($q) => $q
                ->where('available_cartons', '>', 0)
                ->with(['client', 'fishType', 'cartonType'])
                ->orderByDesc('entry_date'),
        ]);

        $stockByClient = $coldRoom->stockBatches
            ->groupBy('client_id')
            ->map(function ($rows) {
                $first = $rows->first();
                return [
                    'client_name' => $first->client->name ?? '-',
                    'lots' => $rows->count(),
                    'cartons' => (int) $rows->sum('available_cartons'),
                    'weight' => (float) $rows->sum('available_weight'),
                ];
            })
            ->sortByDesc('cartons')
            ->values();

        $summary = [
            'lots' => (int) $coldRoom->stockBatches->count(),
            'cartons' => (int) $coldRoom->stockBatches->sum('available_cartons'),
            'weight' => (float) $coldRoom->stockBatches->sum('available_weight'),
            'carton_types' => (int) $coldRoom->stockBatches->pluck('carton_type_id')->filter()->unique()->count(),
            'clients' => (int) $coldRoom->stockBatches->pluck('client_id')->filter()->unique()->count(),
        ];
        return view('cold-rooms.show', compact('coldRoom', 'stockByClient', 'summary'));
    }

    public function edit(ColdRoom $coldRoom)
    {
        return view('cold-rooms.edit', compact('coldRoom'));
    }

    public function update(ColdRoomRequest $request, ColdRoom $coldRoom)
    {
        $coldRoom->update($request->validated());
        return redirect()->route('cold-rooms.show', $coldRoom)->with('success', 'Chambre froide mise à jour.');
    }

    public function destroy(ColdRoom $coldRoom)
    {
        $coldRoom->delete();
        return redirect()->route('cold-rooms.index')->with('success', 'Chambre froide supprimée.');
    }
}
