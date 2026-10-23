@extends('layouts.app')
@section('title', isset($invoice) ? 'Modifier Facture' : 'Nouvelle Facture')
@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('invoices.index') }}" class="p-2 text-slate-500 hover:bg-slate-100 rounded-xl"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">
            {{ isset($invoice) ? 'Modifier: '.$invoice->invoice_number : 'Nouvelle Facture' }}
        </h1>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
        @if($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-950/20 dark:border-red-900 dark:text-red-300">
            <p class="font-semibold mb-1">Impossible d'enregistrer la facture:</p>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ isset($invoice) ? route('invoices.update', $invoice) : route('invoices.store') }}">
            @csrf
            @if(isset($invoice)) @method('PUT') @endif
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Client <span class="text-red-500">*</span></label>
                    <select name="client_id" id="clientSelect" required class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Choisir...</option>
                        @foreach($clients as $c)
                        <option value="{{ $c->id }}" {{ old('client_id', $invoice->client_id ?? request('client_id')) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Ordre de traitement</label>
                    <select name="processing_order_id" id="processingOrderSelect" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Choisir...</option>
                        @foreach($processingOrders ?? [] as $po)
                        <option value="{{ $po->id }}" {{ old('processing_order_id', optional($selectedProcessingOrder)->id ?? request('processing_order_id')) == $po->id ? 'selected' : '' }}>{{ $po->reference }} — {{ $po->client->name ?? '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Date <span class="text-red-500">*</span></label>
                    <input type="date" name="invoice_date" value="{{ old('invoice_date', isset($invoice) ? $invoice->invoice_date->format('Y-m-d') : today()->format('Y-m-d')) }}" required
                        class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Date échéance</label>
                    <input type="date" name="due_date" value="{{ old('due_date', isset($invoice) && $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '') }}"
                        class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">TVA (%)</label>
                    <input type="number" step="0.1" name="tax_rate" value="{{ old('tax_rate', $invoice->tax_rate ?? \App\Models\CompanySetting::taxRate()) }}"
                        class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Statut</label>
                    <select name="status" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="draft" {{ old('status', $invoice->status ?? 'draft') == 'draft' ? 'selected' : '' }}>Brouillon</option>
                        <option value="sent" {{ old('status', $invoice->status ?? '') == 'sent' ? 'selected' : '' }}>Envoyée</option>
                        <option value="paid" {{ old('status', $invoice->status ?? '') == 'paid' ? 'selected' : '' }}>Payée</option>
                        <option value="cancelled" {{ old('status', $invoice->status ?? '') == 'cancelled' ? 'selected' : '' }}>Annulée</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Notes</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">{{ old('notes', $invoice->notes ?? '') }}</textarea>
                </div>
            </div>

            <div class="mb-5 flex items-center justify-between gap-3 rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3 dark:bg-indigo-950/20 dark:border-indigo-900">
                <div>
                    <p class="text-sm font-semibold text-indigo-800 dark:text-indigo-200">Aperçu facture client</p>
                    <p class="text-xs text-indigo-700 dark:text-indigo-300">Les lignes sont calculées depuis le traitement par type de poisson.</p>
                </div>
                @if(old('processing_order_id', optional($selectedProcessingOrder)->id ?? request('processing_order_id')))
                <a href="{{ route('invoices.preview', ['processing_order_id' => old('processing_order_id', optional($selectedProcessingOrder)->id ?? request('processing_order_id')), 'tax_rate' => old('tax_rate', $tax)]) }}" target="_blank" rel="noopener" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold">
                    <i class="fas fa-file-pdf mr-1"></i> Voir le PDF client
                </a>
                @endif
            </div>

            <div class="border-t border-slate-100 dark:border-slate-700 pt-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-white"><i class="fas fa-list mr-1.5 text-primary-500"></i>Lignes de Facturation</h3>
                    <button type="button" onclick="addInvoiceItem()" class="text-xs bg-primary-50 hover:bg-primary-100 text-primary-700 px-3 py-1.5 rounded-xl">
                        <i class="fas fa-plus mr-1"></i> Ajouter
                    </button>
                </div>
                <div id="invoiceItems" class="space-y-2">
                    @foreach(($invoiceItems ?? $invoice->items ?? []) as $i => $item)
                    <div class="grid grid-cols-12 gap-2 bg-slate-50 dark:bg-slate-700 rounded-xl p-2">
                        <input type="hidden" name="items[{{ $i }}][service_type]" value="{{ is_array($item) ? ($item['service_type'] ?? 'other') : ($item->service_type ?? 'other') }}">
                        <input type="hidden" name="items[{{ $i }}][reference_type]" value="{{ is_array($item) ? ($item['reference_type'] ?? '') : ($item->reference_type ?? '') }}">
                        <input type="hidden" name="items[{{ $i }}][reference_id]" value="{{ is_array($item) ? ($item['reference_id'] ?? '') : ($item->reference_id ?? '') }}">
                        <div class="col-span-5"><input type="text" name="items[{{ $i }}][description]" value="{{ is_array($item) ? $item['description'] : $item->description }}" placeholder="Description" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none"></div>
                        <div class="col-span-2"><input type="number" step="0.01" name="items[{{ $i }}][quantity]" value="{{ is_array($item) ? $item['quantity'] : $item->quantity }}" placeholder="Qté" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none" onchange="calcLine(this)"></div>
                        <div class="col-span-2"><input type="number" step="0.01" name="items[{{ $i }}][unit_price]" value="{{ is_array($item) ? $item['unit_price'] : $item->unit_price }}" placeholder="PU" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none" onchange="calcLine(this)"></div>
                        <div class="col-span-2 flex items-center"><span class="total-display text-xs font-bold text-emerald-600">{{ number_format((is_array($item) ? $item['quantity'] * $item['unit_price'] : $item->quantity * $item->unit_price), 2) }}</span></div>
                        <div class="col-span-1 flex items-center justify-end"><button type="button" onclick="removeItem(this)" class="p-1 text-red-600"><i class="fas fa-times text-xs"></i></button></div>
                    </div>
                    @endforeach
                </div>
                <div class="flex justify-end mt-3">
                    <p class="text-sm text-slate-500">Total HT: <span id="grandTotal" class="font-bold text-slate-800 dark:text-white">{{ number_format($subtotal ?? 0, 2) }}</span> MRU</p>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-5 mt-5 border-t border-slate-100 dark:border-slate-700">
                <button type="submit" class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-semibold text-sm">
                    <i class="fas fa-check mr-1.5"></i> {{ isset($invoice) ? 'Mettre à jour' : 'Enregistrer' }}
                </button>
                <a href="{{ route('invoices.index') }}" class="px-6 py-2.5 border border-slate-200 text-slate-600 rounded-xl text-sm">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
let invIdx = {{ isset($invoice) ? count($invoice->items ?? []) : count($invoiceItems ?? []) }};
const initialTax = {{ $tax ?? \App\Models\CompanySetting::taxRate() }};

function addInvoiceItem() {
    const i = invIdx++;
    document.getElementById('invoiceItems').insertAdjacentHTML('beforeend', `
    <div class="grid grid-cols-12 gap-2 bg-slate-50 dark:bg-slate-700 rounded-xl p-2">
        <input type="hidden" name="items[${i}][service_type]" value="other">
        <input type="hidden" name="items[${i}][reference_type]" value="">
        <input type="hidden" name="items[${i}][reference_id]" value="">
        <div class="col-span-5"><input type="text" name="items[${i}][description]" placeholder="Description" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none"></div>
        <div class="col-span-2"><input type="number" step="0.01" name="items[${i}][quantity]" placeholder="Qté" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none" oninput="calcLine(this)"></div>
        <div class="col-span-2"><input type="number" step="0.01" name="items[${i}][unit_price]" placeholder="PU" class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg dark:bg-slate-600 dark:border-slate-500 dark:text-white focus:outline-none" oninput="calcLine(this)"></div>
        <div class="col-span-2 flex items-center"><span class="total-display text-xs font-bold text-emerald-600">0.00</span></div>
        <div class="col-span-1 flex items-center justify-end"><button type="button" onclick="removeItem(this)" class="p-1 text-red-600"><i class="fas fa-times text-xs"></i></button></div>
    </div>`);
}

function calcLine(input) {
    const row = input.closest('.grid');
    const qty = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
    const pu = parseFloat(row.querySelector('input[name*="[unit_price]"]').value) || 0;
    row.querySelector('.total-display').textContent = (qty * pu).toFixed(2);
    updateGrandTotal();
}

function updateGrandTotal() {
    let total = 0;
    document.querySelectorAll('.total-display').forEach(el => { total += parseFloat(el.textContent) || 0; });
    document.getElementById('grandTotal').textContent = total.toFixed(2);
}

function removeItem(btn) { btn.closest('.grid').remove(); updateGrandTotal(); }

document.getElementById('processingOrderSelect')?.addEventListener('change', function() {
    const previewLink = document.querySelector('a[href*="invoices/preview"]');
    if (!previewLink) return;

    const url = new URL(previewLink.href);
    if (this.value) {
        url.searchParams.set('processing_order_id', this.value);
        url.searchParams.set('tax_rate', document.querySelector('input[name="tax_rate"]')?.value || initialTax);
        previewLink.href = url.toString();
    }
});

updateGrandTotal();
</script>
@endpush

