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
        <a href="{{ route('processing.client-pdf', $order) }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
            <i class="fas fa-print"></i> Imprimer Facture Traitement
        </a>
        <a href="{{ route('invoices.create') }}?client_id={{ $order->client_id }}&processing_order_id={{ $order->id }}"
           class="inline-flex items-center gap-2 bg-sky-600 hover:bg-sky-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
            <i class="fas fa-file-invoice"></i> Facture Client
        </a>
        <a href="{{ route('packaging.create') }}?processing_order_id={{ $order->id }}" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
            <i class="fas fa-box"></i> Cartonnage
        </a>
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

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-white"><i class="fas fa-cart-flatbed mr-2 text-emerald-500"></i>Affectation Chariots (Phase 3)</h3>
                <span class="text-xs text-slate-500">Cible stockable: {{ number_format($targetWeight ?? 0, 2) }} kg</span>
            </div>

            @if($errors->has('cart_ids'))
            <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">{{ $errors->first('cart_ids') }}</div>
            @endif

            @php
                $hasActiveAssignments = !empty($activeAssignedCartIds ?? []);
            @endphp

            @if(($availableCartOptions ?? collect())->count() === 0)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">Aucun chariot disponible pour l'instant.</div>
            @elseif($hasActiveAssignments)
            <div class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-xs text-indigo-800">
                Les chariots sont deja valides et en tunnel. Pour refaire une affectation, annulez d'abord l'affectation actuelle.
            </div>

            <form method="POST" action="{{ route('processing.cancel-carts', $order) }}" class="mt-3">
                @csrf
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-semibold"
                        onclick="return confirm('Annuler l affectation actuelle des chariots ? Cette action va aussi vider la repartition enregistree.')">
                    <i class="fas fa-rotate-left mr-1"></i> Annuler Affectation
                </button>
            </form>
            @else
            <form method="POST" action="{{ route('processing.assign-carts', $order) }}">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($availableCartOptions as $cart)
                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-3 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700/30">
                        <input type="checkbox" name="cart_ids[]" value="{{ $cart->id }}" {{ in_array($cart->id, old('cart_ids', $preselectedCartIds ?? [])) ? 'checked' : '' }} class="mt-0.5">
                        <div class="text-sm">
                            <p class="font-semibold text-slate-700 dark:text-white">{{ $cart->code }}</p>
                            <p class="text-xs text-slate-500">Capacite: {{ number_format($cart->capacity_kg, 2) }} kg</p>
                            <p class="text-xs text-slate-500">Tunnel: {{ $cart->tunnel->name ?? '-' }}</p>
                            <p class="text-xs text-indigo-600">Emplacement suggere: E{{ $slotSuggestionByCart[$cart->id] ?? '?' }}</p>
                            <p class="text-xs {{ $cart->status === 'empty' ? 'text-emerald-600' : 'text-amber-600' }}">{{ $cart->status === 'empty' ? 'Disponible' : 'Deja affecte a cette fiche' }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold">
                        <i class="fas fa-check mr-1"></i> Valider Affectation
                    </button>
                    <span class="text-xs text-slate-500">Le systeme preselectionne une suggestion automatique, vous pouvez la modifier avant validation.</span>
                </div>
            </form>
            @endif
        </div>

        @if(!empty($activeAssignedCartIds ?? []))
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-3"><i class="fas fa-location-dot mr-2 text-indigo-500"></i>Suggestion Emplacements Tunnel</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                @foreach(collect($availableCartOptions ?? [])->whereIn('id', $activeAssignedCartIds) as $cart)
                <div class="rounded-xl border border-slate-200 px-3 py-2 text-xs dark:border-slate-700">
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $cart->code }}</span>
                    <span class="text-slate-500"> - {{ $cart->tunnel->name ?? '-' }} - Emplacement E{{ $slotSuggestionByCart[$cart->id] ?? '?' }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-white"><i class="fas fa-diagram-project mr-2 text-orange-500"></i>Repartition Type de Poisson vers Chariots</h3>
                <span class="text-xs text-slate-500">A valider avant impression fiche operateurs</span>
            </div>

            @if($errors->has('distribution'))
            <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">{{ $errors->first('distribution') }}</div>
            @endif

            @php
                $activeCarts = collect($availableCartOptions ?? [])->whereIn('id', $activeAssignedCartIds ?? [])->values();
            @endphp

            @if($order->items->count() === 0)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">Aucun type de poisson saisi en phase 2 pour faire la repartition.</div>
            @elseif($activeCarts->count() === 0)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">Affectez d'abord les chariots (Phase 3), puis repartissez les types.</div>
            @elseif($distributionIsValidated)
            <!-- Distribution is already validated -->
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 mb-3">
                <i class="fas fa-check-circle mr-2"></i> La répartition est validée. Vous pouvez imprimer le bon de chargement ou annuler l'affectation pour refaire la répartition.
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('processing.worker-sheet-pdf', $order) }}" target="_blank" rel="noopener" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-xl text-sm font-semibold">
                    <i class="fas fa-print mr-1"></i> Imprimer Bon de Chargement
                </a>
            </div>
            @else
            <!-- Distribution form (not yet validated) -->
            <div class="mb-3 flex items-center gap-2">
                <form method="POST" action="{{ route('processing.distribution.suggest', $order) }}">
                    @csrf
                    <button type="submit" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold">
                        <i class="fas fa-wand-magic-sparkles mr-1"></i> Suggérer automatiquement
                    </button>
                </form>
                <span class="text-xs text-slate-500">Le systeme propose une repartition selon les capacites des chariots.</span>
            </div>

            <form method="POST" action="{{ route('processing.distribution', $order) }}">
                @csrf
                <div class="space-y-3">
                    @foreach($order->items as $item)
                    @php
                        $selectedCarts = old('distribution.'.$item->id, $itemCartDistribution[$item->id] ?? []);
                    @endphp
                    <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-semibold text-slate-700 dark:text-white">{{ $item->fishType->name ?? 'Type non specifie' }}</p>
                            <p class="text-xs text-slate-500">{{ number_format($item->output_weight, 2) }} kg</p>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            @foreach($activeCarts as $cart)
                            <label class="flex items-center gap-2 text-xs rounded-lg border border-slate-200 px-2 py-1.5 dark:border-slate-700">
                                <input type="checkbox" name="distribution[{{ $item->id }}][]" value="{{ $cart->id }}" {{ in_array($cart->id, $selectedCarts) ? 'checked' : '' }}>
                                <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $cart->code }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <button type="submit" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-xl text-sm font-semibold">
                        <i class="fas fa-check mr-1"></i> Valider Repartition
                    </button>
                    <a href="{{ route('processing.worker-sheet-pdf', $order) }}" target="_blank" rel="noopener" class="px-4 py-2 border border-slate-300 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        <i class="fas fa-print mr-1"></i> Imprimer Bon de Chargement
                    </a>
                </div>
            </form>
            @endif
        </div>

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
                            <th class="px-4 py-2 text-right">Prix/kg</th>
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
                            <td class="px-4 py-3 text-right">{{ $item->price_per_kg ? number_format($item->price_per_kg, 2).' MRU' : '-' }}</td>
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

