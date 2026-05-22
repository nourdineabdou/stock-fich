<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Http\Requests\ClientRequest;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::withCount(['arrivals', 'invoices', 'stockBatches', 'deliveries'])
            ->withSum('invoices as total_invoiced', 'total')
            ->withSum('stockBatches as stock_cartons', 'available_cartons')
            ->withSum('stockBatches as stock_weight', 'available_weight');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('ice_number', 'like', "%{$request->search}%")
                  ->orWhere('address', 'like', "%{$request->search}%");
            });
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('active', $request->status);
        }

        $clients = $query->orderBy('name')->paginate(10)->withQueryString();
        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        return view('clients.create');
    }

    public function store(ClientRequest $request)
    {
        $client = Client::create($request->validated());
        return redirect()->route('clients.show', $client)->with('success', 'Client créé avec succès.');
    }

    public function show(Client $client)
    {
        $client->loadCount(['arrivals', 'invoices', 'deliveries', 'stockBatches']);
        $client->load([
            'arrivals' => fn($q) => $q->latest()->limit(5),
            'invoices' => fn($q) => $q->latest()->limit(5),
            'deliveries' => fn($q) => $q->latest()->limit(5),
            'stockBatches' => fn($q) => $q->where('status', '!=', 'empty')->with(['fishType', 'cartonType', 'coldRoom']),
        ]);

        $storageRule = $client->pricingRules()
            ->where('service_type', 'storage')
            ->latest('id')
            ->first();

        $paymentSummary = [
            'paid' => (float) $client->invoices()->where('status', 'paid')->sum('total'),
            'unpaid' => (float) $client->invoices()->whereIn('status', ['draft', 'partial'])->sum('total'),
            'storage_unpaid' => (float) $client->invoices()->where('invoice_type', 'storage')->whereIn('status', ['draft', 'partial'])->sum('total'),
            'processing_unpaid' => (float) $client->invoices()->where('invoice_type', 'processing')->whereIn('status', ['draft', 'partial'])->sum('total'),
        ];

        return view('clients.show', compact('client', 'storageRule', 'paymentSummary'));
    }

    public function edit(Client $client)
    {
        return view('clients.edit', compact('client'));
    }

    public function update(ClientRequest $request, Client $client)
    {
        $client->update($request->validated());
        return redirect()->route('clients.show', $client)->with('success', 'Client mis à jour.');
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return redirect()->route('clients.index')->with('success', 'Client supprimé.');
    }
}
