@extends('layouts.app')
@section('title', 'Stock Client')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Stock Client</h1>
    <div class="flex items-center gap-2">
        @if(request('client_id'))
        <a href="{{ route('stock.client-statement', request('client_id')) }}" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm">
            <i class="fas fa-file-lines"></i> Suivi Client
        </a>
        @endif
        <a href="{{ route('stock.transactions') }}" class="inline-flex items-center gap-2 bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm">
            <i class="fas fa-arrows-rotate"></i> Transactions
        </a>
        <a href="{{ route('stock.export.pdf', request()->query()) }}" target="_blank" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm">
            <i class="fas fa-file-pdf"></i> PDF
        </a>
        <a href="{{ route('stock.export.excel', request()->query()) }}" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm">
            <i class="fas fa-file-excel"></i> Excel
        </a>
        <a href="{{ route('stock.create') }}" class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm">
            <i class="fas fa-plus"></i> Entrée directe
        </a>
    </div>
</div>

{{-- Summary cards --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Lots actifs</p>
        <p class="text-3xl font-bold text-slate-800 dark:text-white">{{ $entries->total() }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Cartons disponibles</p>
        <p class="text-3xl font-bold text-cyan-600">{{ number_format($totals->cartons ?? 0) }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Poids total (kg)</p>
        <p class="text-3xl font-bold text-emerald-600">{{ number_format($totals->weight ?? 0, 1) }}</p>
    </div>
</div>

{{-- Filters --}}
<div class="bg-white dark:bg-slate-800 rounded-2xl p-4 mb-5 shadow-sm border border-slate-100 dark:border-slate-700">
    <form method="GET" class="flex flex-wrap gap-3">
        <select name="client_id" class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">Tous clients</option>
            @foreach($clients as $c)
            <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
        <select name="fish_type_id" class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">Tous poissons</option>
            @foreach($fishTypes as $ft)
            <option value="{{ $ft->id }}" {{ request('fish_type_id') == $ft->id ? 'selected' : '' }}>{{ $ft->name }}</option>
            @endforeach
        </select>
        <select name="status" class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">Tous statuts</option>
            <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Disponible</option>
            <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partiel</option>
            <option value="depleted" {{ request('status') == 'depleted' ? 'selected' : '' }}>Épuisé</option>
        </select>
        <select name="cold_room_id" class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">Toutes chambres</option>
            @foreach($coldRooms as $room)
            <option value="{{ $room->id }}" {{ request('cold_room_id') == $room->id ? 'selected' : '' }}>{{ $room->name }}</option>
            @endforeach
        </select>
        <select name="origin" class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">Toutes origines</option>
            <option value="direct" {{ request('origin') == 'direct' ? 'selected' : '' }}>Entrée directe</option>
            <option value="processing" {{ request('origin') == 'processing' ? 'selected' : '' }}>Depuis traitement</option>
            <option value="empty" {{ request('origin') == 'empty' ? 'selected' : '' }}>Sortie complète</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-xl text-sm"><i class="fas fa-filter"></i></button>
    </form>
</div>

<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-500 uppercase tracking-wider">
            <tr>
                <th class="px-4 py-3 text-left">N° Entrée</th>
                <th class="px-4 py-3 text-left">Client</th>
                <th class="px-4 py-3 text-left">Poisson(s)</th>
                <th class="px-4 py-3 text-left">Chambre</th>
                <th class="px-4 py-3 text-left">Origine</th>
                <th class="px-4 py-3 text-right">Lignes</th>
                <th class="px-4 py-3 text-right">Dispo</th>
                <th class="px-4 py-3 text-right">Poids(kg)</th>
                <th class="px-4 py-3 text-center">Statut</th>
                <th class="px-4 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
            @forelse($entries as $groupKey => $group)
            @php
                $first = $group->first();
                $fishNames = $group->map(fn($b) => $b->fishType?->name)->filter()->unique()->implode(', ');
                $totalAvailCartons = $group->sum('available_cartons');
                $totalAvailWeight  = $group->sum('available_weight');
                $lineCount         = $group->count();
                $entryRef          = $first->entry_reference;
                $isGroup           = $lineCount > 1;
                $allEmpty          = $group->every(fn($b) => $b->available_cartons == 0);
                $allAvail          = $group->every(fn($b) => $b->status === 'available');
                $hasProcessing     = $group->contains(fn($b) => !is_null($b->packaging_order_id));
                $allExited         = $group->every(fn($b) => $b->available_cartons == 0 && $b->cartons_count > 0);
                $derivedStatus     = $allEmpty ? 'empty' : ($allAvail ? 'available' : 'partial');
            @endphp
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-600 dark:text-slate-300">
                    @if($isGroup)
                        <span class="text-primary-600">{{ $entryRef }}</span>
                    @else
                        {{ $first->lot_number }}
                    @endif
                </td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ $first->client?->name ?? '-' }}</td>
                <td class="px-4 py-3 text-slate-500 text-xs">{{ $fishNames ?: '-' }}</td>
                <td class="px-4 py-3">
                    @if($first->coldRoom)
                    <span class="px-2.5 py-1 inline-flex items-center gap-1.5 rounded-lg text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                        <i class="fas fa-snowflake text-xs"></i> {{ $first->coldRoom->name }}
                    </span>
                    @else
                    <span class="text-slate-400 text-xs">-</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if($allExited)
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">Sortie complète</span>
                    @elseif($hasProcessing)
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">Depuis traitement</span>
                    @else
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">Entrée directe</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right text-xs text-slate-400">
                    @if($isGroup)
                    <span class="px-2 py-0.5 rounded-full bg-cyan-50 text-cyan-700 font-semibold">{{ $lineCount }} lignes</span>
                    @else
                    <span class="text-slate-300">1</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right font-bold text-cyan-700">{{ $totalAvailCartons }}</td>
                <td class="px-4 py-3 text-right font-semibold">{{ number_format($totalAvailWeight, 1) }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ match($derivedStatus) {
                        'available' => 'bg-emerald-100 text-emerald-700',
                        'partial'   => 'bg-amber-100 text-amber-700',
                        'empty'     => 'bg-red-100 text-red-600',
                        default     => 'bg-slate-100 text-slate-500'
                    } }}">{{ match($derivedStatus) {
                        'available' => 'Disponible',
                        'partial'   => 'Partiel',
                        'empty'     => 'Épuisé',
                        default     => $derivedStatus
                    } }}</span>
                </td>
                <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center gap-1">
                        @if($isGroup)
                        <a href="{{ route('stock.entry.show', $entryRef) }}" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg" title="Voir l'entrée"><i class="fas fa-eye text-xs"></i></a>
                        <a href="{{ route('stock.receipt', ['clientStockBatch' => $first->id]) }}" target="_blank" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg" title="Reçu PDF"><i class="fas fa-file-pdf text-xs"></i></a>
                        @else
                        <a href="{{ route('stock.show', $first) }}" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg" title="Voir le lot"><i class="fas fa-eye text-xs"></i></a>
                        @if($first->available_cartons > 0)
                        <button onclick="openTransferModal({{ $first->id }}, '{{ addslashes($first->lot_number) }}', {{ $first->available_cartons }})"
                            class="p-1.5 text-purple-600 hover:bg-purple-50 rounded-lg" title="Transférer"><i class="fas fa-exchange-alt text-xs"></i></button>
                        @endif
                        <a href="{{ route('stock.receipt', ['clientStockBatch' => $first->id]) }}" target="_blank" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg" title="Reçu PDF"><i class="fas fa-file-pdf text-xs"></i></a>
                        <button onclick="confirmDelete('{{ route('stock.destroy', $first) }}','Lot {{ $first->lot_number }}')" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg"><i class="fas fa-trash text-xs"></i></button>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="10" class="text-center py-12 text-slate-400">
                <i class="fas fa-warehouse text-3xl mb-2 block"></i><p>Aucun stock disponible</p>
            </td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-700">{{ $entries->links() }}</div>
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
                        @foreach($coldRooms as $room)
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
