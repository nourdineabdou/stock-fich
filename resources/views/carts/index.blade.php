@extends('layouts.app')
@section('title', 'Chariots')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Chariots</h1>
    <a href="{{ route('carts.create') }}" class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm">
        <i class="fas fa-plus"></i> Nouveau Chariot
    </a>
</div>

<div class="bg-white dark:bg-slate-800 rounded-2xl p-4 mb-5 shadow-sm border border-slate-100 dark:border-slate-700">
    <form method="GET" class="flex flex-wrap gap-3">
        <input name="search" value="{{ request('search') }}" placeholder="Code chariot..."
            class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
        <select name="tunnel_id" class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">Tous tunnels</option>
            @foreach($tunnels as $t)
            <option value="{{ $t->id }}" {{ request('tunnel_id') == $t->id ? 'selected' : '' }}>{{ $t->coldRoom->name ?? '' }} / {{ $t->name }}</option>
            @endforeach
        </select>
        <select name="status" class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">Tous statuts</option>
            <option value="empty" {{ request('status') == 'empty' ? 'selected' : '' }}>Disponible</option>
            <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>En traitement</option>
            <option value="frozen" {{ request('status') == 'frozen' ? 'selected' : '' }}>Congelé</option>
            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Complété</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-xl text-sm"><i class="fas fa-filter"></i></button>
    </form>
</div>

<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-500 uppercase tracking-wider">
            <tr>
                <th class="px-4 py-3 text-left">Code</th>
                <th class="px-4 py-3 text-left">Tunnel / Chambre</th>
                <th class="px-4 py-3 text-right">Capacité</th>
                <th class="px-4 py-3 text-center">Statut</th>
                <th class="px-4 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
            @forelse($carts as $cart)
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <div class="h-8 w-8 bg-violet-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-cart-flatbed text-violet-600 text-xs"></i>
                        </div>
                        <span class="font-bold text-slate-800 dark:text-white font-mono">{{ $cart->code }}</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-slate-500">
                    {{ $cart->tunnel->name ?? '-' }}
                    @if($cart->tunnel?->coldRoom)<span class="text-slate-400"> · {{ $cart->tunnel->coldRoom->name }}</span>@endif
                </td>
                <td class="px-4 py-3 text-right font-semibold">{{ number_format($cart->capacity_kg, 0) }} kg</td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ match($cart->status) {
                        'empty'      => 'bg-emerald-100 text-emerald-700',
                        'processing' => 'bg-amber-100 text-amber-700',
                        'frozen'     => 'bg-blue-100 text-blue-700',
                        default      => 'bg-slate-100 text-slate-500'
                    } }}">{{ match($cart->status) { 'empty'=>'Disponible','processing'=>'En traitement','frozen'=>'Congelé','completed'=>'Complété',default=>$cart->status } }}</span>
                </td>
                <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center gap-1">
                        <a href="{{ route('carts.edit', $cart) }}" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg"><i class="fas fa-pen text-xs"></i></a>
                        <button onclick="confirmDelete('{{ route('carts.destroy', $cart) }}', '{{ addslashes($cart->code) }}')" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg"><i class="fas fa-trash text-xs"></i></button>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center py-12 text-slate-400">
                <i class="fas fa-cart-flatbed text-3xl mb-2 block"></i><p>Aucun chariot</p>
            </td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-700">{{ $carts->links() }}</div>
</div>
@endsection
