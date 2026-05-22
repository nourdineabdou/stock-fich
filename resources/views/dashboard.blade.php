@extends('layouts.app')

@section('title', 'Tableau de Bord')
@section('breadcrumb', 'Tableau de Bord')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Tableau de Bord</h1>
        <p class="text-sm text-slate-500 mt-0.5">{{ now()->isoFormat('dddd D MMMM YYYY') }}</p>
    </div>
    <div class="flex items-center gap-2">
        <button onclick="window.location.reload()"
            class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-xl text-sm font-medium transition-all shadow-sm">
            <i class="fas fa-rotate-right"></i> Actualiser
        </button>
        <a href="{{ route('reports.index') }}"
            class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition-all shadow-sm">
            <i class="fas fa-chart-bar"></i> Rapports
        </a>
    </div>
</div>

{{-- KPI CARDS --}}
<div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-3 mb-6">
    @php
    $kpis = [
        ['label'=>'Clients',         'value'=>$stats['total_clients'],     'icon'=>'fa-users',              'from'=>'from-blue-500',   'to'=>'to-blue-600',   'href'=>route('clients.index')],
        ['label'=>'Tunnels actifs',  'value'=>$stats['total_tunnels'],      'icon'=>'fa-temperature-low',    'from'=>'from-cyan-500',   'to'=>'to-cyan-600',   'href'=>route('tunnels.index')],
        ['label'=>'Chambres',        'value'=>$stats['total_cold_rooms'],   'icon'=>'fa-igloo',              'from'=>'from-indigo-500', 'to'=>'to-indigo-600', 'href'=>route('cold-rooms.index')],
        ['label'=>'Chariots',        'value'=>$stats['total_carts'],        'icon'=>'fa-cart-flatbed',       'from'=>'from-violet-500', 'to'=>'to-violet-600', 'href'=>route('carts.index')],
        ['label'=>'Lots en stock',   'value'=>$stats['stock_batches'],      'icon'=>'fa-warehouse',          'from'=>'from-emerald-500','to'=>'to-emerald-600','href'=>route('stock.index')],
        ['label'=>'Traitements',     'value'=>$stats['processing_active'],  'icon'=>'fa-gears',              'from'=>'from-orange-500', 'to'=>'to-orange-600', 'href'=>route('processing.index')],
        ['label'=>'Arrivages/j',     'value'=>$stats['arrivals_today'],     'icon'=>'fa-truck-ramp-box',     'from'=>'from-teal-500',   'to'=>'to-teal-600',   'href'=>route('arrivals.index')],
        ['label'=>'Fact. impayées',  'value'=>$stats['invoices_unpaid'],    'icon'=>'fa-file-invoice-dollar','from'=>'from-red-500',    'to'=>'to-red-600',    'href'=>route('invoices.index')],
    ];
    @endphp
    @foreach($kpis as $k)
    <a href="{{ $k['href'] }}"
        class="col-span-1 bg-white dark:bg-slate-800 rounded-2xl p-4 flex flex-col items-center text-center hover:shadow-md transition-all duration-200 group border border-slate-100 dark:border-slate-700">
        <div class="h-10 w-10 rounded-xl bg-gradient-to-br {{ $k['from'] }} {{ $k['to'] }} flex items-center justify-center mb-2 group-hover:scale-110 transition-transform shadow-sm">
            <i class="fas {{ $k['icon'] }} text-white text-sm"></i>
        </div>
        <p class="text-xl font-bold text-slate-800 dark:text-white">{{ number_format($k['value']) }}</p>
        <p class="text-xs text-slate-400 font-medium mt-0.5 leading-tight">{{ $k['label'] }}</p>
    </a>
    @endforeach
</div>

{{-- BIG STAT CARDS --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-gradient-to-br from-cold-600 to-primary-700 rounded-2xl p-5 text-white shadow-lg">
        <div class="flex items-center justify-between mb-3">
            <div>
                <p class="text-cold-200 text-xs font-medium uppercase tracking-wider">Stock Total</p>
                <p class="text-3xl font-bold mt-1">{{ number_format($stats['stock_total_weight'], 0, ',', ' ') }}<span class="text-lg font-normal text-cold-200 ml-1">kg</span></p>
            </div>
            <div class="h-12 w-12 bg-white/20 rounded-2xl flex items-center justify-center">
                <i class="fas fa-snowflake text-2xl"></i>
            </div>
        </div>
        <p class="text-cold-200 text-sm"><i class="fas fa-boxes-stacked mr-1 text-xs"></i>{{ number_format($stats['stock_total_cartons']) }} cartons disponibles</p>
    </div>

    <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-2xl p-5 text-white shadow-lg">
        <div class="flex items-center justify-between mb-3">
            <div>
                <p class="text-emerald-200 text-xs font-medium uppercase tracking-wider">Facturation Aujourd'hui</p>
                <p class="text-2xl font-bold mt-1">{{ number_format($stats['invoice_today'], 2, ',', ' ') }}<span class="text-sm font-normal text-emerald-200 ml-1">MRU</span></p>
            </div>
            <div class="h-12 w-12 bg-white/20 rounded-2xl flex items-center justify-center">
                <i class="fas fa-coins text-2xl"></i>
            </div>
        </div>
        <p class="text-emerald-200 text-sm"><i class="fas fa-calendar mr-1 text-xs"></i>{{ number_format($stats['invoice_month'], 2, ',', ' ') }} MRU ce mois</p>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-slate-400 text-xs font-medium uppercase tracking-wider mb-3">État Chariots</p>
        <div class="space-y-2.5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-emerald-400"></div><span class="text-sm text-slate-600 dark:text-slate-300">Disponibles</span></div>
                <span class="font-bold text-slate-800 dark:text-white">{{ $stats['carts_empty'] }}</span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-amber-400"></div><span class="text-sm text-slate-600 dark:text-slate-300">En traitement</span></div>
                <span class="font-bold text-slate-800 dark:text-white">{{ $stats['carts_processing'] }}</span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-blue-400"></div><span class="text-sm text-slate-600 dark:text-slate-300">Congelés</span></div>
                <span class="font-bold text-slate-800 dark:text-white">{{ $stats['carts_frozen'] }}</span>
            </div>
            <div class="pt-2 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between">
                <span class="text-xs text-slate-400">Cycles congélation actifs</span>
                <span class="font-bold text-blue-600">{{ $stats['freezing_active'] }}</span>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-700">
        <p class="text-slate-400 text-xs font-medium uppercase tracking-wider mb-3">Activité du Jour</p>
        <div class="space-y-2.5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-primary-400"></div><span class="text-sm text-slate-600 dark:text-slate-300">Arrivages</span></div>
                <span class="font-bold text-slate-800 dark:text-white">{{ $stats['arrivals_today'] }}</span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-violet-400"></div><span class="text-sm text-slate-600 dark:text-slate-300">Traitements actifs</span></div>
                <span class="font-bold text-slate-800 dark:text-white">{{ $stats['processing_active'] }}</span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-teal-400"></div><span class="text-sm text-slate-600 dark:text-slate-300">Livraisons</span></div>
                <span class="font-bold text-slate-800 dark:text-white">{{ $stats['deliveries_today'] }}</span>
            </div>
            <div class="pt-2 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between">
                <span class="text-xs text-slate-400">Poids reçu</span>
                <span class="font-bold text-primary-600">{{ number_format($stats['weight_today'], 0, ',', ' ') }} kg</span>
            </div>
        </div>
    </div>
</div>

{{-- CHARTS --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
    <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-700">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-sm font-semibold text-slate-700 dark:text-white">Évolution Facturation</h3>
                <p class="text-xs text-slate-400">6 derniers mois (MRU)</p>
            </div>
        </div>
        <div id="revenueChart"></div>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-700">
        <div class="mb-4">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-white">Répartition Stock</h3>
            <p class="text-xs text-slate-400">Par type de poisson</p>
        </div>
        <div id="fishStockChart"></div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-4">
            <i class="fas fa-temperature-low mr-1.5 text-cold-500"></i>Occupation Tunnels
        </h3>
        @forelse($tunnelStats as $t)
        <div class="mb-3 last:mb-0">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-medium text-slate-600 dark:text-slate-300 truncate">{{ $t['name'] }}</span>
                <span class="text-xs font-bold ml-2 flex-shrink-0 {{ $t['pct'] > 80 ? 'text-red-600' : ($t['pct'] > 60 ? 'text-amber-600' : 'text-emerald-600') }}">
                    {{ $t['used'] }}/{{ $t['capacity'] }}
                </span>
            </div>
            <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-2">
                <div class="h-2 rounded-full {{ $t['pct'] > 80 ? 'bg-red-400' : ($t['pct'] > 60 ? 'bg-amber-400' : 'bg-emerald-400') }}"
                     style="width: {{ min($t['pct'], 100) }}%"></div>
            </div>
        </div>
        @empty
        <div class="text-center py-6 text-slate-400">
            <i class="fas fa-temperature-low text-2xl mb-1 block"></i>
            <p class="text-xs">Aucun tunnel</p>
        </div>
        @endforelse
    </div>

    <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-4">
            <i class="fas fa-trophy mr-1.5 text-amber-500"></i>Top Clients par Stock
        </h3>
        <div id="topClientsChart"></div>
    </div>
</div>

{{-- RECENT ACTIVITY --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-white">
                <i class="fas fa-truck-ramp-box mr-1.5 text-primary-500"></i>Derniers Arrivages
            </h3>
            <a href="{{ route('arrivals.index') }}" class="text-xs text-primary-600 hover:underline font-medium">Voir tout</a>
        </div>
        <div class="divide-y divide-slate-50 dark:divide-slate-700">
            @forelse($recentArrivals as $arr)
            <div class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <div class="h-8 w-8 bg-primary-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-box text-primary-600 text-xs"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-700 dark:text-white truncate">{{ $arr->reference }}</p>
                    <p class="text-xs text-slate-400">{{ $arr->client->name ?? '-' }} · {{ $arr->arrival_date->format('d/m H:i') }}</p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-sm font-semibold text-slate-700 dark:text-white">{{ number_format($arr->net_weight, 0) }} kg</p>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ match($arr->status) { 'completed'=>'bg-emerald-100 text-emerald-700', 'processing'=>'bg-amber-100 text-amber-700', default=>'bg-slate-100 text-slate-500' } }}">
                        {{ ucfirst($arr->status) }}
                    </span>
                </div>
            </div>
            @empty
            <div class="text-center py-8 text-slate-400">
                <i class="fas fa-inbox text-2xl mb-1 block"></i>
                <p class="text-xs">Aucun arrivage récent</p>
            </div>
            @endforelse
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-white">
                <i class="fas fa-file-invoice-dollar mr-1.5 text-emerald-500"></i>Dernières Factures
            </h3>
            <a href="{{ route('invoices.index') }}" class="text-xs text-primary-600 hover:underline font-medium">Voir tout</a>
        </div>
        <div class="divide-y divide-slate-50 dark:divide-slate-700">
            @forelse($recentInvoices as $inv)
            <div class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <div class="h-8 w-8 bg-emerald-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-receipt text-emerald-600 text-xs"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-700 dark:text-white truncate">{{ $inv->invoice_number }}</p>
                    <p class="text-xs text-slate-400">{{ $inv->client->name ?? '-' }} · {{ $inv->invoice_date->format('d/m/Y') }}</p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-sm font-semibold text-slate-700 dark:text-white">{{ number_format($inv->total, 2, ',', ' ') }} MRU</p>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ match($inv->status) { 'paid'=>'bg-emerald-100 text-emerald-700', 'partial'=>'bg-amber-100 text-amber-700', 'cancelled'=>'bg-red-100 text-red-700', default=>'bg-slate-100 text-slate-500' } }}">
                        {{ match($inv->status) { 'paid'=>'Payée', 'partial'=>'Partielle', 'cancelled'=>'Annulée', default=>'Brouillon' } }}
                    </span>
                </div>
            </div>
            @empty
            <div class="text-center py-8 text-slate-400">
                <i class="fas fa-file-invoice text-2xl mb-1 block"></i>
                <p class="text-xs">Aucune facture récente</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const revenueData = @json($monthlyInvoices);
new ApexCharts(document.querySelector('#revenueChart'), {
    series: [{ name: 'Facturation (MRU)', data: revenueData.map(d => parseFloat(d.total)||0) }],
    chart: { type: 'area', height: 200, toolbar: { show: false } },
    colors: ['#2563eb'],
    fill: { type: 'gradient', gradient: { opacityFrom: 0.25, opacityTo: 0.02 } },
    stroke: { curve: 'smooth', width: 2.5 },
    xaxis: { categories: revenueData.map(d => d.month), labels: { style: { fontSize: '11px', colors: '#94a3b8' } } },
    yaxis: { labels: { formatter: v => (v/1000).toFixed(0) + 'k', style: { fontSize: '11px', colors: '#94a3b8' } } },
    tooltip: { y: { formatter: v => Number(v).toLocaleString('fr-MA') + ' MRU' } },
    grid: { strokeDashArray: 4, borderColor: '#f1f5f9' },
    dataLabels: { enabled: false },
}).render();

const fishData = @json($stockByFish);
if (fishData.length > 0) {
    new ApexCharts(document.querySelector('#fishStockChart'), {
        series: fishData.map(d => parseFloat(d.total_weight)||0),
        labels: fishData.map(d => d.name),
        chart: { type: 'donut', height: 200 },
        colors: ['#2563eb','#0ea5e9','#06b6d4','#10b981','#3b82f6','#6366f1','#8b5cf6','#f59e0b'],
        legend: { position: 'bottom', fontSize: '11px', markers: { width: 8, height: 8 } },
        plotOptions: { pie: { donut: { size: '65%', labels: { show: true, total: { show: true, label: 'Total', formatter: (w) => Number(w.globals.seriesTotals.reduce((a,b)=>a+b,0)).toLocaleString('fr-MA') + ' kg' } } } } },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: v => Number(v).toLocaleString('fr-MA') + ' kg' } },
    }).render();
} else {
    document.querySelector('#fishStockChart').innerHTML = '<p class="text-center text-slate-400 text-xs py-16">Aucun stock</p>';
}

const clientData = @json($topClients);
if (clientData.length > 0) {
    new ApexCharts(document.querySelector('#topClientsChart'), {
        series: [{ name: 'Stock (kg)', data: clientData.map(d => parseFloat(d.total)||0) }],
        chart: { type: 'bar', height: 200, toolbar: { show: false } },
        colors: ['#0ea5e9'],
        xaxis: { categories: clientData.map(d => d.name), labels: { style: { fontSize: '11px', colors: '#94a3b8' } } },
        yaxis: { labels: { formatter: v => Number(v).toLocaleString('fr-MA') + ' kg', style: { fontSize: '11px', colors: '#94a3b8' } } },
        plotOptions: { bar: { borderRadius: 6, columnWidth: '45%' } },
        dataLabels: { enabled: false },
        grid: { strokeDashArray: 4, borderColor: '#f1f5f9' },
        tooltip: { y: { formatter: v => Number(v).toLocaleString('fr-MA') + ' kg' } },
    }).render();
} else {
    document.querySelector('#topClientsChart').innerHTML = '<p class="text-center text-slate-400 text-xs py-16">Aucune donnée</p>';
}
</script>
@endpush

