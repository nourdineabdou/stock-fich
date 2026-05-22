@extends('layouts.app')
@section('title', 'Lot '.$clientStockBatch->lot_number)
@section('content')
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('stock.index') }}" class="p-2 text-slate-500 hover:bg-slate-100 rounded-xl"><i class="fas fa-arrow-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white font-mono">{{ $clientStockBatch->lot_number }}</h1>
            <p class="text-sm text-slate-500">{{ $clientStockBatch->client->name ?? '-' }} · {{ $clientStockBatch->fishType->name ?? '-' }}</p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('stock.receipt', ['clientStockBatch' => $clientStockBatch->id]) }}" target="_blank" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
            <i class="fas fa-file-pdf"></i> Recu entree PDF
        </a>
        @if($clientStockBatch->client_id)
        <a href="{{ route('stock.client-statement', $clientStockBatch->client_id) }}" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
            <i class="fas fa-file-lines"></i> Suivi client
        </a>
        @endif
    </div>
</div>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">
        {{-- Stock movements --}}
        @if($clientStockBatch->storageEntries && $clientStockBatch->storageEntries->count() > 0)
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-white"><i class="fas fa-history mr-2 text-slate-400"></i>Mouvements</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-2 text-left">Type</th>
                        <th class="px-4 py-2 text-right">Qté</th>
                        <th class="px-4 py-2 text-left">Date</th>
                        <th class="px-4 py-2 text-left">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                    @foreach($clientStockBatch->storageEntries as $mv)
                    <tr>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $mv->type === 'in' ? 'bg-emerald-100 text-emerald-700' : ($mv->type === 'out' ? 'bg-red-100 text-red-600' : 'bg-indigo-100 text-indigo-700') }}">
                                {{ $mv->type === 'in' ? 'Entrée' : ($mv->type === 'out' ? 'Sortie' : 'Transfert') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-bold {{ $mv->type === 'out' ? 'text-red-500' : 'text-emerald-600' }}">{{ $mv->type === 'out' ? '-' : '+' }}{{ $mv->cartons_count }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $mv->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-slate-400 text-xs">{{ $mv->notes }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    <div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-4"><i class="fas fa-info-circle mr-2 text-primary-500"></i>Détails</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-400">Client</dt>
                    <dd class="font-medium text-slate-700 dark:text-white">{{ $clientStockBatch->client->name ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400">Poisson</dt>
                    <dd class="text-slate-700 dark:text-white">{{ $clientStockBatch->fishType->name ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400">Type carton</dt>
                    <dd class="text-slate-700 dark:text-white">{{ $clientStockBatch->cartonType->name ?? '-' }}</dd>
                </div>
                <div class="flex justify-between border-t border-slate-100 dark:border-slate-700 pt-3">
                    <dt class="text-slate-400">Total cartons</dt>
                    <dd class="font-bold text-slate-700 dark:text-white">{{ $clientStockBatch->cartons_count }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400">Disponibles</dt>
                    <dd class="font-bold text-emerald-600">{{ $clientStockBatch->available_cartons }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400">Statut</dt>
                    <dd><span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ match($clientStockBatch->status) {
                        'available'=>'bg-emerald-100 text-emerald-700','partial'=>'bg-amber-100 text-amber-700','depleted'=>'bg-red-100 text-red-600',default=>'bg-slate-100 text-slate-500'
                    } }}">{{ ucfirst($clientStockBatch->status) }}</span></dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection

