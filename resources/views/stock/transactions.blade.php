@extends('layouts.app')
@section('title', 'Suivi Transactions Clients')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Suivi Transactions Clients</h1>
        <p class="text-sm text-slate-500">Vue globale des mouvements de stock par client</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('stock.transactions.excel', request()->query()) }}" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm">
            <i class="fas fa-file-excel"></i> Excel
        </a>
        <a href="{{ route('stock.index') }}" class="inline-flex items-center gap-2 bg-slate-200 hover:bg-slate-300 text-slate-700 px-4 py-2 rounded-xl text-sm font-semibold shadow-sm">
            <i class="fas fa-arrow-left"></i> Stock
        </a>
    </div>
</div>

<div class="bg-white dark:bg-slate-800 rounded-2xl p-4 mb-5 shadow-sm border border-slate-100 dark:border-slate-700">
    <form method="GET" class="flex flex-wrap gap-3">
        <select name="client_id" class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">Tous clients</option>
            @foreach($clients as $client)
            <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
            @endforeach
        </select>

        <select name="movement_type" class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">Tous mouvements</option>
            <option value="in" {{ request('movement_type') === 'in' ? 'selected' : '' }}>Entrée</option>
            <option value="processing_in" {{ request('movement_type') === 'processing_in' ? 'selected' : '' }}>Entrée après traitement</option>
            <option value="out" {{ request('movement_type') === 'out' ? 'selected' : '' }}>Sortie</option>
            <option value="transfer" {{ request('movement_type') === 'transfer' ? 'selected' : '' }}>Transfert</option>
        </select>

        <input type="date" name="start_date" value="{{ request('start_date') }}" class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500" title="Date début">
        <input type="date" name="end_date" value="{{ request('end_date') }}" class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500" title="Date fin">

        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-xl text-sm font-semibold"><i class="fas fa-filter mr-1"></i> Filtrer</button>
        <a href="{{ route('stock.transactions') }}" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold"><i class="fas fa-rotate-left mr-1"></i> Reset</a>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Transactions</p>
        <p class="text-3xl font-bold text-slate-800 dark:text-white">{{ number_format($summary['total_transactions']) }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Entrées</p>
        <p class="text-2xl font-bold text-emerald-600">{{ number_format($summary['in_cartons']) }} cartons</p>
        <p class="text-xs text-slate-500">{{ number_format($summary['in_weight'], 2) }} kg</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Après traitement</p>
        <p class="text-2xl font-bold text-violet-600">{{ number_format($summary['processing_in_cartons']) }} cartons</p>
        <p class="text-xs text-slate-500">{{ number_format($summary['processing_in_weight'], 2) }} kg</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Sorties</p>
        <p class="text-2xl font-bold text-red-600">{{ number_format($summary['out_cartons']) }} cartons</p>
        <p class="text-xs text-slate-500">{{ number_format($summary['out_weight'], 2) }} kg</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Transferts</p>
        <p class="text-2xl font-bold text-indigo-600">{{ number_format($summary['transfer_cartons']) }} cartons</p>
        <p class="text-xs text-slate-500">{{ number_format($summary['transfer_weight'], 2) }} kg</p>
    </div>
</div>

<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-left">Client</th>
                    <th class="px-4 py-3 text-left">Mouvement</th>
                    <th class="px-4 py-3 text-left">Origine</th>
                    <th class="px-4 py-3 text-left">Référence</th>
                    <th class="px-4 py-3 text-left">Poisson/Carton</th>
                    <th class="px-4 py-3 text-left">Chambre</th>
                    <th class="px-4 py-3 text-right">Cartons</th>
                    <th class="px-4 py-3 text-right">Poids (kg)</th>
                    <th class="px-4 py-3 text-left">Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                @forelse($transactions as $tx)
                @php
                    $origin = 'Entrée directe';
                    if ($tx->type === 'transfer') {
                        $origin = 'Transfert interne';
                    } elseif (optional($tx->stockBatch)->packaging_order_id) {
                        $origin = 'Depuis traitement';
                    }

                    $movementLabel = $tx->type === 'in'
                        ? (optional($tx->stockBatch)->packaging_order_id ? 'Entree apres traitement' : 'Entree')
                        : ($tx->type === 'out' ? 'Sortie' : 'Transfert');

                    $movementClass = $tx->type === 'in'
                        ? (optional($tx->stockBatch)->packaging_order_id ? 'bg-violet-100 text-violet-700' : 'bg-emerald-100 text-emerald-700')
                        : ($tx->type === 'out' ? 'bg-red-100 text-red-700' : 'bg-indigo-100 text-indigo-700');
                @endphp
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                    <td class="px-4 py-3 text-xs text-slate-500">{{ $tx->entry_date?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ optional($tx->stockBatch->client)->name ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $movementClass }}">
                            {{ $movementLabel }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $origin === 'Depuis traitement' ? 'bg-purple-100 text-purple-700' : ($origin === 'Transfert interne' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-700') }}">{{ $origin }}</span>
                    </td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $tx->reference ?? '-' }}</td>
                    <td class="px-4 py-3 text-xs">
                        <div class="font-semibold text-slate-700 dark:text-slate-200">{{ optional($tx->stockBatch->fishType)->name ?? '-' }}</div>
                        <div class="text-slate-400">{{ optional($tx->stockBatch->cartonType)->name ?? '-' }}</div>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ optional($tx->coldRoom)->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-right font-semibold {{ $tx->type === 'out' ? 'text-red-600' : 'text-emerald-600' }}">{{ $tx->type === 'out' ? '-' : '+' }}{{ number_format($tx->cartons_count) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($tx->weight_kg, 2) }}</td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ $tx->notes ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center py-12 text-slate-400">
                        <i class="fas fa-arrows-rotate text-3xl mb-2 block"></i>
                        <p>Aucune transaction trouvée</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-700">{{ $transactions->links() }}</div>
</div>
@endsection
