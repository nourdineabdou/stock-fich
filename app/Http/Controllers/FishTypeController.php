<?php

namespace App\Http\Controllers;

use App\Models\FishType;
use App\Models\CartonType;
use App\Http\Requests\FishTypeRequest;
use Illuminate\Http\Request;

class FishTypeController extends Controller
{
    public function index(Request $request)
    {
        $fishTypes = FishType::withCount('arrivalItems')
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
        return view('fish-types.index', compact('fishTypes'));
    }

    public function create()
    {
        $cartonTypes = CartonType::where('active', true)->orderBy('name')->get();
        return view('fish-types.create', compact('cartonTypes'));
    }

    public function store(FishTypeRequest $request)
    {
        $fishType = FishType::create($request->validated());
        if ($request->has('carton_types')) {
            $pivotData = [];
            foreach ($request->carton_types as $ctId => $data) {
                $pivotData[$ctId] = [
                    'default_weight' => $data['default_weight'] ?? null,
                    'is_default'     => isset($data['is_default']) ? 1 : 0,
                    'active'         => 1,
                ];
            }
            $fishType->cartonTypes()->sync($pivotData);
        }
        return redirect()->route('fish-types.index')->with('success', 'Type de poisson créé.');
    }

    public function show(FishType $fishType)
    {
        $fishType->load(['cartonTypes', 'arrivalItems.arrival.client']);
        return view('fish-types.show', compact('fishType'));
    }

    public function edit(FishType $fishType)
    {
        $fishType->load('cartonTypes');
        $cartonTypes = CartonType::where('active', true)->orderBy('name')->get();
        return view('fish-types.edit', compact('fishType', 'cartonTypes'));
    }

    public function update(FishTypeRequest $request, FishType $fishType)
    {
        $fishType->update($request->validated());
        if ($request->has('carton_types')) {
            $pivotData = [];
            foreach ($request->carton_types as $ctId => $data) {
                $pivotData[$ctId] = [
                    'default_weight' => $data['default_weight'] ?? null,
                    'is_default'     => isset($data['is_default']) ? 1 : 0,
                    'active'         => 1,
                ];
            }
            $fishType->cartonTypes()->sync($pivotData);
        }
        return redirect()->route('fish-types.index')->with('success', 'Type de poisson mis à jour.');
    }

    public function destroy(FishType $fishType)
    {
        $fishType->delete();
        return redirect()->route('fish-types.index')->with('success', 'Type de poisson supprimé.');
    }
}
