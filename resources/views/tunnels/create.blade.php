@extends('layouts.app')
@section('title', isset($tunnel) ? 'Modifier: '.$tunnel->name : 'Nouveau Tunnel')
@section('content')
<div class="max-w-xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('tunnels.index') }}" class="p-2 text-slate-500 hover:bg-slate-100 rounded-xl"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">
            {{ isset($tunnel) ? 'Modifier: '.$tunnel->name : 'Nouveau Tunnel' }}
        </h1>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
        <form method="POST" action="{{ isset($tunnel) ? route('tunnels.update', $tunnel) : route('tunnels.store') }}">
            @csrf
            @if(isset($tunnel)) @method('PUT') @endif

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Chambre Froide <span class="text-red-500">*</span></label>
                    <select name="cold_room_id" required class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Choisir une chambre...</option>
                        @foreach($coldRooms as $cr)
                        <option value="{{ $cr->id }}" {{ old('cold_room_id', $tunnel->cold_room_id ?? request('cold_room_id')) == $cr->id ? 'selected' : '' }}>{{ $cr->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Nom <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $tunnel->name ?? '') }}" required
                        class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Capacité (chariots) <span class="text-red-500">*</span></label>
                    <input type="number" name="capacity_chariots" value="{{ old('capacity_chariots', $tunnel->capacity_chariots ?? '') }}" required min="1"
                        class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Température (°C)</label>
                    <input type="number" step="0.1" name="temperature" value="{{ old('temperature', $tunnel->temperature ?? '') }}"
                        class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Statut</label>
                    <select name="active" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="1" {{ old('active', $tunnel->active ?? 1) == 1 ? 'selected' : '' }}>Actif</option>
                        <option value="0" {{ old('active', $tunnel->active ?? 1) == 0 ? 'selected' : '' }}>Inactif</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-4 mt-4 border-t border-slate-100 dark:border-slate-700">
                <button type="submit" class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-semibold text-sm">
                    <i class="fas fa-check mr-1.5"></i> {{ isset($tunnel) ? 'Mettre à jour' : 'Enregistrer' }}
                </button>
                <a href="{{ route('tunnels.index') }}" class="px-6 py-2.5 border border-slate-200 text-slate-600 rounded-xl text-sm">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
