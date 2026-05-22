@extends('layouts.app')
@section('title', $coldRoom->name)
@section('content')
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('cold-rooms.index') }}" class="p-2 text-slate-500 hover:bg-slate-100 rounded-xl"><i class="fas fa-arrow-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">{{ $coldRoom->name }}</h1>
            <p class="text-sm text-slate-500">{{ ucfirst($coldRoom->type) }}
                @if($coldRoom->temperature !== null) · {{ $coldRoom->temperature }}°C @endif
            </p>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('cold-rooms.edit', $coldRoom) }}"
           class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm">
            <i class="fas fa-pen"></i> Modifier
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-5">
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Clients</p>
        <p class="text-3xl font-bold text-slate-800 dark:text-white">{{ number_format($summary['clients']) }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Lots actifs</p>
        <p class="text-3xl font-bold text-slate-800 dark:text-white">{{ number_format($summary['lots']) }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Cartons disponibles</p>
        <p class="text-3xl font-bold text-emerald-600">{{ number_format($summary['cartons']) }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Types de cartons</p>
        <p class="text-3xl font-bold text-indigo-600">{{ number_format($summary['carton_types']) }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Poids disponible</p>
        <p class="text-3xl font-bold text-cyan-600">{{ number_format($summary['weight'], 2) }}</p>
        <p class="text-xs text-slate-400">kg</p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
            <h2 class="text-sm font-semibold text-slate-700 dark:text-white">Stock par client</h2>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-500 uppercase">
                <tr>
                    <th class="px-4 py-2 text-left">Client</th>
                    <th class="px-4 py-2 text-right">Lots</th>
                    <th class="px-4 py-2 text-right">Cartons</th>
                    <th class="px-4 py-2 text-right">Poids (kg)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                @forelse($stockByClient as $row)
                <tr>
                    <td class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200">{{ $row['client_name'] }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($row['lots']) }}</td>
                    <td class="px-4 py-3 text-right font-bold text-emerald-600">{{ number_format($row['cartons']) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($row['weight'], 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">Aucun stock disponible dans cette chambre</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
            <h2 class="text-sm font-semibold text-slate-700 dark:text-white">Détail des lots stockés</h2>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-500 uppercase">
                <tr>
                    <th class="px-4 py-2 text-left">Lot</th>
                    <th class="px-4 py-2 text-left">Client / Contenu</th>
                    <th class="px-4 py-2 text-right">Cartons</th>
                    <th class="px-4 py-2 text-right">Poids (kg)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                @forelse($coldRoom->stockBatches as $batch)
                <tr>
                    <td class="px-4 py-3 font-mono text-xs">{{ $batch->lot_number }}</td>
                    <td class="px-4 py-3 text-xs">
                        <div class="font-semibold text-slate-700 dark:text-slate-200">{{ $batch->client->name ?? '-' }}</div>
                        <div class="text-slate-400">{{ $batch->fishType->name ?? '-' }} · {{ $batch->cartonType->name ?? '-' }}</div>
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-emerald-600">{{ number_format($batch->available_cartons) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($batch->available_weight, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">Aucun lot en stock</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
