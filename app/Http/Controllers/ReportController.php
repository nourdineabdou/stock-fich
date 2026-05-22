<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Arrival;
use App\Models\Delivery;
use App\Models\ClientStockBatch;
use App\Models\ProcessingOrder;
use App\Models\Client;
use App\Models\FishType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $year  = $request->year ?? now()->year;
        $month = $request->month;

        // Monthly invoicing
        $monthlyInvoices = Invoice::selectRaw('MONTH(invoice_date) as m, SUM(total) as total')
            ->whereYear('invoice_date', $year)
            ->groupBy('m')
            ->orderBy('m')
            ->pluck('total', 'm')
            ->all();
        $invoicesByMonth = [];
        for ($i = 1; $i <= 12; $i++) {
            $invoicesByMonth[] = ['month' => $i, 'total' => $monthlyInvoices[$i] ?? 0];
        }

        // Arrivals per month
        $arrivalsPerMonth = Arrival::selectRaw('MONTH(arrival_date) as m, COUNT(*) as count, SUM(net_weight) as weight')
            ->whereYear('arrival_date', $year)
            ->groupBy('m')
            ->orderBy('m')
            ->get()
            ->keyBy('m');

        $arrivalsByMonth = [];
        for ($i = 1; $i <= 12; $i++) {
            $arrivalsByMonth[] = [
                'month'  => $i,
                'count'  => $arrivalsPerMonth[$i]->count ?? 0,
                'weight' => $arrivalsPerMonth[$i]->weight ?? 0,
            ];
        }

        // Top clients
        $topClients = Invoice::with('client')
            ->whereYear('invoice_date', $year)
            ->selectRaw('client_id, SUM(total) as total')
            ->groupBy('client_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Stock by fish type
        $stockByFish = ClientStockBatch::where('status', 'available')
            ->join('fish_types', 'fish_types.id', '=', 'client_stock_batches.fish_type_id')
            ->selectRaw('fish_types.name, SUM(available_weight) as weight, SUM(available_cartons) as cartons')
            ->groupBy('fish_types.name')
            ->get();

        // Processing yield
        $processingYield = ProcessingOrder::with('items')
            ->whereYear('start_date', $year)
            ->where('status', 'completed')
            ->get()
            ->map(function ($o) {
                $in  = $o->items->sum('input_weight');
                $out = $o->items->sum('output_weight');
                return ['ref' => $o->reference, 'in' => $in, 'out' => $out, 'yield' => $in > 0 ? round($out/$in*100, 1) : 0];
            });

        $years = range(now()->year, now()->year - 4);
        $months = [1=>'Jan',2=>'Fév',3=>'Mar',4=>'Avr',5=>'Mai',6=>'Jui',7=>'Jul',8=>'Aoû',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Déc'];

        return view('reports.index', compact(
            'year','month','invoicesByMonth','arrivalsByMonth',
            'topClients','stockByFish','processingYield','years','months'
        ));
    }
}
