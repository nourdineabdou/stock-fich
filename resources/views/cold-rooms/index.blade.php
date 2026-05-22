@extends('layouts.app')
@section('title', 'Chambres Froides')
@section('breadcrumb', 'Chambres Froides')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Chambres Froides</h1>
    <a href="{{ route('cold-rooms.create') }}" class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm">
        <i class="fas fa-plus"></i> Nouvelle Chambre
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
    @forelse($coldRooms as $room)
    @php
    $typeColors = [
        'freezing' => ['bg'=>'from-blue-600 to-cyan-700', 'icon'=>'fa-snowflake', 'label'=>'Congélation'],
        'cold'     => ['bg'=>'from-indigo-500 to-blue-600', 'icon'=>'fa-temperature-low', 'label'=>'Réfrigération'],
        'ambient'  => ['bg'=>'from-slate-500 to-slate-600', 'icon'=>'fa-thermometer-half', 'label'=>'Ambiant'],
    ];
    $tc = $typeColors[$room->type] ?? $typeColors['cold'];
    @endphp
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden hover:shadow-md transition-all">
        <div class="bg-gradient-to-r {{ $tc['bg'] }} p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="fas {{ $tc['icon'] }} text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-white">{{ $room->name }}</h3>
                        <p class="text-white/70 text-xs">{{ $tc['label'] }}</p>
                    </div>
                </div>
                @if($room->temperature !== null)
                <div class="text-right">
                    <p class="text-2xl font-bold text-white">{{ $room->temperature }}°C</p>
                </div>
                @endif
            </div>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-3 gap-3 mb-4">
                <div class="bg-slate-50 dark:bg-slate-700 rounded-xl p-3">
                    <p class="text-xs text-slate-400">Lots en stock</p>
                    <p class="text-lg font-bold text-slate-700 dark:text-white">{{ number_format($room->active_lots_count ?? 0) }}</p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-700 rounded-xl p-3">
                    <p class="text-xs text-slate-400">Cartons disponibles</p>
                    <p class="text-lg font-bold text-slate-700 dark:text-white">{{ number_format($room->available_cartons_sum ?? 0) }}</p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-700 rounded-xl p-3">
                    <p class="text-xs text-slate-400">Types cartons</p>
                    <p class="text-lg font-bold text-slate-700 dark:text-white">{{ number_format($room->carton_types_count ?? 0) }}</p>
                </div>
            </div>
            <p class="text-xs text-slate-400 mb-2">Poids disponible: {{ number_format($room->available_weight_sum ?? 0, 2) }} kg</p>
            @if($room->capacity_kg)
            <p class="text-xs text-slate-400 mb-4">Capacité: {{ number_format($room->capacity_kg, 0) }} kg</p>
            @endif
            <div class="flex items-center justify-between">
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $room->active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600' }}">
                    {{ $room->active ? 'Active' : 'Inactive' }}
                </span>
                <div class="flex items-center gap-1">
                    <a href="{{ route('cold-rooms.show', $room) }}" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg"><i class="fas fa-eye text-xs"></i></a>
                    <a href="{{ route('cold-rooms.edit', $room) }}" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg"><i class="fas fa-pen text-xs"></i></a>
                    <button onclick="confirmDelete('{{ route('cold-rooms.destroy', $room) }}', '{{ addslashes($room->name) }}')"
                        class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg"><i class="fas fa-trash text-xs"></i></button>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-span-full text-center py-12 text-slate-400">
        <i class="fas fa-igloo text-3xl mb-2 block"></i>
        <p>Aucune chambre froide</p>
        <a href="{{ route('cold-rooms.create') }}" class="text-primary-600 text-sm hover:underline">En créer une</a>
    </div>
    @endforelse
</div>
<div class="mt-4">{{ $coldRooms->links() }}</div>
@endsection
