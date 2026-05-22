@extends('layouts.app')

@section('title', 'Modifier Client')
@section('breadcrumb', 'Clients')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('clients.show', $client) }}" class="p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-all">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Modifier: {{ $client->name }}</h1>
            <p class="text-sm text-slate-500">Mettre à jour les informations du client</p>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
        <form method="POST" action="{{ route('clients.update', $client) }}">
            @csrf
            @method('PUT')
            @include('clients._form')
            <div class="flex items-center gap-3 pt-4 mt-4 border-t border-slate-100 dark:border-slate-700">
                <button type="submit" class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-semibold text-sm transition-all shadow-sm">
                    <i class="fas fa-check mr-1.5"></i> Mettre à jour
                </button>
                <a href="{{ route('clients.show', $client) }}" class="px-6 py-2.5 border border-slate-200 text-slate-600 rounded-xl text-sm hover:bg-slate-50 transition-all">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
