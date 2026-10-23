{{-- Bouton « Sortir du tunnel et passer au cartonnage ». Variable : $order --}}
@php
    $placed = $order->cartAssignments->where('status', 'active')->whereNotNull('tunnel_id');
    $unplaced = $order->cartAssignments->where('status', 'active')->whereNull('tunnel_id')->count();
    [$relMin] = \App\Models\CompanySetting::tunnelWindow();
    $tooEarly = $placed->filter(fn ($a) => $a->assigned_at && $a->assigned_at->diffInMinutes(now()) < $relMin * 60)->count();
@endphp
@if($placed->isNotEmpty() && $unplaced === 0 && auth()->user()->can('processing.release-tunnels'))
<form method="POST" action="{{ route('processing.release-tunnels', $order) }}" class="inline-block"
      @if($tooEarly) onsubmit="return confirm('{{ $tooEarly }} chariot(s) sont dans le tunnel depuis moins de {{ $relMin }} h (congélation pas terminée). Les sortir quand même ?')" @endif>
    @csrf
    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white {{ $tooEarly ? 'bg-amber-500 hover:bg-amber-600' : 'bg-emerald-600 hover:bg-emerald-700' }}">
        <i class="fas fa-right-from-bracket"></i>
        Sortir les {{ $placed->count() }} chariot(s) du tunnel et passer au cartonnage
    </button>
    @if($tooEarly)
    <span class="ml-2 text-xs text-amber-700">{{ $tooEarly }} chariot(s) pas encore à {{ $relMin }} h</span>
    @endif
</form>
@endif
