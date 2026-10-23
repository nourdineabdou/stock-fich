@extends('layouts.app')
@section('title', isset($processingOrder) ? 'Modifier Fiche Traitement' : 'Nouvelle Fiche Traitement')
@section('content')
@php
    $routeProcessing = request()->route('processing');
    $editProcessingId = $processingOrder->id
        ?? (is_object($routeProcessing) ? $routeProcessing->id : $routeProcessing);

    $existingLines = old('items')
        ? collect(old('items'))->map(fn ($i) => [
            'fish_type_id' => $i['fish_type_id'] ?? '',
            'weight' => $i['output_weight'] ?? '',
            'price' => $i['price_per_kg'] ?? '',
        ])->values()->all()
        : collect($processingOrder->items ?? [])->map(fn ($i) => [
            'fish_type_id' => $i->fish_type_id,
            'weight' => (float) $i->output_weight,
            'price' => (float) $i->price_per_kg,
        ])->values()->all();

    $num = fn ($v) => $v === null || $v === '' ? '' : (float) $v;
    $formConfig = [
        'fishTypes' => $fishTypes->map(fn ($f) => ['id' => $f->id, 'name' => $f->name])->values(),
        'arrivals' => $arrivals->map(fn ($a) => ['id' => $a->id, 'client_id' => $a->client_id, 'net_weight' => (float) $a->net_weight])->values(),
        'clientId' => old('client_id', $processingOrder->client_id ?? request('client_id')),
        'arrivalId' => old('arrival_id', $processingOrder->arrival_id ?? request('arrival_id')),
        'input' => $num(old('input_weight', $processingOrder->input_weight ?? '')),
        'waste' => $num(old('waste_weight', $processingOrder->waste_weight ?? '')),
        'returned' => $num(old('returned_weight', $processingOrder->returned_weight ?? '')),
        'price' => $num(old('price', $processingOrder->price ?? '')),
        'lines' => $existingLines,
    ];
    $input = 'w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500';
@endphp

<div class="max-w-5xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('processing.index') }}" class="p-2 text-slate-500 hover:bg-slate-100 rounded-xl"><i class="fas fa-arrow-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">
                {{ isset($processingOrder) ? 'Modifier Fiche: '.$processingOrder->reference : 'Nouvelle Fiche de Traitement' }}
            </h1>
            <p class="text-sm text-slate-500">Réception → filtrage → tri par type → frais de traitement par kg</p>
        </div>
    </div>

    @if($errors->any())
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ $editProcessingId ? url('processing/'.$editProcessingId) : route('processing.store') }}"
          x-data="processingForm(@js($formConfig))" class="space-y-4">
        @csrf
        @if($editProcessingId) @method('PUT') @endif
        <input type="hidden" name="output_weight" :value="outputWeight.toFixed(2)">
        <template x-for="(l, idx) in lines" :key="l.uid">
            <div>
                <input type="hidden" :name="`items[${idx}][fish_type_id]`" :value="l.fish_type_id">
                <input type="hidden" :name="`items[${idx}][output_weight]`" :value="l.weight">
                <input type="hidden" :name="`items[${idx}][price_per_kg]`" :value="price">
                <input type="hidden" :name="`items[${idx}][input_weight]`" value="0">
                <input type="hidden" :name="`items[${idx}][waste_weight]`" value="0">
                <input type="hidden" :name="`items[${idx}][returned_weight]`" value="0">
            </div>
        </template>

        {{-- 1. Client et arrivage --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-4">
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-700 text-white text-xs mr-2">1</span>Client et arrivage
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Client <span class="text-red-500">*</span></label>
                    <select name="client_id" x-model="clientId" required class="{{ $input }}">
                        <option value="">Choisir...</option>
                        @foreach($clients as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Arrivage lié</label>
                    <select name="arrival_id" x-model="arrivalId" class="{{ $input }}">
                        <option value="">Aucun</option>
                        @foreach($arrivals as $arr)
                        <option value="{{ $arr->id }}">{{ $arr->reference }} ({{ $arr->client->name ?? '' }}) — {{ number_format($arr->net_weight, 0, ',', ' ') }} kg</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Date début <span class="text-red-500">*</span></label>
                    <input type="date" name="start_date" required class="{{ $input }}"
                           value="{{ old('start_date', isset($processingOrder) && $processingOrder->start_date ? $processingOrder->start_date->format('Y-m-d') : today()->format('Y-m-d')) }}">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Date fin</label>
                    <input type="date" name="end_date" class="{{ $input }}"
                           value="{{ old('end_date', isset($processingOrder) && $processingOrder->end_date ? $processingOrder->end_date->format('Y-m-d') : '') }}">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Statut</label>
                    <select name="status" class="{{ $input }}">
                        @foreach(['pending' => 'En attente', 'processing' => 'En cours', 'completed' => 'Complété', 'cancelled' => 'Annulé'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('status', $processingOrder->status ?? 'processing') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Facturation</label>
                    <select name="billing_method" class="{{ $input }}">
                        @foreach(['kg' => 'Par kg', 'carton' => 'Par carton', 'day' => 'Par jour', 'fixed' => 'Forfait'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('billing_method', $processingOrder->billing_method ?? 'kg') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- 2. Reception et filtrage --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-4">
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-cyan-600 text-white text-xs mr-2">2</span>Réception et filtrage
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Poids reçu (kg)</label>
                    <input type="number" step="0.01" min="0" name="input_weight" x-model="input" class="{{ $input }} text-lg font-bold">
                    <p class="mt-1 text-xs text-slate-400" x-show="received >= 1000" x-text="`≈ ${fmt(received / 1000, 3)} tonne(s)`"></p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Non utilisable / déchets (kg)</label>
                    <input type="number" step="0.01" min="0" name="waste_weight" x-model="waste" class="{{ $input }} text-lg font-bold text-red-600">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Retour client (kg)</label>
                    <input type="number" step="0.01" min="0" name="returned_weight" x-model="returned" class="{{ $input }} text-lg font-bold">
                </div>
                <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-3">
                    <p class="text-xs text-emerald-700">Utilisable à trier</p>
                    <p class="text-2xl font-bold text-emerald-700"><span x-text="fmt(usable)"></span> <span class="text-sm">kg</span></p>
                </div>
            </div>

            <div class="mt-4" x-show="received > 0">
                <div class="flex h-4 rounded-full overflow-hidden bg-slate-100">
                    <div class="bg-emerald-500 transition-all" :style="`width:${pct(usable)}%`"></div>
                    <div class="bg-red-400 transition-all" :style="`width:${pct(waste)}%`"></div>
                    <div class="bg-amber-400 transition-all" :style="`width:${pct(returned)}%`"></div>
                </div>
                <div class="mt-1.5 flex flex-wrap gap-4 text-xs text-slate-500">
                    <span><i class="fas fa-square text-emerald-500 mr-1"></i>Utilisable <b x-text="fmt(pct(usable), 1) + ' %'"></b></span>
                    <span><i class="fas fa-square text-red-400 mr-1"></i>Non utilisable <b x-text="fmt(pct(waste), 1) + ' %'"></b></span>
                    <span x-show="returned > 0"><i class="fas fa-square text-amber-400 mr-1"></i>Retour <b x-text="fmt(pct(returned), 1) + ' %'"></b></span>
                </div>
            </div>
            <p x-show="received > 0 && (Number(waste) || 0) + (Number(returned) || 0) > received" class="mt-2 text-xs text-red-600">
                <i class="fas fa-triangle-exclamation mr-1"></i>Le non utilisable + retour dépasse le poids reçu.
            </p>
        </div>

        {{-- 3. Tri par type de poisson --}}
        <div id="phase2-triage" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-white">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-white text-xs mr-2">3</span>Tri par type de poisson
                </h3>
                <div class="flex items-center gap-2">
                    <label class="text-xs text-slate-500">Frais de traitement par kg (tous types)</label>
                    <div class="relative">
                        <input type="number" step="0.01" min="0" name="price" x-model="price" class="w-32 pl-3 pr-14 py-2 border-2 border-sky-300 rounded-xl text-sm font-bold dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">MRU/kg</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-1.5 mb-4">
                <span class="text-xs text-slate-500 self-center mr-1">Ajouter :</span>
                <template x-for="f in fishTypes" :key="f.id">
                    <button type="button" @click="addFish(f.id)"
                            class="px-3 py-1.5 rounded-full border text-xs font-semibold transition-all"
                            :class="isUsed(f.id) ? 'bg-blue-50 border-blue-200 text-blue-400' : 'border-slate-200 text-slate-600 hover:border-blue-400 hover:text-blue-700'">
                        <i class="fas fa-plus text-[10px] mr-0.5"></i><span x-text="f.name"></span>
                    </button>
                </template>
            </div>

            <div class="hidden md:grid grid-cols-12 gap-2 px-3 pb-2 text-[11px] font-semibold uppercase text-slate-400">
                <div class="col-span-6">Type de poisson</div>
                <div class="col-span-5">Quantité (kg)</div>
            </div>
            <div class="space-y-2">
                <template x-for="l in lines" :key="l.uid">
                    <div class="grid grid-cols-12 gap-2 items-center rounded-xl bg-slate-50 dark:bg-slate-700/50 p-2.5">
                        <div class="col-span-12 md:col-span-6">
                            <select x-model="l.fish_type_id" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                                <option value="">Type de poisson...</option>
                                <template x-for="f in fishTypes" :key="f.id"><option :value="String(f.id)" x-text="f.name" :selected="String(f.id) === l.fish_type_id"></option></template>
                            </select>
                        </div>
                        <div class="col-span-10 md:col-span-5 flex gap-1">
                            <input type="number" step="0.01" min="0" x-model="l.weight" placeholder="kg" class="w-full px-3 py-2 text-sm font-semibold border border-slate-200 rounded-lg dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                            <button type="button" x-show="remaining > 0.009" @click="putRemainder(l)" class="px-2 text-[11px] whitespace-nowrap rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-semibold" title="Ajouter le reste à trier">+ reste</button>
                        </div>
                        <div class="col-span-2 md:col-span-1 text-right">
                            <button type="button" @click="removeLine(l)" class="p-2 text-red-500 hover:bg-red-50 rounded-lg"><i class="fas fa-trash text-xs"></i></button>
                        </div>
                    </div>
                </template>
            </div>
            <button type="button" @click="addLine()" class="mt-2 text-xs text-blue-700 hover:bg-blue-50 px-3 py-1.5 rounded-xl font-semibold"><i class="fas fa-plus mr-1"></i> Ajouter une ligne</button>

            <div class="mt-3 rounded-xl px-4 py-2.5 text-xs flex flex-wrap items-center gap-3"
                 :class="Math.abs(remaining) < 0.01 ? 'bg-emerald-50 text-emerald-700' : (remaining > 0 ? 'bg-amber-50 text-amber-800' : 'bg-red-50 text-red-700')"
                 x-show="usable > 0">
                <template x-if="Math.abs(remaining) < 0.01"><span><i class="fas fa-check-circle mr-1"></i>Tout le poisson utilisable est trié.</span></template>
                <template x-if="remaining >= 0.01">
                    <span><i class="fas fa-hourglass-half mr-1"></i>Reste à trier : <b x-text="fmt(remaining) + ' kg'"></b></span>
                </template>
                <button type="button" x-show="remaining >= 0.01" @click="remainderToWaste()" class="px-2.5 py-1 rounded-lg bg-white border border-amber-200 font-semibold hover:bg-amber-100">Compter le reste comme non utilisable</button>
                <template x-if="remaining <= -0.01"><span><i class="fas fa-triangle-exclamation mr-1"></i>Le trié dépasse l'utilisable de <b x-text="fmt(-remaining) + ' kg'"></b></span></template>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
            <label class="block text-xs font-medium text-slate-500 mb-1">Notes (imprimées sur la fiche client)</label>
            <textarea name="notes" rows="2" class="{{ $input }}">{{ old('notes', $processingOrder->notes ?? '') }}</textarea>
        </div>

        {{-- Recapitulatif --}}
        <div class="sticky bottom-3 z-10 bg-slate-900 text-white rounded-2xl shadow-xl p-4 flex flex-wrap items-center gap-6">
            <div><p class="text-[11px] text-slate-400 uppercase">Reçu</p><p class="font-bold"><span x-text="fmt(received)"></span> kg</p></div>
            <div><p class="text-[11px] text-slate-400 uppercase">Non utilisable</p><p class="font-bold text-red-300"><span x-text="fmt(waste)"></span> kg</p></div>
            <div><p class="text-[11px] text-slate-400 uppercase">Total poisson trié</p><p class="font-bold text-emerald-300"><span x-text="fmt(sorted)"></span> kg</p></div>
            <div><p class="text-[11px] text-slate-400 uppercase">Total traitement</p><p class="text-xl font-extrabold text-sky-300"><span x-text="fmt(total)"></span> MRU</p></div>
            <div class="ml-auto flex items-center gap-2">
                <a href="{{ route('processing.index') }}" class="px-4 py-2.5 border border-slate-600 text-slate-200 rounded-xl text-sm">Annuler</a>
                <button type="submit" :disabled="!clientId || overReceived" class="px-6 py-2.5 bg-sky-500 hover:bg-sky-600 disabled:opacity-40 text-white rounded-xl font-semibold text-sm">
                    <i class="fas fa-check mr-1.5"></i> {{ isset($processingOrder) ? 'Mettre à jour' : 'Enregistrer' }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/traitement.js') }}?v={{ filemtime(public_path('js/traitement.js')) }}"></script>
@endpush
