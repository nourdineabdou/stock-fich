@extends('layouts.app')
@section('title', $order->reference)
@section('content')
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('processing.index') }}" class="p-2 text-slate-500 hover:bg-slate-100 rounded-xl"><i class="fas fa-arrow-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white font-mono">{{ $order->reference }}</h1>
            <p class="text-sm text-slate-500">{{ $order->client->name ?? '-' }} · {{ $order->start_date?->format('d/m/Y') ?? '—' }}</p>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('processing.worker-sheet-pdf', $order) }}" target="_blank" rel="noopener"
           class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
            <i class="fas fa-clipboard-list"></i> Bon de Chargement
        </a>
        <a href="{{ route('processing.client-pdf', $order) }}" target="_blank" rel="noopener"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
            <i class="fas fa-print"></i> Fiche client PDF (A4)
        </a>
        <a href="{{ route('invoices.create') }}?client_id={{ $order->client_id }}&processing_order_id={{ $order->id }}"
           class="inline-flex items-center gap-2 bg-sky-600 hover:bg-sky-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
            <i class="fas fa-file-invoice"></i> Facture Client
        </a>
        @php $packagingBlock = $order->packagingBlockReason(); $existingPackaging = $order->packagingOrders->first(); @endphp
        @if($existingPackaging)
        <a href="{{ route('packaging.show', $existingPackaging) }}" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
            <i class="fas fa-box"></i> Cartonnage
        </a>
        @elseif($packagingBlock)
        <span title="{{ $packagingBlock }}" aria-disabled="true"
              class="inline-flex items-center gap-2 bg-slate-200 text-slate-400 px-4 py-2 rounded-xl text-sm font-semibold cursor-not-allowed select-none dark:bg-slate-700 dark:text-slate-500">
            <i class="fas fa-lock"></i> Cartonnage
        </span>
        @else
        <a href="{{ route('packaging.create') }}?processing_order_id={{ $order->id }}" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
            <i class="fas fa-box"></i> Cartonnage
        </a>
        @endif
        <a href="{{ url('processing/'.$order->id.'/edit') }}" class="p-2 text-amber-600 bg-amber-50 hover:bg-amber-100 rounded-xl"><i class="fas fa-pen"></i></a>
        <button onclick="confirmDelete('{{ url('processing/'.$order->id) }}', '{{ addslashes($order->reference) }}')" class="p-2 text-red-600 border border-red-200 hover:bg-red-50 rounded-xl"><i class="fas fa-trash"></i></button>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">
        @php
            $totalInputWeight = $order->input_weight ?? $order->items->sum('input_weight');
            $totalOutputWeight = $order->output_weight ?? $order->items->sum('output_weight');
            $totalWasteWeight = $order->waste_weight ?? $order->items->sum('waste_weight');
            $totalReturnedWeight = $order->returned_weight ?? $order->items->sum('returned_weight');
            $wasteRate = $totalInputWeight > 0 ? round(($totalWasteWeight / $totalInputWeight) * 100, 1) : 0;
            $phase1Done = $totalInputWeight > 0 || $totalOutputWeight > 0 || $totalWasteWeight > 0;
            $phase2Done = $order->items->count() > 0;
            $phase3Done = $order->packagingOrders->count() > 0;
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="rounded-xl border p-3 {{ $phase1Done ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-slate-50 text-slate-500' }}">
                <p class="text-xs font-semibold uppercase">Phase 1</p>
                <p class="text-sm">Dechets vs stockable</p>
            </div>
            <div class="rounded-xl border p-3 {{ $phase2Done ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-slate-50 text-slate-500' }}">
                <p class="text-xs font-semibold uppercase">Phase 2</p>
                <p class="text-sm">Tri par type et prix/kg</p>
            </div>
            <div class="rounded-xl border p-3 {{ $phase3Done ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-slate-50 text-slate-500' }}">
                <p class="text-xs font-semibold uppercase">Phase 3</p>
                <p class="text-sm">Cartonnage et stockage</p>
            </div>
        </div>

        @if($packagingBlock && ! $existingPackaging)
        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3 text-sm text-slate-600 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300">
            <i class="fas fa-lock mr-2 text-slate-400"></i><b>Cartonnage verrouillé</b> — {{ $packagingBlock }}
            @if($order->cartAssignments->where('status', 'active')->isNotEmpty())
                @php $tunnelIds = $order->cartAssignments->where('status', 'active')->pluck('tunnel_id')->filter()->unique(); @endphp
                @foreach($tunnelIds as $tid)
                <a href="{{ route('tunnels.show', $tid) }}" class="ml-2 text-indigo-600 font-semibold hover:underline">Aller au tunnel {{ $order->cartAssignments->firstWhere('tunnel_id', $tid)?->cart?->tunnel?->name ?? '' }} →</a>
                @endforeach
                <div class="mt-3">@include('processing._release-button', ['order' => $order])</div>
            @endif
        </div>
        @endif

        @if(! $phase2Done)
        <div class="rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-800 dark:bg-blue-950/20 dark:border-blue-900 dark:text-blue-200">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="font-semibold"><i class="fas fa-fish mr-2"></i>Phase 2 non renseignee</p>
                    <p class="text-xs mt-1">Renseignez le tri par type (poids reussi + prix/kg) avant l'affectation finale aux chariots.</p>
                </div>
                <a href="{{ url('processing/'.$order->id.'/edit#phase2-triage') }}" class="px-3 py-2 rounded-lg bg-blue-600 text-white text-xs font-semibold whitespace-nowrap">Ouvrir Phase 2</a>
            </div>
        </div>
        @endif

        @php
            $hasActiveAssignments = !empty($activeAssignedCartIds ?? []);
            $cartsJs = collect($availableCartOptions ?? [])->map(fn ($c) => [
                'id' => (int) $c->id,
                'code' => $c->code,
                'capacity' => (float) $c->capacity_kg,
                'tunnel' => $c->tunnel->name ?? '-',
                'status' => $c->status,
                'slot' => $slotSuggestionByCart[$c->id] ?? null,
            ])->values();
            $activeCartsJs = $cartsJs->whereIn('id', $activeAssignedCartIds ?? [])->values();
            $itemsJs = $order->items->map(fn ($i) => [
                'id' => (int) $i->id,
                'fish' => $i->fishType->name ?? 'Type non specifie',
                'weight' => (float) $i->output_weight,
            ])->values();
            $ownCartsJs = collect($ownAssignments ?? [])->map(fn ($a) => [
                'cart_id' => (int) $a->cart_id,
                'code' => $a->cart->code ?? ('CH-'.$a->cart_id),
                'capacity' => (float) ($a->cart->capacity_kg ?? 0),
                'tunnel_id' => $a->tunnel_id ? (int) $a->tunnel_id : null,
                'slot' => $activeSlotMap[$a->cart_id]['slot'] ?? null,
            ])->values();
        @endphp

        {{-- Frais de traitement : un seul prix par kg pour toute la quantite triee --}}
        @if($order->items->isNotEmpty())
        @php
            $priceLocked = $order->invoice && in_array($order->invoice->status, ['paid', 'cancelled'], true);
            $canEditPrices = ! $priceLocked && auth()->user()->can('processing.prices');
            $sortedKg = (float) $order->items->sum('output_weight');
            $currentPrice = (float) ($order->price ?: $order->items->max('price_per_kg'));
        @endphp
        <form method="POST" action="{{ route('processing.prices', $order) }}"
              x-data="{ price: {{ $currentPrice }}, kg: {{ $sortedKg }},
                        fmt(n) { return Number(n || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); } }"
              class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border-2 border-sky-200 dark:border-sky-900 p-5">
            @csrf
            <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-4"><i class="fas fa-tags mr-2 text-sky-500"></i>Frais de traitement</h3>

            @error('price')<div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">{{ $message }}</div>@enderror
            @if($priceLocked)
            <div class="mb-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600"><i class="fas fa-lock mr-1"></i>Facture {{ $order->invoice->invoice_number }} {{ $order->invoice->status === 'paid' ? 'payée' : 'annulée' }} : prix figé.</div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center">
                <div class="rounded-xl bg-slate-50 dark:bg-slate-700/40 p-3">
                    <p class="text-xs text-slate-500">Quantité triée (tous types)</p>
                    <p class="text-xl font-bold text-slate-800 dark:text-white"><span x-text="fmt(kg)"></span> kg</p>
                </div>
                <div class="rounded-xl p-3 {{ $canEditPrices ? 'border-2 border-sky-300 bg-sky-50/50' : 'bg-slate-50' }}">
                    <p class="text-xs text-slate-500 mb-1">Frais de traitement par kg</p>
                    @if($canEditPrices)
                    <div class="relative">
                        <input type="number" step="0.01" min="0" name="price" x-model.number="price" required
                               class="w-full pl-3 pr-16 py-2 border border-slate-200 rounded-lg text-lg font-bold dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">MRU/kg</span>
                    </div>
                    @else
                    <p class="text-xl font-bold text-slate-800 dark:text-white"><span x-text="fmt(price)"></span> MRU/kg</p>
                    @endif
                </div>
                <div class="rounded-xl bg-sky-600 text-white p-3">
                    <p class="text-xs text-sky-100">Total traitement</p>
                    <p class="text-2xl font-extrabold"><span x-text="fmt(kg * (Number(price) || 0))"></span> MRU</p>
                    <p class="text-[11px] text-sky-100"><span x-text="fmt(kg)"></span> kg × <span x-text="fmt(price)"></span></p>
                </div>
            </div>

            @if($canEditPrices)
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <button type="submit" class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-sm font-semibold"><i class="fas fa-check mr-1"></i> Enregistrer le prix</button>
                <span x-show="!(Number(price) > 0)" class="text-xs text-amber-700"><i class="fas fa-triangle-exclamation mr-1"></i>Prix non renseigné</span>
                <span class="text-xs text-slate-500">La facture de traitement et la fiche client PDF sont mises à jour.</span>
            </div>
            @endif
        </form>
        @endif

        {{-- ETAPE 1 : choix des chariots --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-white">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-600 text-white text-xs mr-2">1</span>Choix des chariots
                </h3>
                <span class="text-xs text-slate-500">Poisson à charger : <b>{{ number_format($targetWeight ?? 0, 2, ',', ' ') }} kg</b></span>
            </div>

            @if($errors->has('cart_ids'))
            <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">{{ $errors->first('cart_ids') }}</div>
            @endif

            @if($hasActiveAssignments)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3">
                <div class="text-xs text-indigo-800">
                    <p class="font-semibold mb-1">{{ count($activeAssignedCartIds) }} chariot(s) affecté(s) — {{ number_format($activeCartsJs->sum('capacity'), 0, ',', ' ') }} kg de capacité</p>
                    <div class="flex flex-wrap gap-1">
                        @foreach($activeCartsJs as $c)
                        <span class="px-2 py-0.5 rounded-md bg-white border border-indigo-200 font-mono">{{ $c['code'] }}</span>
                        @endforeach
                    </div>
                </div>
                <form method="POST" action="{{ route('processing.cancel-carts', $order) }}">
                    @csrf
                    <button type="submit" class="px-3 py-2 bg-white border border-red-200 text-red-600 hover:bg-red-50 rounded-xl text-xs font-semibold"
                            onclick="return confirm('Annuler l\'affectation actuelle des chariots ? La répartition enregistrée sera aussi vidée.')">
                        <i class="fas fa-rotate-left mr-1"></i> Changer les chariots
                    </button>
                </form>
            </div>
            @elseif($order->cartAssignments->where('status', 'completed')->isNotEmpty())
            @php $released = $order->cartAssignments->where('status', 'completed'); @endphp
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-800">
                <p class="font-semibold mb-1"><i class="fas fa-circle-check mr-1"></i>{{ $released->count() }} chariot(s) sorti(s) du tunnel — dernière sortie le {{ $released->max('released_at')?->format('d/m/Y H:i') }}</p>
                <div class="flex flex-wrap gap-1">
                    @foreach($released as $a)
                    <span class="px-2 py-0.5 rounded-md bg-white border border-emerald-200 font-mono">{{ $a->cart->code ?? 'CH-'.$a->cart_id }}</span>
                    @endforeach
                </div>
            </div>
            @elseif($cartsJs->isEmpty())
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">Aucun chariot disponible pour l'instant.</div>
            @else
            <form method="POST" action="{{ route('processing.assign-carts', $order) }}"
                  x-data="cartPicker(@js($cartsJs), @js(array_map('intval', old('cart_ids', $preselectedCartIds ?? []))), {{ (float) ($targetWeight ?? 0) }})">
                @csrf
                <template x-for="id in selected" :key="id"><input type="hidden" name="cart_ids[]" :value="id"></template>

                {{-- Jauge capacite --}}
                <div class="rounded-xl border p-3 mb-4" :class="missing > 0 ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50'">
                    <div class="flex items-center justify-between text-xs font-semibold mb-2">
                        <span :class="missing > 0 ? 'text-amber-800' : 'text-emerald-800'">
                            <span x-text="selected.length"></span> chariot(s) · <span x-text="fmt(capacity)"></span> kg / <span x-text="fmt(target)"></span> kg
                        </span>
                        <span x-show="missing > 0" class="text-amber-700">Il manque <span x-text="fmt(missing)"></span> kg</span>
                        <span x-show="missing <= 0 && selected.length" class="text-emerald-700"><i class="fas fa-check mr-1"></i>Capacité suffisante (marge <span x-text="fmt(-missing)"></span> kg)</span>
                    </div>
                    <div class="h-2.5 rounded-full bg-white overflow-hidden">
                        <div class="h-full rounded-full transition-all" :class="missing > 0 ? 'bg-amber-500' : 'bg-emerald-500'" :style="`width:${coverage}%`"></div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 mb-3">
                    <div class="relative flex-1 min-w-[10rem]">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" x-model="search" placeholder="Rechercher un chariot..." class="w-full pl-8 pr-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                    </div>
                    <button type="button" @click="auto()" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold"><i class="fas fa-wand-magic-sparkles mr-1"></i> Choix automatique</button>
                    <button type="button" @click="selected = []" class="px-3 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl text-xs font-semibold">Vider</button>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-2">
                    <template x-for="cart in visible" :key="cart.id">
                        <button type="button" @click="toggle(cart.id)"
                                class="relative text-left rounded-xl border-2 p-3 transition-all"
                                :class="isSelected(cart.id) ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 shadow-sm' : 'border-slate-200 hover:border-slate-300 dark:border-slate-700'">
                            <i class="fas absolute top-2 right-2 text-sm" :class="isSelected(cart.id) ? 'fa-circle-check text-emerald-600' : 'fa-circle text-slate-200'"></i>
                            <p class="font-mono font-bold text-slate-800 dark:text-white" x-text="cart.code"></p>
                            <p class="text-xs text-slate-500"><span x-text="fmt(cart.capacity)"></span> kg</p>
                        </button>
                    </template>
                </div>
                <p x-show="!visible.length" class="text-xs text-slate-400 text-center py-4">Aucun chariot ne correspond.</p>

                <div class="mt-4 flex items-center gap-3">
                    <button type="submit" :disabled="!selected.length" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-40 text-white rounded-xl text-sm font-semibold">
                        <i class="fas fa-check mr-1"></i> Valider les chariots
                    </button>
                    <span class="text-xs text-slate-500">Cliquez sur un chariot pour le sélectionner ou le retirer.</span>
                </div>
            </form>
            @endif
        </div>

        {{-- ETAPE 2 : poissons -> chariots --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-white">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-orange-500 text-white text-xs mr-2">2</span>Poissons dans les chariots
                </h3>
                <span class="text-xs text-slate-500">À valider avant le bon de chargement</span>
            </div>

            @if($errors->has('distribution'))
            <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">{{ $errors->first('distribution') }}</div>
            @endif

            @if($order->items->count() === 0)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">Aucun type de poisson saisi en phase 2 pour faire la répartition.</div>
            @elseif($activeCartsJs->isEmpty())
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">Validez d'abord les chariots (étape 1).</div>
            @elseif($distributionIsValidated)
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 mb-3">
                <i class="fas fa-check-circle mr-2"></i> Répartition validée. Pour la refaire, cliquez sur « Changer les chariots » à l'étape 1.
            </div>
            <a href="{{ route('processing.worker-sheet-pdf', $order) }}" target="_blank" rel="noopener" class="inline-flex px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-xl text-sm font-semibold">
                <i class="fas fa-print mr-1"></i> Imprimer Bon de Chargement
            </a>
            @else
            <form method="POST" action="{{ route('processing.distribution', $order) }}"
                  x-data="distributionBoard(@js($itemsJs), @js($activeCartsJs), @js((object) ($itemCartDistribution ?? [])))">
                @csrf
                <template x-for="p in pairs" :key="p.key">
                    <input type="hidden" :name="`distribution[${p.item}][]`" :value="p.cart">
                </template>

                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <button type="button" @click="auto()" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold"><i class="fas fa-wand-magic-sparkles mr-1"></i> Répartir automatiquement</button>
                    <button type="button" @click="clear()" class="px-3 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl text-xs font-semibold">Tout effacer</button>
                    <span class="text-xs text-slate-500">Pour chaque poisson, cliquez sur les chariots où le charger (dans l'ordre de remplissage).</span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                    {{-- Poissons --}}
                    <div class="lg:col-span-3 space-y-3">
                        <template x-for="item in items" :key="item.id">
                            <div class="rounded-xl border p-3" :class="left(item.id) > 0.009 ? 'border-amber-200' : 'border-emerald-200 bg-emerald-50/40'">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-sm font-semibold text-slate-700 dark:text-white"><i class="fas fa-fish text-cyan-500 mr-1"></i><span x-text="item.fish"></span></p>
                                    <p class="text-xs">
                                        <span class="font-semibold" :class="left(item.id) > 0.009 ? 'text-amber-600' : 'text-emerald-600'" x-text="fmt(item.weight - left(item.id))"></span>
                                        <span class="text-slate-400">/ <span x-text="fmt(item.weight)"></span> kg</span>
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="cart in carts" :key="cart.id">
                                        <button type="button" @click="toggle(item.id, cart.id)"
                                                class="px-2.5 py-1 rounded-lg border text-xs font-mono font-semibold transition-all"
                                                :class="has(item.id, cart.id) ? 'bg-orange-500 border-orange-500 text-white' : (free(cart.id) <= 0 ? 'border-slate-200 text-slate-300' : 'border-slate-200 text-slate-600 hover:border-orange-300')">
                                            <span x-show="has(item.id, cart.id)" x-text="(dist[item.id].indexOf(cart.id) + 1) + '.'"></span>
                                            <span x-text="cart.code"></span>
                                        </button>
                                    </template>
                                </div>
                                <p x-show="left(item.id) > 0.009" class="mt-2 text-[11px] text-amber-700"><i class="fas fa-triangle-exclamation mr-1"></i>Reste <span x-text="fmt(left(item.id))"></span> kg à placer</p>
                            </div>
                        </template>
                    </div>

                    {{-- Remplissage des chariots --}}
                    <div class="lg:col-span-2 space-y-2">
                        <p class="text-xs font-semibold text-slate-500 uppercase">Remplissage des chariots</p>
                        <template x-for="cart in carts" :key="cart.id">
                            <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-2.5">
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="font-mono font-bold text-slate-700 dark:text-white" x-text="cart.code"></span>
                                    <span class="text-slate-500"><span x-text="fmt(cart.capacity - free(cart.id))"></span> / <span x-text="fmt(cart.capacity)"></span> kg</span>
                                </div>
                                <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full bg-orange-500 transition-all" :style="`width:${cart.capacity > 0 ? Math.min(100, (cart.capacity - free(cart.id)) / cart.capacity * 100) : 0}%`"></div>
                                </div>
                                <div class="mt-1 flex flex-wrap gap-1">
                                    <template x-for="(load, idx) in allocation.perCart[cart.id]" :key="idx">
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-orange-50 text-orange-700"><span x-text="load.fish"></span> <span x-text="fmt(load.kg)"></span> kg</span>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button type="submit" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-xl text-sm font-semibold">
                        <i class="fas fa-check mr-1"></i> Valider la répartition
                    </button>
                    <span x-show="unplaced > 0.009" class="text-xs text-amber-700"><i class="fas fa-triangle-exclamation mr-1"></i><span x-text="fmt(unplaced)"></span> kg non placés : ils seront ajoutés au dernier chariot du bon de chargement.</span>
                </div>
            </form>
            @endif
        </div>

        {{-- ETAPE 3 : chariots -> tunnels --}}
        @if($ownCartsJs->isNotEmpty())
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-white">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-white text-xs mr-2">3</span>Chariots dans les tunnels
                </h3>
                <span class="text-xs text-slate-500">Cliquez sur un tunnel pour y placer le chariot</span>
            </div>

            @php $inTunnel = collect($ownAssignments ?? [])->filter(fn ($a) => $a->tunnel_id && $a->assigned_at); @endphp
            @if($inTunnel->isNotEmpty())
            <div class="mb-4">
                <p class="text-xs font-semibold text-slate-500 uppercase mb-2"><i class="fas fa-stopwatch mr-1"></i>Temps dans le tunnel</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-2">
                    @foreach($inTunnel as $a)
                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 px-3 py-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-mono font-bold text-slate-700 dark:text-white">{{ $a->cart->code ?? 'CH-'.$a->cart_id }}</span>
                            <span class="text-slate-400 truncate ml-2">{{ $a->tunnel->name ?? '' }}</span>
                        </div>
                        @include('partials.tunnel-timer', ['since' => $a->assigned_at])
                        <p class="text-[10px] text-slate-400">Entré le {{ $a->assigned_at->format('d/m/Y à H:i') }}</p>
                    </div>
                    @endforeach
                </div>
                <div class="mt-3">@include('processing._release-button', ['order' => $order])</div>
            </div>
            @endif

            @if($errors->has('placements'))
            <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">{{ $errors->first('placements') }}</div>
            @endif

            @if($tunnelOptions->isEmpty())
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">Aucun tunnel actif.</div>
            @else
            <form method="POST" action="{{ route('processing.place-tunnels', $order) }}" x-data="tunnelBoard(@js($tunnelOptions), @js($ownCartsJs))">
                @csrf
                <template x-for="cart in carts" :key="cart.cart_id">
                    <input type="hidden" :name="`placements[${cart.cart_id}]`" :value="placement[cart.cart_id] ?? ''">
                </template>

                {{-- Occupation des tunnels --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-2 mb-4">
                    <template x-for="t in tunnels" :key="t.id">
                        <div class="rounded-xl border p-3" :class="over(t) ? 'border-red-300 bg-red-50' : 'border-slate-200 dark:border-slate-700'">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-slate-700 dark:text-white" x-text="t.name"></p>
                                    <p class="text-[11px] text-slate-400" x-text="t.cold_room"></p>
                                </div>
                                <button type="button" @click="allIn(t)" :disabled="!fitsAll(t)" class="text-[11px] px-2 py-1 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 disabled:opacity-40 font-semibold whitespace-nowrap">Tout ici</button>
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                <div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden flex">
                                    <div class="h-full bg-slate-400" :style="`width:${pct(t, t.occupied_others)}%`" title="Autres traitements"></div>
                                    <div class="h-full bg-indigo-500" :style="`width:${pct(t, countIn(t.id))}%`" title="Ce traitement"></div>
                                </div>
                                <span class="text-[11px] text-slate-500 whitespace-nowrap" x-text="t.capacity > 0 ? `${used(t)}/${t.capacity}` : `${used(t)} (illimité)`"></span>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Placement de chaque chariot --}}
                <div class="space-y-2">
                    <template x-for="cart in carts" :key="cart.cart_id">
                        <div class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 px-3 py-2">
                            <div class="w-32">
                                <p class="font-mono font-bold text-sm text-slate-700 dark:text-white" x-text="cart.code"></p>
                                <p class="text-[11px] text-slate-400"><span x-text="fmt(cart.capacity)"></span> kg<span x-show="cart.slot"> · E<span x-text="cart.slot"></span></span></p>
                            </div>
                            <div class="flex flex-wrap gap-1.5 flex-1">
                                <template x-for="t in tunnels" :key="t.id">
                                    <button type="button" @click="place(cart.cart_id, t.id)" :disabled="!canPlace(t, cart.cart_id)"
                                            class="px-2.5 py-1 rounded-lg border text-xs font-semibold transition-all disabled:opacity-30 disabled:cursor-not-allowed"
                                            :class="placement[cart.cart_id] === t.id ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-slate-200 text-slate-600 hover:border-indigo-300'"
                                            x-text="t.name"></button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <button type="submit" :disabled="!dirty || unplaced > 0" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 text-white rounded-xl text-sm font-semibold">
                        <i class="fas fa-check mr-1"></i> Enregistrer le placement
                    </button>
                    <span x-show="!dirty" class="text-xs text-slate-500">Placement actuel enregistré.</span>
                    <span x-show="unplaced > 0" class="text-xs text-amber-700"><span x-text="unplaced"></span> chariot(s) sans tunnel.</span>
                </div>
            </form>
            @endif
        </div>
        @endif

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-400">Entrée totale</p>
                <p class="mt-2 text-2xl font-bold text-slate-800 dark:text-white">{{ number_format($totalInputWeight, 2) }} kg</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-400">Stockable</p>
                <p class="mt-2 text-2xl font-bold text-emerald-600">{{ number_format($totalOutputWeight, 2) }} kg</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-400">Déchets / pertes</p>
                <p class="mt-2 text-2xl font-bold text-red-600">{{ number_format($totalWasteWeight, 2) }} kg</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-400">Taux de déchets</p>
                <p class="mt-2 text-2xl font-bold text-amber-600">{{ number_format($wasteRate, 1) }}%</p>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-4"><i class="fas fa-weight-scale mr-2 text-primary-500"></i>Synthèse globale</h3>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="rounded-xl bg-slate-50 dark:bg-slate-700/40 p-4">
                    <dt class="text-slate-400 mb-1">Poids reçu total</dt>
                    <dd class="text-xl font-bold text-slate-800 dark:text-white">{{ number_format($totalInputWeight, 2) }} kg</dd>
                </div>
                <div class="rounded-xl bg-emerald-50 dark:bg-emerald-950/20 p-4">
                    <dt class="text-emerald-600 mb-1">Poids stockable</dt>
                    <dd class="text-xl font-bold text-emerald-600">{{ number_format($totalOutputWeight, 2) }} kg</dd>
                </div>
                <div class="rounded-xl bg-red-50 dark:bg-red-950/20 p-4">
                    <dt class="text-red-600 mb-1">Déchets / pertes</dt>
                    <dd class="text-xl font-bold text-red-600">{{ number_format($totalWasteWeight, 2) }} kg</dd>
                </div>
                <div class="rounded-xl bg-slate-50 dark:bg-slate-700/40 p-4">
                    <dt class="text-slate-400 mb-1">Retour client</dt>
                    <dd class="text-xl font-bold text-slate-800 dark:text-white">{{ number_format($totalReturnedWeight, 2) }} kg</dd>
                </div>
            </dl>
        </div>

        @if($totalWasteWeight > 0)
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700 dark:bg-red-950/20 dark:border-red-900 dark:text-red-300">
            <i class="fas fa-triangle-exclamation mr-2"></i>
            Les déchets sont saisis séparément: ce lot contient {{ number_format($totalWasteWeight, 2) }} kg de pertes.
        </div>
        @endif

        @if($order->items->count() > 0)
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-white"><i class="fas fa-fish mr-2 text-cyan-500"></i>Détail par type</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-500 uppercase">
                        <tr>
                            <th class="px-4 py-2 text-left">Type</th>
                            <th class="px-4 py-2 text-right">Entrée(kg)</th>
                            <th class="px-4 py-2 text-right">Sortie(kg)</th>
                            <th class="px-4 py-2 text-right">Déchets / pertes</th>
                            <th class="px-4 py-2 text-right">Rendement</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                        @foreach($order->items as $item)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-700 dark:text-white">{{ $item->fishType->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($item->input_weight, 2) }}</td>
                            <td class="px-4 py-3 text-right text-emerald-600 font-semibold">{{ number_format($item->output_weight, 2) }}</td>
                            <td class="px-4 py-3 text-right text-red-500 font-semibold">{{ number_format($item->waste_weight, 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                @if($item->input_weight > 0)
                                    {{ number_format($item->output_weight / $item->input_weight * 100, 1) }}%
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm text-slate-600 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300">
            Ce traitement est saisi en mode global uniquement: aucun détail par type n'a ete enregistre.
        </div>
        @endif

        {{-- Packaging Orders --}}
        @if($order->packagingOrders->count() > 0)
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-white"><i class="fas fa-box mr-2 text-purple-500"></i>Ordres de Cartonnage</h3>
            </div>
            <div class="divide-y divide-slate-50 dark:divide-slate-700">
                @foreach($order->packagingOrders as $pkg)
                <div class="flex items-center justify-between px-5 py-3">
                    <a href="{{ route('packaging.show', $pkg) }}" class="font-mono text-sm font-semibold text-primary-600 hover:underline">{{ $pkg->reference }}</a>
                    <span class="text-xs text-slate-500">{{ $pkg->created_at?->format('d/m/Y') ?? '—' }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="space-y-5">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-4"><i class="fas fa-info-circle mr-2 text-primary-500"></i>Résumé</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-400">Statut</dt>
                    <dd><span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ match($order->status) {
                        'pending'=>'bg-slate-100 text-slate-600','processing'=>'bg-amber-100 text-amber-700',
                        'completed'=>'bg-emerald-100 text-emerald-700','cancelled'=>'bg-red-100 text-red-600',default=>'bg-slate-100 text-slate-500'
                    } }}">{{ match($order->status) { 'pending'=>'En attente','processing'=>'En cours','completed'=>'Complété','cancelled'=>'Annulé',default=>$order->status } }}</span></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400">Facturation</dt>
                    <dd class="text-slate-700 dark:text-white">{{ ucfirst($order->billing_method) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400">Déchets / pertes</dt>
                    <dd class="font-semibold text-red-600">{{ number_format($totalWasteWeight, 2) }} kg</dd>
                </div>
                @if($order->price)
                <div class="flex justify-between">
                    <dt class="text-slate-400">Prix</dt>
                    <dd class="font-bold text-slate-700 dark:text-white">{{ number_format($order->price, 2, ',', ' ') }} MRU</dd>
                </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-slate-400">Début</dt>
                    <dd class="text-slate-700 dark:text-white">{{ $order->start_date?->format('d/m/Y') ?? '—' }}</dd>
                </div>
                @if($order->end_date)
                <div class="flex justify-between">
                    <dt class="text-slate-400">Fin</dt>
                    <dd class="text-slate-700 dark:text-white">{{ $order->end_date?->format('d/m/Y') ?? '—' }}</dd>
                </div>
                @endif
                @if($order->arrival)
                <div class="flex justify-between">
                    <dt class="text-slate-400">Arrivage</dt>
                    <dd><a href="{{ route('arrivals.show', $order->arrival) }}" class="text-primary-600 font-mono text-xs hover:underline">{{ $order->arrival->reference }}</a></dd>
                </div>
                @endif
            </dl>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/affectation.js') }}?v={{ filemtime(public_path('js/affectation.js')) }}"></script>
@endpush
