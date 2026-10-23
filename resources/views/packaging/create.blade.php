@extends('layouts.app')
@section('title', isset($packagingOrder) ? 'Modifier Cartonnage' : 'Nouvel Ordre de Cartonnage')
@section('content')
@php
    $pendingOrderIds = session('pending_processing_order_ids', []);

    $ordersJs = $processingOrders->map(fn ($po) => [
        'id' => $po->id,
        'label' => $po->reference . ' — ' . ($po->client->name ?? ''),
        'fish' => (object) $po->items
            ->groupBy('fish_type_id')
            ->map(fn ($rows) => (float) $rows->sum('output_weight'))
            ->all(),
    ])->values();

    $cartonTypesJs = $cartonTypes->map(fn ($c) => [
        'id' => $c->id,
        'name' => $c->name,
        'weight_kg' => (float) ($c->weight_kg ?? 0),
    ])->values();

    $cartonTypesByFish = [];
    foreach ($cartonTypes as $ct) {
        foreach ($ct->fishTypes ?? [] as $ft) {
            if (! ($ft->pivot->active ?? true)) continue;
            $cartonTypesByFish[$ft->id][] = [
                'id' => $ct->id,
                'name' => $ct->name,
                'weight_kg' => (float) ($ft->pivot->default_weight ?: $ct->weight_kg ?: 0),
                'is_default' => (bool) ($ft->pivot->is_default ?? false),
            ];
        }
    }

    $existingLines = old('items')
        ? array_values(old('items'))
        : collect($packagingOrder->items ?? [])->map(fn ($i) => [
            'fish_type_id' => $i->fish_type_id,
            'carton_type_id' => $i->carton_type_id,
            'cartons_count' => $i->cartons_count,
            'cold_room_id' => $i->cold_room_id,
        ])->values()->all();

    $formConfig = [
        'orders' => $ordersJs,
        'fishTypes' => $fishTypes->map(fn ($f) => ['id' => $f->id, 'name' => $f->name])->values(),
        'cartonTypes' => $cartonTypesJs,
        'cartonTypesByFish' => (object) $cartonTypesByFish,
        'orderId' => old('processing_order_id', $packagingOrder->processing_order_id ?? request('processing_order_id')),
        'coldRoomId' => old('cold_room_id'),
        'lines' => $existingLines,
    ];
@endphp

<div class="max-w-4xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('packaging.index') }}" class="p-2 text-slate-500 hover:bg-slate-100 rounded-xl"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">
            {{ isset($packagingOrder) ? 'Modifier: '.$packagingOrder->reference : 'Nouvel Ordre de Cartonnage' }}
        </h1>
    </div>

    @if(!empty($pendingOrderIds))
    <div class="mb-6 rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 dark:bg-blue-950/20 dark:border-blue-900">
        <div class="flex items-center gap-3 mb-3">
            <i class="fas fa-list-check text-blue-600 dark:text-blue-400"></i>
            <h2 class="font-semibold text-blue-900 dark:text-blue-200">Traitement(s) en attente de cartonnage</h2>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach($pendingOrderIds as $poId)
                @php $po = $processingOrders->firstWhere('id', $poId); @endphp
                @if($po)
                <a href="{{ route('packaging.create', ['processing_order_id' => $po->id]) }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">
                    <i class="fas fa-box mr-1"></i> {{ $po->reference }} — {{ $po->client->name ?? '' }}
                </a>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ isset($packagingOrder) ? route('packaging.update', $packagingOrder) : route('packaging.store') }}"
          x-data="packagingForm(@js($formConfig))">
        @csrf
        @if(isset($packagingOrder)) @method('PUT') @endif
        <input type="hidden" name="status" value="completed">

        <template x-for="(l, idx) in lines" :key="l.uid">
            <div>
                <input type="hidden" :name="`items[${idx}][fish_type_id]`" :value="l.fish_type_id">
                <input type="hidden" :name="`items[${idx}][carton_type_id]`" :value="l.carton_type_id">
                <input type="hidden" :name="`items[${idx}][cartons_count]`" :value="l.cartons">
                <input type="hidden" :name="`items[${idx}][weight_per_carton]`" :value="cartonWeight(l.fish_type_id, l.carton_type_id)">
                <input type="hidden" :name="`items[${idx}][cold_room_id]`" :value="coldRoomId">
            </div>
        </template>

        {{-- En-tete --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Ordre de traitement <span class="text-red-500">*</span></label>
                    <select name="processing_order_id" x-model="orderId" required class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Choisir...</option>
                        <template x-for="o in orders" :key="o.id">
                            <option :value="String(o.id)" x-text="o.label" :selected="String(o.id) === orderId"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Date de cartonnage <span class="text-red-500">*</span></label>
                    <input type="date" name="date" required
                           value="{{ old('date', optional($packagingOrder->date ?? null)->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                           class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Chambre froide (pour toutes les lignes)</label>
                    <select name="cold_room_id" x-model="coldRoomId" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Choisie à la finalisation</option>
                        @foreach($coldRooms as $cr)
                        <option value="{{ $cr->id }}">{{ $cr->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div x-show="!orderId" class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 mb-4">
            <i class="fas fa-hand-pointer text-2xl text-slate-300 mb-2 block"></i>
            Choisissez l'ordre de traitement : les poissons triés et leurs poids s'affichent automatiquement.
        </div>

        {{-- Une carte par poisson --}}
        <div x-show="orderId" class="space-y-3 mb-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-white"><i class="fas fa-box mr-1.5 text-purple-500"></i>Cartons par poisson</h3>
                <button type="button" @click="if (!lines.length || confirm('Recalculer toutes les lignes ?')) suggest()" class="text-xs bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-3 py-1.5 rounded-xl font-semibold">
                    <i class="fas fa-wand-magic-sparkles mr-1"></i> Recalculer automatiquement
                </button>
            </div>

            <template x-for="fishId in fishIds" :key="fishId">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                        <div>
                            <p class="font-semibold text-slate-800 dark:text-white"><i class="fas fa-fish text-cyan-500 mr-1"></i><span x-text="fishName(fishId)"></span></p>
                            <p class="text-xs text-slate-500">
                                Trié : <b x-text="fmt(fishWeights[fishId] || 0)"></b> kg · Cartonné : <b x-text="fmt(packedKg(fishId))"></b> kg
                            </p>
                        </div>
                        <template x-if="fishWeights[fishId]">
                            <span class="text-xs px-2.5 py-1 rounded-full font-semibold"
                                  :class="Math.abs(packedKg(fishId) - fishWeights[fishId]) < 0.01 ? 'bg-emerald-100 text-emerald-700' : (packedKg(fishId) < fishWeights[fishId] ? 'bg-amber-100 text-amber-700' : 'bg-sky-100 text-sky-700')"
                                  x-text="Math.abs(packedKg(fishId) - fishWeights[fishId]) < 0.01 ? 'Complet' : (packedKg(fishId) < fishWeights[fishId] ? `Reste ${fmt(fishWeights[fishId] - packedKg(fishId))} kg` : `+${fmt(packedKg(fishId) - fishWeights[fishId])} kg (dernier carton)`)"></span>
                        </template>
                    </div>

                    <div class="space-y-2">
                        <template x-for="l in linesFor(fishId)" :key="l.uid">
                            <div class="rounded-xl bg-slate-50 dark:bg-slate-700/50 p-3">
                                <div class="flex flex-wrap gap-1.5 mb-2">
                                    <template x-for="c in cartonsFor(fishId)" :key="c.id">
                                        <button type="button" @click="pickCarton(l, c.id)"
                                                class="px-3 py-1.5 rounded-lg border text-xs font-semibold transition-all"
                                                :class="l.carton_type_id === c.id ? 'bg-purple-600 border-purple-600 text-white' : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:border-purple-300'">
                                            <span x-text="c.name"></span> <span class="opacity-75" x-text="`· ${fmt(c.weight_kg)} kg`"></span>
                                        </button>
                                    </template>
                                </div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <div class="flex items-center rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 overflow-hidden">
                                        <button type="button" @click="l.cartons = Math.max(1, (Number(l.cartons) || 0) - 1)" class="px-3 py-2 text-slate-500 hover:bg-slate-100"><i class="fas fa-minus text-xs"></i></button>
                                        <input type="number" min="1" x-model.number="l.cartons" class="w-20 text-center py-2 text-sm font-bold border-0 focus:ring-0 dark:bg-slate-800 dark:text-white">
                                        <button type="button" @click="l.cartons = (Number(l.cartons) || 0) + 1" class="px-3 py-2 text-slate-500 hover:bg-slate-100"><i class="fas fa-plus text-xs"></i></button>
                                    </div>
                                    <span class="text-sm text-slate-600 dark:text-slate-300">cartons = <b x-text="fmt((Number(l.cartons) || 0) * cartonWeight(fishId, l.carton_type_id))"></b> kg</span>
                                    <button type="button" @click="fill(l)" class="text-xs text-indigo-600 hover:underline" title="Calculer selon le poids restant">Calculer</button>
                                    <button type="button" @click="removeLine(l)" class="ml-auto p-2 text-red-500 hover:bg-red-50 rounded-lg" title="Supprimer"><i class="fas fa-trash text-xs"></i></button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <button type="button" @click="addLine(fishId)" class="mt-2 text-xs text-purple-700 hover:bg-purple-50 px-3 py-1.5 rounded-xl font-semibold">
                        <i class="fas fa-plus mr-1"></i> Ajouter un autre type de carton
                    </button>
                </div>
            </template>

            <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-dashed border-slate-300 dark:border-slate-600 p-3" x-show="availableFish.length">
                <select x-model="addFishId" class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                    <option value="">Ajouter un autre poisson...</option>
                    <template x-for="f in availableFish" :key="f.id"><option :value="f.id" x-text="f.name"></option></template>
                </select>
                <button type="button" @click="addFish()" :disabled="!addFishId" class="px-3 py-2 text-xs bg-purple-600 hover:bg-purple-700 disabled:opacity-40 text-white rounded-xl font-semibold">Ajouter</button>
            </div>
        </div>

        {{-- Recapitulatif --}}
        <div class="sticky bottom-3 z-10 bg-white/95 dark:bg-slate-800/95 backdrop-blur rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 p-4 flex flex-wrap items-center gap-4">
            <div class="flex gap-5 text-sm">
                <div><p class="text-xs text-slate-400">Cartons</p><p class="font-bold text-slate-800 dark:text-white" x-text="totals.cartons"></p></div>
                <div><p class="text-xs text-slate-400">Cartonné</p><p class="font-bold text-slate-800 dark:text-white"><span x-text="fmt(totals.packed)"></span> kg</p></div>
                <div><p class="text-xs text-slate-400">Trié</p><p class="font-bold text-slate-800 dark:text-white"><span x-text="fmt(totals.target)"></span> kg</p></div>
                <div x-show="totals.target > 0"><p class="text-xs text-slate-400">Écart</p>
                    <p class="font-bold" :class="Math.abs(totals.diff) < 0.01 ? 'text-emerald-600' : (totals.diff < 0 ? 'text-amber-600' : 'text-sky-600')"><span x-text="(totals.diff > 0 ? '+' : '') + fmt(totals.diff)"></span> kg</p>
                </div>
            </div>
            <div class="ml-auto flex items-center gap-2">
                <a href="{{ route('packaging.index') }}" class="px-5 py-2.5 border border-slate-200 text-slate-600 rounded-xl text-sm">Annuler</a>
                <button type="submit" :disabled="!valid" class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 disabled:opacity-40 text-white rounded-xl font-semibold text-sm">
                    <i class="fas fa-check mr-1.5"></i> {{ isset($packagingOrder) ? 'Mettre à jour' : 'Enregistrer' }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/affectation.js') }}?v={{ filemtime(public_path('js/affectation.js')) }}"></script>
@endpush
