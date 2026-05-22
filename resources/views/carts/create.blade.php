@extends('layouts.app')
@section('title', isset($cart) ? 'Modifier: '.$cart->code : 'Nouveau Chariot')
@section('content')
<div class="max-w-xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('carts.index') }}" class="p-2 text-slate-500 hover:bg-slate-100 rounded-xl"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">
            {{ isset($cart) ? 'Modifier: '.$cart->code : 'Nouveau Chariot' }}
        </h1>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
        <form method="POST" action="{{ isset($cart) ? route('carts.update', $cart) : route('carts.store') }}">
            @csrf
            @if(isset($cart)) @method('PUT') @endif

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Tunnel <span class="text-red-500">*</span></label>
                    <select name="tunnel_id" required class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Choisir un tunnel...</option>
                        @foreach($tunnels as $t)
                        <option value="{{ $t->id }}" {{ old('tunnel_id', $cart->tunnel_id ?? request('tunnel_id')) == $t->id ? 'selected' : '' }}>
                            {{ $t->coldRoom->name ?? '' }} / {{ $t->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code', $cart->code ?? '') }}" required
                        class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm font-mono dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Capacité (kg) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" name="capacity_kg" value="{{ old('capacity_kg', $cart->capacity_kg ?? '') }}" required
                        class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Statut</label>
                    <select name="status" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="empty" {{ old('status', $cart->status ?? 'empty') == 'empty' ? 'selected' : '' }}>Disponible</option>
                        <option value="processing" {{ old('status', $cart->status ?? '') == 'processing' ? 'selected' : '' }}>En traitement</option>
                        <option value="frozen" {{ old('status', $cart->status ?? '') == 'frozen' ? 'selected' : '' }}>Congelé</option>
                        <option value="completed" {{ old('status', $cart->status ?? '') == 'completed' ? 'selected' : '' }}>Complété</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-4 mt-4 border-t border-slate-100 dark:border-slate-700">
                <button type="submit" class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-semibold text-sm">
                    <i class="fas fa-check mr-1.5"></i> {{ isset($cart) ? 'Mettre à jour' : 'Enregistrer' }}
                </button>
                <a href="{{ route('carts.index') }}" class="px-6 py-2.5 border border-slate-200 text-slate-600 rounded-xl text-sm">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
