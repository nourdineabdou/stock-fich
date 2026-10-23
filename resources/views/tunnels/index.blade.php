@extends('layouts.app')
@section('title', 'Tunnels de Congélation')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Tunnels</h1>
    <a href="{{ route('tunnels.create') }}" class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm">
        <i class="fas fa-plus"></i> Nouveau Tunnel
    </a>
</div>

<div class="bg-white dark:bg-slate-800 rounded-2xl p-4 mb-5 shadow-sm border border-slate-100 dark:border-slate-700">
    <form method="GET" class="flex flex-wrap gap-3">
        <div class="flex-1 min-w-48">
            <input name="search" value="{{ request('search') }}" placeholder="Rechercher..."
                class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>
        <select name="cold_room_id" class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">Toutes chambres</option>
            @foreach($coldRooms as $cr)
            <option value="{{ $cr->id }}" {{ request('cold_room_id') == $cr->id ? 'selected' : '' }}>{{ $cr->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-xl text-sm"><i class="fas fa-filter"></i></button>
    </form>
</div>

<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-500 uppercase tracking-wider">
            <tr>
                <th class="px-4 py-3 text-left">Tunnel</th>
                <th class="px-4 py-3 text-left">Chambre</th>
                <th class="px-4 py-3 text-right">Temp.</th>
                <th class="px-4 py-3 text-center">Chariots dedans</th>
                <th class="px-4 py-3 text-center">Occupation</th>
                <th class="px-4 py-3 text-center">Statut</th>
                <th class="px-4 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
            @forelse($tunnels as $tunnel)
            @php
                // Occupation reelle : chariots actuellement dans le tunnel
                [$tMin, $tMax] = \App\Models\CompanySetting::tunnelWindow();
                $inside = $tunnel->cartAssignments;
                $pct = $tunnel->capacity_chariots > 0 ? ($inside->count() / $tunnel->capacity_chariots * 100) : 0;
                $hoursOf = fn ($a) => $a->assigned_at ? $a->assigned_at->diffInMinutes(now()) / 60 : 0;
                $readyCount = $inside->filter(fn ($a) => $hoursOf($a) >= $tMin && $hoursOf($a) <= $tMax)->count();
                $lateCount = $inside->filter(fn ($a) => $hoursOf($a) > $tMax)->count();
            @endphp
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <div class="h-8 w-8 bg-cyan-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-temperature-low text-cyan-600 text-xs"></i>
                        </div>
                        <a href="{{ route('tunnels.show', $tunnel) }}" class="font-semibold text-slate-800 dark:text-white hover:text-primary-600">{{ $tunnel->name }}</a>
                    </div>
                </td>
                <td class="px-4 py-3 text-slate-500">{{ $tunnel->coldRoom->name ?? '-' }}</td>
                <td class="px-4 py-3 text-right font-mono text-blue-600">{{ $tunnel->temperature !== null ? $tunnel->temperature.'°C' : '-' }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="font-semibold">{{ $inside->count() }}/{{ $tunnel->capacity_chariots }}</span>
                    @if($readyCount || $lateCount)
                    <div class="mt-1 flex justify-center gap-1 text-[10px] font-semibold">
                        @if($readyCount)<span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700">{{ $readyCount }} prêt(s)</span>@endif
                        @if($lateCount)<span class="px-1.5 py-0.5 rounded bg-red-100 text-red-700">{{ $lateCount }} dépassé(s)</span>@endif
                    </div>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <div class="flex-1 bg-slate-100 dark:bg-slate-700 rounded-full h-2">
                            <div class="h-2 rounded-full {{ $pct > 80 ? 'bg-red-400' : ($pct > 60 ? 'bg-amber-400' : 'bg-emerald-400') }}"
                                 style="width: {{ min($pct, 100) }}%"></div>
                        </div>
                        <span class="text-xs font-medium {{ $pct > 80 ? 'text-red-600' : 'text-slate-500' }}">{{ round($pct) }}%</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-xs {{ $tunnel->active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600' }}">
                        {{ $tunnel->active ? 'Actif' : 'Inactif' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center gap-1">
                        <a href="{{ route('tunnels.show', $tunnel) }}" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg"><i class="fas fa-eye text-xs"></i></a>
                        <a href="{{ route('tunnels.edit', $tunnel) }}" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg"><i class="fas fa-pen text-xs"></i></a>
                        <button onclick="confirmDelete('{{ route('tunnels.destroy', $tunnel) }}', '{{ addslashes($tunnel->name) }}')" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg"><i class="fas fa-trash text-xs"></i></button>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center py-12 text-slate-400">
                <i class="fas fa-temperature-low text-3xl mb-2 block"></i><p>Aucun tunnel</p>
            </td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-700">{{ $tunnels->links() }}</div>
</div>
@endsection
