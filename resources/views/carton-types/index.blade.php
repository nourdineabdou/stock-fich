@extends('layouts.app')
@section('title', 'Types de Cartons')
@section('breadcrumb', 'Types de Cartons')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Types de Cartons</h1>
        <p class="text-sm text-slate-500">{{ $cartonTypes->total() }} type(s)</p>
    </div>
    <a href="{{ route('carton-types.create') }}" class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm">
        <i class="fas fa-plus"></i> Nouveau Type
    </a>
</div>

<div class="bg-white dark:bg-slate-800 rounded-2xl p-4 mb-5 shadow-sm border border-slate-100 dark:border-slate-700">
    <form method="GET" class="flex gap-3">
        <div class="flex-1">
            <input name="search" value="{{ request('search') }}" placeholder="Rechercher..."
                class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>
        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-xl text-sm"><i class="fas fa-search"></i></button>
    </form>
</div>

<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-500 uppercase tracking-wider">
            <tr>
                <th class="px-4 py-3 text-left">Nom</th>
                <th class="px-4 py-3 text-right">Poids (kg)</th>
                <th class="px-4 py-3 text-center">Utilisé</th>
                <th class="px-4 py-3 text-center">Statut</th>
                <th class="px-4 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
            @forelse($cartonTypes as $ct)
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 bg-amber-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-box text-amber-600 text-xs"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-800 dark:text-white">{{ $ct->name }}</p>
                            @if($ct->description)<p class="text-xs text-slate-400">{{ Str::limit($ct->description, 60) }}</p>@endif
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 text-right font-semibold text-slate-700 dark:text-white">{{ $ct->weight_kg }} kg</td>
                <td class="px-4 py-3 text-center">
                    <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-xs">{{ $ct->packaging_items_count }}</span>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $ct->active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600' }}">
                        {{ $ct->active ? 'Actif' : 'Inactif' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center gap-1">
                        <a href="{{ route('carton-types.edit', $ct) }}" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg">
                            <i class="fas fa-pen text-xs"></i>
                        </a>
                        <button onclick="confirmDelete('{{ route('carton-types.destroy', $ct) }}', '{{ addslashes($ct->name) }}')"
                            class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg">
                            <i class="fas fa-trash text-xs"></i>
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center py-12 text-slate-400">
                <i class="fas fa-box text-3xl mb-2 block"></i><p>Aucun type de carton</p>
            </td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-700">{{ $cartonTypes->links() }}</div>
</div>
@endsection
