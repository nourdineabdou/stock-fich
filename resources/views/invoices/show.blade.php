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
        <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
            <i class="fas fa-print"></i> Imprimer la facture
        </a>
        @can('invoices.edit')
        <a href="{{ route('invoices.edit', $invoice) }}" class="p-2 text-amber-600 bg-amber-50 hover:bg-amber-100 rounded-xl"><i class="fas fa-pen"></i></a>
        @endcan
        @can('invoices.destroy')
        <button onclick="confirmDelete('{{ route('invoices.destroy', $invoice) }}','{{ addslashes($invoice->invoice_number) }}')" class="p-2 text-red-600 border border-red-200 hover:bg-red-50 rounded-xl"><i class="fas fa-trash"></i></button>
        @endcan
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
                    @if($invoice->tax_rate > 0)
                    <tr>
                        <td colspan="3" class="px-4 py-2 text-right text-slate-500">TVA ({{ $invoice->tax_rate }}%)</td>
                        <td class="px-4 py-2 text-right font-semibold">{{ number_format($tax, 2) }} MRU</td>
                    </tr>
                    @endif
                    <tr class="font-bold text-slate-800 dark:text-white">
                        <td colspan="3" class="px-4 py-2 text-right">{{ $invoice->tax_rate > 0 ? 'Total TTC' : 'Total' }}</td>
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
                        'paid'=>'bg-emerald-100 text-emerald-700','partial'=>'bg-amber-100 text-amber-700',
                        'cancelled'=>'bg-red-100 text-red-600',default=>'bg-slate-100 text-slate-500'
                    } }}">{{ match($invoice->status) { 'draft'=>'À payer','sent'=>'Envoyée','paid'=>'Payée','partial'=>'Payée partiellement','cancelled'=>'Annulée',default=>$invoice->status } }}</span></dd>
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
                <div class="flex justify-between">
                    <dt class="text-slate-400">Encaissé</dt>
                    <dd class="font-semibold text-emerald-600">{{ number_format($invoice->paid_amount, 2, ',', ' ') }} MRU</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400">Reste à payer</dt>
                    <dd class="font-bold {{ $invoice->balance > 0 ? 'text-amber-600' : 'text-emerald-600' }}">{{ number_format($invoice->balance, 2, ',', ' ') }} MRU</dd>
                </div>
            </dl>
            @php $paidPct = $invoice->total > 0 ? min(100, $invoice->paid_amount / $invoice->total * 100) : 0; @endphp
            <div class="mt-3 h-2 rounded-full bg-slate-100 overflow-hidden"><div class="h-full bg-emerald-500" style="width: {{ $paidPct }}%"></div></div>
        </div>

        {{-- Encaissement : partiel ou total --}}
        @can('invoices.payments.store')
        @if($invoice->balance > 0 && $invoice->status !== 'cancelled')
        <form method="POST" action="{{ route('invoices.payments.store', $invoice) }}"
              x-data="{ amount: {{ old('amount', $invoice->balance) }}, balance: {{ $invoice->balance }} }"
              class="mt-5 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-emerald-200 dark:border-emerald-900 p-5">
            @csrf
            <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-3"><i class="fas fa-hand-holding-dollar mr-2 text-emerald-500"></i>Encaisser</h3>
            @error('amount')<p class="mb-2 text-xs text-red-600">{{ $message }}</p>@enderror
            <label class="block text-xs text-slate-500 mb-1">Montant reçu (MRU)</label>
            <input type="number" name="amount" step="0.01" min="0.01" :max="balance" x-model.number="amount" required
                   class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-lg font-bold dark:bg-slate-700 dark:border-slate-600 dark:text-white">
            <div class="flex gap-1.5 mt-2">
                <button type="button" @click="amount = balance" class="flex-1 px-2 py-1.5 text-xs rounded-lg bg-emerald-50 text-emerald-700 font-semibold hover:bg-emerald-100">Tout</button>
                <button type="button" @click="amount = Math.round(balance * 50) / 100" class="flex-1 px-2 py-1.5 text-xs rounded-lg bg-slate-100 text-slate-600 font-semibold hover:bg-slate-200">Moitié</button>
            </div>
            <p class="mt-2 text-xs" :class="amount < balance ? 'text-amber-600' : 'text-emerald-600'"
               x-text="amount > 0 && amount < balance ? `Paiement partiel — il restera ${(balance - amount).toLocaleString('fr-FR', {minimumFractionDigits: 2})} MRU` : 'Paiement total — la facture sera soldée'"></p>
            <div class="grid grid-cols-2 gap-2 mt-3">
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Mode</label>
                    <select name="method" class="w-full px-2 py-2 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                        @foreach(\App\Models\InvoicePayment::METHODS as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Date</label>
                    <input type="date" name="paid_at" value="{{ now()->toDateString() }}" class="w-full px-2 py-2 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                </div>
            </div>
            <input type="text" name="reference" placeholder="Référence (n° chèque, virement…)" class="mt-2 w-full px-3 py-2 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white">
            <button type="submit" :disabled="!(amount > 0 && amount <= balance + 0.001)" class="mt-3 w-full px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-40 text-white rounded-xl text-sm font-semibold">
                <i class="fas fa-check mr-1"></i> Enregistrer l'encaissement
            </button>
        </form>
        @endif
        @endcan

        @if($invoice->payments->isNotEmpty())
        <div class="mt-5 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
            <h3 class="px-5 py-3 border-b border-slate-100 dark:border-slate-700 text-sm font-semibold text-slate-700 dark:text-white"><i class="fas fa-receipt mr-2 text-emerald-500"></i>Encaissements</h3>
            <div class="divide-y divide-slate-50 dark:divide-slate-700">
                @foreach($invoice->payments as $p)
                <div class="px-5 py-3 flex items-center justify-between gap-2 text-sm">
                    <div>
                        <p class="font-semibold text-slate-700 dark:text-white">{{ number_format($p->amount, 2, ',', ' ') }} MRU</p>
                        <p class="text-xs text-slate-400">{{ $p->paid_at->format('d/m/Y') }} · {{ $p->method_label }}{{ $p->reference ? ' · '.$p->reference : '' }}{{ $p->receiver ? ' · '.$p->receiver->name : '' }}</p>
                    </div>
                    <div class="flex items-center gap-1">
                        <a href="{{ route('payments.receipt', $p->receipt_number) }}" target="_blank" class="text-xs font-mono text-primary-600 hover:underline" title="Imprimer le reçu">{{ $p->receipt_number }}</a>
                        @can('payments.destroy')
                        <form method="POST" action="{{ route('payments.destroy', $p) }}" onsubmit="return confirm('Annuler cet encaissement ?')">
                            @csrf @method('DELETE')
                            <button class="p-1.5 text-red-400 hover:text-red-600" title="Annuler"><i class="fas fa-xmark text-xs"></i></button>
                        </form>
                        @endcan
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

