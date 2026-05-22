@extends('layouts.app')
@section('title', 'Rapports')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Rapports & Statistiques</h1>
    <form method="GET" class="flex items-center gap-2">
        <select name="year" class="px-3 py-2 text-sm border border-slate-200 rounded-xl dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            @for($y = now()->year; $y >= now()->year - 4; $y--)
            <option value="{{ $y }}" {{ request('year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-xl text-sm"><i class="fas fa-chart-bar mr-1"></i> Filtrer</button>
    </form>
</div>

{{-- KPI row --}}
<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase">Chiffre d'affaires {{ $year }}</p>
        <p class="text-2xl font-bold text-primary-600 mt-1">{{ number_format(array_sum(array_column($invoicesByMonth, 'total')), 0, ',', ' ') }} MRU</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase">Arrivages {{ $year }}</p>
        <p class="text-2xl font-bold text-cyan-600 mt-1">{{ array_sum(array_column($arrivalsByMonth, 'count')) }}</p>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-400 uppercase">Traitements analysés</p>
        <p class="text-2xl font-bold text-orange-600 mt-1">{{ count($processingYield) }}</p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-5 mb-5">
    {{-- Monthly Revenue --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-4"><i class="fas fa-chart-bar mr-2 text-primary-500"></i>Facturation Mensuelle</h3>
        <div id="revenueChart" style="height:250px"></div>
    </div>
    {{-- Monthly Arrivals --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-4"><i class="fas fa-chart-area mr-2 text-cyan-500"></i>Arrivages Mensuels</h3>
        <div id="arrivalsChart" style="height:250px"></div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-5 mb-5">
    {{-- Top Clients --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-4"><i class="fas fa-users mr-2 text-emerald-500"></i>Top Clients</h3>
        <div id="clientsChart" style="height:250px"></div>
    </div>
    {{-- Stock by fish --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-4"><i class="fas fa-fish mr-2 text-cyan-500"></i>Stock par Espèce</h3>
        <div id="stockChart" style="height:250px"></div>
    </div>
</div>

@if(!empty($processingYield) && count($processingYield) > 0)
<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-white"><i class="fas fa-percentage mr-2 text-orange-500"></i>Rendement Traitement</h3>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs text-slate-500 uppercase">
            <tr>
                <th class="px-4 py-2 text-left">Référence</th>
                <th class="px-4 py-2 text-right">Entrée (kg)</th>
                <th class="px-4 py-2 text-right">Sortie (kg)</th>
                <th class="px-4 py-2 text-right">Rendement</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
            @foreach($processingYield as $row)
            <tr>
                <td class="px-4 py-3 font-mono text-xs text-slate-600 dark:text-slate-300">{{ $row['ref'] }}</td>
                <td class="px-4 py-3 text-right">{{ number_format($row['in'], 2) }} kg</td>
                <td class="px-4 py-3 text-right text-emerald-600 font-bold">{{ number_format($row['out'], 2) }} kg</td>
                <td class="px-4 py-3 text-right">
                    @php $y = $row['yield']; @endphp
                    <div class="flex items-center justify-end gap-2">
                        <div class="w-16 bg-slate-200 rounded-full h-1.5"><div class="h-1.5 rounded-full {{ $y >= 70 ? 'bg-emerald-500' : ($y >= 50 ? 'bg-amber-500' : 'bg-red-500') }}" style="width:{{ min($y,100) }}%"></div></div>
                        <span class="font-bold {{ $y >= 70 ? 'text-emerald-600' : ($y >= 50 ? 'text-amber-600' : 'text-red-600') }}">{{ $y }}%</span>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
@push('scripts')
<script>
const months = @json(array_values($months));

// Revenue chart
new ApexCharts(document.getElementById('revenueChart'), {
    chart: { type: 'bar', height: 250, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
    series: [{ name: 'CA (MRU)', data: @json(array_column($invoicesByMonth, 'total')) }],
    xaxis: { categories: months },
    colors: ['#2563eb'],
    dataLabels: { enabled: false },
    yaxis: { labels: { formatter: v => v.toLocaleString() } },
    plotOptions: { bar: { borderRadius: 4 } },
    theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
}).render();

// Arrivals chart
new ApexCharts(document.getElementById('arrivalsChart'), {
    chart: { type: 'area', height: 250, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
    series: [{ name: 'Arrivages', data: @json(array_column($arrivalsByMonth, 'count')) }],
    xaxis: { categories: months },
    colors: ['#0891b2'],
    dataLabels: { enabled: false },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
    stroke: { curve: 'smooth', width: 2 },
    theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
}).render();

// Top clients
new ApexCharts(document.getElementById('clientsChart'), {
    chart: { type: 'bar', height: 250, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
    series: [{ name: 'CA (MRU)', data: @json($topClients->pluck('total')->toArray()) }],
    xaxis: { categories: @json($topClients->map(fn($t) => $t->client->name ?? 'N/A')->toArray()) },
    colors: ['#059669'],
    dataLabels: { enabled: false },
    plotOptions: { bar: { borderRadius: 4, horizontal: true } },
    theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
}).render();

// Stock by fish donut
new ApexCharts(document.getElementById('stockChart'), {
    chart: { type: 'donut', height: 250, fontFamily: 'Inter, sans-serif' },
    series: @json($stockByFish->pluck('cartons')->toArray()),
    labels: @json($stockByFish->pluck('name')->toArray()),
    colors: ['#2563eb','#0891b2','#059669','#d97706','#7c3aed','#dc2626'],
    legend: { position: 'bottom', fontSize: '11px' },
    dataLabels: { formatter: (val) => val.toFixed(1)+'%' },
    theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
}).render();
</script>
@endpush

