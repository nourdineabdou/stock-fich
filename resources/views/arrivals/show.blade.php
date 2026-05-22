@extends('layouts.app')
@section('title', $arrival->reference)
@section('content')
<div class="flex items-center justify-between mb-6">
    @php($processingOrder = $arrival->processingOrders->first())
    <div class="flex items-center gap-3">
        <a href="{{ route('arrivals.index') }}" class="p-2 text-slate-500 hover:bg-slate-100 rounded-xl"><i class="fas fa-arrow-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white font-mono">{{ $arrival->reference }}</h1>
            <p class="text-sm text-slate-500">{{ $arrival->client->name ?? '-' }} · {{ $arrival->arrival_date->format('d/m/Y H:i') }}</p>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="{{ $processingOrder ? route('processing.edit', $processingOrder) : route('processing.create').'?arrival_id='.$arrival->id.'&client_id='.$arrival->client_id }}"
           class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
            <i class="fas fa-gears"></i> {{ $processingOrder ? 'Modifier Fiche Traitement' : 'Demarrer Fiche Traitement' }}
        </a>
        <a href="{{ route('arrivals.edit', $arrival) }}" class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-xl text-sm font-semibold">
            <i class="fas fa-pen"></i>
        </a>
        <button onclick="confirmDelete('{{ route('arrivals.destroy', $arrival) }}', '{{ addslashes($arrival->reference) }}')"
            class="inline-flex items-center gap-2 border border-red-200 text-red-600 hover:bg-red-50 px-3 py-2 rounded-xl text-sm">
            <i class="fas fa-trash"></i>
        </button>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">
        {{-- Items --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-white"><i class="fas fa-fish mr-2 text-cyan-500"></i>Détail des Poissons</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-2 text-left">Type</th>
                        <th class="px-4 py-2 text-right">Poids (kg)</th>
                        <th class="px-4 py-2 text-center">Quantité</th>
                        <th class="px-4 py-2 text-left">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                    @foreach($arrival->items as $item)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-700 dark:text-white">{{ $item->fishType->name ?? 'Poisson non trié' }}</td>
                        <td class="px-4 py-3 text-right font-bold">{{ number_format($item->weight_kg, 2) }}</td>
                        <td class="px-4 py-3 text-center">{{ $item->quantity }}</td>
                        <td class="px-4 py-3 text-slate-400 text-xs">{{ $item->notes }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <td class="px-4 py-2 font-bold text-sm text-slate-700 dark:text-white">Total</td>
                        <td class="px-4 py-2 text-right font-bold text-slate-800 dark:text-white">{{ number_format($arrival->net_weight, 2) }} kg</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Processing orders --}}
        @if($processingOrder)
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-white"><i class="fas fa-gears mr-2 text-orange-500"></i>Fiche de Traitement</h3>
            </div>
            <div class="px-5 py-3">
                <div class="flex items-center justify-between">
                    <div>
                        <a href="{{ route('processing.show', $processingOrder) }}" class="font-mono text-sm font-semibold text-primary-600 hover:underline">{{ $processingOrder->reference }}</a>
                        <p class="text-xs text-slate-400">{{ $processingOrder->start_date?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-xs {{ match($processingOrder->status) {
                        'completed'=>'bg-emerald-100 text-emerald-700',
                        'processing'=>'bg-amber-100 text-amber-700',
                        default=>'bg-slate-100 text-slate-500'
                    } }}">{{ ucfirst($processingOrder->status) }}</span>
                </div>
            </div>
        </div>
        @endif

        <div class="rounded-2xl border border-cyan-200 bg-cyan-50 px-5 py-4 text-sm text-cyan-900 dark:bg-cyan-950/20 dark:border-cyan-900 dark:text-cyan-200">
            <p class="font-semibold mb-1"><i class="fas fa-diagram-project mr-2"></i>Parcours metier</p>
            <p>1) Separer dechets/stockable dans la fiche traitement. 2) Trier la partie stockable par type avec prix/kg. 3) Cartonnage puis tunnel/chambre froide.</p>
        </div>
    </div>

    {{-- Summary --}}
    <div class="space-y-5">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-4"><i class="fas fa-info-circle mr-2 text-primary-500"></i>Résumé</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-400">Statut</dt>
                    <dd>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ match($arrival->status) {
                            'pending'    => 'bg-slate-100 text-slate-600',
                            'processing' => 'bg-amber-100 text-amber-700',
                            'completed'  => 'bg-emerald-100 text-emerald-700',
                            default      => 'bg-slate-100 text-slate-500'
                        } }}">{{ match($arrival->status) { 'pending'=>'En attente','processing'=>'Traitement','completed'=>'Complété',default=>$arrival->status } }}</span>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400">Poids brut</dt>
                    <dd class="font-semibold text-slate-700 dark:text-white">{{ number_format($arrival->gross_weight, 2) }} kg</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400">Poids net</dt>
                    <dd class="font-semibold text-slate-700 dark:text-white">{{ number_format($arrival->net_weight, 2) }} kg</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400">Lignes</dt>
                    <dd class="font-semibold text-slate-700 dark:text-white">{{ $arrival->items->count() }}</dd>
                </div>
                @if($arrival->notes)
                <div class="pt-3 border-t border-slate-100 dark:border-slate-700">
                    <dt class="text-slate-400 text-xs mb-1">Notes</dt>
                    <dd class="text-slate-600 dark:text-slate-300 text-xs">{{ $arrival->notes }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>
</div>
@endsection
