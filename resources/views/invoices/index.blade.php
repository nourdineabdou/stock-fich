@extends('layouts.app')
@section('title', 'Facturation Traitement')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Facturation Traitement</h1>
        <p class="text-sm text-slate-400 mt-0.5">Factures de traitement de poisson uniquement</p>
    </div>
    <a href="{{ route('invoices.create') }}" class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
        <i class="fas fa-plus"></i> Nouvelle Facture
    </a>
</div>

{{-- Summary --}}
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase mb-1">Ce mois (traitement)</p>
        <p class="text-2xl font-bold text-primary-600">{{ number_format($totals['this_month'], 0, ',', ' ') }} MRU</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase mb-1">Non payées</p>
        <p class="text-2xl font-bold text-amber-600">{{ number_format($totals['unpaid'], 0, ',', ' ') }} MRU</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase mb-1">Payées</p>
        <p class="text-2xl font-bold text-emerald-600">{{ number_format($totals['paid'], 0, ',', ' ') }} MRU</p>
    </div>
</div>

<div class="bg-white dark:bg-slate-800 rounded-2xl p-4 mb-5 shadow-sm border border-slate-100 dark:border-slate-700">
    <form method="GET" class="flex flex-wrap gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="N° facture ou réf traitement (TR-...)"
            class="min-w-[260px] px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
        <select name="client_id" class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">Tous clients</option>
            @foreach($clients as $c)
            <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
        <select name="status" class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">Tous</option>
            <option value="draft" {{ request('status')=='draft'?'selected':'' }}>Brouillon</option>
            <option value="sent" {{ request('status')=='sent'?'selected':'' }}>Envoyée</option>
            <option value="paid" {{ request('status')=='paid'?'selected':'' }}>Payée</option>
            <option value="cancelled" {{ request('status')=='cancelled'?'selected':'' }}>Annulée</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-xl text-sm"><i class="fas fa-filter"></i></button>
    </form>
</div>

<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-500 uppercase tracking-wider">
            <tr>
                <th class="px-4 py-3 text-left">N° Facture</th>
                <th class="px-4 py-3 text-left">Client</th>
                <th class="px-4 py-3 text-left">Réf. Traitement</th>
                <th class="px-4 py-3 text-left">Date</th>
                <th class="px-4 py-3 text-right">Montant</th>
                <th class="px-4 py-3 text-center">Statut</th>
                <th class="px-4 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
            @forelse($invoices as $invoice)
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <td class="px-4 py-3">
                    <a href="{{ route('invoices.show', $invoice) }}" class="font-mono font-semibold text-primary-600 hover:underline text-xs">{{ $invoice->invoice_number }}</a>
                    @if($invoice->processingOrder)
                    <div class="mt-1 text-[11px] text-slate-500">{{ $invoice->processingOrder->reference }}</div>
                    @endif
                </td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ $invoice->client->name ?? '-' }}</td>
                <td class="px-4 py-3 text-xs text-slate-500">
                    {{ $invoice->processingOrder?->reference ?? '—' }}
                </td>
                <td class="px-4 py-3 text-slate-500">{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                <td class="px-4 py-3 text-right font-bold text-slate-800 dark:text-white">{{ number_format($invoice->total_amount, 2, ',', ' ') }} MRU</td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ match($invoice->status) {
                        'draft'=>'bg-slate-100 text-slate-600','sent'=>'bg-blue-100 text-blue-700',
                        'paid'=>'bg-emerald-100 text-emerald-700','cancelled'=>'bg-red-100 text-red-600',default=>'bg-slate-100 text-slate-500'
                    } }}">{{ match($invoice->status) { 'draft'=>'Brouillon','sent'=>'Envoyée','paid'=>'Payée','cancelled'=>'Annulée',default=>$invoice->status } }}</span>
                </td>
                <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center gap-1">
                        <a href="{{ route('invoices.show', $invoice) }}" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg"><i class="fas fa-eye text-xs"></i></a>
                        <a href="{{ route('invoices.edit', $invoice) }}" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg"><i class="fas fa-pen text-xs"></i></a>
                        <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg"><i class="fas fa-file-pdf text-xs"></i></a>
                        @if($invoice->status !== 'paid')
                        <form method="POST" action="{{ route('invoices.mark-paid', $invoice) }}" class="inline">@csrf @method('PATCH')
                            <button type="submit" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg" title="Marquer payée"><i class="fas fa-check text-xs"></i></button>
                        </form>
                        @endif
                        <button onclick="confirmDelete('{{ route('invoices.destroy', $invoice) }}','{{ addslashes($invoice->invoice_number) }}')" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg"><i class="fas fa-trash text-xs"></i></button>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center py-12 text-slate-400">
                <i class="fas fa-file-invoice text-3xl mb-2 block"></i><p>Aucune facture</p>
            </td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-700">{{ $invoices->links() }}</div>
</div>
@endsection

