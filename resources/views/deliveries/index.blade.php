@extends('layouts.app')
@section('title', 'Livraisons')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Livraisons</h1>
    <a href="{{ route('deliveries.create') }}" class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
        <i class="fas fa-plus"></i> Nouvelle Livraison
    </a>
</div>

<div class="bg-white dark:bg-slate-800 rounded-2xl p-4 mb-5 shadow-sm border border-slate-100 dark:border-slate-700">
    <form method="GET" class="flex flex-wrap gap-3">
        <select name="client_id" class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">Tous clients</option>
            @foreach($clients as $c)
            <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
        <select name="status" class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">Tous statuts</option>
            <option value="pending" {{ request('status')=='pending'?'selected':'' }}>En attente</option>
            <option value="delivered" {{ request('status')=='delivered'?'selected':'' }}>Livré</option>
            <option value="cancelled" {{ request('status')=='cancelled'?'selected':'' }}>Annulé</option>
        </select>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-xl text-sm"><i class="fas fa-filter"></i></button>
    </form>
</div>

<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-500 uppercase tracking-wider">
            <tr>
                <th class="px-4 py-3 text-left">Référence</th>
                <th class="px-4 py-3 text-left">Client</th>
                <th class="px-4 py-3 text-left">Date</th>
                <th class="px-4 py-3 text-center">Statut</th>
                <th class="px-4 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
            @forelse($deliveries as $delivery)
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <td class="px-4 py-3 font-mono text-xs font-semibold text-primary-600">
                    <a href="{{ route('deliveries.show', $delivery) }}" class="hover:underline">{{ $delivery->reference }}</a>
                </td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ $delivery->client->name ?? '-' }}</td>
                <td class="px-4 py-3 text-slate-500">{{ $delivery->delivery_date->format('d/m/Y') }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ match($delivery->status) {
                        'pending'=>'bg-amber-100 text-amber-700','delivered'=>'bg-emerald-100 text-emerald-700','cancelled'=>'bg-red-100 text-red-600',default=>'bg-slate-100 text-slate-500'
                    } }}">{{ match($delivery->status) { 'pending'=>'En attente','delivered'=>'Livré','cancelled'=>'Annulé',default=>$delivery->status } }}</span>
                </td>
                <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center gap-1">
                        <a href="{{ route('deliveries.show', $delivery) }}" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg"><i class="fas fa-eye text-xs"></i></a>
                        <a href="{{ route('deliveries.edit', $delivery) }}" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg"><i class="fas fa-pen text-xs"></i></a>
                        <a href="{{ route('deliveries.pdf', $delivery) }}" target="_blank" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg" title="PDF"><i class="fas fa-file-pdf text-xs"></i></a>
                        <button onclick="confirmDelete('{{ route('deliveries.destroy', $delivery) }}','{{ addslashes($delivery->reference) }}')" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg"><i class="fas fa-trash text-xs"></i></button>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center py-12 text-slate-400">
                <i class="fas fa-truck text-3xl mb-2 block"></i><p>Aucune livraison</p>
            </td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-700">{{ $deliveries->links() }}</div>
</div>
@endsection
