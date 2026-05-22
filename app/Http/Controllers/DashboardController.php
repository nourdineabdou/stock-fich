<?php

namespace App\Http\Controllers;

use App\Models\Arrival;
use App\Models\Cart;
use App\Models\Client;
use App\Models\ClientStockBatch;
use App\Models\ColdRoom;
use App\Models\Delivery;
use App\Models\FishType;
use App\Models\FreezingCycle;
use App\Models\Invoice;
use App\Models\ProcessingOrder;
use App\Models\StockMovement;
use App\Models\Tunnel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = Cache::remember('dashboard_stats', 120, function () {
            $today = now()->toDateString();
            $month = now()->startOfMonth();

            return [
                'total_clients'       => Client::where('active', true)->count(),
                'total_tunnels'       => Tunnel::where('active', true)->count(),
                'total_cold_rooms'    => ColdRoom::where('active', true)->count(),
                'total_carts'         => Cart::count(),
                'carts_processing'    => Cart::where('status', 'processing')->count(),
                'carts_frozen'        => Cart::where('status', 'frozen')->count(),
                'carts_empty'         => Cart::where('status', 'empty')->count(),

                'arrivals_today'      => Arrival::whereDate('arrival_date', $today)->count(),
                'arrivals_month'      => Arrival::where('arrival_date', '>=', $month)->count(),
                'weight_today'        => Arrival::whereDate('arrival_date', $today)->sum('net_weight'),

                'processing_active'   => ProcessingOrder::where('status', 'processing')->count(),
                'processing_pending'  => ProcessingOrder::where('status', 'pending')->count(),

                'stock_total_cartons' => ClientStockBatch::where('status', '!=', 'empty')->sum('available_cartons'),
                'stock_total_weight'  => ClientStockBatch::where('status', '!=', 'empty')->sum('available_weight'),
                'stock_batches'       => ClientStockBatch::where('status', 'active')->count(),

                'deliveries_today'    => Delivery::whereDate('delivery_date', $today)->count(),
                'deliveries_month'    => Delivery::where('delivery_date', '>=', $month)->count(),

                'invoice_today'       => Invoice::whereDate('invoice_date', $today)->where('status', '!=', 'cancelled')->sum('total'),
                'invoice_month'       => Invoice::where('invoice_date', '>=', $month)->where('status', '!=', 'cancelled')->sum('total'),
                'invoices_unpaid'     => Invoice::whereIn('status', ['draft', 'partial'])->count(),

                'freezing_active'     => FreezingCycle::where('status', 'running')->count(),
            ];
        });

        // Stock by fish type
        $stockByFish = DB::table('client_stock_batches')
            ->join('fish_types', 'fish_types.id', '=', 'client_stock_batches.fish_type_id')
            ->where('client_stock_batches.status', '!=', 'empty')
            ->groupBy('fish_types.id', 'fish_types.name')
            ->select('fish_types.name', DB::raw('SUM(client_stock_batches.available_weight) as total_weight'))
            ->orderByDesc('total_weight')
            ->limit(8)
            ->get();

        // Monthly invoices (last 6 months)
        $monthlyInvoices = DB::table('invoices')
            ->where('invoice_date', '>=', now()->subMonths(6))
            ->where('status', '!=', 'cancelled')
            ->select(DB::raw('DATE_FORMAT(invoice_date, "%Y-%m") as month'), DB::raw('SUM(total) as total'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Top clients by stock
        $topClients = DB::table('client_stock_batches')
            ->join('clients', 'clients.id', '=', 'client_stock_batches.client_id')
            ->where('client_stock_batches.status', '!=', 'empty')
            ->groupBy('clients.id', 'clients.name')
            ->select('clients.name', DB::raw('SUM(client_stock_batches.available_weight) as total'))
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Tunnel occupation
        $tunnelStats = Tunnel::where('active', true)
            ->withCount(['carts as active_carts' => fn($q) => $q->whereIn('status', ['processing', 'frozen'])])
            ->get()
            ->map(fn($t) => [
                'name' => $t->name,
                'capacity' => $t->capacity_chariots,
                'used' => $t->active_carts,
                'pct' => $t->capacity_chariots > 0 ? round($t->active_carts / $t->capacity_chariots * 100) : 0,
            ]);

        // Recent arrivals
        $recentArrivals = Arrival::with('client')
            ->latest('arrival_date')
            ->limit(5)
            ->get();

        // Recent invoices
        $recentInvoices = Invoice::with('client')
            ->latest('invoice_date')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'stats', 'stockByFish', 'monthlyInvoices', 'topClients',
            'tunnelStats', 'recentArrivals', 'recentInvoices'
        ));
    }
}
