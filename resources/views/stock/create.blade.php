@extends('layouts.app')
@section('title', 'Entrée directe stock')
@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('stock.index') }}" class="p-2 text-slate-500 hover:bg-slate-100 rounded-xl"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Entrée directe de cartons</h1>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
        <form method="POST" action="{{ route('stock.store') }}" class="space-y-5" id="stock-entry-form">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Client <span class="text-red-500">*</span></label>
                    <select name="client_id" required class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Choisir...</option>
                        @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Date d'entrée <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="entry_date" value="{{ now()->format('Y-m-d\TH:i') }}" required class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Salle froide</label>
                    <select name="cold_room_id" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Aucune</option>
                        @foreach($coldRooms as $coldRoom)
                        <option value="{{ $coldRoom->id }}">{{ $coldRoom->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Notes</label>
                    <textarea name="notes" rows="3" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="Entrée directe du client, sans traitement"></textarea>
                </div>
            </div>

            <div class="border border-slate-200 dark:border-slate-700 rounded-2xl overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/40">
                    <div>
                        <p class="text-sm font-semibold text-slate-700 dark:text-white">Lignes d'entrée</p>
                        <p class="text-xs text-slate-400">Ajoutez plusieurs types de poisson / carton dans la même entrée.</p>
                    </div>
                    <button type="button" id="add-stock-line" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-semibold">
                        <i class="fas fa-plus"></i> Ajouter une ligne
                    </button>
                </div>

                <div id="stock-lines" class="divide-y divide-slate-100 dark:divide-slate-700">
                    <div class="stock-line grid grid-cols-1 md:grid-cols-12 gap-3 p-4 items-end">
                        <div class="md:col-span-3">
                            <label class="block text-xs font-medium text-slate-500 mb-1">Type de poisson</label>
                            <select name="items[0][fish_type_id]" required class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                                <option value="">Choisir...</option>
                                @foreach($fishTypes as $fishType)
                                <option value="{{ $fishType->id }}">{{ $fishType->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-xs font-medium text-slate-500 mb-1">Type carton</label>
                            <select name="items[0][carton_type_id]" required class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                                <option value="">Choisir...</option>
                                @foreach($cartonTypes as $cartonType)
                                <option value="{{ $cartonType->id }}">{{ $cartonType->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-slate-500 mb-1">Cartons</label>
                            <input type="number" name="items[0][cartons_count]" min="1" step="1" value="1" required class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-slate-500 mb-1">Poids / carton (kg)</label>
                            <input type="number" name="items[0][weight_per_carton]" min="0.01" step="0.01" value="16" required class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                        </div>
                        <div class="md:col-span-2 flex justify-end">
                            <button type="button" class="remove-stock-line hidden px-3 py-2.5 rounded-xl border border-red-200 text-red-600 hover:bg-red-50 text-sm font-semibold">
                                <i class="fas fa-trash mr-1"></i> Supprimer
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <template id="stock-line-template">
                <div class="stock-line grid grid-cols-1 md:grid-cols-12 gap-3 p-4 items-end">
                    <div class="md:col-span-3">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Type de poisson</label>
                        <select data-name="fish_type_id" required class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                            <option value="">Choisir...</option>
                            @foreach($fishTypes as $fishType)
                            <option value="{{ $fishType->id }}">{{ $fishType->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Type carton</label>
                        <select data-name="carton_type_id" required class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                            <option value="">Choisir...</option>
                            @foreach($cartonTypes as $cartonType)
                            <option value="{{ $cartonType->id }}">{{ $cartonType->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Cartons</label>
                        <input type="number" data-name="cartons_count" min="1" step="1" value="1" required class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Poids / carton (kg)</label>
                        <input type="number" data-name="weight_per_carton" min="0.01" step="0.01" value="16" required class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                    </div>
                    <div class="md:col-span-2 flex justify-end">
                        <button type="button" class="remove-stock-line px-3 py-2.5 rounded-xl border border-red-200 text-red-600 hover:bg-red-50 text-sm font-semibold">
                            <i class="fas fa-trash mr-1"></i> Supprimer
                        </button>
                    </div>
                </div>
            </template>

            <div class="col-span-2 flex items-center gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-semibold text-sm">
                    <i class="fas fa-check mr-1.5"></i> Enregistrer l'entrée
                </button>
                <a href="{{ route('stock.index') }}" class="px-6 py-2.5 border border-slate-200 text-slate-600 rounded-xl text-sm">Annuler</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('stock-entry-form');
    const container = document.getElementById('stock-lines');
    const template = document.getElementById('stock-line-template');
    const addButton = document.getElementById('add-stock-line');

    let index = 1;

    function refreshRemoveButtons() {
        const rows = container.querySelectorAll('.stock-line');
        rows.forEach((row) => {
            const btn = row.querySelector('.remove-stock-line');
            if (!btn) return;
            btn.classList.toggle('hidden', rows.length === 1);
        });
    }

    function reindexRows() {
        container.querySelectorAll('.stock-line').forEach((row, rowIndex) => {
            row.querySelectorAll('[data-name]').forEach((field) => {
                field.name = `items[${rowIndex}][${field.dataset.name}]`;
            });
        });
        index = container.querySelectorAll('.stock-line').length;
        refreshRemoveButtons();
    }

    addButton.addEventListener('click', () => {
        const clone = template.content.firstElementChild.cloneNode(true);
        container.appendChild(clone);
        reindexRows();
    });

    container.addEventListener('click', (event) => {
        const btn = event.target.closest('.remove-stock-line');
        if (!btn) return;
        const row = btn.closest('.stock-line');
        if (row) {
            row.remove();
            reindexRows();
        }
    });

    refreshRemoveButtons();
})();
</script>
@endsection