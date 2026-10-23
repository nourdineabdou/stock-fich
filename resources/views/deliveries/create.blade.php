@extends('layouts.app')
@section('title', isset($delivery) ? 'Modifier Livraison' : 'Nouvelle Livraison')
@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('deliveries.index') }}" class="p-2 text-slate-500 hover:bg-slate-100 rounded-xl"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">
            {{ isset($delivery) ? 'Modifier Livraison' : 'Nouvelle Livraison' }}
        </h1>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
        @if($errors->has('stock_validation'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first('stock_validation') }}
        </div>
        @endif

        <form method="POST" action="{{ isset($delivery) ? route('deliveries.update', $delivery) : route('deliveries.store') }}">
            @csrf
            @if(isset($delivery)) @method('PUT') @endif
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Client <span class="text-red-500">*</span></label>
                    <select name="client_id" id="clientSelect" required class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Choisir...</option>
                        @foreach($clients as $c)
                        <option value="{{ $c->id }}" {{ old('client_id', $delivery->client_id ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Date de livraison <span class="text-red-500">*</span></label>
                    <input type="date" name="delivery_date" value="{{ old('delivery_date', isset($delivery) ? $delivery->delivery_date->format('Y-m-d') : today()->format('Y-m-d')) }}" required
                        class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Statut</label>
                    <select name="status" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="pending" {{ old('status', $delivery->status ?? 'pending') == 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="delivered" {{ old('status', $delivery->status ?? '') == 'delivered' ? 'selected' : '' }}>Livré</option>
                        <option value="cancelled" {{ old('status', $delivery->status ?? '') == 'cancelled' ? 'selected' : '' }}>Annulé</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Notes</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">{{ old('notes', $delivery->notes ?? '') }}</textarea>
                </div>
                <div class="col-span-2 border-t border-slate-100 dark:border-slate-700 pt-4 mt-1">
                    <p class="text-xs font-semibold text-slate-600 uppercase tracking-wide mb-3">Accuse de retrait client</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Nom receptionnaire</label>
                            <input type="text" name="receiver_name" value="{{ old('receiver_name', $delivery->receiver_name ?? '') }}"
                                class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">CIN/NIF receptionnaire</label>
                            <input type="text" name="receiver_id_number" value="{{ old('receiver_id_number', $delivery->receiver_id_number ?? '') }}"
                                class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Telephone receptionnaire</label>
                            <input type="text" name="receiver_phone" value="{{ old('receiver_phone', $delivery->receiver_phone ?? '') }}"
                                class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Items --}}
            <div class="border-t border-slate-100 dark:border-slate-700 pt-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-white"><i class="fas fa-box mr-1.5 text-cyan-500"></i>Articles Livrés</h3>
                    <button type="button" id="addDeliveryItemBtn" class="text-xs bg-cyan-50 hover:bg-cyan-100 text-cyan-700 px-3 py-1.5 rounded-xl disabled:opacity-50" disabled>
                        <i class="fas fa-plus mr-1"></i> Ajouter
                    </button>
                </div>
                <div id="deliveryItems" class="space-y-2">
                    @if(isset($delivery))
                    @foreach($delivery->items as $i => $item)
                    <div class="grid grid-cols-12 gap-2 bg-slate-50 dark:bg-slate-700 rounded-xl p-2">
                        <div class="col-span-5">
                            <select name="items[{{ $i }}][client_stock_batch_id]" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none">
                                <option value="{{ $item->client_stock_batch_id }}">{{ $item->stockBatch->lot_number ?? $item->client_stock_batch_id }}</option>
                            </select>
                        </div>
                        <div class="col-span-6">
                            <input type="number" name="items[{{ $i }}][cartons_count]" value="{{ $item->cartons_count }}" placeholder="Nombre de cartons" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none">
                        </div>
                        <div class="col-span-1 flex items-center justify-end">
                            <button type="button" onclick="removeItem(this)" class="p-1 text-red-600"><i class="fas fa-times text-xs"></i></button>
                        </div>
                    </div>
                    @endforeach
                    @endif
                </div>
                <p class="text-xs text-slate-400 mt-2" id="selectClientHint">Sélectionnez d'abord un client pour charger le stock disponible.</p>
            </div>

            <div class="flex items-center gap-3 pt-5 mt-5 border-t border-slate-100 dark:border-slate-700">
                <button type="submit" class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-semibold text-sm">
                    <i class="fas fa-check mr-1.5"></i> {{ isset($delivery) ? 'Mettre à jour' : 'Enregistrer' }}
                </button>
                <a href="{{ route('deliveries.index') }}" class="px-6 py-2.5 border border-slate-200 text-slate-600 rounded-xl text-sm">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
let dIdx = {{ isset($delivery) ? count($delivery->items) : 0 }};
let availableStock = [];

$('#clientSelect').on('change', function() {
    const clientId = $(this).val();
    if (!clientId) {
        $('#addDeliveryItemBtn').prop('disabled', true);
        $('#selectClientHint').show();
        return;
    }
    $.getJSON('/deliveries/client-stock', {client_id: clientId}, function(data) {
        availableStock = data;
        $('#addDeliveryItemBtn').prop('disabled', false);
        $('#selectClientHint').hide();
    });
});

@if(isset($delivery) && $delivery->client_id)
$('#clientSelect').trigger('change');
@endif

$('#addDeliveryItemBtn').on('click', function() {
    if (!availableStock.length) return;
    const i = dIdx++;
    const opts = availableStock.map(s => `<option value="${s.id}">[${s.lot_number}] ${s.fish_type} — ${s.carton_type} (${s.available_cartons} dispo)</option>`).join('');
    $('#deliveryItems').append(`
    <div class="grid grid-cols-12 gap-2 bg-slate-50 dark:bg-slate-700 rounded-xl p-2">
        <div class="col-span-5"><select name="items[${i}][client_stock_batch_id]" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none">${opts}</select></div>
        <div class="col-span-6"><input type="number" name="items[${i}][cartons_count]" placeholder="Nombre de cartons" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none"></div>
        <div class="col-span-1 flex items-center justify-end"><button type="button" onclick="removeItem(this)" class="p-1 text-red-600"><i class="fas fa-times text-xs"></i></button></div>
    </div>`);
});

function removeItem(btn) { $(btn).closest('.grid').remove(); }
</script>
@endpush
