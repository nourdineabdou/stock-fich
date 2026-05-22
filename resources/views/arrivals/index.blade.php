@extends('layouts.app')
@section('title', 'Arrivages')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Arrivages</h1>
    <a href="{{ route('arrivals.create') }}" class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm">
        <i class="fas fa-plus"></i> Nouvel Arrivage
    </a>
</div>

<div class="bg-white dark:bg-slate-800 rounded-2xl p-4 mb-5 shadow-sm border border-slate-100 dark:border-slate-700">
    <form method="GET" class="flex flex-wrap gap-3">
        <input name="search" value="{{ request('search') }}" placeholder="Référence..."
            class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
        <select name="client_id" class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">Tous clients</option>
            @foreach($clients as $c)
            <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
        <select name="status" class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">Tous statuts</option>
            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
            <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>En traitement</option>
            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Complété</option>
        </select>
        <input type="date" name="date_from" value="{{ request('date_from') }}"
            class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
        <input type="date" name="date_to" value="{{ request('date_to') }}"
            class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
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
                <th class="px-4 py-3 text-right">Poids Net</th>
                <th class="px-4 py-3 text-center">Types de poissons</th>
                <th class="px-4 py-3 text-center">Statut</th>
                <th class="px-4 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
            @forelse($arrivals as $arr)
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <td class="px-4 py-3">
                    <a href="{{ route('arrivals.show', $arr) }}" class="font-mono font-semibold text-primary-600 hover:underline">{{ $arr->reference }}</a>
                </td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ $arr->client->name ?? '-' }}</td>
                <td class="px-4 py-3 text-slate-500">{{ $arr->arrival_date->format('d/m/Y') }}</td>
                <td class="px-4 py-3 text-right font-semibold">{{ number_format($arr->net_weight, 0) }} kg</td>
                <td class="px-4 py-3 text-center">
                    <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full">
                        {{ $arr->items->count() }} ligne(s) non triée(s)
                    </span>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ match($arr->status) {
                        'pending'    => 'bg-slate-100 text-slate-600',
                        'processing' => 'bg-amber-100 text-amber-700',
                        'completed'  => 'bg-emerald-100 text-emerald-700',
                        default      => 'bg-slate-100 text-slate-500'
                    } }}">{{ match($arr->status) { 'pending'=>'En attente','processing'=>'Traitement','completed'=>'Complété',default=>$arr->status } }}</span>
                </td>
                <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center gap-1">
                        <a href="{{ route('arrivals.show', $arr) }}" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg"><i class="fas fa-eye text-xs"></i></a>
                        <a href="{{ route('arrivals.edit', $arr) }}" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg"><i class="fas fa-pen text-xs"></i></a>
                        <button onclick="confirmDelete('{{ route('arrivals.destroy', $arr) }}', '{{ addslashes($arr->reference) }}')" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg"><i class="fas fa-trash text-xs"></i></button>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center py-12 text-slate-400">
                <i class="fas fa-truck-ramp-box text-3xl mb-2 block"></i><p>Aucun arrivage</p>
            </td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-700">{{ $arrivals->links() }}</div>
</div>
@endsection
