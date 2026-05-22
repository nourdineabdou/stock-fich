@extends('layouts.app')
@section('title', 'Suivi Stock - '.$client->name)
@section('content')
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('stock.index') }}" class="p-2 text-slate-500 hover:bg-slate-100 rounded-xl"><i class="fas fa-arrow-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Suivi Stock Client</h1>
            <p class="text-sm text-slate-500">{{ $client->name }}</p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('stock.client-statement.pdf', $client->id) }}?{{ request()->getQueryString() }}" target="_blank" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
            <i class="fas fa-file-pdf"></i> PDF
        </a>
        <a href="{{ route('stock.client-statement.excel', $client->id) }}?{{ request()->getQueryString() }}" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
            <i class="fas fa-file-excel"></i> Excel
        </a>
    </div>
</div>

{{-- Date Range Filter --}}
<div class="bg-white dark:bg-slate-800 rounded-2xl p-4 mb-6 shadow-sm border border-slate-100 dark:border-slate-700">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs mb-1.5 text-slate-500 uppercase">Du</label>
            <input type="date" name="start_date" value="{{ $startDate ?? '' }}" class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>
        <div>
            <label class="block text-xs mb-1.5 text-slate-500 uppercase">Au</label>
            <input type="date" name="end_date" value="{{ $endDate ?? '' }}" class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>
        <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-sm font-semibold">
            <i class="fas fa-filter mr-1"></i> Filtrer
        </button>
        @if($startDate || $endDate)
        <a href="{{ route('stock.client-statement', $client) }}" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-sm font-semibold">
            <i class="fas fa-times mr-1"></i> Réinitialiser
        </a>
        @endif
    </form>
</div>

<div class="bg-white dark:bg-slate-800 rounded-2xl p-4 mb-6 shadow-sm border border-slate-100 dark:border-slate-700">
    <div class="mb-3">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-white">Facture Chambre Froide Automatique</h3>
        <p class="text-xs text-slate-500">Le calcul se fait automatiquement selon vos parametres (kg, carton, jour, semaine, mois).</p>
    </div>
    <form method="POST" action="{{ route('invoices.storage-generate', $client) }}" class="grid grid-cols-1 md:grid-cols-6 gap-3">
        @csrf
        <div>
            <label class="block text-xs mb-1 text-slate-500">Du</label>
            <input type="date" name="start_date" value="{{ old('start_date', now()->startOfMonth()->format('Y-m-d')) }}" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white">
        </div>
        <div>
            <label class="block text-xs mb-1 text-slate-500">Au</label>
            <input type="date" name="end_date" value="{{ old('end_date', now()->format('Y-m-d')) }}" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white">
        </div>
        <div>
            <label class="block text-xs mb-1 text-slate-500">Unité</label>
            <select name="charge_unit" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                <option value="kg" {{ old('charge_unit') === 'kg' ? 'selected' : '' }}>Par kg</option>
                <option value="carton" {{ old('charge_unit') === 'carton' ? 'selected' : '' }}>Par carton</option>
            </select>
        </div>
        <div>
            <label class="block text-xs mb-1 text-slate-500">Fréquence</label>
            <select name="billing_frequency" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                <option value="day" {{ old('billing_frequency') === 'day' ? 'selected' : '' }}>Journalière</option>
                <option value="week" {{ old('billing_frequency', 'week') === 'week' ? 'selected' : '' }}>Hebdomadaire</option>
                <option value="month" {{ old('billing_frequency') === 'month' ? 'selected' : '' }}>Mensuelle</option>
            </select>
        </div>
        <div>
            <label class="block text-xs mb-1 text-slate-500">Prix unitaire (optionnel)</label>
            <input type="number" step="0.01" min="0" name="price" value="{{ old('price') }}" placeholder="Sinon règle tarifaire" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white">
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
                <i class="fas fa-file-invoice"></i> Générer Facture
            </button>
        </div>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Lots</p>
        <p class="text-3xl font-bold text-slate-800 dark:text-white">{{ $summary['total_lots'] }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Cartons entrés</p>
        <p class="text-3xl font-bold text-cyan-600">{{ number_format($summary['total_cartons']) }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Cartons retirés</p>
        <p class="text-3xl font-bold text-amber-600">{{ number_format($summary['delivered_cartons']) }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Disponible</p>
        <p class="text-3xl font-bold text-emerald-600">{{ number_format($summary['available_cartons']) }}</p>
        <p class="text-xs text-slate-400">{{ number_format($summary['available_weight'], 2) }} kg</p>
    </div>
</div>

<div class="space-y-5">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-white">Stock par type (poisson, carton, chambre froide)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-2 text-left">Contenu carton</th>
                        <th class="px-4 py-2 text-left">Type carton</th>
                        <th class="px-4 py-2 text-left">Chambre</th>
                        <th class="px-4 py-2 text-right">Lots</th>
                        <th class="px-4 py-2 text-right">Cartons dispo</th>
                        <th class="px-4 py-2 text-right">Poids dispo (kg)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                    @forelse($stockByType as $row)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200">{{ $row['fish'] }}</td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $row['carton'] }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $row['cold_room'] }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($row['lots']) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-emerald-600">{{ number_format($row['cartons']) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($row['weight'], 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Aucun stock disponible</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-white">Historique complet des transactions (entrées, sorties, transferts)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-2 text-left">Date</th>
                        <th class="px-4 py-2 text-left">Type</th>
                        <th class="px-4 py-2 text-left">Origine</th>
                        <th class="px-4 py-2 text-left">Référence</th>
                        <th class="px-4 py-2 text-left">Poisson/Carton</th>
                        <th class="px-4 py-2 text-left">Chambre</th>
                        <th class="px-4 py-2 text-right">Cartons</th>
                        <th class="px-4 py-2 text-right">Poids (kg)</th>
                        <th class="px-4 py-2 text-left">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                    @forelse($transactions as $tx)
                    <tr>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $tx->entry_date?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $tx->type === 'in' ? 'bg-emerald-100 text-emerald-700' : ($tx->type === 'out' ? 'bg-red-100 text-red-700' : 'bg-indigo-100 text-indigo-700') }}">
                                {{ $tx->type === 'in' ? 'Entrée' : ($tx->type === 'out' ? 'Sortie' : 'Transfert') }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $origin = 'Entrée directe';
                                if ($tx->stockBatch->packaging_order_id) {
                                    $origin = 'Depuis traitement';
                                } elseif ($tx->type === 'transfer') {
                                    $origin = 'Transfert';
                                }
                            @endphp
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold 
                                {{ $origin === 'Depuis traitement' ? 'bg-purple-100 text-purple-700' : 
                                   ($origin === 'Transfert' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-700') }}">
                                {{ $origin }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $tx->reference ?? '-' }}</td>
                        <td class="px-4 py-3 text-xs">
                            <div class="font-semibold text-slate-700 dark:text-slate-200">{{ $tx->stockBatch->fishType->name ?? '-' }}</div>
                            <div class="text-slate-400">{{ $tx->stockBatch->cartonType->name ?? '-' }}</div>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $tx->coldRoom->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-right font-semibold {{ $tx->type === 'out' ? 'text-red-600' : 'text-emerald-600' }}">{{ $tx->type === 'out' ? '-' : '+' }}{{ number_format($tx->cartons_count) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($tx->weight_kg, 2) }}</td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $tx->notes ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-slate-400">Aucune transaction enregistrée</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-white">Retraits clients (livraisons validées)</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-500 uppercase">
                <tr>
                    <th class="px-4 py-2 text-left">Référence</th>
                    <th class="px-4 py-2 text-left">Date</th>
                    <th class="px-4 py-2 text-right">Cartons</th>
                    <th class="px-4 py-2 text-center">Statut</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                @forelse($deliveries as $delivery)
                <tr>
                    <td class="px-4 py-3 font-mono text-xs">
                        <a href="{{ route('deliveries.show', $delivery) }}" class="text-primary-600 hover:underline">{{ $delivery->reference }}</a>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ $delivery->delivery_date?->format('d/m/Y') ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">{{ $delivery->items->sum('cartons_count') }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $delivery->status === 'delivered' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $delivery->status }}</span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">Aucun retrait</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
