@extends('layouts.app')

@section('title', 'Clients')
@section('breadcrumb', 'Clients')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Clients</h1>
        <p class="text-sm text-slate-500 mt-0.5">{{ $clients->total() }} client(s) enregistré(s)</p>
    </div>
    <a href="{{ route('clients.create') }}"
       class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm transition-all">
        <i class="fas fa-plus"></i> Nouveau Client
    </a>
</div>

{{-- Filters --}}
<div class="bg-white dark:bg-slate-800 rounded-2xl p-4 mb-5 shadow-sm border border-slate-100 dark:border-slate-700">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-48">
            <label class="block text-xs font-medium text-slate-500 mb-1">Recherche</label>
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input name="search" value="{{ request('search') }}" placeholder="Nom, téléphone, email..."
                    class="pl-8 pr-3 py-2 text-sm border border-slate-200 rounded-xl w-full dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Statut</label>
            <select name="status" class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">Tous</option>
                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Actif</option>
                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactif</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-xl text-sm hover:bg-primary-700 transition-all">
            <i class="fas fa-filter mr-1"></i> Filtrer
        </button>
        @if(request()->hasAny(['search','status']))
        <a href="{{ route('clients.index') }}" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl text-sm hover:bg-slate-50 transition-all">
            <i class="fas fa-times mr-1"></i> Effacer
        </a>
        @endif
    </form>
</div>

{{-- Table --}}
<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-sm table-fixed">
        <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-500 uppercase tracking-wider">
            <tr>
                <th class="px-3 py-3 text-left w-80">Client</th>
                <th class="px-3 py-3 text-left w-52">Téléphone</th>
                <th class="px-3 py-3 text-center w-24">Arr.</th>
                <th class="px-3 py-3 text-center w-24">Statut</th>
                <th class="px-3 py-3 text-center w-32">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
            @forelse($clients as $client)
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                <td class="px-3 py-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="h-7 w-7 bg-primary-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <span class="text-primary-700 font-bold text-xs">{{ substr($client->name, 0, 2) }}</span>
                        </div>
                        <div class="min-w-0">
                            <a href="{{ route('clients.show', $client) }}" class="font-semibold text-slate-800 dark:text-white hover:text-primary-600 block truncate">{{ $client->name }}</a>
                        </div>
                    </div>
                </td>
                <td class="px-3 py-3 text-slate-600 dark:text-slate-300 truncate" title="{{ $client->phone }}">{{ $client->phone ?? '-' }}</td>
                <td class="px-3 py-3 text-center">
                    <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-xs font-semibold">{{ $client->arrivals_count }}</span>
                </td>
                <td class="px-3 py-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $client->active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600' }}">
                        {{ $client->active ? 'Actif' : 'Inactif' }}
                    </span>
                </td>
                <td class="px-3 py-3 text-center">
                    <div class="flex items-center justify-center gap-1">
                        <a href="{{ route('clients.show', $client) }}" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg" title="Voir">
                            <i class="fas fa-eye text-xs"></i>
                        </a>
                        <a href="{{ route('clients.edit', $client) }}" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg" title="Modifier">
                            <i class="fas fa-pen text-xs"></i>
                        </a>
                        <button onclick="confirmDelete('{{ route('clients.destroy', $client) }}', '{{ addslashes($client->name) }}')"
                            class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg" title="Supprimer">
                            <i class="fas fa-trash text-xs"></i>
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center py-12 text-slate-400">
                    <i class="fas fa-users text-3xl mb-2 block"></i>
                    <p>Aucun client trouvé</p>
                    <a href="{{ route('clients.create') }}" class="text-primary-600 text-sm hover:underline">Créer le premier client</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-700">
        {{ $clients->links() }}
    </div>
</div>
@endsection

