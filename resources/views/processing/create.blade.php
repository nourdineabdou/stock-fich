@extends('layouts.app')
@section('title', isset($processingOrder) ? 'Modifier Fiche Traitement' : 'Nouvelle Fiche Traitement')
@section('content')
<div class="max-w-5xl mx-auto">
    @php
        $routeProcessing = request()->route('processing');
        $editProcessingId = $processingOrder->id
            ?? (is_object($routeProcessing) ? $routeProcessing->id : $routeProcessing);
    @endphp
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('processing.index') }}" class="p-2 text-slate-500 hover:bg-slate-100 rounded-xl"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">
            {{ isset($processingOrder) ? 'Modifier Fiche: '.$processingOrder->reference : 'Nouvelle Fiche de Traitement' }}
        </h1>
    </div>

    <div class="mb-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-4 text-sm text-cyan-900 dark:bg-cyan-950/20 dark:border-cyan-900 dark:text-cyan-200">
        <p class="font-semibold mb-1"><i class="fas fa-list-check mr-2"></i>Workflow Traitement</p>
        <p>Phase 1: separer dechets et stockable. Phase 2: trier la partie stockable par type avec prix/kg. Phase 3: passer au cartonnage puis tunnel/chambre froide.</p>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
        <form method="POST" action="{{ $editProcessingId ? url('processing/'.$editProcessingId) : route('processing.store') }}">
            @csrf
            @if($editProcessingId) @method('PUT') @endif

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Client <span class="text-red-500">*</span></label>
                    <select name="client_id" required class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Choisir...</option>
                        @foreach($clients as $c)
                        <option value="{{ $c->id }}" {{ old('client_id', $processingOrder->client_id ?? request('client_id')) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Arrivage lié</label>
                    <select id="arrival_id" name="arrival_id" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Aucun</option>
                        @foreach($arrivals as $arr)
                        <option value="{{ $arr->id }}" data-net-weight="{{ $arr->net_weight }}" {{ old('arrival_id', $processingOrder->arrival_id ?? request('arrival_id')) == $arr->id ? 'selected' : '' }}>
                            {{ $arr->reference }} ({{ $arr->client->name ?? '' }})
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Date début <span class="text-red-500">*</span></label>
                    <input type="date" name="start_date" value="{{ old('start_date', isset($processingOrder) && $processingOrder->start_date ? $processingOrder->start_date->format('Y-m-d') : today()->format('Y-m-d')) }}" required
                        class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Date fin</label>
                    <input type="date" name="end_date" value="{{ old('end_date', isset($processingOrder) && $processingOrder->end_date ? $processingOrder->end_date->format('Y-m-d') : '') }}"
                        class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Mode facturation <span class="text-red-500">*</span></label>
                    <select name="billing_method" required class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="kg" {{ old('billing_method', $processingOrder->billing_method ?? 'kg') == 'kg' ? 'selected' : '' }}>Par kg</option>
                        <option value="carton" {{ old('billing_method', $processingOrder->billing_method ?? '') == 'carton' ? 'selected' : '' }}>Par carton</option>
                        <option value="day" {{ old('billing_method', $processingOrder->billing_method ?? '') == 'day' ? 'selected' : '' }}>Par jour</option>
                        <option value="fixed" {{ old('billing_method', $processingOrder->billing_method ?? '') == 'fixed' ? 'selected' : '' }}>Forfait</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Prix unitaire</label>
                    <input type="number" step="0.01" name="price" value="{{ old('price', $processingOrder->price ?? '') }}"
                        class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Statut</label>
                    <select name="status" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="pending" {{ old('status', $processingOrder->status ?? 'pending') == 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="processing" {{ old('status', $processingOrder->status ?? '') == 'processing' ? 'selected' : '' }}>En cours</option>
                        <option value="completed" {{ old('status', $processingOrder->status ?? '') == 'completed' ? 'selected' : '' }}>Complété</option>
                        <option value="cancelled" {{ old('status', $processingOrder->status ?? '') == 'cancelled' ? 'selected' : '' }}>Annulé</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Notes</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">{{ old('notes', $processingOrder->notes ?? '') }}</textarea>
                </div>
            </div>

            {{-- Phase 1 --}}
            <div class="border-t border-slate-100 dark:border-slate-700 pt-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-white"><i class="fas fa-scale-balanced mr-1.5 text-cyan-500"></i>Phase 1 - Separation dechets / stockable</h3>
                </div>
                <div class="mb-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800 dark:bg-amber-950/20 dark:border-amber-900 dark:text-amber-300">
                    Saisissez les totaux globaux de la premiere phase: poids recu, poids stockable, dechets et retour client.
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Poids reçu total (kg)</label>
                        <input id="input_weight" type="number" step="0.01" min="0" name="input_weight" value="{{ old('input_weight', $processingOrder->input_weight ?? '') }}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Poids stockable (kg)</label>
                        <input type="number" step="0.01" min="0" name="output_weight" value="{{ old('output_weight', $processingOrder->output_weight ?? '') }}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Déchets / pertes (kg)</label>
                        <input type="number" step="0.01" min="0" name="waste_weight" value="{{ old('waste_weight', $processingOrder->waste_weight ?? '') }}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Retour client (kg)</label>
                        <input type="number" step="0.01" min="0" name="returned_weight" value="{{ old('returned_weight', $processingOrder->returned_weight ?? '') }}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Optionnel. Ne rien saisir si aucun produit n'est retourne au client.</p>
                        @error('returned_weight')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Phase 2 --}}
            <div id="phase2-triage" class="border-t border-slate-100 dark:border-slate-700 pt-5 mt-5">
                @php
                    $triItems = old('items', isset($processingOrder)
                        ? $processingOrder->items->map(fn($item) => [
                            'fish_type_id' => $item->fish_type_id,
                            'output_weight' => $item->output_weight,
                            'price_per_kg' => $item->price_per_kg,
                        ])->toArray()
                        : []);
                @endphp

                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-white"><i class="fas fa-fish mr-1.5 text-blue-500"></i>Phase 2 - Tri de la partie stockable</h3>
                    <button type="button" onclick="addTriItem()" class="text-xs bg-blue-50 hover:bg-blue-100 text-blue-700 px-3 py-1.5 rounded-xl">
                        <i class="fas fa-plus mr-1"></i> Ajouter un type
                    </button>
                </div>
                <div class="mb-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-xs text-blue-900 dark:bg-blue-950/20 dark:border-blue-900 dark:text-blue-200">
                    Optionnel a ce stade: si vous avez deja trie, detaillez ici les types et le poids reussi pour preparer la facturation client.
                </div>

                @error('items')
                <p class="mb-2 text-xs text-red-600">{{ $message }}</p>
                @enderror

                <div id="triItems" class="space-y-2">
                    @foreach($triItems as $i => $item)
                    <div class="grid grid-cols-12 gap-2 bg-slate-50 dark:bg-slate-700 rounded-xl p-2">
                        <div class="col-span-5">
                            <select name="items[{{ $i }}][fish_type_id]" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none">
                                <option value="">Type de poisson...</option>
                                @foreach($fishTypes as $ft)
                                <option value="{{ $ft->id }}" {{ ($item['fish_type_id'] ?? null) == $ft->id ? 'selected' : '' }}>{{ $ft->name }}</option>
                                @endforeach
                            </select>
                            @error('items.'.$i.'.fish_type_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-span-3">
                            <input type="number" step="0.01" min="0" name="items[{{ $i }}][output_weight]" value="{{ $item['output_weight'] ?? '' }}" placeholder="Poids trie (kg)" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none" oninput="calcTriLine(this)">
                            @error('items.'.$i.'.output_weight')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-span-3">
                            <input type="number" step="0.01" min="0" name="items[{{ $i }}][price_per_kg]" value="{{ $item['price_per_kg'] ?? '' }}" placeholder="Prix/kg" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none" oninput="calcTriLine(this)">
                        </div>
                        <div class="col-span-1 flex items-center justify-end">
                            <button type="button" onclick="removeItem(this)" class="p-1 text-red-600"><i class="fas fa-times text-xs"></i></button>
                        </div>

                        <input type="hidden" name="items[{{ $i }}][input_weight]" value="0">
                        <input type="hidden" name="items[{{ $i }}][waste_weight]" value="0">
                        <input type="hidden" name="items[{{ $i }}][returned_weight]" value="0">
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-3 pt-5 mt-5 border-t border-slate-100 dark:border-slate-700">
                <button type="submit" class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-semibold text-sm">
                    <i class="fas fa-check mr-1.5"></i> {{ isset($processingOrder) ? 'Mettre à jour' : 'Enregistrer' }}
                </button>
                <a href="{{ route('processing.index') }}" class="px-6 py-2.5 border border-slate-200 text-slate-600 rounded-xl text-sm">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
const fishTypes = @json($fishTypes->map(fn($f) => ['id' => $f->id, 'name' => $f->name]));
let triIdx = {{ count($triItems ?? []) }};

function addTriItem() {
    const i = triIdx++;
    const opts = fishTypes.map(f => `<option value="${f.id}">${f.name}</option>`).join('');
    document.getElementById('triItems').insertAdjacentHTML('beforeend', `
    <div class="grid grid-cols-12 gap-2 bg-slate-50 dark:bg-slate-700 rounded-xl p-2">
        <div class="col-span-5">
            <select name="items[${i}][fish_type_id]" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none">
                <option value="">Type de poisson...</option>
                ${opts}
            </select>
        </div>
        <div class="col-span-3">
            <input type="number" step="0.01" min="0" name="items[${i}][output_weight]" placeholder="Poids trie (kg)" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none" oninput="calcTriLine(this)">
        </div>
        <div class="col-span-3">
            <input type="number" step="0.01" min="0" name="items[${i}][price_per_kg]" placeholder="Prix/kg" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none" oninput="calcTriLine(this)">
        </div>
        <div class="col-span-1 flex items-center justify-end">
            <button type="button" onclick="removeItem(this)" class="p-1 text-red-600"><i class="fas fa-times text-xs"></i></button>
        </div>
        <input type="hidden" name="items[${i}][input_weight]" value="0">
        <input type="hidden" name="items[${i}][waste_weight]" value="0">
        <input type="hidden" name="items[${i}][returned_weight]" value="0">
    </div>`);
}

function calcTriLine(input) {
    return input;
}

function removeItem(btn) {
    btn.closest('.grid').remove();
}

function fillInputWeightFromArrival(force = false) {
    const arrivalSelect = document.getElementById('arrival_id');
    const inputWeight = document.getElementById('input_weight');
    if (!arrivalSelect || !inputWeight) return;

    const selectedOption = arrivalSelect.options[arrivalSelect.selectedIndex];
    const netWeight = selectedOption?.dataset?.netWeight;
    if (!netWeight) return;

    if (force || inputWeight.value === '' || Number(inputWeight.value) === 0) {
        inputWeight.value = Number(netWeight).toFixed(2);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const arrivalSelect = document.getElementById('arrival_id');
    const triContainer = document.getElementById('triItems');
    if (triContainer && triContainer.children.length === 0) {
        addTriItem();
    }

    if (!arrivalSelect) return;

    fillInputWeightFromArrival(false);
    arrivalSelect.addEventListener('change', () => fillInputWeightFromArrival(true));
});
</script>
@endpush
