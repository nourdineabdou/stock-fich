@extends('layouts.app')

@section('title', $client->name)
@section('breadcrumb', 'Clients')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('clients.index') }}" class="p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-all">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">{{ $client->name }}</h1>
            <div class="flex items-center gap-2 mt-0.5">
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $client->active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600' }}">
                    {{ $client->active ? 'Actif' : 'Inactif' }}
                </span>
                @if($client->ice_number)
                <span class="text-xs text-slate-400 font-mono">ICE: {{ $client->ice_number }}</span>
                @endif
            </div>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('clients.edit', $client) }}"
           class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm transition-all">
            <i class="fas fa-pen"></i> Modifier
        </a>
        <a href="{{ route('stock.client-statement', $client) }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm transition-all">
            <i class="fas fa-file-lines"></i> Suivi Stock
        </a>
        <button onclick="confirmDelete('{{ route('clients.destroy', $client) }}', '{{ addslashes($client->name) }}')"
           class="inline-flex items-center gap-2 border border-red-200 text-red-600 hover:bg-red-50 px-4 py-2 rounded-xl text-sm font-semibold transition-all">
            <i class="fas fa-trash"></i> Supprimer
        </button>
    </div>
</div>

{{-- Stats cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    @php
    $statCards = [
        ['label' => 'Arrivages', 'value' => $client->arrivals_count, 'icon' => 'fa-truck-ramp-box', 'color' => 'text-blue-600 bg-blue-100'],
        ['label' => 'Factures', 'value' => $client->invoices_count, 'icon' => 'fa-file-invoice', 'color' => 'text-emerald-600 bg-emerald-100'],
        ['label' => 'Livraisons', 'value' => $client->deliveries_count, 'icon' => 'fa-truck', 'color' => 'text-violet-600 bg-violet-100'],
        ['label' => 'Lots en stock', 'value' => $client->stock_batches_count, 'icon' => 'fa-warehouse', 'color' => 'text-orange-600 bg-orange-100'],
    ];
    @endphp
    @foreach($statCards as $s)
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700 flex items-center gap-3">
        <div class="h-10 w-10 {{ $s['color'] }} rounded-xl flex items-center justify-center">
            <i class="fas {{ $s['icon'] }} text-sm"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ $s['value'] }}</p>
            <p class="text-xs text-slate-400">{{ $s['label'] }}</p>
        </div>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    {{-- Contact Info --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-4"><i class="fas fa-address-card mr-2 text-primary-500"></i>Informations</h3>
        <dl class="space-y-3 text-sm">
            @if($client->phone)
            <div class="flex items-center gap-3">
                <dt class="w-6 text-slate-400"><i class="fas fa-phone"></i></dt>
                <dd class="text-slate-700 dark:text-slate-300">{{ $client->phone }}</dd>
            </div>
            @endif
            @if($client->email)
            <div class="flex items-center gap-3">
                <dt class="w-6 text-slate-400"><i class="fas fa-envelope"></i></dt>
                <dd class="text-slate-700 dark:text-slate-300">{{ $client->email }}</dd>
            </div>
            @endif
            @if($client->address)
            <div class="flex items-start gap-3">
                <dt class="w-6 text-slate-400 mt-0.5"><i class="fas fa-location-dot"></i></dt>
                <dd class="text-slate-700 dark:text-slate-300">{{ $client->address }}</dd>
            </div>
            @endif
        </dl>
        <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
            <a href="{{ route('arrivals.create') }}?client_id={{ $client->id }}" class="w-full inline-flex items-center justify-center gap-2 bg-primary-50 hover:bg-primary-100 text-primary-700 px-3 py-2 rounded-xl text-sm font-medium transition-all">
                <i class="fas fa-plus text-xs"></i> Nouvel Arrivage
            </a>
        </div>
    </div>

    {{-- Recent Stock --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-white"><i class="fas fa-warehouse mr-2 text-orange-500"></i>Stock Actif</h3>
            <a href="{{ route('stock.index') }}?client_id={{ $client->id }}" class="text-xs text-primary-600 hover:underline">Voir tout</a>
        </div>
        <div class="divide-y divide-slate-50 dark:divide-slate-700">
            @forelse($client->stockBatches as $batch)
            <div class="px-5 py-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-700 dark:text-white">{{ $batch->fishType->name ?? '-' }}</p>
                        <p class="text-xs text-slate-400">{{ $batch->lot_number }} · {{ $batch->coldRoom->name ?? '-' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-bold text-slate-700 dark:text-white">{{ $batch->available_cartons }} cartons</p>
                        <p class="text-xs text-slate-400">{{ number_format($batch->available_weight, 0) }} kg</p>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-6 text-slate-400 text-xs">Aucun stock actif</div>
            @endforelse
        </div>
    </div>

    {{-- Recent Invoices --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-white"><i class="fas fa-file-invoice-dollar mr-2 text-emerald-500"></i>Factures Récentes</h3>
            <a href="{{ route('invoices.index') }}?client_id={{ $client->id }}" class="text-xs text-primary-600 hover:underline">Voir tout</a>
        </div>
        <div class="divide-y divide-slate-50 dark:divide-slate-700">
            @forelse($client->invoices as $inv)
            <div class="flex items-center justify-between px-5 py-3">
                <div>
                    <a href="{{ route('invoices.show', $inv) }}" class="text-xs font-semibold text-primary-600 hover:underline">{{ $inv->invoice_number }}</a>
                    <p class="text-xs text-slate-400">{{ $inv->invoice_date->format('d/m/Y') }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-bold text-slate-700 dark:text-white">{{ number_format($inv->total, 2, ',', ' ') }} MRU</p>
                    <span class="text-xs {{ match($inv->status) { 'paid'=>'text-emerald-600', 'partial'=>'text-amber-600', default=>'text-slate-500' } }}">
                        {{ match($inv->status) { 'paid'=>'Payée', 'partial'=>'Partielle', default=>ucfirst($inv->status) } }}
                    </span>
                </div>
            </div>
            @empty
            <div class="text-center py-6 text-slate-400 text-xs">Aucune facture</div>
            @endforelse
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-5">
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-4"><i class="fas fa-snowflake mr-2 text-cyan-500"></i>Parametrage Facturation Congelation</h3>
        <form method="POST" action="{{ route('invoices.storage-config', $client) }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @csrf
            <div>
                <label class="block text-xs text-slate-500 mb-1">Tarif</label>
                <input type="number" step="0.01" min="0.01" name="price" value="{{ old('price', $storageRule->price ?? '') }}" required class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Unite</label>
                <select name="charge_unit" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                    <option value="kg" {{ old('charge_unit', $storageRule->charge_unit ?? 'kg') === 'kg' ? 'selected' : '' }}>Par kilogramme</option>
                    <option value="carton" {{ old('charge_unit', $storageRule->charge_unit ?? '') === 'carton' ? 'selected' : '' }}>Par carton</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Frequence</label>
                <select name="billing_frequency" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                    <option value="day" {{ old('billing_frequency', $storageRule->billing_frequency ?? '') === 'day' ? 'selected' : '' }}>Chaque jour</option>
                    <option value="week" {{ old('billing_frequency', $storageRule->billing_frequency ?? 'week') === 'week' ? 'selected' : '' }}>Chaque semaine</option>
                    <option value="month" {{ old('billing_frequency', $storageRule->billing_frequency ?? '') === 'month' ? 'selected' : '' }}>Chaque mois</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Jour debut semaine (0=Dim,1=Lun)</label>
                <input type="number" min="0" max="6" name="week_start_day" value="{{ old('week_start_day', $storageRule->week_start_day ?? 1) }}" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Jour du mois (1-28)</label>
                <input type="number" min="1" max="28" name="month_day" value="{{ old('month_day', $storageRule->month_day ?? 1) }}" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Delai paiement (jours)</label>
                <input type="number" min="0" max="90" name="due_days" value="{{ old('due_days', $storageRule->due_days ?? 7) }}" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white">
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                <input type="checkbox" name="auto_generate" value="1" {{ old('auto_generate', $storageRule->auto_generate ?? false) ? 'checked' : '' }}>
                Facturation automatique activee
            </label>
            <label class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                <input type="checkbox" name="active" value="1" {{ old('active', $storageRule->active ?? true) ? 'checked' : '' }}>
                Regle active
            </label>
            <div class="md:col-span-2">
                <button type="submit" class="inline-flex items-center gap-2 bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
                    <i class="fas fa-save"></i> Enregistrer regle de congelation
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-4"><i class="fas fa-wallet mr-2 text-emerald-500"></i>Suivi Paiements</h3>
        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-xl bg-emerald-50 p-3 border border-emerald-100">
                <p class="text-xs text-emerald-700">Total Paye</p>
                <p class="text-lg font-bold text-emerald-700">{{ number_format($paymentSummary['paid'], 2, ',', ' ') }} MRU</p>
            </div>
            <div class="rounded-xl bg-amber-50 p-3 border border-amber-100">
                <p class="text-xs text-amber-700">Total Impaye</p>
                <p class="text-lg font-bold text-amber-700">{{ number_format($paymentSummary['unpaid'], 2, ',', ' ') }} MRU</p>
            </div>
            <div class="rounded-xl bg-cyan-50 p-3 border border-cyan-100">
                <p class="text-xs text-cyan-700">Impayes Congelation</p>
                <p class="text-lg font-bold text-cyan-700">{{ number_format($paymentSummary['storage_unpaid'], 2, ',', ' ') }} MRU</p>
            </div>
            <div class="rounded-xl bg-indigo-50 p-3 border border-indigo-100">
                <p class="text-xs text-indigo-700">Impayes Traitement</p>
                <p class="text-lg font-bold text-indigo-700">{{ number_format($paymentSummary['processing_unpaid'], 2, ',', ' ') }} MRU</p>
            </div>
        </div>
        <a href="{{ route('invoices.index', ['client_id' => $client->id]) }}" class="inline-flex items-center gap-2 mt-4 text-sm text-primary-600 hover:underline">
            <i class="fas fa-arrow-right"></i> Voir toutes les factures du client
        </a>
        <a href="{{ route('storage-billing.index', ['client_id' => $client->id]) }}" class="inline-flex items-center gap-2 mt-2 text-sm text-cyan-600 hover:underline">
            <i class="fas fa-snowflake"></i> Facturation congélation du client
        </a>
    </div>
</div>
@endsection

