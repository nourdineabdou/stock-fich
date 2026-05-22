@extends('layouts.app')
@section('title', $delivery->reference)
@section('content')
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('deliveries.index') }}" class="p-2 text-slate-500 hover:bg-slate-100 rounded-xl"><i class="fas fa-arrow-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white font-mono">{{ $delivery->reference }}</h1>
            <p class="text-sm text-slate-500">{{ $delivery->client->name ?? '-' }} · {{ $delivery->delivery_date->format('d/m/Y') }}</p>
        </div>
    </div>
    <div class="flex gap-2">
        @if($delivery->status !== 'delivered')
        <form method="POST" action="{{ route('deliveries.validate', $delivery) }}">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-semibold"
                    onclick="return confirm('Valider ce retrait client et decremeter le stock ?')">
                <i class="fas fa-check"></i> Valider Retrait
            </button>
        </form>
        @endif
        <a href="{{ route('deliveries.pdf', $delivery) }}" target="_blank" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
            <i class="fas fa-file-pdf"></i> Recu sortie PDF
        </a>
        <a href="{{ route('deliveries.edit', $delivery) }}" class="p-2 text-amber-600 bg-amber-50 hover:bg-amber-100 rounded-xl"><i class="fas fa-pen"></i></a>
        <button onclick="confirmDelete('{{ route('deliveries.destroy', $delivery) }}','{{ addslashes($delivery->reference) }}')" class="p-2 text-red-600 border border-red-200 hover:bg-red-50 rounded-xl"><i class="fas fa-trash"></i></button>
    </div>
</div>

@if($errors->has('stock_validation'))
<div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
    {{ $errors->first('stock_validation') }}
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-white"><i class="fas fa-list mr-2 text-cyan-500"></i>Articles Livrés</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-2 text-left">Lot</th>
                        <th class="px-4 py-2 text-left">Poisson / Carton</th>
                        <th class="px-4 py-2 text-right">Qté</th>
                        <th class="px-4 py-2 text-right">Prix/carton</th>
                        <th class="px-4 py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                    @foreach($delivery->items as $item)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $item->stockBatch->lot_number ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-700 dark:text-white">{{ $item->stockBatch->fishType->name ?? '-' }}</div>
                            <div class="text-xs text-slate-400">{{ $item->stockBatch->cartonType->name ?? '-' }}</div>
                        </td>
                        <td class="px-4 py-3 text-right font-bold">{{ $item->cartons_count }}</td>
                        <td class="px-4 py-3 text-right">{{ $item->price_per_carton ? number_format($item->price_per_carton, 2).' MRU' : '-' }}</td>
                        <td class="px-4 py-3 text-right font-bold text-emerald-600">
                            {{ $item->price_per_carton ? number_format($item->cartons_count * $item->price_per_carton, 2).' MRU' : '-' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <td colspan="4" class="px-4 py-2 text-right font-bold text-slate-700 dark:text-white">Total</td>
                        <td class="px-4 py-2 text-right font-bold text-emerald-600">
                            {{ number_format($delivery->items->sum(fn($i) => $i->cartons_count * ($i->price_per_carton ?? 0)), 2) }} MRU
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-4"><i class="fas fa-info-circle mr-2 text-primary-500"></i>Résumé</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-400">Statut</dt>
                    <dd><span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ match($delivery->status) {
                        'pending'=>'bg-amber-100 text-amber-700','delivered'=>'bg-emerald-100 text-emerald-700','cancelled'=>'bg-red-100 text-red-600',default=>'bg-slate-100 text-slate-500'
                    } }}">{{ match($delivery->status) { 'pending'=>'En attente','delivered'=>'Livré','cancelled'=>'Annulé',default=>$delivery->status } }}</span></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400">Date</dt>
                    <dd class="text-slate-700 dark:text-white">{{ $delivery->delivery_date->format('d/m/Y') }}</dd>
                </div>
                <div class="pt-3 border-t border-slate-100 dark:border-slate-700">
                    <dt class="text-slate-400 text-xs mb-1">Receptionnaire</dt>
                    <dd class="text-xs text-slate-700 dark:text-slate-200">{{ $delivery->receiver_name ?? '-' }}</dd>
                    <dd class="text-xs text-slate-500">CIN/NIF: {{ $delivery->receiver_id_number ?? '-' }}</dd>
                    <dd class="text-xs text-slate-500">Telephone: {{ $delivery->receiver_phone ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400">Validation</dt>
                    <dd class="text-slate-700 dark:text-white">{{ $delivery->validated_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                </div>
                @if($delivery->notes)
                <div class="pt-3 border-t border-slate-100 dark:border-slate-700">
                    <dt class="text-slate-400 text-xs mb-1">Notes</dt>
                    <dd class="text-xs text-slate-600 dark:text-slate-300">{{ $delivery->notes }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>
</div>
@endsection

