@extends('layouts.app')
@section('title', isset($arrival) ? 'Modifier Arrivage' : 'Nouvel Arrivage')
@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('arrivals.index') }}" class="p-2 text-slate-500 hover:bg-slate-100 rounded-xl"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">
            {{ isset($arrival) ? 'Modifier: '.$arrival->reference : 'Nouvel Arrivage' }}
        </h1>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
        <form method="POST" action="{{ isset($arrival) ? route('arrivals.update', $arrival) : route('arrivals.store') }}" id="arrivalForm">
            @csrf
            @if(isset($arrival)) @method('PUT') @endif

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Client <span class="text-red-500">*</span></label>
                    <select name="client_id" required class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Choisir un client...</option>
                        @foreach($clients as $c)
                        <option value="{{ $c->id }}" {{ old('client_id', $arrival->client_id ?? request('client_id')) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('client_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Date d'arrivage <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="arrival_date" value="{{ old('arrival_date', isset($arrival) ? $arrival->arrival_date->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}" required
                        class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Statut</label>
                    <select name="status" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="pending" {{ old('status', $arrival->status ?? 'pending') == 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="processing" {{ old('status', $arrival->status ?? '') == 'processing' ? 'selected' : '' }}>En traitement</option>
                        <option value="completed" {{ old('status', $arrival->status ?? '') == 'completed' ? 'selected' : '' }}>Complété</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Notes</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">{{ old('notes', $arrival->notes ?? '') }}</textarea>
                </div>
            </div>

            {{-- Items --}}
            <div class="border-t border-slate-100 dark:border-slate-700 pt-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-white">
                        <i class="fas fa-fish mr-1.5 text-cyan-500"></i>Lignes d'arrivage non triées
                    </h3>
                    <button type="button" onclick="addArrivalItem()"
                        class="inline-flex items-center gap-1.5 bg-cyan-50 hover:bg-cyan-100 text-cyan-700 px-3 py-1.5 rounded-xl text-xs font-medium">
                        <i class="fas fa-plus text-xs"></i> Ajouter ligne
                    </button>
                </div>
                <div class="mb-3 rounded-xl border border-cyan-200 bg-cyan-50 px-4 py-3 text-xs text-cyan-800 dark:bg-cyan-950/20 dark:border-cyan-900 dark:text-cyan-300">
                    Ici, on saisit seulement le poids reçu. Les caisses, cartons et quantités seront définis après le traitement.
                </div>

                <div id="itemsContainer" class="space-y-3">
                    @if(isset($arrival) && $arrival->items->count() > 0)
                    @foreach($arrival->items as $i => $item)
                    <div class="grid grid-cols-12 gap-2 items-end bg-slate-50 dark:bg-slate-700 rounded-xl p-3">
                        <div class="col-span-6">
                            <label class="block text-xs text-slate-500 mb-1">Poids (kg) *</label>
                            <input type="number" step="0.01" name="items[{{ $i }}][weight_kg]" value="{{ $item->weight_kg }}" required
                                class="w-full px-2 py-2 text-sm border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        </div>
                        <div class="col-span-5">
                            <label class="block text-xs text-slate-500 mb-1">Notes</label>
                            <input type="text" name="items[{{ $i }}][notes]" value="{{ $item->notes }}"
                                class="w-full px-2 py-2 text-sm border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        </div>
                        <div class="col-span-1 flex justify-end">
                            <button type="button" onclick="removeItem(this)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </div>
                    </div>
                    @endforeach
                    @else
                    <div class="grid grid-cols-12 gap-2 items-end bg-slate-50 dark:bg-slate-700 rounded-xl p-3">
                        <div class="col-span-6">
                            <label class="block text-xs text-slate-500 mb-1">Poids (kg) *</label>
                            <input type="number" step="0.01" name="items[0][weight_kg]" required
                                class="w-full px-2 py-2 text-sm border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        </div>
                        <div class="col-span-5">
                            <label class="block text-xs text-slate-500 mb-1">Notes</label>
                            <input type="text" name="items[0][notes]"
                                class="w-full px-2 py-2 text-sm border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        </div>
                        <div class="col-span-1 flex justify-end">
                            <button type="button" onclick="removeItem(this)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </div>
                    </div>
                    @endif
                </div>
                @error('items')<p class="text-red-500 text-xs mt-2">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center gap-3 pt-5 mt-5 border-t border-slate-100 dark:border-slate-700">
                <button type="submit" class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-semibold text-sm">
                    <i class="fas fa-check mr-1.5"></i> {{ isset($arrival) ? 'Mettre à jour' : 'Enregistrer l\'arrivage' }}
                </button>
                <a href="{{ route('arrivals.index') }}" class="px-6 py-2.5 border border-slate-200 text-slate-600 rounded-xl text-sm">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
let itemIdx = {{ isset($arrival) ? $arrival->items->count() : 1 }};

function addArrivalItem() {
    const i = itemIdx++;
    const html = `
    <div class="grid grid-cols-12 gap-2 items-end bg-slate-50 dark:bg-slate-700 rounded-xl p-3">
        <div class="col-span-6">
            <label class="block text-xs text-slate-500 mb-1">Poids (kg) *</label>
            <input type="number" step="0.01" name="items[${i}][weight_kg]" required class="w-full px-2 py-2 text-sm border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>
        <div class="col-span-5">
            <label class="block text-xs text-slate-500 mb-1">Notes</label>
            <input type="text" name="items[${i}][notes]" class="w-full px-2 py-2 text-sm border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>
        <div class="col-span-1 flex justify-end">
            <button type="button" onclick="removeItem(this)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
    </div>`;
    document.getElementById('itemsContainer').insertAdjacentHTML('beforeend', html);
}

function removeItem(btn) {
    btn.closest('.grid').remove();
}
</script>
@endpush
