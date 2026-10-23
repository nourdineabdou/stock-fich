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
        @can('clients.edit')
        <a href="{{ route('clients.edit', $client) }}"
           class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm transition-all">
            <i class="fas fa-pen"></i> Modifier
        </a>
        @endcan
        <a href="{{ route('stock.client-statement', $client) }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm transition-all">
            <i class="fas fa-file-lines"></i> Suivi Stock
        </a>
        @can('clients.destroy')
        <button onclick="confirmDelete('{{ route('clients.destroy', $client) }}', '{{ addslashes($client->name) }}')"
           class="inline-flex items-center gap-2 border border-red-200 text-red-600 hover:bg-red-50 px-4 py-2 rounded-xl text-sm font-semibold transition-all">
            <i class="fas fa-trash"></i> Supprimer
        </button>
        @endcan
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

{{-- Stock detaille par poisson et type de carton --}}
<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 mb-5 overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-4 border-b border-slate-100 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-white"><i class="fas fa-boxes-stacked mr-2 text-orange-500"></i>Stock actuel par poisson et carton</h3>
        <div class="flex gap-3 text-xs">
            <span class="px-3 py-1 rounded-full bg-orange-50 text-orange-700 font-semibold">{{ number_format($stockSummary->sum('cartons'), 0, ',', ' ') }} cartons</span>
            <span class="px-3 py-1 rounded-full bg-sky-50 text-sky-700 font-semibold">{{ number_format($stockSummary->sum('weight'), 2, ',', ' ') }} kg</span>
        </div>
    </div>
    @if($stockSummary->isEmpty())
        <div class="text-center py-6 text-slate-400 text-xs">Aucun stock disponible{{ ($filters['fish_type_id'] ?? null) || ($filters['carton_type_id'] ?? null) ? ' pour ce filtre' : '' }}</div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 p-5">
        @foreach($stockSummary as $line)
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="font-semibold text-slate-800 dark:text-white">{{ $line['fish'] }}</p>
                    <p class="text-xs text-slate-500">{{ $line['carton'] }} · {{ rtrim(rtrim(number_format($line['carton_weight'], 2, ',', ''), '0'), ',') }} kg/carton</p>
                </div>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-300">{{ $line['lots'] }} lot(s)</span>
            </div>
            <div class="mt-3 flex items-end justify-between">
                <p class="text-3xl font-bold text-orange-600">{{ number_format($line['cartons'], 0, ',', ' ') }} <span class="text-xs font-medium text-slate-400">cartons</span></p>
                <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">{{ number_format($line['weight'], 2, ',', ' ') }} kg</p>
            </div>
        </div>
        @endforeach
    </div>
    @endif
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
        @can('arrivals.create')
        <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
            <a href="{{ route('arrivals.create') }}?client_id={{ $client->id }}" class="w-full inline-flex items-center justify-center gap-2 bg-primary-50 hover:bg-primary-100 text-primary-700 px-3 py-2 rounded-xl text-sm font-medium transition-all">
                <i class="fas fa-plus text-xs"></i> Nouvel Arrivage
            </a>
        </div>
        @endcan
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

{{-- Frais de froid cumules par semaine --}}
@php
    $feeUnit = $coldFees['unit'] === 'kg' ? 'kg' : 'cartons';
    $fmtQty = fn ($v) => $coldFees['unit'] === 'kg' ? number_format($v, 2, ',', ' ') : number_format($v, 0, ',', ' ');
@endphp
<div id="frais-froid" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 mt-5 overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-4 border-b border-slate-100 dark:border-slate-700">
        <div>
            <h3 class="text-sm font-semibold text-slate-700 dark:text-white"><i class="fas fa-temperature-low mr-2 text-cyan-500"></i>Frais de froid par semaine</h3>
            <p class="text-xs text-slate-500 mt-0.5">Quantité facturée = stock en début de semaine + entrées de la semaine. Les sorties sont déduites à partir de la semaine suivante.</p>
        </div>
        @if($storageRule && $storageRule->active)
            <span class="px-3 py-1 rounded-full bg-cyan-50 text-cyan-700 text-xs font-semibold">
                Tarif actuel : {{ number_format($coldFees['current_price'], 2, ',', ' ') }} MRU / {{ $coldFees['unit'] === 'kg' ? 'kg' : 'carton' }} / semaine
            </span>
        @endif
    </div>

    @if(! $storageRule || ! $storageRule->active)
        <div class="m-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
            Aucun tarif de froid actif pour ce client. Renseignez le tarif (ex. 50 MRU ou 40 MRU) et l'unité (kg ou carton) dans <a href="#parametrage-froid" class="font-semibold underline">Paramétrage facturation congélation</a> ci-dessous.
        </div>
    @endif

    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 p-5">
        <div class="rounded-xl bg-slate-50 dark:bg-slate-700/40 p-3">
            <p class="text-xs text-slate-500">Total cumulé</p>
            <p class="text-lg font-bold text-slate-800 dark:text-white">{{ number_format($coldFees['total'], 2, ',', ' ') }} MRU</p>
        </div>
        <div class="rounded-xl bg-indigo-50 p-3">
            <p class="text-xs text-indigo-700">Facturé</p>
            <p class="text-lg font-bold text-indigo-700">{{ number_format($coldFees['invoiced'], 2, ',', ' ') }} MRU</p>
        </div>
        <div class="rounded-xl bg-emerald-50 p-3">
            <p class="text-xs text-emerald-700">Encaissé</p>
            <p class="text-lg font-bold text-emerald-700">{{ number_format($coldFees['paid'], 2, ',', ' ') }} MRU</p>
        </div>
        <div class="rounded-xl bg-red-50 p-3">
            <p class="text-xs text-red-700">Reste à payer (facturé)</p>
            <p class="text-lg font-bold text-red-700">{{ number_format($coldFees['balance'], 2, ',', ' ') }} MRU</p>
        </div>
        <div class="rounded-xl bg-amber-50 p-3">
            <p class="text-xs text-amber-700">Pas encore facturé</p>
            <p class="text-lg font-bold text-amber-700">{{ number_format($coldFees['unbilled'], 2, ',', ' ') }} MRU</p>
        </div>
    </div>

    @if($storageRule && $storageRule->active)
    <div class="flex flex-wrap items-start gap-3 px-5 pb-5">
        @can('storage-billing.generate-unbilled')
        @if($coldFees['billable_weeks'] > 0)
        <form method="POST" action="{{ route('storage-billing.generate-unbilled', $client) }}">
            @csrf
            <button type="submit" onclick="return confirm('Générer une facture froid pour les {{ $coldFees['billable_weeks'] }} semaine(s) terminée(s) non facturée(s) ?')"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-xl text-sm font-semibold">
                <i class="fas fa-file-invoice"></i> Facturer les {{ $coldFees['billable_weeks'] }} semaine(s) non facturée(s)
            </button>
        </form>
        @endif
        @endcan

        @can('storage-billing.collect')
        @if($coldFees['balance'] > 0)
        <form method="POST" action="{{ route('storage-billing.collect', $client) }}"
              x-data="{ amount: {{ $coldFees['balance'] }}, balance: {{ $coldFees['balance'] }} }"
              class="flex flex-wrap items-end gap-2 rounded-xl border border-emerald-200 bg-emerald-50/50 p-3">
            @csrf
            <div>
                <label class="block text-xs text-emerald-800 mb-1">Encaisser (MRU)</label>
                <input type="number" name="amount" step="0.01" min="0.01" :max="balance" x-model.number="amount" class="w-36 px-3 py-2 border border-emerald-200 rounded-xl text-sm font-bold">
            </div>
            <button type="button" @click="amount = balance" class="px-2 py-2 text-xs rounded-lg bg-white border border-emerald-200 text-emerald-700 font-semibold">Tout</button>
            <select name="method" class="px-2 py-2 border border-emerald-200 rounded-xl text-sm">
                @foreach(\App\Models\InvoicePayment::METHODS as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <input type="text" name="reference" placeholder="Référence" class="w-32 px-3 py-2 border border-emerald-200 rounded-xl text-sm">
            <button type="submit" :disabled="!(amount > 0 && amount <= balance + 0.001)" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-40 text-white rounded-xl text-sm font-semibold">
                <i class="fas fa-hand-holding-dollar mr-1"></i> Encaisser
            </button>
            <p class="w-full text-[11px] text-emerald-800">Le montant est réparti sur les factures froid les plus anciennes. Paiement partiel accepté.</p>
            @error('amount')<p class="w-full text-xs text-red-600">{{ $message }}</p>@enderror
        </form>
        @endif
        @endcan
    </div>
    @endif

    @if(empty($coldFees['rows']))
        <div class="text-center pb-6 text-slate-400 text-xs">Aucun mouvement de stock pour calculer les frais.</div>
    @else
    <div class="overflow-x-auto max-h-[28rem] overflow-y-auto border-t border-slate-100 dark:border-slate-700">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-500 uppercase sticky top-0">
                <tr>
                    <th class="px-4 py-2 text-left">Semaine</th>
                    <th class="px-3 py-2 text-right">Stock début</th>
                    <th class="px-3 py-2 text-right">Entrées</th>
                    <th class="px-3 py-2 text-right">Sorties</th>
                    <th class="px-3 py-2 text-right">Facturé ({{ $feeUnit }})</th>
                    <th class="px-3 py-2 text-right">Prix</th>
                    <th class="px-3 py-2 text-right">Montant</th>
                    <th class="px-3 py-2 text-right">Cumul</th>
                    <th class="px-4 py-2 text-center">Statut</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                @foreach($coldFees['rows'] as $row)
                <tr class="{{ $row['is_current'] ? 'bg-cyan-50/50 dark:bg-cyan-900/10' : '' }}">
                    <td class="px-4 py-2 whitespace-nowrap">
                        <span class="font-medium text-slate-700 dark:text-white">{{ $row['week_start']->format('d/m') }} → {{ $row['week_end']->format('d/m/Y') }}</span>
                        @if($row['is_current'])<span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-cyan-100 text-cyan-700">en cours</span>@endif
                    </td>
                    <td class="px-3 py-2 text-right text-slate-600 dark:text-slate-300">{{ $fmtQty($row['opening']) }}</td>
                    <td class="px-3 py-2 text-right text-emerald-600">{{ $row['entries'] > 0 ? '+'.$fmtQty($row['entries']) : '—' }}</td>
                    <td class="px-3 py-2 text-right text-red-500">{{ $row['exits'] > 0 ? '-'.$fmtQty($row['exits']) : '—' }}</td>
                    <td class="px-3 py-2 text-right font-semibold text-slate-800 dark:text-white">{{ $fmtQty($row['quantity']) }}</td>
                    <td class="px-3 py-2 text-right text-slate-500">{{ number_format($row['price'], 2, ',', ' ') }}</td>
                    <td class="px-3 py-2 text-right font-semibold text-slate-800 dark:text-white">{{ number_format($row['amount'], 2, ',', ' ') }}</td>
                    <td class="px-3 py-2 text-right text-slate-500">{{ number_format($row['cumulative'], 2, ',', ' ') }}</td>
                    <td class="px-4 py-2 text-center whitespace-nowrap">
                        @if($row['invoice'])
                            <a href="{{ route('invoices.show', $row['invoice']) }}" class="text-xs font-semibold {{ $row['invoice']->status === 'paid' ? 'text-emerald-600' : 'text-indigo-600' }} hover:underline">
                                {{ $row['invoice']->invoice_number }} · {{ match($row['invoice']->status) { 'paid' => 'Payée', 'partial' => 'Partielle', default => 'Facturée' } }}
                            </a>
                        @elseif($storageRule && $storageRule->active && ! $row['is_current'] && auth()->user()->can('storage-billing.generate-week'))
                            <form method="POST" action="{{ route('storage-billing.generate-week', $client) }}">
                                @csrf
                                <input type="hidden" name="week_start" value="{{ $row['week_start']->toDateString() }}">
                                <button type="submit" class="text-xs px-2.5 py-1 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold">Facturer</button>
                            </form>
                        @else
                            <span class="text-xs text-slate-400">{{ $row['is_current'] ? 'Semaine en cours' : 'Non facturée' }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- Historique des mouvements avec filtres --}}
@php
    $histIn = $historyTotals->get('in');
    $histOut = $historyTotals->get('out');
    $hasFilters = collect($filters)->except('date_mode')->filter()->isNotEmpty();
@endphp
<div id="historique" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 mt-5 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-white"><i class="fas fa-clock-rotate-left mr-2 text-violet-500"></i>Historique entrées / retraits</h3>
    </div>

    <form method="GET" action="{{ route('clients.show', $client) }}#historique"
          x-data="{ mode: '{{ $filters['date_mode'] ?? 'range' }}' }"
          class="grid grid-cols-1 md:grid-cols-6 gap-3 p-5 bg-slate-50/60 dark:bg-slate-900/20 border-b border-slate-100 dark:border-slate-700">
        <input type="hidden" name="date_mode" :value="mode">
        <div class="md:col-span-2">
            <div class="flex rounded-xl border border-slate-200 dark:border-slate-600 p-0.5 bg-white dark:bg-slate-700 mb-2 text-xs font-semibold">
                <button type="button" @click="mode = 'range'" :class="mode === 'range' ? 'bg-violet-600 text-white' : 'text-slate-600 dark:text-slate-300'" class="flex-1 py-1.5 rounded-lg">Entre deux dates</button>
                <button type="button" @click="mode = 'exact'" :class="mode === 'exact' ? 'bg-violet-600 text-white' : 'text-slate-600 dark:text-slate-300'" class="flex-1 py-1.5 rounded-lg">Date précise</button>
            </div>
            <div x-show="mode === 'range'" class="grid grid-cols-2 gap-2">
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" :disabled="mode !== 'range'" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white" title="Du">
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" :disabled="mode !== 'range'" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white" title="Au">
            </div>
            <div x-show="mode === 'exact'" x-cloak>
                <input type="date" name="date" value="{{ $filters['date'] ?? '' }}" :disabled="mode !== 'exact'" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white">
            </div>
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">Poisson</label>
            <select name="fish_type_id" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                <option value="">Tous</option>
                @foreach($fishTypes as $ft)
                <option value="{{ $ft->id }}" @selected(($filters['fish_type_id'] ?? null) == $ft->id)>{{ $ft->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">Type de carton</label>
            <select name="carton_type_id" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                <option value="">Tous</option>
                @foreach($cartonTypes as $ct)
                <option value="{{ $ct->id }}" @selected(($filters['carton_type_id'] ?? null) == $ct->id)>{{ $ct->name }}{{ $ct->weight_kg ? ' ('.(float) $ct->weight_kg.' kg)' : '' }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">Mouvement</label>
            <select name="movement" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                <option value="">Tous</option>
                <option value="in" @selected(($filters['movement'] ?? null) === 'in')>Entrées</option>
                <option value="out" @selected(($filters['movement'] ?? null) === 'out')>Retraits</option>
                <option value="transfer" @selected(($filters['movement'] ?? null) === 'transfer')>Transferts</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-sm font-semibold"><i class="fas fa-filter mr-1"></i> Filtrer</button>
            @if($hasFilters)
            <a href="{{ route('clients.show', $client) }}#historique" class="px-3 py-2 border border-slate-200 rounded-xl text-sm text-slate-600 hover:bg-white" title="Réinitialiser"><i class="fas fa-xmark"></i></a>
            @endif
        </div>
        @if($errors->any())
        <p class="md:col-span-6 text-xs text-red-600">{{ $errors->first() }}</p>
        @endif
    </form>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 px-5 pt-4">
        <div class="rounded-xl bg-emerald-50 p-3">
            <p class="text-xs text-emerald-700">Entrées (filtre)</p>
            <p class="font-bold text-emerald-700">{{ number_format($histIn->cartons ?? 0, 0, ',', ' ') }} cartons · {{ number_format($histIn->weight ?? 0, 2, ',', ' ') }} kg</p>
        </div>
        <div class="rounded-xl bg-red-50 p-3">
            <p class="text-xs text-red-700">Retraits (filtre)</p>
            <p class="font-bold text-red-700">{{ number_format($histOut->cartons ?? 0, 0, ',', ' ') }} cartons · {{ number_format($histOut->weight ?? 0, 2, ',', ' ') }} kg</p>
        </div>
        @if($hasFilters)
        <div class="md:col-span-2 rounded-xl border border-violet-200 bg-violet-50 p-3 text-xs text-violet-700 flex items-center">
            <i class="fas fa-circle-info mr-2"></i> Le filtre poisson / carton s'applique aussi au stock actuel en haut de la page.
        </div>
        @endif
    </div>

    <div class="overflow-x-auto mt-4">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-500 uppercase">
                <tr>
                    <th class="px-4 py-2 text-left">Date</th>
                    <th class="px-3 py-2 text-left">Mouvement</th>
                    <th class="px-3 py-2 text-left">Poisson</th>
                    <th class="px-3 py-2 text-left">Carton</th>
                    <th class="px-3 py-2 text-right">Cartons</th>
                    <th class="px-3 py-2 text-right">Poids (kg)</th>
                    <th class="px-3 py-2 text-left">Chambre</th>
                    <th class="px-4 py-2 text-left">Référence</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                @forelse($history as $mv)
                @php
                    [$mvLabel, $mvClass, $mvSign] = match($mv->type) {
                        'in' => ['Entrée', 'bg-emerald-100 text-emerald-700', '+'],
                        'out' => ['Retrait', 'bg-red-100 text-red-700', '-'],
                        default => ['Transfert', 'bg-sky-100 text-sky-700', ''],
                    };
                @endphp
                <tr>
                    <td class="px-4 py-2 whitespace-nowrap text-slate-600 dark:text-slate-300">{{ $mv->entry_date?->format('d/m/Y H:i') }}</td>
                    <td class="px-3 py-2"><span class="text-xs px-2 py-0.5 rounded-full font-semibold {{ $mvClass }}">{{ $mvLabel }}</span></td>
                    <td class="px-3 py-2 font-medium text-slate-700 dark:text-white">{{ $mv->stockBatch->fishType->name ?? '-' }}</td>
                    <td class="px-3 py-2 text-slate-600 dark:text-slate-300">{{ $mv->stockBatch->cartonType->name ?? '-' }}</td>
                    <td class="px-3 py-2 text-right font-semibold">{{ $mvSign }}{{ number_format($mv->cartons_count, 0, ',', ' ') }}</td>
                    <td class="px-3 py-2 text-right">{{ $mvSign }}{{ number_format($mv->weight_kg, 2, ',', ' ') }}</td>
                    <td class="px-3 py-2 text-slate-500">{{ $mv->coldRoom->name ?? '-' }}</td>
                    <td class="px-4 py-2 font-mono text-xs text-slate-500">{{ $mv->reference ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-6 text-slate-400 text-xs">Aucun mouvement pour ces critères</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($history->hasPages())
    <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-700">{{ $history->links() }}</div>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-5">
    <div id="parametrage-froid" class="bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-4"><i class="fas fa-snowflake mr-2 text-cyan-500"></i>Parametrage Facturation Congelation</h3>
        <form method="POST" action="{{ route('invoices.storage-config', $client) }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @csrf
            <div>
                <label class="block text-xs text-slate-500 mb-1">Tarif (MRU par unité et par période, ex. 50 ou 40)</label>
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

