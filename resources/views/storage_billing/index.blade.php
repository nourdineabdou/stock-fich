@extends('layouts.app')
@section('title', 'Facturation Congélation')
@section('content')

<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white flex items-center gap-2">
            <i class="fas fa-snowflake text-cyan-500"></i> Facturation Congélation
        </h1>
        <p class="text-sm text-slate-400 mt-0.5">Frais chambre froide · facturation automatique par période</p>
    </div>
    <a href="{{ route('invoices.index') }}" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-primary-600 border border-slate-200 px-3 py-2 rounded-xl hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700">
        <i class="fas fa-file-invoice-dollar"></i> <span class="hidden sm:inline">Fact. Traitement</span>
    </a>
</div>

{{-- Flash messages --}}
@if(session('success'))
<div class="mb-4 flex items-center justify-between gap-4 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm dark:bg-emerald-900/20 dark:border-emerald-700 dark:text-emerald-300">
    <span><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</span>
    @if(session('pdf_url'))
    <a href="{{ session('pdf_url') }}" target="_blank"
       class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold flex-shrink-0">
        <i class="fas fa-file-pdf"></i> Télécharger reçu PDF
    </a>
    @endif
</div>
@endif
@if(session('warning'))
<div class="mb-4 bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl text-sm dark:bg-amber-900/20 dark:border-amber-700 dark:text-amber-300">
    <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('warning') }}
</div>
@endif
@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm dark:bg-red-900/20 dark:border-red-700">
    @foreach($errors->all() as $e)<div><i class="fas fa-times-circle mr-1"></i>{{ $e }}</div>@endforeach
</div>
@endif

{{-- Summary cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase mb-1">Impayé total</p>
        <p class="text-2xl font-bold text-amber-600">{{ number_format($totals['total_unpaid'], 0, ',', ' ') }} MRU</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase mb-1">Encaissé total</p>
        <p class="text-2xl font-bold text-emerald-600">{{ number_format($totals['total_paid'], 0, ',', ' ') }} MRU</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase mb-1">Clients actifs</p>
        <p class="text-2xl font-bold text-slate-700 dark:text-white">{{ $totals['nb_clients'] }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase mb-1">Périodes en retard</p>
        <p class="text-2xl font-bold text-red-600">{{ $totals['nb_overdue'] }}</p>
    </div>
</div>

{{-- Client selector --}}
<div class="bg-white dark:bg-slate-800 rounded-2xl p-4 mb-5 shadow-sm border border-slate-100 dark:border-slate-700">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[240px]">
            <label class="block text-xs font-medium text-slate-500 mb-1">Sélectionner un client</label>
            <select name="client_id" onchange="this.form.submit()"
                class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-cyan-500">
                <option value="">— Vue d'ensemble (tous les clients) —</option>
                @foreach($allClients as $c)
                <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        @if(request('client_id'))
        <a href="{{ route('storage-billing.index') }}"
           class="px-4 py-2 text-sm text-slate-500 border border-slate-200 rounded-xl hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700">
            <i class="fas fa-times mr-1"></i> Vue d'ensemble
        </a>
        @endif
    </form>
</div>

{{-- DETAIL CLIENT --}}
@if($selectedClient)
@php
    $freq = $clientRule?->billing_frequency ?? 'week';
    $freqLabel = match($freq) { 'day' => 'Journalier', 'month' => 'Mensuel', default => 'Hebdomadaire (lundi)' };
@endphp

<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 mb-4">
    <div class="flex flex-wrap items-center justify-between gap-4 px-5 py-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-cyan-600 text-white flex items-center justify-center text-base font-bold">
                {{ strtoupper(substr($selectedClient->name, 0, 1)) }}
            </div>
            <div>
                <h2 class="font-bold text-slate-800 dark:text-white text-base">{{ $selectedClient->name }}</h2>
                @if($selectedClient->phone)
                <p class="text-xs text-slate-400">{{ $selectedClient->phone }}</p>
                @endif
            </div>
        </div>
        <div class="flex flex-wrap gap-2 text-xs">
            @if($clientRule)
            <span class="px-2.5 py-1 rounded-lg bg-cyan-100 text-cyan-700 font-medium dark:bg-cyan-900/30 dark:text-cyan-300">
                <i class="fas fa-repeat mr-1"></i>{{ $freqLabel }}
            </span>
            <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                {{ number_format($clientRule->price, 2) }} MRU / {{ $clientRule->charge_unit === 'kg' ? 'kg' : 'carton' }}
            </span>
            @if($clientRule->auto_generate)
            <span class="px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400">
                <i class="fas fa-robot mr-1"></i>Auto activé
            </span>
            @else
            <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400">
                <i class="fas fa-hand-pointer mr-1"></i>Manuel
            </span>
            @endif
            @else
            <span class="px-2.5 py-1 rounded-lg bg-amber-100 text-amber-600 text-xs">
                <i class="fas fa-exclamation-triangle mr-1"></i>Aucune règle configurée
            </span>
            @endif
        </div>
        <div class="flex items-center gap-2">
            @if($clientRule)
            @can('storage-billing.generate-unbilled')
            <form method="POST" action="{{ route('storage-billing.generate-unbilled', $selectedClient) }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 rounded-xl text-sm font-semibold"
                        title="Une ligne par semaine terminée, selon le stock réel de chaque semaine">
                    <i class="fas fa-calendar-week"></i> Facturer semaines non facturées
                </button>
            </form>
            @endcan
            @endif
            @if($canGenerate)
            <form method="POST" action="{{ route('storage-billing.generate', $selectedClient) }}">
                @csrf
                <button type="submit"
                    onclick="return confirm('Générer la facture congélation pour la période en cours ?')"
                    class="inline-flex items-center gap-1.5 bg-cyan-600 hover:bg-cyan-700 text-white px-3 py-2 rounded-xl text-sm font-semibold">
                    <i class="fas fa-plus"></i> Générer période courante
                </button>
            </form>
            @endif
            <a href="{{ route('clients.show', $selectedClient) }}#storage-config"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-sm border border-slate-200 text-slate-500 hover:text-cyan-600 hover:bg-cyan-50 rounded-xl dark:border-slate-600 dark:hover:bg-slate-700">
                <i class="fas fa-cog"></i> Config
            </a>
        </div>
    </div>
    <div class="grid grid-cols-2 border-t border-slate-100 dark:border-slate-700">
        <div class="px-5 py-3 border-r border-slate-100 dark:border-slate-700">
            <p class="text-xs text-slate-400 mb-0.5">Cumulé impayé</p>
            <p class="text-xl font-bold text-amber-600">{{ number_format($cumulativeUnpaid, 2, ',', ' ') }} MRU</p>
        </div>
        <div class="px-5 py-3">
            <p class="text-xs text-slate-400 mb-0.5">Total encaissé</p>
            <p class="text-xl font-bold text-emerald-600">{{ number_format($totalPaid, 2, ',', ' ') }} MRU</p>
        </div>
    </div>
    @can('storage-billing.collect')
    @if($cumulativeUnpaid > 0)
    <form method="POST" action="{{ route('storage-billing.collect', $selectedClient) }}"
          x-data="{ amount: {{ $cumulativeUnpaid }}, balance: {{ $cumulativeUnpaid }} }"
          class="flex flex-wrap items-end gap-2 px-5 py-3 border-t border-slate-100 dark:border-slate-700 bg-emerald-50/40">
        @csrf
        <div>
            <label class="block text-xs text-emerald-800 mb-1">Encaisser un paiement (partiel ou total)</label>
            <input type="number" name="amount" step="0.01" min="0.01" :max="balance" x-model.number="amount" class="w-40 px-3 py-2 border border-emerald-200 rounded-xl text-sm font-bold">
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
        <span class="text-[11px] text-emerald-800">Réparti automatiquement sur les factures les plus anciennes.</span>
        @error('amount')<p class="w-full text-xs text-red-600">{{ $message }}</p>@enderror
    </form>
    @endif
    @endcan
</div>

@if($clientInvoices->isEmpty())
<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 text-center py-14 text-slate-400">
    <i class="fas fa-snowflake text-3xl mb-3 opacity-20"></i>
    <p class="font-medium">Aucune facture congélation pour ce client</p>
    @if($clientRule)
    <p class="text-xs mt-1">La prochaine échéance sera générée automatiquement.</p>
    @else
    <p class="text-xs mt-1">Configurez d'abord une règle de facturation dans la fiche client.</p>
    <a href="{{ route('clients.show', $selectedClient) }}#storage-config"
       class="inline-flex items-center gap-2 mt-3 bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
        <i class="fas fa-cog"></i> Configurer
    </a>
    @endif
</div>
@else
<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
    <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-white">
            <i class="fas fa-history mr-2 text-cyan-500"></i>Historique des échéances ({{ $clientInvoices->count() }})
        </h3>
        @php $nbUnpaid = $clientInvoices->whereIn('status', ['draft','partial'])->count(); @endphp
        @if($nbUnpaid > 0)
        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
            {{ $nbUnpaid }} impayée{{ $nbUnpaid > 1 ? 's' : '' }}
        </span>
        @endif
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-400 uppercase">
                <tr>
                    <th class="px-5 py-2.5 text-left">N° Facture</th>
                    <th class="px-5 py-2.5 text-left">Date</th>
                    <th class="px-5 py-2.5 text-left">Période / Description</th>
                    <th class="px-5 py-2.5 text-right">Qté</th>
                    <th class="px-5 py-2.5 text-right">PU</th>
                    <th class="px-5 py-2.5 text-right">Total</th>
                    <th class="px-5 py-2.5 text-center">Statut</th>
                    <th class="px-5 py-2.5 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
            @foreach($clientInvoices as $invoice)
            @php
                $isPaid = $invoice->status === 'paid';
                $isLate = !$isPaid && $invoice->due_date && $invoice->due_date->isPast();
                $item   = $invoice->items->first();
            @endphp
            <tr class="{{ $isLate ? 'bg-red-50/30 dark:bg-red-900/10' : ($isPaid ? 'bg-emerald-50/20 dark:bg-emerald-900/5' : '') }}">
                <td class="px-5 py-3">
                    <a href="{{ route('invoices.show', $invoice) }}"
                       class="font-mono text-xs font-semibold text-primary-600 hover:underline">
                        {{ $invoice->invoice_number }}
                    </a>
                </td>
                <td class="px-5 py-3 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
                    {{ $invoice->invoice_date->format('d/m/Y') }}
                    @if($invoice->due_date)
                    <br><span class="{{ $isLate ? 'text-red-500 font-semibold' : 'text-slate-400' }}">
                        éch. {{ $invoice->due_date->format('d/m/Y') }}
                        @if($isLate)<i class="fas fa-exclamation-circle"></i>@endif
                    </span>
                    @endif
                </td>
                <td class="px-5 py-3 text-xs text-slate-600 dark:text-slate-300 max-w-xs">
                    @if($item)
                    <span>{{ $item->description }}</span>
                    @else <span class="text-slate-300">—</span>@endif
                </td>
                <td class="px-5 py-3 text-right text-xs text-slate-600 dark:text-slate-300 whitespace-nowrap">
                    @if($item){{ number_format($item->quantity, 2) }} {{ str_contains(strtolower($item->description ?? ''), 'kg') ? 'kg' : 'ctn' }}@else —@endif
                </td>
                <td class="px-5 py-3 text-right text-xs text-slate-500 whitespace-nowrap">
                    @if($item){{ number_format($item->unit_price, 2) }} MRU@else —@endif
                </td>
                <td class="px-5 py-3 text-right font-bold text-sm whitespace-nowrap {{ $isPaid ? 'text-emerald-700' : 'text-slate-800 dark:text-white' }}">
                    {{ number_format($invoice->total, 2, ',', ' ') }} MRU
                    @if($invoice->status === 'partial')
                    <br><span class="text-[11px] font-medium text-amber-600">reste {{ number_format($invoice->balance, 2, ',', ' ') }}</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-center">
                    @if($isPaid)
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                        <i class="fas fa-check mr-0.5"></i>Payée
                    </span>
                    @elseif($invoice->status === 'partial')
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">Partielle</span>
                    @elseif($isLate)
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                        <i class="fas fa-clock mr-0.5"></i>En retard
                    </span>
                    @else
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">À payer</span>
                    @endif
                </td>
                <td class="px-5 py-3">
                    <div class="flex items-center justify-center gap-1.5">
                        @if(!$isPaid && auth()->user()->can('storage-billing.pay'))
                        <form method="POST" action="{{ route('storage-billing.pay', $invoice) }}">
                            @csrf @method('PATCH')
                            <button type="submit"
                                onclick="return confirm('Encaisser le solde de {{ number_format($invoice->balance, 2, ',', ' ') }} MRU pour {{ $invoice->invoice_number }} ?')"
                                class="inline-flex items-center gap-1 bg-emerald-600 hover:bg-emerald-700 text-white px-2.5 py-1.5 rounded-lg text-xs font-semibold">
                                <i class="fas fa-check"></i> Solder
                            </button>
                        </form>
                        @endif
                        <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank"
                           class="p-1.5 text-red-500 border border-red-200 hover:bg-red-50 rounded-lg dark:border-red-800 dark:hover:bg-red-900/20" title="PDF">
                            <i class="fas fa-file-pdf text-xs"></i>
                        </a>
                        <a href="{{ route('invoices.show', $invoice) }}"
                           class="p-1.5 text-slate-400 border border-slate-200 hover:bg-slate-50 rounded-lg dark:border-slate-600 dark:hover:bg-slate-700" title="Détails">
                            <i class="fas fa-eye text-xs"></i>
                        </a>
                    </div>
                </td>
            </tr>
            @endforeach
            </tbody>
            @if($cumulativeUnpaid > 0)
            <tfoot>
                <tr class="bg-amber-50 dark:bg-amber-900/20 border-t-2 border-amber-200 dark:border-amber-800">
                    <td colspan="5" class="px-5 py-3 text-sm font-bold text-amber-700 dark:text-amber-400 text-right">
                        Total cumulé impayé
                    </td>
                    <td class="px-5 py-3 text-right font-bold text-amber-700 dark:text-amber-400 text-base whitespace-nowrap">
                        {{ number_format($cumulativeUnpaid, 2, ',', ' ') }} MRU
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endif

{{-- VUE D'ENSEMBLE --}}
@else

@if($overview->isEmpty())
<div class="text-center py-16 text-slate-400">
    <i class="fas fa-snowflake text-4xl mb-4 opacity-20"></i>
    <p class="text-lg font-medium">Aucun client avec facturation congélation</p>
    <p class="text-sm mt-1">Configurez une règle de facturation dans la fiche de chaque client.</p>
    <a href="{{ route('clients.index') }}"
       class="mt-4 inline-flex items-center gap-2 bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
        <i class="fas fa-users"></i> Gérer les clients
    </a>
</div>
@else
<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
    <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-white">
            <i class="fas fa-users mr-2 text-cyan-500"></i>Clients — Résumé congélation ({{ $overview->count() }})
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-400 uppercase">
                <tr>
                    <th class="px-5 py-2.5 text-left">Client</th>
                    <th class="px-5 py-2.5 text-left">Fréquence</th>
                    <th class="px-5 py-2.5 text-left">Tarif</th>
                    <th class="px-5 py-2.5 text-left">Période courante</th>
                    <th class="px-5 py-2.5 text-right">Impayé cumulé</th>
                    <th class="px-5 py-2.5 text-left">Dernière facture</th>
                    <th class="px-5 py-2.5 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
            @foreach($overview as $row)
            <tr class="{{ $row['is_overdue'] ? 'bg-red-50/30 dark:bg-red-900/10' : '' }}">
                <td class="px-5 py-3">
                    <a href="{{ route('storage-billing.index', ['client_id' => $row['client']->id]) }}"
                       class="font-semibold text-slate-800 dark:text-white hover:text-cyan-600">
                        {{ $row['client']->name }}
                    </a>
                    @if($row['is_overdue'])
                    <span class="ml-1.5 px-1.5 py-0.5 rounded text-xs bg-red-100 text-red-600 font-semibold dark:bg-red-900/30 dark:text-red-400">
                        <i class="fas fa-clock"></i> Retard
                    </span>
                    @endif
                </td>
                <td class="px-5 py-3 text-xs text-slate-500 dark:text-slate-400">
                    @if($row['rule'])
                    {{ match($row['rule']->billing_frequency ?? 'week') { 'day'=>'Journalier','month'=>'Mensuel',default=>'Hebdo (lundi)' } }}
                    @else <span class="text-slate-300">—</span>@endif
                </td>
                <td class="px-5 py-3 text-xs text-slate-600 dark:text-slate-300 whitespace-nowrap">
                    @if($row['rule'])
                    {{ number_format($row['rule']->price, 2) }} MRU/{{ $row['rule']->charge_unit === 'kg' ? 'kg' : 'ctn' }}
                    @else <span class="text-slate-300">Non configuré</span>@endif
                </td>
                <td class="px-5 py-3 text-xs text-indigo-600 dark:text-indigo-400 whitespace-nowrap">
                    {{ $row['period_label'] ?? '—' }}
                </td>
                <td class="px-5 py-3 text-right">
                    @if($row['total_unpaid'] > 0)
                    <span class="font-bold text-amber-600">{{ number_format($row['total_unpaid'], 2, ',', ' ') }} MRU</span>
                    <span class="ml-1 text-xs text-amber-500">({{ $row['nb_unpaid'] }})</span>
                    @else
                    <span class="text-emerald-600 text-xs font-semibold">À jour</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-xs text-slate-400 whitespace-nowrap">
                    @if($row['last_invoice'])
                    {{ $row['last_invoice']->invoice_date->format('d/m/Y') }}
                    @else <span class="text-slate-300">Jamais</span>@endif
                </td>
                <td class="px-5 py-3">
                    <div class="flex items-center justify-center gap-1.5">
                        <a href="{{ route('storage-billing.index', ['client_id' => $row['client']->id]) }}"
                           class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg font-semibold">
                            <i class="fas fa-eye"></i> Détail
                        </a>
                        @if($row['rule'] && $row['is_overdue'])
                        <form method="POST" action="{{ route('storage-billing.generate', $row['client']) }}">
                            @csrf
                            <button type="submit"
                                onclick="return confirm('Générer facture pour {{ addslashes($row["client"]->name) }} ?')"
                                class="px-2.5 py-1.5 text-xs bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-semibold">
                                <i class="fas fa-plus"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endif

@endsection
