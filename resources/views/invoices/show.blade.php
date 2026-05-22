@extends('layouts.app')
@section('title', $invoice->invoice_number)
@section('content')
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('invoices.index') }}" class="p-2 text-slate-500 hover:bg-slate-100 rounded-xl"><i class="fas fa-arrow-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white font-mono">{{ $invoice->invoice_number }}</h1>
            <p class="text-sm text-slate-500">{{ $invoice->client->name ?? '-' }} · {{ $invoice->invoice_date->format('d/m/Y') }}</p>
        </div>
    </div>
    <div class="flex gap-2">
        @if($invoice->status !== 'paid')
        <form method="POST" action="{{ route('invoices.mark-paid', $invoice) }}">@csrf @method('PATCH')
            <button type="submit" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
                <i class="fas fa-check"></i> Marquer payée
            </button>
        </form>
        @endif
        <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
            <i class="fas fa-file-pdf"></i> PDF
        </a>
        <a href="{{ route('invoices.edit', $invoice) }}" class="p-2 text-amber-600 bg-amber-50 hover:bg-amber-100 rounded-xl"><i class="fas fa-pen"></i></a>
        <button onclick="confirmDelete('{{ route('invoices.destroy', $invoice) }}','{{ addslashes($invoice->invoice_number) }}')" class="p-2 text-red-600 border border-red-200 hover:bg-red-50 rounded-xl"><i class="fas fa-trash"></i></button>
    </div>
</div>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-white"><i class="fas fa-list mr-2 text-primary-500"></i>Lignes de Facturation</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-2 text-left">Description</th>
                        <th class="px-4 py-2 text-right">Qté</th>
                        <th class="px-4 py-2 text-right">PU</th>
                        <th class="px-4 py-2 text-right">Total HT</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                    @foreach($invoice->items as $item)
                    <tr>
                        <td class="px-4 py-3 text-slate-700 dark:text-white">{{ $item->description }}</td>
                        <td class="px-4 py-3 text-right">{{ $item->quantity }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($item->unit_price, 2) }} MRU</td>
                        <td class="px-4 py-3 text-right font-bold">{{ number_format($item->quantity * $item->unit_price, 2) }} MRU</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50 dark:bg-slate-700/50 text-sm">
                    @php $ht = $invoice->items->sum(fn($i) => $i->quantity * $i->unit_price); $tax = $ht * ($invoice->tax_rate / 100); @endphp
                    <tr>
                        <td colspan="3" class="px-4 py-2 text-right text-slate-500">Sous-total HT</td>
                        <td class="px-4 py-2 text-right font-semibold">{{ number_format($ht, 2) }} MRU</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="px-4 py-2 text-right text-slate-500">TVA ({{ $invoice->tax_rate }}%)</td>
                        <td class="px-4 py-2 text-right font-semibold">{{ number_format($tax, 2) }} MRU</td>
                    </tr>
                    <tr class="font-bold text-slate-800 dark:text-white">
                        <td colspan="3" class="px-4 py-2 text-right">Total TTC</td>
                        <td class="px-4 py-2 text-right text-primary-600 text-base">{{ number_format($invoice->total_amount, 2) }} MRU</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-4"><i class="fas fa-info-circle mr-2 text-primary-500"></i>Infos</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-400">Statut</dt>
                    <dd><span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ match($invoice->status) {
                        'draft'=>'bg-slate-100 text-slate-600','sent'=>'bg-blue-100 text-blue-700',
                        'paid'=>'bg-emerald-100 text-emerald-700','cancelled'=>'bg-red-100 text-red-600',default=>'bg-slate-100 text-slate-500'
                    } }}">{{ match($invoice->status) { 'draft'=>'Brouillon','sent'=>'Envoyée','paid'=>'Payée','cancelled'=>'Annulée',default=>$invoice->status } }}</span></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400">Émise le</dt>
                    <dd class="text-slate-700 dark:text-white">{{ $invoice->invoice_date->format('d/m/Y') }}</dd>
                </div>
                @if($invoice->due_date)
                <div class="flex justify-between">
                    <dt class="text-slate-400">Échéance</dt>
                    <dd class="text-slate-700 dark:text-white {{ $invoice->status !== 'paid' && $invoice->due_date->isPast() ? 'text-red-500 font-bold' : '' }}">{{ $invoice->due_date->format('d/m/Y') }}</dd>
                </div>
                @endif
                <div class="flex justify-between border-t border-slate-100 dark:border-slate-700 pt-3">
                    <dt class="text-slate-400 font-bold">Total TTC</dt>
                    <dd class="font-bold text-primary-600 text-base">{{ number_format($invoice->total_amount, 2) }} MRU</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection

