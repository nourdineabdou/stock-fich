{{-- Temps en tunnel en direct. Variables : $since (Carbon, entree dans le tunnel), $compact (bool, optionnel) --}}
@php [$tunnelMinH, $tunnelMaxH] = \App\Models\CompanySetting::tunnelWindow(); @endphp
<div x-data="tunnelTimer('{{ $since->toIso8601String() }}', {{ $tunnelMinH }}, {{ $tunnelMaxH }})" class="{{ ($compact ?? false) ? '' : 'mt-2' }}">
    <div class="flex items-center justify-between gap-2">
        <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold"
              :class="{ freezing: 'bg-sky-100 text-sky-700', ready: 'bg-emerald-100 text-emerald-700', late: 'bg-red-100 text-red-700 animate-pulse' }[state]"
              x-text="stateLabel">{{ $since->diffForHumans(null, true) }}</span>
        <span class="text-xs font-bold text-slate-700 dark:text-white whitespace-nowrap" x-text="elapsed"></span>
    </div>
    <div class="relative mt-1 h-1.5 rounded-full bg-slate-200 dark:bg-slate-600 overflow-hidden">
        <div class="h-full rounded-full transition-all"
             :class="{ freezing: 'bg-sky-500', ready: 'bg-emerald-500', late: 'bg-red-500' }[state]"
             :style="`width:${pct}%`"></div>
        <div class="absolute top-0 h-full w-0.5 bg-slate-500/60" :style="`left:${minPct}%`" title="Temps minimum"></div>
    </div>
    @unless($compact ?? false)
    <p class="mt-0.5 text-[10px] text-slate-500" x-text="hint"></p>
    @endunless
</div>
