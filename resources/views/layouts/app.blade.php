<!DOCTYPE html>
<html lang="fr" x-data="{ sidebarOpen: window.innerWidth >= 1024, darkMode: false }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'FreezePro ERP') — {{ \App\Models\CompanySetting::get('company_name', 'FreezePro') }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: { 50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a' },
                        cold:    { 50:'#f0f9ff',100:'#e0f2fe',200:'#bae6fd',300:'#7dd3fc',400:'#38bdf8',500:'#0ea5e9',600:'#0284c7',700:'#0369a1',800:'#075985',900:'#0c4a6e' },
                    }
                }
            }
        }
    </script>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.tailwindcss.min.css">
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.tailwindcss.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
        .sidebar-transition { transition: width 0.3s cubic-bezier(0.4,0,0.2,1); }
        .content-transition { transition: margin-left 0.3s cubic-bezier(0.4,0,0.2,1); }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .select2-container--default .select2-selection--single { border: 1px solid #e2e8f0 !important; border-radius: 0.75rem !important; height: 42px !important; padding: 6px 12px !important; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 28px !important; color: #374151 !important; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px !important; }
        .select2-dropdown { border-radius: 0.75rem !important; border-color: #e2e8f0 !important; box-shadow: 0 10px 25px rgba(0,0,0,.1) !important; }
        .select2-container--default .select2-results__option--highlighted { background-color: #2563eb !important; }
        table.dataTable thead th { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; background: #f8fafc; }
        .dataTables_wrapper .dataTables_filter input { border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 0.4rem 0.75rem; font-size: 0.875rem; }
        .dataTables_wrapper .dataTables_length select { border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 0.3rem 0.5rem; }
        .skeleton { background: linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%); background-size: 200% 100%; animation: skeleton-anim 1.5s infinite; border-radius: 0.5rem; }
        @keyframes skeleton-anim { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
        .toast-container { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999; display: flex; flex-direction: column; gap: 0.5rem; }
        .toast { display: flex; align-items: center; gap: 0.75rem; padding: 1rem 1.25rem; border-radius: 1rem; color: white; font-size: 0.875rem; font-weight: 500; box-shadow: 0 10px 25px rgba(0,0,0,.15); animation: toast-in .3s ease; min-width: 280px; }
        @keyframes toast-in { from{transform:translateX(100%);opacity:0} to{transform:translateX(0);opacity:1} }
        .modal { position: fixed; inset: 0; z-index: 60; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .modal-overlay { position: absolute; inset: 0; background: rgba(0,0,0,.5); backdrop-filter: blur(4px); }
        .modal-box { position: relative; background: white; border-radius: 1.5rem; box-shadow: 0 25px 50px rgba(0,0,0,.2); max-height: 90vh; overflow-y: auto; width: 100%; }
    </style>

    @stack('styles')
</head>
<body class="bg-slate-50 dark:bg-slate-900 min-h-screen" x-cloak>

<div class="toast-container" id="toastContainer"></div>

{{-- SIDEBAR --}}
<aside id="sidebar"
    class="fixed inset-y-0 left-0 z-50 flex flex-col bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 shadow-2xl sidebar-transition overflow-hidden"
    :class="sidebarOpen ? 'w-64' : 'w-[4.5rem]'">

    {{-- Brand --}}
    <div class="flex items-center h-16 px-4 border-b border-white/10 flex-shrink-0">
        @php $logo = \App\Models\CompanySetting::get('logo'); $cname = \App\Models\CompanySetting::get('company_name', 'FreezePro ERP'); @endphp
        @if($logo)
            <img src="{{ asset('storage/' . $logo) }}" class="h-9 w-9 rounded-xl object-cover flex-shrink-0" alt="Logo">
        @else
            <div class="h-9 w-9 rounded-xl bg-gradient-to-br from-cold-400 to-primary-600 flex items-center justify-center flex-shrink-0 shadow-lg">
                <i class="fas fa-snowflake text-white text-sm"></i>
            </div>
        @endif
        <div class="ml-3 overflow-hidden" x-show="sidebarOpen" x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <p class="text-white font-bold text-sm leading-tight truncate">{{ $cname }}</p>
            <p class="text-slate-400 text-xs mt-0.5">Gestion Frigorifique</p>
        </div>
    </div>

    {{-- Nav --}}
    <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-0.5">
        {{-- Section: Principal --}}
        <div class="px-3 py-1 text-xs font-semibold text-slate-500 uppercase tracking-wider nav-label" x-show="sidebarOpen">Principal</div>

        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('dashboard') ? 'text-white bg-white/15 shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
            <i class="fas fa-gauge-high w-5 text-center flex-shrink-0"></i>
            <span x-show="sidebarOpen" class="whitespace-nowrap">Tableau de Bord</span>
        </a>
        <a href="{{ route('presentation') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('presentation') ? 'text-white bg-white/15 shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
            <i class="fas fa-person-chalkboard w-5 text-center flex-shrink-0"></i>
            <span x-show="sidebarOpen" class="whitespace-nowrap">Présentation</span>
        </a>

        {{-- Section: Opérations --}}
        <div class="px-3 py-1 mt-3 text-xs font-semibold text-slate-500 uppercase tracking-wider nav-label" x-show="sidebarOpen">Opérations</div>

        @can('arrivals.index')

        <a href="{{ route('arrivals.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('arrivals.*') ? 'text-white bg-white/15' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
            <i class="fas fa-truck-ramp-box w-5 text-center flex-shrink-0"></i>
            <span x-show="sidebarOpen" class="whitespace-nowrap">Arrivages</span>
        </a>

        @endcan
        @can('processing.index')
        <a href="{{ route('processing.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('processing.*') ? 'text-white bg-white/15' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
            <i class="fas fa-gears w-5 text-center flex-shrink-0"></i>
            <span x-show="sidebarOpen" class="whitespace-nowrap">Traitement</span>
        </a>
        @endcan
        @can('packaging.index')
        <a href="{{ route('packaging.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('packaging.*') ? 'text-white bg-white/15' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
            <i class="fas fa-box-open w-5 text-center flex-shrink-0"></i>
            <span x-show="sidebarOpen" class="whitespace-nowrap">Cartonnage</span>
        </a>
        @endcan

        {{-- Section: Congélation --}}
        <div class="px-3 py-1 mt-3 text-xs font-semibold text-slate-500 uppercase tracking-wider" x-show="sidebarOpen">Congélation</div>

        @can('tunnels.index')

        <a href="{{ route('tunnels.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('tunnels.*') ? 'text-white bg-white/15' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
            <i class="fas fa-temperature-low w-5 text-center flex-shrink-0"></i>
            <span x-show="sidebarOpen" class="whitespace-nowrap">Tunnels</span>
        </a>

        @endcan
        @can('carts.index')
        <a href="{{ route('carts.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('carts.*') ? 'text-white bg-white/15' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
            <i class="fas fa-cart-flatbed-suitcase w-5 text-center flex-shrink-0"></i>
            <span x-show="sidebarOpen" class="whitespace-nowrap">Chariots</span>
        </a>
        @endcan
        @can('cold-rooms.index')
        <a href="{{ route('cold-rooms.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('cold-rooms.*') ? 'text-white bg-white/15' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
            <i class="fas fa-igloo w-5 text-center flex-shrink-0"></i>
            <span x-show="sidebarOpen" class="whitespace-nowrap">Chambres Froides</span>
        </a>
        @endcan

        {{-- Section: Stock --}}
        <div class="px-3 py-1 mt-3 text-xs font-semibold text-slate-500 uppercase tracking-wider" x-show="sidebarOpen">Stock</div>

        @can('stock.index')

        <a href="{{ route('stock.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('stock.*') ? 'text-white bg-white/15' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
            <i class="fas fa-warehouse w-5 text-center flex-shrink-0"></i>
            <span x-show="sidebarOpen" class="whitespace-nowrap">Stock Client</span>
        </a>

        @endcan
        @can('stock.create')
        <a href="{{ route('stock.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('stock.create') ? 'text-white bg-white/15 shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
            <i class="fas fa-arrow-right-to-bracket w-5 text-center flex-shrink-0"></i>
            <span x-show="sidebarOpen" class="whitespace-nowrap">Entrée directe client</span>
        </a>
        @endcan
        @can('stock.transactions')
        <a href="{{ route('stock.transactions') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('stock.transactions*') ? 'text-white bg-white/15' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
            <i class="fas fa-arrows-rotate w-5 text-center flex-shrink-0"></i>
            <span x-show="sidebarOpen" class="whitespace-nowrap">Suivi transactions</span>
        </a>
        @endcan
        @can('deliveries.index')
        <a href="{{ route('deliveries.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('deliveries.*') ? 'text-white bg-white/15' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
            <i class="fas fa-truck w-5 text-center flex-shrink-0"></i>
            <span x-show="sidebarOpen" class="whitespace-nowrap">Livraisons</span>
        </a>
        @endcan

        {{-- Section: Commercial --}}
        <div class="px-3 py-1 mt-3 text-xs font-semibold text-slate-500 uppercase tracking-wider" x-show="sidebarOpen">Commercial</div>

        @can('clients.index')

        <a href="{{ route('clients.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('clients.*') ? 'text-white bg-white/15' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
            <i class="fas fa-users w-5 text-center flex-shrink-0"></i>
            <span x-show="sidebarOpen" class="whitespace-nowrap">Clients</span>
        </a>

        @endcan
        @can('invoices.index')
        <a href="{{ route('invoices.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('invoices.*') ? 'text-white bg-white/15' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
            <i class="fas fa-file-invoice-dollar w-5 text-center flex-shrink-0"></i>
            <span x-show="sidebarOpen" class="whitespace-nowrap">Fact. Traitement</span>
        </a>
        @endcan
        @can('storage-billing.index')
        <a href="{{ route('storage-billing.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('storage-billing.*') ? 'text-white bg-white/15' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
            <i class="fas fa-snowflake w-5 text-center flex-shrink-0"></i>
            <span x-show="sidebarOpen" class="whitespace-nowrap">Fact. Congélation</span>
        </a>
        @endcan

        {{-- Section: Référentiels --}}
        <div class="px-3 py-1 mt-3 text-xs font-semibold text-slate-500 uppercase tracking-wider" x-show="sidebarOpen">Référentiels</div>

        @can('fish-types.index')

        <a href="{{ route('fish-types.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('fish-types.*') ? 'text-white bg-white/15' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
            <i class="fas fa-fish w-5 text-center flex-shrink-0"></i>
            <span x-show="sidebarOpen" class="whitespace-nowrap">Types Poissons</span>
        </a>

        @endcan
        @can('carton-types.index')
        <a href="{{ route('carton-types.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('carton-types.*') ? 'text-white bg-white/15' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
            <i class="fas fa-boxes-stacked w-5 text-center flex-shrink-0"></i>
            <span x-show="sidebarOpen" class="whitespace-nowrap">Types Cartons</span>
        </a>
        @endcan

        {{-- Section: Rapports --}}
        <div class="px-3 py-1 mt-3 text-xs font-semibold text-slate-500 uppercase tracking-wider" x-show="sidebarOpen">Rapports</div>

        @can('reports.index')

        <a href="{{ route('reports.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('reports.*') ? 'text-white bg-white/15' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
            <i class="fas fa-chart-bar w-5 text-center flex-shrink-0"></i>
            <span x-show="sidebarOpen" class="whitespace-nowrap">Rapports</span>
        </a>

        @endcan

        @if(auth()->user()->can('users.index') || auth()->user()->can('roles.index') || auth()->user()->can('settings.index'))
        <div class="px-3 py-1 mt-3 text-xs font-semibold text-slate-500 uppercase tracking-wider" x-show="sidebarOpen">Administration</div>
        @endif
        @can('roles.index')
        <a href="{{ route('roles.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('roles.*') ? 'text-white bg-white/15' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
            <i class="fas fa-key w-5 text-center flex-shrink-0"></i>
            <span x-show="sidebarOpen" class="whitespace-nowrap">Rôles et permissions</span>
        </a>
        @endcan
        @can('users.index')
        <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('users.*') ? 'text-white bg-white/15' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
            <i class="fas fa-user-shield w-5 text-center flex-shrink-0"></i>
            <span x-show="sidebarOpen" class="whitespace-nowrap">Utilisateurs</span>
        </a>
        @endcan
        @can('settings.index')
        <a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('settings.*') ? 'text-white bg-white/15' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
            <i class="fas fa-sliders w-5 text-center flex-shrink-0"></i>
            <span x-show="sidebarOpen" class="whitespace-nowrap">Paramètres</span>
        </a>
        @endcan
    </nav>

    {{-- User footer --}}
    <div class="border-t border-white/10 p-3 flex-shrink-0">
        <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-full bg-gradient-to-br from-primary-400 to-cold-500 flex items-center justify-center flex-shrink-0 shadow">
                <span class="text-white text-xs font-bold">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
            </div>
            <div class="overflow-hidden flex-1" x-show="sidebarOpen">
                <p class="text-white text-sm font-medium truncate">{{ auth()->user()->name ?? 'Utilisateur' }}</p>
                <p class="text-slate-400 text-xs truncate">{{ auth()->user()->getRoleNames()->first() ?? 'N/A' }}</p>
            </div>
        </div>
    </div>
</aside>

{{-- MAIN --}}
<div class="content-transition" :class="sidebarOpen ? 'ml-64' : 'ml-[4.5rem]'">

    {{-- HEADER --}}
    <header class="sticky top-0 z-40 bg-white/95 dark:bg-slate-800/95 backdrop-blur border-b border-slate-200 dark:border-slate-700 shadow-sm">
        <div class="flex items-center justify-between h-16 px-6">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = !sidebarOpen"
                    class="p-2 rounded-xl text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition-colors">
                    <i class="fas fa-bars text-base"></i>
                </button>
                <nav class="hidden md:flex items-center gap-1 text-sm text-slate-400">
                    <a href="{{ route('dashboard') }}" class="hover:text-primary-600 transition-colors">
                        <i class="fas fa-house text-xs"></i>
                    </a>
                    @hasSection('breadcrumb')
                        <i class="fas fa-chevron-right text-[10px]"></i>
                        <span class="text-slate-600 font-medium">@yield('breadcrumb')</span>
                    @endif
                </nav>
            </div>

            <div class="flex items-center gap-2">
                <div class="relative hidden lg:block">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" placeholder="Recherche rapide..." id="globalSearch"
                        class="pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 w-56 bg-slate-50 dark:bg-slate-700 dark:border-slate-600">
                </div>

                <button @click="darkMode = !darkMode"
                    class="p-2 rounded-xl text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-700 transition-colors">
                    <i class="fas" :class="darkMode ? 'fa-sun' : 'fa-moon'"></i>
                </button>

                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                        class="relative p-2 rounded-xl text-slate-500 hover:bg-slate-100 transition-colors">
                        <i class="fas fa-bell text-base"></i>
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white"></span>
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition
                        class="absolute right-0 top-full mt-2 w-72 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700 p-3 z-50">
                        <p class="text-sm font-semibold text-slate-700 dark:text-white mb-2 px-2">Notifications</p>
                        <div class="text-xs text-slate-400 text-center py-6">
                            <i class="fas fa-bell-slash text-2xl mb-2 block"></i>
                            Aucune notification récente
                        </div>
                    </div>
                </div>

                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        <div class="h-8 w-8 rounded-full bg-gradient-to-br from-primary-400 to-cold-500 flex items-center justify-center shadow-sm">
                            <span class="text-white text-xs font-bold">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
                        </div>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-200 hidden md:block">{{ auth()->user()->name ?? '' }}</span>
                        <i class="fas fa-chevron-down text-[10px] text-slate-400"></i>
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition
                        class="absolute right-0 top-full mt-2 w-48 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700 py-1.5 z-50">
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700">
                            <i class="fas fa-user-circle w-4 text-slate-400"></i> Mon Profil
                        </a>
                        <a href="{{ route('settings.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700">
                            <i class="fas fa-cog w-4 text-slate-400"></i> Paramètres
                        </a>
                        <div class="my-1 border-t border-slate-100 dark:border-slate-600"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                                <i class="fas fa-right-from-bracket w-4"></i> Déconnexion
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- PAGE CONTENT --}}
    <main class="p-6">
        @if(session('success'))
        <div class="mb-5 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl"
             x-data="{ show: true }" x-show="show" x-transition>
            <i class="fas fa-circle-check text-emerald-500 text-lg flex-shrink-0"></i>
            <span class="text-sm font-medium flex-1">{{ session('success') }}</span>
            @if(session('pdf_url'))
            <a href="{{ session('pdf_url') }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white">
                <i class="fas fa-file-pdf"></i> {{ session('pdf_label', 'Telecharger PDF') }}
            </a>
            @endif
            <button @click="show=false" class="text-emerald-500 hover:text-emerald-700">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        @endif
        @if(session('error'))
        <div class="mb-5 flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-2xl"
             x-data="{ show: true }" x-show="show" x-transition>
            <i class="fas fa-circle-exclamation text-red-500 text-lg flex-shrink-0"></i>
            <span class="text-sm font-medium flex-1">{{ session('error') }}</span>
            <button @click="show=false" class="text-red-500 hover:text-red-700">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        @endif
        @foreach(['warning' => ['amber', 'fa-triangle-exclamation'], 'info' => ['sky', 'fa-circle-info']] as $flashKey => [$flashColor, $flashIcon])
        @if(session($flashKey))
        <div class="mb-5 flex items-center gap-3 bg-{{ $flashColor }}-50 border border-{{ $flashColor }}-200 text-{{ $flashColor }}-800 px-4 py-3 rounded-2xl"
             x-data="{ show: true }" x-show="show" x-transition>
            <i class="fas {{ $flashIcon }} text-{{ $flashColor }}-500 text-lg flex-shrink-0"></i>
            <span class="text-sm font-medium flex-1">{{ session($flashKey) }}</span>
            <button @click="show=false" class="text-{{ $flashColor }}-500 hover:text-{{ $flashColor }}-700">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        @endif
        @endforeach

        @yield('content')
    </main>

    <footer class="border-t border-slate-200 dark:border-slate-700 px-6 py-3 text-xs text-slate-400 flex items-center justify-between">
        <span>© {{ date('Y') }} {{ \App\Models\CompanySetting::get('company_name', 'FreezePro ERP') }} — Tous droits réservés</span>
        <span class="hidden sm:block">Système de Gestion Frigorifique v1.0</span>
    </footer>
</div>

<script>
// CSRF setup
$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

// DataTables French
$.extend(true, $.fn.dataTable.defaults, {
    language: { url: '//cdn.datatables.net/plug-ins/2.0.8/i18n/fr-FR.json' },
    pageLength: 25,
    dom: '<"flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4"<"flex items-center gap-2"l><"flex items-center gap-2"f>>rt<"flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-4"<"text-sm text-slate-500"i><"flex items-center gap-1"p>>',
});

// Toast
function showToast(msg, type = 'success') {
    const cfg = {
        success: { bg: 'bg-emerald-500', icon: 'fa-circle-check' },
        error:   { bg: 'bg-red-500',     icon: 'fa-circle-xmark' },
        warning: { bg: 'bg-amber-500',   icon: 'fa-triangle-exclamation' },
        info:    { bg: 'bg-blue-500',    icon: 'fa-circle-info' },
    };
    const c = cfg[type] || cfg.info;
    const el = document.createElement('div');
    el.className = `toast ${c.bg}`;
    el.innerHTML = `<i class="fas ${c.icon}"></i><span>${msg}</span>`;
    document.getElementById('toastContainer').appendChild(el);
    setTimeout(() => { el.style.animation = 'none'; el.style.opacity = '0'; el.style.transition = 'opacity .3s'; setTimeout(() => el.remove(), 300); }, 3500);
}

// Confirm delete
function confirmDelete(url, name = 'cet élément') {
    Swal.fire({
        title: 'Confirmer la suppression',
        html: `Voulez-vous vraiment supprimer <strong>${name}</strong> ?<br><small class="text-gray-400">Cette action est irréversible.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-trash mr-1"></i> Supprimer',
        cancelButtonText: '<i class="fas fa-xmark mr-1"></i> Annuler',
        customClass: { popup: 'rounded-3xl font-medium' }
    }).then(r => {
        if (r.isConfirmed) {
            const f = document.createElement('form');
            f.method = 'POST'; f.action = url;
            f.innerHTML = `<input type="hidden" name="_token" value="${$('meta[name=csrf-token]').attr('content')}"><input type="hidden" name="_method" value="DELETE">`;
            document.body.appendChild(f); f.submit();
        }
    });
}

// Modal helpers
function openModal(id) {
    const m = document.getElementById(id);
    if (m) { m.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
    if (id) {
        const m = document.getElementById(id);
        if (m) m.classList.add('hidden');
    } else {
        document.querySelectorAll('.modal').forEach(m => m.classList.add('hidden'));
    }
    document.body.style.overflow = '';
}
</script>

@stack('scripts')
</body>
</html>
