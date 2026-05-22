@extends('layouts.app')
@section('title', 'Types de Poissons')
@section('breadcrumb', 'Types de Poissons')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Types de Poissons</h1>
        <p class="text-sm text-slate-500">{{ $fishTypes->total() }} type(s)</p>
    </div>
    <a href="{{ route('fish-types.create') }}" class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm">
        <i class="fas fa-plus"></i> Nouveau Type
    </a>
</div>

<div class="bg-white dark:bg-slate-800 rounded-2xl p-4 mb-5 shadow-sm border border-slate-100 dark:border-slate-700">
    <form method="GET" class="flex gap-3 items-end">
        <div class="flex-1">
            <input name="search" value="{{ request('search') }}" placeholder="Rechercher..."
                class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>
        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-xl text-sm">
            <i class="fas fa-search"></i>
        </button>
        @if(request('search'))
        <a href="{{ route('fish-types.index') }}" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl text-sm">Effacer</a>
        @endif
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    @forelse($fishTypes as $ft)
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden hover:shadow-md transition-all">
        <div class="p-4">
            <div class="flex items-start justify-between mb-3">
                <div class="h-10 w-10 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-fish text-white text-sm"></i>
                </div>
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $ft->active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600' }}">
                    {{ $ft->active ? 'Actif' : 'Inactif' }}
                </span>
            </div>
            <h3 class="font-semibold text-slate-800 dark:text-white">{{ $ft->name }}</h3>
            @if($ft->scientific_name)
            <p class="text-xs text-slate-400 italic">{{ $ft->scientific_name }}</p>
            @endif
            <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-50 dark:border-slate-700">
                <span class="text-xs text-slate-500">{{ $ft->arrival_items_count }} arrivages</span>
                <div class="flex items-center gap-1">
                    <a href="{{ route('fish-types.edit', $ft) }}" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg">
                        <i class="fas fa-pen text-xs"></i>
                    </a>
                    <button onclick="confirmDelete('{{ route('fish-types.destroy', $ft) }}', '{{ addslashes($ft->name) }}')"
                        class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg">
                        <i class="fas fa-trash text-xs"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-span-full text-center py-12 text-slate-400">
        <i class="fas fa-fish text-3xl mb-2 block"></i>
        <p>Aucun type de poisson</p>
        <a href="{{ route('fish-types.create') }}" class="text-primary-600 text-sm hover:underline">En créer un</a>
    </div>
    @endforelse
</div>
<div class="mt-4">{{ $fishTypes->links() }}</div>
@endsection
