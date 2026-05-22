<?php

namespace App\Http\Controllers;

use App\Models\CartonType;
use App\Http\Requests\CartonTypeRequest;
use Illuminate\Http\Request;

class CartonTypeController extends Controller
{
    public function index(Request $request)
    {
        $cartonTypes = CartonType::withCount('packagingItems')
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
        return view('carton-types.index', compact('cartonTypes'));
    }

    public function create()
    {
        return view('carton-types.create');
    }

    public function store(CartonTypeRequest $request)
    {
        CartonType::create($request->validated());
        return redirect()->route('carton-types.index')->with('success', 'Type de carton créé.');
    }

    public function show(CartonType $cartonType)
    {
        $cartonType->load('fishTypes');
        return view('carton-types.show', compact('cartonType'));
    }

    public function edit(CartonType $cartonType)
    {
        return view('carton-types.edit', compact('cartonType'));
    }

    public function update(CartonTypeRequest $request, CartonType $cartonType)
    {
        $cartonType->update($request->validated());
        return redirect()->route('carton-types.index')->with('success', 'Type de carton mis à jour.');
    }

    public function destroy(CartonType $cartonType)
    {
        $cartonType->delete();
        return redirect()->route('carton-types.index')->with('success', 'Type de carton supprimé.');
    }
}
