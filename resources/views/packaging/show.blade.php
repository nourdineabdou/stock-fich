@extends('layouts.app')
@section('title', $order->reference)
@section('content')
@php
    $routePackaging = request()->route('packaging');
    $packagingRouteKey = $packagingOrder instanceof \Illuminate\Database\Eloquent\Model
        ? $packagingOrder->getKey()
        : ($routePackaging instanceof \Illuminate\Database\Eloquent\Model
            ? $routePackaging->getKey()
            : $routePackaging);
@endphp
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('packaging.index') }}" class="p-2 text-slate-500 hover:bg-slate-100 rounded-xl"><i class="fas fa-arrow-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white font-mono">{{ $order->reference }}</h1>
            <p class="text-sm text-slate-500">{{ $order->created_at?->format('d/m/Y') ?? $order->updated_at?->format('d/m/Y') ?? '—' }}</p>
        </div>
    </div>
    <div class="flex gap-2">
        @if($order->status === 'pending')
        <button onclick="openModal('finalizeModal')"
            class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-semibold animate-pulse">
            <i class="fas fa-check-circle"></i> Valider la destination
        </button>
        @endif
        <a href="{{ route('packaging.print', ['packaging' => $packagingRouteKey]) }}" target="_blank" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
            <i class="fas fa-print"></i> Imprimer Bon Cartonnage
        </a>
        @if($order->status === 'pending')
        <a href="{{ route('packaging.edit', ['packaging' => $packagingRouteKey]) }}" class="p-2 text-amber-600 bg-amber-50 hover:bg-amber-100 rounded-xl"><i class="fas fa-pen"></i></a>
        @endif
        <button onclick="confirmDelete('{{ route('packaging.destroy', ['packaging' => $packagingRouteKey]) }}','{{ addslashes($order->reference) }}')" class="p-2 text-red-600 border border-red-200 hover:bg-red-50 rounded-xl"><i class="fas fa-trash"></i></button>
    </div>
</div>

{{-- Pending alert banner --}}
@if($order->status === 'pending')
<div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 dark:bg-amber-950/20 dark:border-amber-800 px-5 py-4 flex items-center gap-4">
    <i class="fas fa-hourglass-half text-2xl text-amber-500"></i>
    <div>
        <p class="font-semibold text-amber-800 dark:text-amber-300">En attente de validation</p>
        <p class="text-sm text-amber-700 dark:text-amber-400">Le cartonnage est enregistré mais les cartons ne sont pas encore stockés ni livrés. Cliquez sur <strong>"Valider la destination"</strong> pour finaliser.</p>
    </div>
</div>
@elseif($order->status === 'delivered')
<div class="mb-5 rounded-2xl border border-blue-200 bg-blue-50 dark:bg-blue-950/20 dark:border-blue-800 px-5 py-4 flex items-center gap-4">
    <i class="fas fa-truck text-2xl text-blue-500"></i>
    <div>
        <p class="font-semibold text-blue-800 dark:text-blue-300">Livraison directe au client</p>
        <p class="text-sm text-blue-700 dark:text-blue-400">Les cartons ont été récupérés directement par le client — aucune entrée en stock.</p>
    </div>
</div>
@elseif($order->status === 'completed')
<div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20 dark:border-emerald-800 px-5 py-4 flex items-center gap-4">
    <i class="fas fa-warehouse text-2xl text-emerald-500"></i>
    <div>
        <p class="font-semibold text-emerald-800 dark:text-emerald-300">Stocké en chambre froide</p>
        <p class="text-sm text-emerald-700 dark:text-emerald-400">Les cartons ont été transférés en stock. <a href="{{ route('stock.index') }}" class="underline font-semibold">Voir le stock</a></p>
    </div>
</div>
@endif
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-white"><i class="fas fa-box mr-2 text-purple-500"></i>Détail des Cartons</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-2 text-left">Poisson</th>
                        <th class="px-4 py-2 text-left">Type carton</th>
                        <th class="px-4 py-2 text-right">Qté</th>
                        <th class="px-4 py-2 text-right">Poids/carton</th>
                        <th class="px-4 py-2 text-right">Total kg</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                    @foreach($order->items as $item)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-700 dark:text-white">{{ $item->fishType->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $item->cartonType->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-right font-bold">{{ $item->cartons_count }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($item->weight_per_carton, 3) }} kg</td>
                        <td class="px-4 py-3 text-right text-emerald-600 font-bold">{{ number_format($item->cartons_count * $item->weight_per_carton, 2) }} kg</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-4"><i class="fas fa-info-circle mr-2 text-primary-500"></i>Info</h3>
            <dl class="space-y-3 text-sm">
                @if($order->processingOrder)
                <div class="flex justify-between">
                    <dt class="text-slate-400">Traitement</dt>
                    <dd><a href="{{ route('processing.show', $order->processingOrder->id) }}" class="font-mono text-xs text-primary-600 hover:underline">{{ $order->processingOrder->reference }}</a></dd>
                </div>
                @endif
                @if($order->coldRoom)
                <div class="flex justify-between">
                    <dt class="text-slate-400">Salle froide</dt>
                    <dd class="text-slate-700 dark:text-white">{{ $order->coldRoom->name }}</dd>
                </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-slate-400">Statut</dt>
                    <dd><span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ match($order->status) {
                        'pending'   => 'bg-amber-100 text-amber-700',
                        'completed' => 'bg-emerald-100 text-emerald-700',
                        'delivered' => 'bg-blue-100 text-blue-700',
                        default     => 'bg-slate-100 text-slate-500',
                    } }}">{{ match($order->status) {
                        'pending'   => 'En attente',
                        'completed' => 'Stocké',
                        'delivered' => 'Livraison directe',
                        default     => ucfirst($order->status),
                    } }}</span></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400">Total cartons</dt>
                    <dd class="font-bold text-slate-700 dark:text-white">{{ $order->items->sum('cartons_count') }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection

@if($order->status === 'pending')
{{-- Finalization Modal --}}
<div id="finalizeModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-1"><i class="fas fa-check-circle mr-2 text-emerald-500"></i>Valider la destination des cartons</h3>
        <p class="text-sm text-slate-500 mb-5">Que doit-il se passer avec les <strong>{{ $order->items->sum('cartons_count') }} cartons</strong> de <strong>{{ $order->processingOrder?->client?->name }}</strong> ?</p>

        <form method="POST" action="{{ route('packaging.finalize', ['packaging' => $packagingRouteKey]) }}" id="finalizeForm">
            @csrf
            <div class="space-y-3 mb-5">
                <label class="flex items-start gap-3 p-3 rounded-xl border-2 border-slate-200 dark:border-slate-600 cursor-pointer has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 dark:has-[:checked]:bg-emerald-950/20">
                    <input type="radio" name="mode" value="store" class="mt-0.5" onchange="toggleColdRoom(this)" required>
                    <div>
                        <p class="font-semibold text-slate-700 dark:text-white text-sm"><i class="fas fa-warehouse mr-1 text-emerald-600"></i> Stocker en chambre froide</p>
                        <p class="text-xs text-slate-400">Les cartons seront mis en stock — type "Transfert après traitement"</p>
                    </div>
                </label>
                <label class="flex items-start gap-3 p-3 rounded-xl border-2 border-slate-200 dark:border-slate-600 cursor-pointer has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-950/20">
                    <input type="radio" name="mode" value="pickup" class="mt-0.5" onchange="toggleColdRoom(this)">
                    <div>
                        <p class="font-semibold text-slate-700 dark:text-white text-sm"><i class="fas fa-truck mr-1 text-blue-600"></i> Livraison directe au client</p>
                        <p class="text-xs text-slate-400">Le client récupère ses cartons maintenant — aucune entrée en stock</p>
                    </div>
                </label>
            </div>

            <div id="coldRoomSection" class="mb-5 hidden">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Chambre froide de destination <span class="text-red-500">*</span></label>
                <select name="cold_room_id" id="coldRoomSelect" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="">Choisir une chambre...</option>
                    @foreach(\App\Models\ColdRoom::where('active', true)->orderBy('name')->get() as $room)
                    <option value="{{ $room->id }}">{{ $room->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-semibold text-sm">
                    <i class="fas fa-check mr-1"></i> Confirmer
                </button>
                <button type="button" onclick="closeModal('finalizeModal')" class="flex-1 py-2.5 border border-slate-200 text-slate-600 rounded-xl text-sm">Annuler</button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
function toggleColdRoom(radio) {
    const section = document.getElementById('coldRoomSection');
    const select  = document.getElementById('coldRoomSelect');
    if (radio.value === 'store') {
        section.classList.remove('hidden');
        select.required = true;
    } else {
        section.classList.add('hidden');
        select.required = false;
    }
}
</script>
@endpush
@endif
