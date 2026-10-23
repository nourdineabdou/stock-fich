@extends('layouts.app')
@section('title', $tunnel->name)
@section('content')
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('tunnels.index') }}" class="p-2 text-slate-500 hover:bg-slate-100 rounded-xl"><i class="fas fa-arrow-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">{{ $tunnel->name }}</h1>
            <p class="text-sm text-slate-500">{{ $tunnel->coldRoom->name ?? '' }}
                @if($tunnel->temperature !== null) · {{ $tunnel->temperature }}°C @endif
                · {{ $tunnel->carts->count() }}/{{ $tunnel->capacity_chariots }} chariots
            </p>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('carts.create') }}?tunnel_id={{ $tunnel->id }}"
           class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
            <i class="fas fa-plus"></i> Chariot
        </a>
        <a href="{{ route('tunnels.edit', $tunnel) }}" class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-xl text-sm font-semibold">
            <i class="fas fa-pen"></i>
        </a>
    </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    @php
    $carts = $tunnel->carts;
    $statusGroups = $carts->groupBy('status');
    @endphp
    <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl p-4">
        <p class="text-xs text-emerald-600 font-medium">Disponibles</p>
        <p class="text-3xl font-bold text-emerald-700">{{ ($statusGroups->get('empty') ?? collect())->count() }}</p>
    </div>
    <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-4">
        <p class="text-xs text-amber-600 font-medium">En traitement</p>
        <p class="text-3xl font-bold text-amber-700">{{ ($statusGroups->get('processing') ?? collect())->count() }}</p>
    </div>
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-4">
        <p class="text-xs text-blue-600 font-medium">Congelés</p>
        <p class="text-3xl font-bold text-blue-700">{{ ($statusGroups->get('frozen') ?? collect())->count() }}</p>
    </div>
    <div class="bg-slate-50 dark:bg-slate-700 rounded-2xl p-4">
        <p class="text-xs text-slate-500 font-medium">Complétés</p>
        <p class="text-3xl font-bold text-slate-700 dark:text-white">{{ ($statusGroups->get('completed') ?? collect())->count() }}</p>
    </div>
</div>

@php
    [$tMin, $tMax] = \App\Models\CompanySetting::tunnelWindow();
    $byState = $activeAssignmentsByCart->filter(fn ($a) => $a->assigned_at)->groupBy(function ($a) use ($tMin, $tMax) {
        $h = $a->assigned_at->diffInMinutes(now()) / 60;
        return $h < $tMin ? 'freezing' : ($h <= $tMax ? 'ready' : 'late');
    });
@endphp
@if($activeAssignmentsByCart->isNotEmpty())
<div class="mb-4 flex flex-wrap items-center gap-2 text-sm">
    <span class="text-slate-500"><i class="fas fa-stopwatch mr-1"></i>Temps attendu : {{ $tMin }} h à {{ $tMax }} h —</span>
    <span class="px-3 py-1 rounded-full bg-sky-100 text-sky-700 font-semibold">{{ ($byState['freezing'] ?? collect())->count() }} en congélation</span>
    <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 font-semibold">{{ ($byState['ready'] ?? collect())->count() }} prêt(s) à sortir</span>
    @if(($byState['late'] ?? collect())->isNotEmpty())
    <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 font-semibold animate-pulse">{{ $byState['late']->count() }} dépassé(s) — à sortir</span>
    @endif
</div>
@endif

<h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wider mb-3">Chariots</h2>

@if($activeAssignmentsByCart->count() > 0)
<div class="mb-4 flex items-center gap-3 p-4 rounded-xl border border-slate-200 bg-slate-50 dark:bg-slate-800 dark:border-slate-700">
    <div class="flex-1">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" id="select-all-carts" class="w-4 h-4 rounded">
            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Sélectionner tous les chariots actifs</span>
        </label>
        <p class="text-xs text-slate-500 mt-2 dark:text-slate-400"><span id="selected-count">0</span> chariot(s) sélectionné(s)</p>
    </div>
    <form method="POST" action="{{ route('tunnels.release-multiple-assignments', $tunnel) }}" class="flex gap-2" id="bulk-release-form" style="display: none;">
        @csrf
        <div id="hidden-inputs-container"></div>
        <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-semibold"
                onclick="return confirm('Dissocier les ' + document.querySelectorAll('input.cart-checkbox:checked').length + ' chariot(s) sélectionné(s) et passer au cartonnage ?')">
            <i class="fas fa-right-from-bracket mr-1"></i> Sortir les sélectionnés du tunnel
        </button>
    </form>
</div>
@endif

<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3">
    @forelse($tunnel->carts as $cart)
    @php
    $colors = ['empty'=>'border-emerald-300 bg-emerald-50','processing'=>'border-amber-300 bg-amber-50','frozen'=>'border-blue-300 bg-blue-50','completed'=>'border-slate-300 bg-slate-50'];
    $cc = $colors[$cart->status] ?? 'border-slate-200 bg-white';
    $assignment = $activeAssignmentsByCart[$cart->id] ?? null;
    $elapsedLabel = null;
    if ($assignment?->assigned_at) {
        $minutes = $assignment->assigned_at->diffInMinutes(now());
        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);
        $mins = $minutes % 60;
        $elapsedLabel = $days.'j '.$hours.'h '.$mins.'min';
    }
    @endphp
    <div class="rounded-2xl border-2 {{ $cc }} p-3 dark:bg-slate-800 dark:border-slate-700">
        @if($assignment)
        <div class="flex items-start gap-2 mb-2">
            <input type="checkbox" class="cart-checkbox mt-0.5" data-assignment-id="{{ $assignment->id }}" onchange="updateBulkForm()">
            <div class="flex-1">
                <p class="font-bold text-slate-800 dark:text-white">{{ $cart->code }}</p>
                <p class="text-xs text-center text-slate-500">{{ number_format($cart->capacity_kg, 0) }} kg</p>
            </div>
        </div>
        @else
        <p class="font-bold text-slate-800 dark:text-white text-center">{{ $cart->code }}</p>
        <p class="text-xs text-center text-slate-500 mt-1">{{ number_format($cart->capacity_kg, 0) }} kg</p>
        @endif
        <p class="text-center mt-2">
            <span class="text-xs px-2 py-0.5 rounded-full {{ match($cart->status) {
                'empty'=>'bg-emerald-100 text-emerald-700',
                'processing'=>'bg-amber-100 text-amber-700',
                'frozen'=>'bg-blue-100 text-blue-700',
                default=>'bg-slate-100 text-slate-500'
            } }}">{{ ucfirst($cart->status) }}</span>
        </p>

        @if($assignment)
        <div class="mt-2 border-t border-slate-200 pt-2 text-[11px] text-slate-600 space-y-1 dark:border-slate-700 dark:text-slate-300">
            <p><span class="font-semibold">Depuis:</span> {{ $assignment->assigned_at?->format('d/m/Y H:i') ?? '-' }}</p>
            @if($assignment->assigned_at)
            @include('partials.tunnel-timer', ['since' => $assignment->assigned_at])
            @endif
            <p><span class="font-semibold">Traitement:</span> {{ $assignment->processingOrder->reference ?? '-' }}</p>
            <p><span class="font-semibold">Client:</span> {{ $assignment->processingOrder->client->name ?? '-' }}</p>
        </div>

        <div class="mt-2 flex gap-2">
            <form method="POST" action="{{ route('tunnels.release-assignment', ['tunnel' => $tunnel, 'assignment' => $assignment]) }}" class="flex-1">
                @csrf
                <button type="submit" class="w-full px-2 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-semibold"
                        onclick="return confirm('Sortir ce chariot du tunnel ?')">
                    <i class="fas fa-right-from-bracket mr-1"></i> Sortir du tunnel
                </button>
            </form>
        </div>
        @elseif(in_array($cart->status, ['processing', 'frozen']))
        <p class="mt-2 text-[11px] text-amber-700 text-center">Chargement en cours — pas encore dans un tunnel</p>
        @endif
    </div>
    @empty
    <div class="col-span-full text-center py-8 text-slate-400 text-sm">Aucun chariot</div>
    @endforelse
</div>

<script>
function updateBulkForm() {
    const checkboxes = document.querySelectorAll('input.cart-checkbox:checked');
    const selectAllCheckbox = document.getElementById('select-all-carts');
    const allCheckboxes = document.querySelectorAll('input.cart-checkbox');
    
    // Mettre à jour le compteur
    document.getElementById('selected-count').textContent = checkboxes.length;
    
    // Afficher/masquer le formulaire de dissociation de masse
    const bulkForm = document.getElementById('bulk-release-form');
    if (checkboxes.length > 0) {
        bulkForm.style.display = 'flex';
    } else {
        bulkForm.style.display = 'none';
    }
    
    // Mettre à jour les inputs cachés pour le formulaire
    const hiddenContainer = document.getElementById('hidden-inputs-container');
    hiddenContainer.innerHTML = '';
    checkboxes.forEach((checkbox) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'assignment_ids[]';
        input.value = checkbox.dataset.assignmentId;
        hiddenContainer.appendChild(input);
    });
    
    // Mettre à jour le checkbox "Sélectionner tous"
    const allSelected = allCheckboxes.length > 0 && checkboxes.length === allCheckboxes.length;
    selectAllCheckbox.checked = allSelected;
    selectAllCheckbox.indeterminate = checkboxes.length > 0 && checkboxes.length < allCheckboxes.length;
}

document.getElementById('select-all-carts').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('input.cart-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = this.checked;
    });
    updateBulkForm();
});

// Initialiser l'état au chargement
updateBulkForm();
</script>
@endsection
@push('scripts')
<script src="{{ asset('js/affectation.js') }}?v={{ filemtime(public_path('js/affectation.js')) }}"></script>
@endpush
