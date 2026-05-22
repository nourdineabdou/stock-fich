@extends('layouts.app')
@section('title', 'Entrée '.$reference)
@section('content')
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('stock.index') }}" class="p-2 text-slate-500 hover:bg-slate-100 rounded-xl"><i class="fas fa-arrow-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white font-mono">{{ $reference }}</h1>
            <p class="text-sm text-slate-500">
                {{ $firstBatch->client?->name ?? '-' }} ·
                {{ $batches->count() }} ligne{{ $batches->count() > 1 ? 's' : '' }} ·
                {{ \Carbon\Carbon::parse($firstBatch->entry_date)->format('d/m/Y') }}
            </p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('stock.receipt', ['clientStockBatch' => $firstBatch->id]) }}" target="_blank"
           class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
            <i class="fas fa-file-pdf"></i> Reçu entrée PDF
        </a>
        @if($firstBatch->client_id)
        <a href="{{ route('stock.client-statement', $firstBatch->client_id) }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
            <i class="fas fa-file-lines"></i> Suivi client
        </a>
        @endif
    </div>
</div>

{{-- Summary cards --}}
@php
    $totalCartons   = $batches->sum('cartons_count');
    $totalAvail     = $batches->sum('available_cartons');
    $totalWeight    = $batches->sum('total_weight');
    $totalAvailW    = $batches->sum('available_weight');
@endphp
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Cartons entrés</p>
        <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ number_format($totalCartons) }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Disponibles</p>
        <p class="text-2xl font-bold text-emerald-600">{{ number_format($totalAvail) }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Poids total (kg)</p>
        <p class="text-2xl font-bold text-cyan-600">{{ number_format($totalWeight, 1) }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Poids dispo (kg)</p>
        <p class="text-2xl font-bold text-slate-700 dark:text-white">{{ number_format($totalAvailW, 1) }}</p>
    </div>
</div>

{{-- Lines table --}}
<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-white"><i class="fas fa-list mr-2 text-cyan-500"></i>Lignes de l'entrée</h3>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-500 uppercase tracking-wider">
            <tr>
                <th class="px-4 py-3 text-left">N° Lot</th>
                <th class="px-4 py-3 text-left">Poisson</th>
                <th class="px-4 py-3 text-left">Carton</th>
                <th class="px-4 py-3 text-left">Chambre</th>
                <th class="px-4 py-3 text-right">Cartons</th>
                <th class="px-4 py-3 text-right">Dispo</th>
                <th class="px-4 py-3 text-right">Poids/ctn</th>
                <th class="px-4 py-3 text-right">Poids total</th>
                <th class="px-4 py-3 text-center">Statut</th>
                <th class="px-4 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
            @foreach($batches as $batch)
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-600 dark:text-slate-300">{{ $batch->lot_number }}</td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-200 font-medium">{{ $batch->fishType?->name ?? '-' }}</td>
                <td class="px-4 py-3 text-slate-400 text-xs">{{ $batch->cartonType?->name ?? '-' }}</td>
                <td class="px-4 py-3">
                    @if($batch->coldRoom)
                    <span class="px-2 py-0.5 rounded-lg text-xs font-semibold bg-blue-100 text-blue-700">
                        <i class="fas fa-snowflake text-xs mr-1"></i>{{ $batch->coldRoom->name }}
                    </span>
                    @else
                    <span class="text-slate-400 text-xs">-</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right font-bold text-slate-700 dark:text-white">{{ $batch->cartons_count }}</td>
                <td class="px-4 py-3 text-right font-bold text-emerald-600">{{ $batch->available_cartons }}</td>
                <td class="px-4 py-3 text-right text-slate-500 text-xs">{{ $batch->weight_per_carton }} kg</td>
                <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ number_format($batch->total_weight, 1) }} kg</td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ match($batch->status) {
                        'available' => 'bg-emerald-100 text-emerald-700',
                        'partial'   => 'bg-amber-100 text-amber-700',
                        'empty'     => 'bg-red-100 text-red-600',
                        default     => 'bg-slate-100 text-slate-500'
                    } }}">{{ match($batch->status) {
                        'available' => 'Disponible',
                        'partial'   => 'Partiel',
                        'empty'     => 'Épuisé',
                        default     => $batch->status
                    } }}</span>
                </td>
                <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center gap-1">
                        <a href="{{ route('stock.show', $batch) }}" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg" title="Détails du lot"><i class="fas fa-eye text-xs"></i></a>
                        @if($batch->available_cartons > 0)
                        <button onclick="openTransferModal({{ $batch->id }}, '{{ addslashes($batch->lot_number) }}', {{ $batch->available_cartons }})"
                            class="p-1.5 text-purple-600 hover:bg-purple-50 rounded-lg" title="Transférer"><i class="fas fa-exchange-alt text-xs"></i></button>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot class="bg-slate-50 dark:bg-slate-700/30 border-t border-slate-200 dark:border-slate-600">
            <tr>
                <td colspan="4" class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Total</td>
                <td class="px-4 py-3 text-right font-bold text-slate-700 dark:text-white">{{ $totalCartons }}</td>
                <td class="px-4 py-3 text-right font-bold text-emerald-600">{{ $totalAvail }}</td>
                <td></td>
                <td class="px-4 py-3 text-right font-bold text-slate-700 dark:text-white">{{ number_format($totalWeight, 1) }} kg</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</div>

{{-- Transfer Modal --}}
<div id="transferModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6 w-full max-w-md">
        <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4"><i class="fas fa-exchange-alt mr-2 text-purple-500"></i>Transfert de Lot</h3>
        <form id="transferForm" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Lot: <span id="modalLotNumber" class="font-mono text-primary-600"></span></label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Chambre froide destination <span class="text-red-500">*</span></label>
                    <select name="cold_room_id" required class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Choisir...</option>
                        @foreach(\App\Models\ColdRoom::where('active', true)->orderBy('name')->get() as $room)
                        <option value="{{ $room->id }}">{{ $room->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Cartons à transférer</label>
                    <input type="number" name="cartons_count" id="modalQty" min="1" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <p class="text-xs text-slate-400 mt-1">Saisissez une quantité de cartons (max selon le lot).</p>
                </div>
            </div>
            <div class="flex gap-3 mt-5">
                <button type="submit" class="flex-1 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-semibold text-sm">Transférer</button>
                <button type="button" onclick="closeModal('transferModal')" class="flex-1 py-2.5 border border-slate-200 text-slate-600 rounded-xl text-sm">Annuler</button>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
function openTransferModal(batchId, lotNumber, maxQty) {
    document.getElementById('modalLotNumber').textContent = lotNumber;
    document.getElementById('modalQty').max = maxQty;
    document.getElementById('modalQty').placeholder = 'Max: ' + maxQty;
    document.getElementById('transferForm').action = `/stock/${batchId}/transfer`;
    openModal('transferModal');
}
</script>
@endpush
