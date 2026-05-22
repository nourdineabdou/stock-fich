@extends('layouts.app')
@section('title', isset($fishType) ? 'Modifier: '.$fishType->name : 'Nouveau Type de Poisson')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('fish-types.index') }}" class="p-2 text-slate-500 hover:bg-slate-100 rounded-xl">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">
            {{ isset($fishType) ? 'Modifier: '.$fishType->name : 'Nouveau Type de Poisson' }}
        </h1>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
        <form method="POST" action="{{ isset($fishType) ? route('fish-types.update', $fishType) : route('fish-types.store') }}">
            @csrf
            @if(isset($fishType)) @method('PUT') @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Nom <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $fishType->name ?? '') }}" required
                        class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Nom scientifique</label>
                    <input type="text" name="scientific_name" value="{{ old('scientific_name', $fishType->scientific_name ?? '') }}"
                        class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm italic dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Statut</label>
                    <select name="active" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="1" {{ old('active', $fishType->active ?? 1) == 1 ? 'selected' : '' }}>Actif</option>
                        <option value="0" {{ old('active', $fishType->active ?? 1) == 0 ? 'selected' : '' }}>Inactif</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Description</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">{{ old('description', $fishType->description ?? '') }}</textarea>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-4 mt-4 border-t border-slate-100 dark:border-slate-700">
                <button type="submit" class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-semibold text-sm">
                    <i class="fas fa-check mr-1.5"></i> {{ isset($fishType) ? 'Mettre à jour' : 'Enregistrer' }}
                </button>
                <a href="{{ route('fish-types.index') }}" class="px-6 py-2.5 border border-slate-200 text-slate-600 rounded-xl text-sm">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
