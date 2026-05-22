@extends('layouts.app')
@section('title', isset($packagingOrder) ? 'Modifier Cartonnage' : 'Nouvel Ordre de Cartonnage')
@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('packaging.index') }}" class="p-2 text-slate-500 hover:bg-slate-100 rounded-xl"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">
            {{ isset($packagingOrder) ? 'Modifier: '.$packagingOrder->reference : 'Nouvel Ordre de Cartonnage' }}
        </h1>
    </div>

    @php
        $pendingOrderIds = session('pending_processing_order_ids', []);
    @endphp

    @if(!empty($pendingOrderIds))
    <div class="mb-6 rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 dark:bg-blue-950/20 dark:border-blue-900">
        <div class="flex items-center gap-3 mb-3">
            <i class="fas fa-list-check text-blue-600 dark:text-blue-400"></i>
            <h2 class="font-semibold text-blue-900 dark:text-blue-200">Traitement(s) en attente de cartonnage</h2>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach($pendingOrderIds as $poId)
                @php
                    $po = $processingOrders->firstWhere('id', $poId);
                @endphp
                @if($po)
                <a href="{{ route('packaging.create', ['processing_order_id' => $po->id]) }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">
                    <i class="fas fa-box mr-1"></i>
                    {{ $po->reference }} — {{ $po->client->name ?? '' }}
                </a>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
        <form method="POST" action="{{ isset($packagingOrder) ? route('packaging.update', $packagingOrder) : route('packaging.store') }}">
            @csrf
            @if(isset($packagingOrder)) @method('PUT') @endif

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Ordre de Traitement <span class="text-red-500">*</span></label>
                    <select name="processing_order_id" id="processingOrderSelect" required class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Choisir...</option>
                        @foreach($processingOrders as $po)
                        <option value="{{ $po->id }}" {{ old('processing_order_id', $packagingOrder->processing_order_id ?? request('processing_order_id')) == $po->id ? 'selected' : '' }}>
                            {{ $po->reference }} — {{ $po->client->name ?? '' }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Date de cartonnage <span class="text-red-500">*</span></label>
                    <input type="date" name="date" required
                           value="{{ old('date', optional($packagingOrder->date ?? null)->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                           class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Salle froide destination</label>
                    <select name="cold_room_id" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Aucune</option>
                        @foreach($coldRooms as $cr)
                        <option value="{{ $cr->id }}" {{ old('cold_room_id', $packagingOrder->cold_room_id ?? '') == $cr->id ? 'selected' : '' }}>{{ $cr->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Statut</label>
                    <input type="hidden" name="status" value="completed">
                    <div class="w-full px-3 py-2.5 border border-emerald-200 bg-emerald-50 text-emerald-700 rounded-xl text-sm dark:bg-emerald-950/30 dark:border-emerald-800 dark:text-emerald-300">
                        Complete automatiquement a l'enregistrement.
                    </div>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Notes</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">{{ old('notes', $packagingOrder->notes ?? '') }}</textarea>
                </div>
            </div>

            <div class="border-t border-slate-100 dark:border-slate-700 pt-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-white"><i class="fas fa-box mr-1.5 text-purple-500"></i>Cartonnage par type de poisson et type carton</h3>
                    <div class="flex items-center gap-2">
                        <button type="button" id="suggestPackagingBtn" class="text-xs bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-3 py-1.5 rounded-xl">
                            <i class="fas fa-wand-magic-sparkles mr-1"></i> Suggérer
                        </button>
                        <button type="button" onclick="addPkgItem()" class="text-xs bg-purple-50 hover:bg-purple-100 text-purple-700 px-3 py-1.5 rounded-xl">
                            <i class="fas fa-plus mr-1"></i> Ajouter
                        </button>
                    </div>
                </div>

                <!-- Headers -->
                <div class="hidden md:grid grid-cols-13 gap-2 mb-2 px-2 pb-2 border-b border-slate-200 dark:border-slate-600">
                    <div class="col-span-2 text-xs font-semibold text-slate-600 dark:text-slate-400">Poisson (Poids Total)</div>
                    <div class="col-span-2 text-xs font-semibold text-slate-600 dark:text-slate-400">Type Carton</div>
                    <div class="col-span-2 text-xs font-semibold text-slate-600 dark:text-slate-400">Poids/Carton</div>
                    <div class="col-span-2 text-xs font-semibold text-slate-600 dark:text-slate-400">Nb Cartons</div>
                    <div class="col-span-3 text-xs font-semibold text-slate-600 dark:text-slate-400">Chambre Froide</div>
                    <div class="col-span-1"></div>
                </div>

                <div id="pkgItems" class="space-y-2">
                    @forelse($packagingOrder->items ?? [] as $i => $item)
                    <div class="grid grid-cols-13 gap-2 bg-slate-50 dark:bg-slate-700 rounded-xl p-3 items-center">
                        <div class="col-span-12 md:col-span-2">
                            <label class="block md:hidden text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Poisson</label>
                            <select name="items[{{ $i }}][fish_type_id]" onchange="onFishTypeChanged(this)" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none">
                                @foreach($fishTypes as $ft)
                                <option value="{{ $ft->id }}" {{ $item->fish_type_id == $ft->id ? 'selected' : '' }}>{{ $ft->name }}</option>
                                @endforeach
                            </select>
                            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1"><i class="fas fa-weight mr-1"></i><span class="fish-weight">0</span> kg</div>
                        </div>
                        <div class="col-span-12 md:col-span-2">
                            <label class="block md:hidden text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Type Carton</label>
                            <select name="items[{{ $i }}][carton_type_id]" onchange="onCartonTypeChanged(this)" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none">
                                @foreach($cartonTypes as $ct)
                                <option value="{{ $ct->id }}" data-weight="{{ $ct->weight_kg ?? 0 }}" {{ $item->carton_type_id == $ct->id ? 'selected' : '' }}>{{ $ct->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-12 md:col-span-2">
                            <label class="block md:hidden text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Poids/Carton (kg)</label>
                            <input type="number" step="0.001" name="items[{{ $i }}][weight_per_carton]" value="{{ $item->weight_per_carton }}" readonly class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none bg-slate-100 dark:bg-slate-800">
                        </div>
                        <div class="col-span-12 md:col-span-2">
                            <label class="block md:hidden text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Nb Cartons</label>
                            <input type="number" name="items[{{ $i }}][cartons_count]" value="{{ $item->cartons_count }}" placeholder="0" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none font-semibold">
                        </div>
                        <div class="col-span-12 md:col-span-3">
                            <label class="block md:hidden text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Chambre Froide</label>
                            <select name="items[{{ $i }}][cold_room_id]" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none">
                                <option value="">Aucune</option>
                                @foreach($coldRooms as $cr)
                                <option value="{{ $cr->id }}" {{ $item->cold_room_id == $cr->id ? 'selected' : '' }}>{{ $cr->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-12 md:col-span-1 flex items-center justify-end">
                            <button type="button" onclick="removeItem(this)" class="p-1 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg"><i class="fas fa-times text-xs"></i></button>
                        </div>
                    </div>
                    @empty
                    @endforelse
                </div>
                <p class="text-xs text-slate-500 mt-2">
                    Vous pouvez ajouter des lignes hybrides, ou utiliser deux types de cartons pour le meme poisson en ajoutant plusieurs lignes.
                </p>
            </div>

            <div class="flex items-center gap-3 pt-5 mt-5 border-t border-slate-100 dark:border-slate-700">
                <button type="submit" class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-semibold text-sm">
                    <i class="fas fa-check mr-1.5"></i> {{ isset($packagingOrder) ? 'Mettre à jour' : 'Enregistrer' }}
                </button>
                <a href="{{ route('packaging.index') }}" class="px-6 py-2.5 border border-slate-200 text-slate-600 rounded-xl text-sm">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
const fishTypes = {!! json_encode($fishTypes->map(fn($f) => ['id'=>$f->id,'name'=>$f->name])->toArray()) !!};
const cartonTypes = {!! json_encode($cartonTypes->map(fn($c) => ['id'=>$c->id,'name'=>$c->name,'weight_kg'=>(float)($c->weight_kg ?? 0)])->toArray()) !!};
const coldRooms = {!! json_encode($coldRooms->map(fn($cr) => ['id'=>$cr->id,'name'=>$cr->name])->toArray()) !!};

// Build cartonTypesByFish mapping
const cartonTypesByFish = {};
@foreach($fishTypes as $fish)
    cartonTypesByFish[{{ $fish->id }}] = {!! json_encode(
        $cartonTypes
            ->filter(fn($ct) => $ct->fishTypes->contains(fn($ft) => (int) $ft->id === (int) $fish->id && (bool) ($ft->pivot->active ?? true)))
            ->map(function($ct) use ($fish) {
                $pivot = $ct->fishTypes->firstWhere('id', $fish->id)?->pivot;
                return [
                    'id' => $ct->id,
                    'name' => $ct->name,
                    'weight_kg' => (float) ($pivot->default_weight ?? $ct->weight_kg ?? 0),
                    'is_default' => (bool) ($pivot->is_default ?? false),
                ];
            })
            ->values()
            ->toArray()
    ) !!};
@endforeach

// Build processingFishMap
const processingFishMap = {
@foreach($processingOrders as $po)
    {{ $po->id }}: {!! json_encode(
        $po->items
            ->groupBy('fish_type_id')
            ->mapWithKeys(fn($rows, $fishId) => [$fishId => (float) $rows->sum('output_weight')])
            ->toArray()
    ) !!},
@endforeach
};

let pkgIdx = {{ isset($packagingOrder) ? max(count($packagingOrder->items ?? []),0) : 0 }};

function getFishName(fishId) {
    const fish = fishTypes.find(f => String(f.id) === String(fishId));
    return fish ? fish.name : '?';
}

function getFishWeight(processingId, fishId) {
    const weights = processingFishMap[String(processingId)] || processingFishMap[processingId];
    if (!weights) return 0;
    return parseFloat(weights[String(fishId)] || weights[fishId] || 0);
}

function updateFishWeightDisplay(row) {
    const fishSelect = row.querySelector('select[name$="[fish_type_id]"]');
    const processingSelect = document.getElementById('processingOrderSelect');
    if (!fishSelect || !processingSelect) return;
    
    const fishId = fishSelect.value;
    const processingId = processingSelect.value;
    const weight = getFishWeight(processingId, fishId);
    const weightDisplay = row.querySelector('.fish-weight');
    if (weightDisplay) {
        weightDisplay.textContent = weight.toFixed(2);
    }
}

function buildCartonOptions(fishTypeId) {
    // Show all carton types regardless of fish type
    return cartonTypes.map(c => `<option value="${c.id}" data-weight="${c.weight_kg || 0}">${c.name}</option>`).join('');
}

function buildColdRoomOptions() {
    return '<option value="">Aucune</option>' + coldRooms.map(cr => `<option value="${cr.id}">${cr.name}</option>`).join('');
}

function recalculateCartonCount(row) {
    const fishSelect = row.querySelector('select[name$="[fish_type_id]"]');
    const cartonSelect = row.querySelector('select[name$="[carton_type_id]"]');
    const cartonCountInput = row.querySelector('input[name$="[cartons_count]"]');
    const weightPerCartonInput = row.querySelector('input[name$="[weight_per_carton]"]');
    const processingSelect = document.getElementById('processingOrderSelect');
    
    if (!fishSelect || !cartonSelect || !cartonCountInput || !weightPerCartonInput || !processingSelect) return;
    
    const fishId = fishSelect.value;
    const processingId = processingSelect.value;
    const fishWeight = getFishWeight(processingId, fishId);
    
    const selectedOption = cartonSelect.options[cartonSelect.selectedIndex];
    const cartonWeight = parseFloat(selectedOption?.dataset.weight || '0') || 0;
    
    // Update weight per carton display
    weightPerCartonInput.value = cartonWeight > 0 ? cartonWeight.toFixed(3) : '';
    
    // Calculate carton count
    if (fishWeight > 0 && cartonWeight > 0) {
        const cartonCount = Math.ceil(fishWeight / cartonWeight);
        cartonCountInput.value = cartonCount;
    } else if (fishWeight === 0) {
        cartonCountInput.value = '';
    }
}

function addPkgItem() {
    const i = pkgIdx++;
    const processingId = document.getElementById('processingOrderSelect')?.value;
    const firstFishId = fishTypes.length ? fishTypes[0].id : null;
    const fOpts = fishTypes.map(f => `<option value="${f.id}">${f.name}</option>`).join('');
    const cOpts = firstFishId ? buildCartonOptions(firstFishId) : '';
    const fishWeight = firstFishId ? getFishWeight(processingId, firstFishId) : 0;
    
    document.getElementById('pkgItems').insertAdjacentHTML('beforeend', `
    <div class="grid grid-cols-13 gap-2 bg-slate-50 dark:bg-slate-700 rounded-xl p-3 items-center">
        <div class="col-span-12 md:col-span-2">
            <label class="block md:hidden text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Poisson</label>
            <select name="items[${i}][fish_type_id]" onchange="onFishTypeChanged(this)" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none">${fOpts}</select>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1"><i class="fas fa-weight mr-1"></i><span class="fish-weight">${fishWeight.toFixed(2)}</span> kg</div>
        </div>
        <div class="col-span-12 md:col-span-2">
            <label class="block md:hidden text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Type Carton</label>
            <select name="items[${i}][carton_type_id]" onchange="onCartonTypeChanged(this)" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none">${cOpts}</select>
        </div>
        <div class="col-span-12 md:col-span-2">
            <label class="block md:hidden text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Poids/Carton (kg)</label>
            <input type="number" step="0.001" name="items[${i}][weight_per_carton]" readonly class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none bg-slate-100 dark:bg-slate-800">
        </div>
        <div class="col-span-12 md:col-span-2">
            <label class="block md:hidden text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Nb Cartons</label>
            <input type="number" name="items[${i}][cartons_count]" placeholder="0" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none font-semibold">
        </div>
        <div class="col-span-12 md:col-span-3">
            <label class="block md:hidden text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Chambre Froide</label>
            <select name="items[${i}][cold_room_id]" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none">${buildColdRoomOptions()}</select>
        </div>
        <div class="col-span-12 md:col-span-1 flex items-center justify-end">
            <button type="button" onclick="removeItem(this)" class="p-1 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg"><i class="fas fa-times text-xs"></i></button>
        </div>
    </div>`);

    const lastRow = document.querySelector('#pkgItems > div:last-child');
    if (lastRow) {
        recalculateCartonCount(lastRow);
    }
}

function onFishTypeChanged(selectEl) {
    const row = selectEl.closest('.grid');
    updateFishWeightDisplay(row);
    
    const cartonSelect = row.querySelector('select[name$="[carton_type_id]"]');
    if (!cartonSelect) return;

    cartonSelect.innerHTML = buildCartonOptions(selectEl.value);
    recalculateCartonCount(row);
}

function onCartonTypeChanged(selectEl) {
    const row = selectEl.closest('.grid');
    recalculateCartonCount(row);
}

function suggestPackagingLines() {
    const processingId = document.getElementById('processingOrderSelect')?.value;
    const fishWeights = processingFishMap[String(processingId)] || processingFishMap[processingId];
    if (!fishWeights || Object.keys(fishWeights).length === 0) return;

    document.getElementById('pkgItems').innerHTML = '';
    Object.entries(fishWeights).forEach(([fishId, fishWeight]) => {
        const linked = cartonTypesByFish[String(fishId)] || cartonTypesByFish[fishId] || [];
        const chosen = linked.find(x => x.is_default) || linked[0] || cartonTypes[0] || null;
        if (!chosen) return;

        const i = pkgIdx++;
        const fOpts = fishTypes.map(f => `<option value="${f.id}" ${String(f.id) === String(fishId) ? 'selected' : ''}>${f.name}</option>`).join('');
        const cOpts = buildCartonOptions(fishId);
        const unitWeight = parseFloat(chosen.weight_kg || 0) || 1;
        const cartons = Math.max(1, Math.ceil(parseFloat(fishWeight || 0) / unitWeight));

        document.getElementById('pkgItems').insertAdjacentHTML('beforeend', `
        <div class="grid grid-cols-13 gap-2 bg-slate-50 dark:bg-slate-700 rounded-xl p-3 items-center">
            <div class="col-span-12 md:col-span-2">
                <label class="block md:hidden text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Poisson</label>
                <select name="items[${i}][fish_type_id]" onchange="onFishTypeChanged(this)" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none">${fOpts}</select>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1"><i class="fas fa-weight mr-1"></i><span class="fish-weight">${parseFloat(fishWeight).toFixed(2)}</span> kg</div>
            </div>
            <div class="col-span-12 md:col-span-2">
                <label class="block md:hidden text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Type Carton</label>
                <select name="items[${i}][carton_type_id]" onchange="onCartonTypeChanged(this)" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none">${cOpts}</select>
            </div>
            <div class="col-span-12 md:col-span-2">
                <label class="block md:hidden text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Poids/Carton (kg)</label>
                <input type="number" step="0.001" name="items[${i}][weight_per_carton]" value="${unitWeight.toFixed(3)}" readonly class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none bg-slate-100 dark:bg-slate-800">
            </div>
            <div class="col-span-12 md:col-span-2">
                <label class="block md:hidden text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Nb Cartons</label>
                <input type="number" name="items[${i}][cartons_count]" value="${cartons}" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none font-semibold">
            </div>
            <div class="col-span-12 md:col-span-3">
                <label class="block md:hidden text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Chambre Froide</label>
                <select name="items[${i}][cold_room_id]" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none">${buildColdRoomOptions()}</select>
            </div>
            <div class="col-span-12 md:col-span-1 flex items-center justify-end">
                <button type="button" onclick="removeItem(this)" class="p-1 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg"><i class="fas fa-times text-xs"></i></button>
            </div>
        </div>`);

        const row = document.querySelector('#pkgItems > div:last-child');
        if (row) {
            const cartonSelect = row.querySelector('select[name$="[carton_type_id]"]');
            if (cartonSelect) {
                cartonSelect.value = String(chosen.id);
            }
        }
    });
}

document.getElementById('suggestPackagingBtn')?.addEventListener('click', suggestPackagingLines);

// Update all rows when processing order changes
document.getElementById('processingOrderSelect')?.addEventListener('change', function() {
    document.querySelectorAll('#pkgItems > .grid').forEach(row => {
        updateFishWeightDisplay(row);
        recalculateCartonCount(row);
    });
});

// Initialize existing rows on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('#pkgItems > .grid').forEach(row => {
        updateFishWeightDisplay(row);
        recalculateCartonCount(row);
    });
});

function removeItem(btn) { btn.closest('.grid').remove(); }
</script>
@endpush
