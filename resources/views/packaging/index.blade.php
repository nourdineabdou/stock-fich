@extends('layouts.app')
@section('title', 'Cartonnage')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Ordres de Cartonnage</h1>
    <a href="{{ route('packaging.create') }}" class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
        <i class="fas fa-plus"></i> Nouvel Ordre
    </a>
</div>
<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-500 uppercase tracking-wider">
            <tr>
                <th class="px-4 py-3 text-left">Référence</th>
                <th class="px-4 py-3 text-left">Traitement</th>
                <th class="px-4 py-3 text-left">Client</th>
                <th class="px-4 py-3 text-left">Date</th>
                <th class="px-4 py-3 text-center">Statut</th>
                <th class="px-4 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
            @forelse($orders as $order)
            @php($packagingRouteKey = $order->getKey())
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <td class="px-4 py-3">
                    <a href="{{ route('packaging.show', ['packaging' => $packagingRouteKey]) }}" class="font-mono font-semibold text-primary-600 hover:underline text-xs">{{ $order->reference }}</a>
                </td>
                <td class="px-4 py-3 text-slate-500 text-xs">{{ $order->processingOrder->reference ?? '-' }}</td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ $order->client->name ?? $order->processingOrder->client->name ?? '-' }}</td>
                <td class="px-4 py-3 text-slate-500 text-xs">{{ $order->created_at?->format('d/m/Y') ?? $order->date?->format('d/m/Y') ?? '—' }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ match($order->status ?? 'pending') {
                        'completed' => 'bg-emerald-100 text-emerald-700',
                        'pending'   => 'bg-amber-100 text-amber-700',
                        'delivered' => 'bg-blue-100 text-blue-700',
                        default     => 'bg-slate-100 text-slate-600',
                    } }}">{{ match($order->status ?? 'pending') {
                        'completed' => 'Stocké',
                        'pending'   => 'En attente',
                        'delivered' => 'Livraison directe',
                        default     => ucfirst($order->status ?? 'pending'),
                    } }}</span>
                </td>
                <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center gap-1">
                        <a href="{{ route('packaging.show', ['packaging' => $packagingRouteKey]) }}" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg"><i class="fas fa-eye text-xs"></i></a>
                        <a href="{{ route('packaging.edit', ['packaging' => $packagingRouteKey]) }}" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg"><i class="fas fa-pen text-xs"></i></a>
                        <button onclick="confirmDelete('{{ route('packaging.destroy', ['packaging' => $packagingRouteKey]) }}','{{ addslashes($order->reference) }}')" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg"><i class="fas fa-trash text-xs"></i></button>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center py-12 text-slate-400">
                <i class="fas fa-box text-3xl mb-2 block"></i><p>Aucun ordre de cartonnage</p>
            </td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-700">{{ $orders->links() }}</div>
</div>
@endsection
