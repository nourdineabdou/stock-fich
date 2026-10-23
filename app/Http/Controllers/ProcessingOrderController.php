<?php

namespace App\Http\Controllers;

use App\Models\ProcessingOrder;
use App\Models\ProcessingItem;
use App\Models\Cart;
use App\Models\CartAssignment;
use App\Models\Client;
use App\Models\Arrival;
use App\Models\FishType;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Tunnel;
use App\Http\Requests\ProcessingOrderRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class ProcessingOrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = ProcessingOrder::with(['client', 'arrival', 'items.fishType'])
            ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('reference', 'like', "%{$request->search}%"))
            ->latest('start_date')
            ->paginate(10)
            ->withQueryString();

        $clients = Client::where('active', true)->orderBy('name')->get();
        return view('processing.index', compact('orders', 'clients'));
    }

    public function create()
    {
        if (request()->filled('arrival_id')) {
            $existingOrder = ProcessingOrder::where('arrival_id', request('arrival_id'))
                ->latest('id')
                ->first();

            if ($existingOrder) {
                return redirect()
                    ->route('processing.edit', $existingOrder)
                    ->with('info', 'Cet arrivage a deja un ordre de traitement. Modifiez-le au lieu d en creer un autre.');
            }
        }

        $clients   = Client::where('active', true)->orderBy('name')->get();
        $fishTypes = FishType::where('active', true)->orderBy('name')->get();
        $arrivals  = Arrival::where('status', '!=', 'completed')->with('client')->latest()->get();
        return view('processing.create', compact('clients', 'fishTypes', 'arrivals'));
    }

    public function store(ProcessingOrderRequest $request)
    {
        if ($request->filled('arrival_id')) {
            $existingOrder = ProcessingOrder::where('arrival_id', $request->arrival_id)
                ->latest('id')
                ->first();

            if ($existingOrder) {
                return redirect()
                    ->route('processing.edit', $existingOrder)
                    ->with('info', 'Cet arrivage a deja un ordre de traitement. Modifiez-le au lieu d en creer un autre.');
            }
        }

        $prefix = CompanySetting::get('processing_prefix', 'TRT');
        $ref = $prefix . '-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));

        $processingData = array_merge($request->safe()->except('items'), [
            'price' => $request->input('price', 0) ?: 0,
            'input_weight' => $request->input('input_weight', 0) ?: 0,
            'output_weight' => $request->input('output_weight', 0) ?: 0,
            'waste_weight' => $request->input('waste_weight', 0) ?: 0,
            'returned_weight' => $request->input('returned_weight', 0) ?: 0,
        ]);

        $order = DB::transaction(function () use ($processingData, $ref, $request) {
            $order = ProcessingOrder::create(array_merge($processingData, ['reference' => $ref]));

            foreach ($request->items ?? [] as $item) {
                if (empty($item['fish_type_id']) || ($item['output_weight'] ?? 0) <= 0) continue;
                ProcessingItem::create([
                    'processing_order_id' => $order->id,
                    'fish_type_id'        => $item['fish_type_id'],
                    'input_weight'        => $item['input_weight'] ?? 0,
                    'output_weight'       => $item['output_weight'] ?? 0,
                    'waste_weight'        => $item['waste_weight'] ?? 0,
                    'returned_weight'     => $item['returned_weight'] ?? 0,
                    'price_per_kg'        => (float) $request->input('price', 0),
                    'total'               => ($item['output_weight'] ?? 0) * (float) $request->input('price', 0),
                ]);
            }

            $this->syncProcessingInvoice($order->fresh());

            return $order;
        });

        return redirect()->route('processing.show', $order)
            ->with('success', 'Fiche de traitement créée: ' . $ref)
            ->with('pdf_url', route('processing.client-pdf', $order))
            ->with('pdf_label', 'Imprimer la fiche client');
    }

    public function show(ProcessingOrder $processing)
    {
        $processing->load([
            'client', 'arrival', 'items.fishType',
            'cartAssignments.cart.tunnel', 'packagingOrders.items',
            'freezingCycles.tunnel',
        ]);

        $assignedCartIds = $processing->cartAssignments
            ->where('status', 'active')
            ->pluck('cart_id')
            ->values()
            ->all();

        $availableCartOptions = Cart::with('tunnel')
            ->where(function ($query) use ($assignedCartIds) {
                $query->where('status', 'empty');
                if (!empty($assignedCartIds)) {
                    $query->orWhereIn('id', $assignedCartIds);
                }
            })
            ->orderByDesc('capacity_kg')
            ->orderBy('code')
            ->get();

        $targetWeight = (float) ($processing->output_weight ?: $processing->items->sum('output_weight'));
        $hasPhase2Triage = $processing->items->where('output_weight', '>', 0)->isNotEmpty() || $targetWeight > 0;
        $remaining = $targetWeight;
        $suggestedCartIds = [];

        if ($hasPhase2Triage) {
            foreach ($availableCartOptions->where('status', 'empty') as $cart) {
                if ($remaining <= 0) {
                    break;
                }
                $suggestedCartIds[] = $cart->id;
                $remaining -= (float) $cart->capacity_kg;
            }
        }

        $preselectedCartIds = array_values(array_unique(array_merge($assignedCartIds, $suggestedCartIds)));
        $activeAssignedCartIds = $assignedCartIds;

        $activeSlotMap = $this->getActiveTunnelSlotMap();
        $activeCountByTunnel = collect($activeSlotMap)
            ->groupBy('tunnel_id')
            ->map(fn ($rows) => $rows->count())
            ->all();

        $slotSuggestionByCart = $this->buildSlotSuggestionByCart($availableCartOptions, $activeSlotMap, $activeCountByTunnel);

        $activeCartPool = $availableCartOptions->whereIn('id', $activeAssignedCartIds)->values();
        $suggestedDistribution = $this->buildSuggestedDistribution($processing->items, $activeCartPool);
        $pendingDistribution = session('suggested_distribution', []);

        $itemCartDistribution = [];
        foreach ($processing->items as $item) {
            $saved = collect($processing->cart_distribution[$item->id] ?? [])->map(fn ($id) => (int) $id)->values()->all();
            $pending = collect($pendingDistribution[$item->id] ?? [])->map(fn ($id) => (int) $id)->values()->all();
            $itemCartDistribution[$item->id] = !empty($saved)
                ? $saved
                : (!empty($pending) ? $pending : ($suggestedDistribution[$item->id] ?? []));
        }

        $distributionIsValidated = !empty($processing->cart_distribution) && is_array($processing->cart_distribution) && count($processing->cart_distribution) > 0;

        $ownAssignments = $processing->cartAssignments->where('status', 'active');
        $ownCountByTunnel = $ownAssignments->groupBy('tunnel_id')->map->count();
        $tunnelOptions = Tunnel::with('coldRoom')
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'cold_room' => $t->coldRoom->name ?? '',
                'capacity' => (int) $t->capacity_chariots,
                // Places deja prises par d'autres traitements
                'occupied_others' => (int) ($activeCountByTunnel[$t->id] ?? 0) - (int) ($ownCountByTunnel[$t->id] ?? 0),
            ])
            ->values();

        $order = $processing;
        return view('processing.show', compact('order', 'availableCartOptions', 'preselectedCartIds', 'targetWeight', 'itemCartDistribution', 'activeAssignedCartIds', 'slotSuggestionByCart', 'activeSlotMap', 'distributionIsValidated', 'tunnelOptions', 'ownAssignments'));
    }

    public function edit(ProcessingOrder $processing)
    {
        $processing->load('items.fishType');
        $clients   = Client::where('active', true)->orderBy('name')->get();
        $fishTypes = FishType::where('active', true)->orderBy('name')->get();
        $arrivals  = Arrival::where('status', '!=', 'completed')->with('client')->latest()->get();
        $processingOrder = $processing;
        return view('processing.create', compact('processingOrder', 'clients', 'fishTypes', 'arrivals'));
    }

    public function update(ProcessingOrderRequest $request, ProcessingOrder $processing)
    {
        DB::transaction(function () use ($request, $processing) {
            $processing->update(array_merge($request->safe()->except('items'), [
                'price' => $request->input('price', 0) ?: 0,
                'input_weight' => $request->input('input_weight', 0) ?: 0,
                'output_weight' => $request->input('output_weight', 0) ?: 0,
                'waste_weight' => $request->input('waste_weight', 0) ?: 0,
                'returned_weight' => $request->input('returned_weight', 0) ?: 0,
            ]));

            if ($request->has('items')) {
                $processing->items()->delete();
                foreach ($request->items as $item) {
                    if (empty($item['fish_type_id']) || ($item['output_weight'] ?? 0) <= 0) continue;
                    ProcessingItem::create([
                        'processing_order_id' => $processing->id,
                        'fish_type_id'        => $item['fish_type_id'],
                        'input_weight'        => $item['input_weight'] ?? 0,
                        'output_weight'       => $item['output_weight'] ?? 0,
                        'waste_weight'        => $item['waste_weight'] ?? 0,
                        'returned_weight'     => $item['returned_weight'] ?? 0,
                        'price_per_kg'        => (float) $request->input('price', 0),
                        'total'               => ($item['output_weight'] ?? 0) * (float) $request->input('price', 0),
                    ]);
                }
            }

            $this->syncProcessingInvoice($processing->fresh());
        });

        return redirect()->route('processing.show', $processing)
            ->with('success', 'Fiche de traitement mise à jour.')
            ->with('pdf_url', route('processing.client-pdf', $processing))
            ->with('pdf_label', 'Imprimer la fiche client');
    }

    public function destroy(ProcessingOrder $processing)
    {
        $processing->delete();
        return redirect()->route('processing.index')->with('success', 'Ordre de traitement supprimé.');
    }

    public function assignCarts(Request $request, ProcessingOrder $processing)
    {
        $triageWeight = (float) ($processing->output_weight ?: $processing->items()->sum('output_weight'));
        if ($triageWeight <= 0) {
            return back()->withErrors([
                'cart_ids' => 'Completez d abord le triage (phase 2) avant l affectation des chariots.',
            ]);
        }

        $validated = $request->validate([
            'cart_ids' => 'nullable|array',
            'cart_ids.*' => 'integer|exists:carts,id',
        ]);

        $requestedCartIds = collect($validated['cart_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();

        $existingAssignedIds = $processing->cartAssignments()
            ->where('status', 'active')
            ->pluck('cart_id');

        $allowedCartIds = Cart::query()
            ->whereIn('id', $requestedCartIds)
            ->where(function ($query) use ($existingAssignedIds) {
                $query->where('status', 'empty');
                if ($existingAssignedIds->isNotEmpty()) {
                    $query->orWhereIn('id', $existingAssignedIds);
                }
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        if ($allowedCartIds->count() !== $requestedCartIds->count()) {
            return back()->withErrors([
                'cart_ids' => 'Un ou plusieurs chariots ne sont plus disponibles. Rechargez la page puis revalidez.',
            ]);
        }

        DB::transaction(function () use ($processing, $requestedCartIds) {
            $activeAssignments = $processing->cartAssignments()->where('status', 'active')->get();

            foreach ($activeAssignments as $assignment) {
                if (! $requestedCartIds->contains((int) $assignment->cart_id)) {
                    // Chariot retire avant passage au tunnel : ce n'est pas une sortie de tunnel
                    $assignment->cart()?->update(['status' => 'empty']);
                    $assignment->delete();
                }
            }

            foreach ($requestedCartIds as $cartId) {
                $existing = $activeAssignments->firstWhere('cart_id', $cartId);
                if (! $existing) {
                    // Pas encore dans un tunnel : le tunnel est choisi a l'etape « Chariots dans les tunnels »
                    CartAssignment::create([
                        'cart_id' => $cartId,
                        'processing_order_id' => $processing->id,
                        'tunnel_id' => null,
                        'assigned_at' => now(),
                        'status' => 'active',
                    ]);
                }

                Cart::where('id', $cartId)->update(['status' => 'processing']);
            }
        });

        return back()->with('success', 'Affectation des chariots enregistree.');
    }

    /** Frais de traitement : un seul prix par kg applique a toute la quantite triee (tous types). */
    public function savePrices(Request $request, ProcessingOrder $processing)
    {
        $data = $request->validate(['price' => 'required|numeric|min:0']);

        if ($processing->invoice && in_array($processing->invoice->status, ['paid', 'cancelled'], true)) {
            return back()->withErrors(['price' => 'La facture de ce traitement est déjà ' . ($processing->invoice->status === 'paid' ? 'payée' : 'annulée') . ' : le prix ne peut plus être modifié.']);
        }

        $price = (float) $data['price'];

        DB::transaction(function () use ($processing, $price) {
            $processing->update(['price' => $price]);
            $this->applyUniformPrice($processing);
            $this->syncProcessingInvoice($processing->fresh());
        });

        return back()->with('success', 'Frais de traitement enregistrés : ' . number_format($price, 2, ',', ' ') . ' MRU/kg. Facture mise à jour.')
            ->with('pdf_url', route('processing.client-pdf', $processing))
            ->with('pdf_label', 'Imprimer la fiche client');
    }

    /** Le prix au kg du traitement s'applique a chaque ligne de tri (memes frais pour tous les types). */
    private function applyUniformPrice(ProcessingOrder $processing): void
    {
        $price = (float) $processing->price;
        foreach ($processing->items()->get() as $item) {
            $item->update(['price_per_kg' => $price, 'total' => (float) $item->output_weight * $price]);
        }
    }

    /** Sortie du tunnel de tous les chariots de ce traitement, puis passage au cartonnage. */
    public function releaseFromTunnels(ProcessingOrder $processing)
    {
        $active = $processing->cartAssignments()->where('status', 'active')->with('cart')->get();

        if ($active->isEmpty()) {
            return back()->with('info', 'Aucun chariot dans le tunnel pour ce traitement.');
        }

        $notPlaced = $active->whereNull('tunnel_id');
        if ($notPlaced->isNotEmpty()) {
            return back()->withErrors([
                'placements' => $notPlaced->count() . ' chariot(s) ne sont pas encore dans un tunnel : placez-les d\'abord.',
            ]);
        }

        DB::transaction(function () use ($active) {
            foreach ($active as $assignment) {
                $assignment->update(['status' => 'completed', 'released_at' => now()]);
                $assignment->cart?->update(['status' => 'empty']);
            }
        });

        return redirect()->route('packaging.create', ['processing_order_id' => $processing->id])
            ->with('success', $active->count() . ' chariot(s) sorti(s) du tunnel. Vous pouvez faire le cartonnage.');
    }

    public function placeInTunnels(Request $request, ProcessingOrder $processing)
    {
        $validated = $request->validate([
            'placements' => 'required|array|min:1',
            'placements.*' => 'required|integer|exists:tunnels,id',
        ]);

        $assignments = $processing->cartAssignments()
            ->where('status', 'active')
            ->get()
            ->keyBy('cart_id');

        $placements = collect($validated['placements'])
            ->mapWithKeys(fn ($tunnelId, $cartId) => [(int) $cartId => (int) $tunnelId]);

        if ($placements->keys()->diff($assignments->keys())->isNotEmpty()) {
            return back()->withErrors(['placements' => 'Un ou plusieurs chariots ne sont pas affectes a ce traitement.']);
        }

        $tunnels = Tunnel::whereIn('id', $placements->values()->unique())->where('active', true)->get()->keyBy('id');
        if ($tunnels->count() !== $placements->values()->unique()->count()) {
            return back()->withErrors(['placements' => 'Tunnel introuvable ou inactif.']);
        }

        foreach ($placements->countBy() as $tunnelId => $wanted) {
            $tunnel = $tunnels[$tunnelId];
            if ((int) $tunnel->capacity_chariots <= 0) {
                continue;
            }

            $occupiedByOthers = CartAssignment::where('status', 'active')
                ->where('tunnel_id', $tunnelId)
                ->whereNotIn('cart_id', $placements->keys())
                ->count();

            if ($occupiedByOthers + $wanted > (int) $tunnel->capacity_chariots) {
                $free = max(0, (int) $tunnel->capacity_chariots - $occupiedByOthers);
                return back()->withErrors([
                    'placements' => "Le tunnel {$tunnel->name} n'a que {$free} place(s) libre(s) pour {$wanted} chariot(s).",
                ]);
            }
        }

        DB::transaction(function () use ($placements, $assignments) {
            foreach ($placements as $cartId => $tunnelId) {
                $assignment = $assignments[$cartId];
                $assignment->update([
                    'tunnel_id' => $tunnelId,
                    // Entree reelle dans le tunnel (debut du temps de congelation)
                    'assigned_at' => $assignment->tunnel_id ? $assignment->assigned_at : now(),
                ]);
                Cart::whereKey($cartId)->update(['tunnel_id' => $tunnelId]);
            }
        });

        return back()->with('success', 'Placement des chariots dans les tunnels enregistre.');
    }

    public function cancelCarts(ProcessingOrder $processing)
    {
        $activeAssignments = $processing->cartAssignments()->where('status', 'active')->get();

        if ($activeAssignments->isEmpty()) {
            return back()->with('info', 'Aucune affectation active a annuler.');
        }

        DB::transaction(function () use ($processing, $activeAssignments) {
            foreach ($activeAssignments as $assignment) {
                // Annulation : supprimee (et non « sortie du tunnel ») pour ne pas debloquer le cartonnage
                $assignment->cart()?->update(['status' => 'empty']);
                $assignment->delete();
            }

            $processing->update(['cart_distribution' => []]);
        });

        return back()->with('success', 'Affectation annulee. Vous pouvez reaffecter les chariots.');
    }

    public function saveDistribution(Request $request, ProcessingOrder $processing)
    {
        $validated = $request->validate([
            'distribution' => 'nullable|array',
            'distribution.*' => 'array',
            'distribution.*.*' => 'integer|exists:carts,id',
        ]);

        $assignedCartIds = $processing->cartAssignments()
            ->where('status', 'active')
            ->pluck('cart_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($assignedCartIds->isEmpty()) {
            return back()->withErrors([
                'distribution' => 'Affectez d abord les chariots disponibles avant de repartir les types de poisson.',
            ]);
        }

        $validItemIds = $processing->items()->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $rawDistribution = $validated['distribution'] ?? [];

        $cleanDistribution = [];
        foreach ($rawDistribution as $itemId => $cartIds) {
            $itemIdInt = (int) $itemId;
            if (!in_array($itemIdInt, $validItemIds, true)) {
                continue;
            }

            $cleanCartIds = collect($cartIds)
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $assignedCartIds->contains($id))
                ->unique()
                ->values()
                ->all();

            if (!empty($cleanCartIds)) {
                $cleanDistribution[$itemIdInt] = $cleanCartIds;
            }
        }

        $processing->update(['cart_distribution' => $cleanDistribution]);

        return back()->with('success', 'Répartition poissons / chariots validée. Imprimez le bon de chargement pour les ouvriers.')
            ->with('pdf_url', route('processing.worker-sheet-pdf', $processing))
            ->with('pdf_label', 'Imprimer le bon de chargement');
    }

    public function suggestDistribution(ProcessingOrder $processing)
    {
        $processing->load('items');

        $assignedCartIds = $processing->cartAssignments()
            ->where('status', 'active')
            ->pluck('cart_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($assignedCartIds->isEmpty()) {
            return back()->withErrors([
                'distribution' => 'Affectez d abord les chariots disponibles avant la suggestion automatique.',
            ]);
        }

        $carts = Cart::whereIn('id', $assignedCartIds)->orderByDesc('capacity_kg')->get();
        $distribution = $this->buildSuggestedDistribution($processing->items, $carts);

        return back()
            ->with('suggested_distribution', $distribution)
            ->with('success', 'Suggestion proposee. Verifiez puis cliquez sur Valider Repartition pour enregistrer.');
    }

    public function clientPdf(ProcessingOrder $processing)
    {
        $processing->load(['client', 'arrival', 'items.fishType']);

        $lines = $processing->items->map(function ($item) use ($processing) {
            $qty = (float) $item->output_weight;
            $unitPrice = (float) ($item->price_per_kg ?: $processing->price ?: 0);

            return [
                'fish' => $item->fishType->name ?? 'Non specifie',
                'quantity_kg' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $qty * $unitPrice,
            ];
        })->values();

        if ($lines->isEmpty()) {
            $qty = (float) ($processing->output_weight ?: 0);
            if ($qty > 0) {
                $unitPrice = (float) ($processing->price ?: 0);
                $lines = collect([[
                    'fish' => 'Poisson trie (global)',
                    'quantity_kg' => $qty,
                    'unit_price' => $unitPrice,
                    'line_total' => $qty * $unitPrice,
                ]]);
            }
        }

        $totalWeight = $lines->sum('quantity_kg');
        $subtotal = $lines->sum('line_total');
        // Un seul prix par kg pour toute la quantite triee
        $unitPrice = (float) ($processing->price ?: ($totalWeight > 0 ? $subtotal / $totalWeight : 0));
        $lines = $lines->map(fn ($l) => $l + ['share' => $totalWeight > 0 ? $l['quantity_kg'] / $totalWeight * 100 : 0]);

        // Facture de traitement seule (aucun lien avec la refrigeration) : on reprend sa TVA si elle existe
        $invoice = Invoice::where('processing_order_id', $processing->id)->first();
        $tax = $invoice ? (float) $invoice->tax : 0.0;
        $taxRate = $invoice ? (float) $invoice->tax_rate : 0.0;
        $grandTotal = $subtotal + $tax;

        $received = (float) $processing->input_weight;
        $waste = (float) $processing->waste_weight;
        $returned = (float) $processing->returned_weight;
        $reception = [
            'received' => $received,
            'waste' => $waste,
            'returned' => $returned,
            'usable' => max(0, $received - $waste - $returned),
            'yield' => $received > 0 ? $totalWeight / $received * 100 : null,
            'pct' => fn ($v) => $received > 0 ? $v / $received * 100 : 0,
        ];

        $settings = [
            'company_name' => CompanySetting::get('company_name', 'FreezePro'),
            'company_address' => CompanySetting::get('company_address', ''),
            'company_phone' => CompanySetting::get('company_phone', ''),
            'company_email' => CompanySetting::get('company_email', ''),
            'company_nif' => CompanySetting::get('company_nif', ''),
            'company_ice' => CompanySetting::get('company_ice', ''),
            'currency' => CompanySetting::get('currency', 'MRU'),
            'company_logo' => CompanySetting::get('company_logo', null),
        ];

        $logoPath = null;
        $logoValue = (string) ($settings['company_logo'] ?? '');
        if ($logoValue !== '') {
            $normalized = ltrim(str_replace('\\', '/', $logoValue), '/');
            if (! str_starts_with($logoValue, 'http')) {
                $candidate = null;
                if (str_starts_with($normalized, 'storage/')) {
                    $candidate = public_path($normalized);
                } elseif (str_starts_with($normalized, 'public/')) {
                    $candidate = storage_path('app/' . $normalized);
                } else {
                    $candidate = public_path($normalized);
                }

                if ($candidate && file_exists($candidate)) {
                    $logoPath = $candidate;
                }
            }
        }

        $pdf = Pdf::loadView('processing.client_pdf', compact('processing', 'lines', 'totalWeight', 'subtotal', 'settings', 'logoPath', 'invoice', 'tax', 'taxRate', 'grandTotal', 'reception', 'unitPrice'))
            ->setPaper('a4');

        return $pdf->stream('Facture-Traitement-' . $processing->reference . '.pdf');
    }

    public function workerSheetPdf(ProcessingOrder $processing)
    {
        $processing->load(['client', 'arrival', 'items.fishType', 'cartAssignments.cart.tunnel']);

        $assignedCarts = $processing->cartAssignments
            ->where('status', 'active')
            ->mapWithKeys(fn ($assignment) => [(int) $assignment->cart_id => $assignment->cart])
            ->all();

        $distribution = is_array($processing->cart_distribution) ? $processing->cart_distribution : [];
        $activeSlotMap = $this->getActiveTunnelSlotMap();
        $cartRemaining = collect($assignedCarts)->mapWithKeys(function ($cart, $cartId) {
            return [(int) $cartId => max(0, (float) ($cart->capacity_kg ?? 0))];
        })->all();

        $rows = collect();
        foreach ($processing->items as $item) {
            $fishName = $item->fishType->name ?? 'Non specifie';
            $cartIds = collect($distribution[$item->id] ?? [])->map(fn ($id) => (int) $id)->filter()->values();

            if ($cartIds->isEmpty() && !empty($assignedCarts)) {
                $cartIds = collect([array_key_first($assignedCarts)]);
            }

            $remainingItemWeight = (float) $item->output_weight;
            $itemLastRowIndex = null;

            foreach ($cartIds as $cartId) {
                $cart = $assignedCarts[$cartId] ?? null;
                if (! $cart) {
                    continue;
                }

                if ($remainingItemWeight <= 0) {
                    break;
                }

                $availableForCart = $cartRemaining[$cartId] ?? max(0, (float) ($cart->capacity_kg ?? 0));
                if ($availableForCart <= 0) {
                    continue;
                }

                $allocatedWeight = min($remainingItemWeight, $availableForCart);
                $remainingItemWeight -= $allocatedWeight;
                $cartRemaining[$cartId] = max(0, $availableForCart - $allocatedWeight);

                $tunnelName = $cart->tunnel->name ?? '-';
                $slot = $activeSlotMap[$cartId]['slot'] ?? null;

                $rows->push([
                    'tunnel_id' => (int) ($cart->tunnel_id ?? 0),
                    'tunnel_name' => $tunnelName,
                    'slot' => $slot,
                    'cart_code' => $cart->code ?? 'CH-' . $cartId,
                    'cart_capacity' => (float) ($cart->capacity_kg ?? 0),
                    'fish' => $fishName,
                    'quantity_kg' => $allocatedWeight,
                    'overflow' => 0.0,
                ]);
                $itemLastRowIndex = $rows->count() - 1;
            }

            // Poids qui depasse la capacite des chariots choisis : ajoute a la derniere ligne
            // de CE poisson (ou a son premier chariot s'ils sont deja pleins) et signale.
            if ($remainingItemWeight > 0.0001) {
                if ($itemLastRowIndex !== null) {
                    $row = $rows->get($itemLastRowIndex);
                    $row['quantity_kg'] += $remainingItemWeight;
                    $row['overflow'] += $remainingItemWeight;
                    $rows->put($itemLastRowIndex, $row);
                } elseif ($cart = $assignedCarts[$cartIds->first()] ?? null) {
                    $rows->push([
                        'tunnel_id' => (int) ($cart->tunnel_id ?? 0),
                        'tunnel_name' => $cart->tunnel->name ?? '-',
                        'slot' => $activeSlotMap[$cart->id]['slot'] ?? null,
                        'cart_code' => $cart->code ?? 'CH-' . $cart->id,
                        'cart_capacity' => (float) ($cart->capacity_kg ?? 0),
                        'fish' => $fishName,
                        'quantity_kg' => $remainingItemWeight,
                        'overflow' => $remainingItemWeight,
                    ]);
                }
            }
        }

        if ($rows->isEmpty()) {
            $rows = collect([[
                'tunnel_id' => 0,
                'tunnel_name' => 'Affecter',
                'slot' => null,
                'cart_code' => 'A affecter',
                'fish' => 'Poisson trie (global)',
                'quantity_kg' => (float) ($processing->output_weight ?: 0),
            ]]);
        }

        $rows = $rows->sortBy([
            fn ($row) => $row['tunnel_name'] ?? '',
            fn ($row) => $row['slot'] ?? PHP_INT_MAX,
            fn ($row) => $row['cart_code'] ?? '',
            fn ($row) => $row['fish'] ?? '',
        ])->values();

        $totalWeight = $rows->sum('quantity_kg');

        // Fiche ouvrier : un bloc par chariot (ordre tunnel / emplacement), et un recap par poisson
        $carts = $rows->groupBy('cart_code')->map(fn ($lines) => [
            'code' => $lines->first()['cart_code'],
            'tunnel' => $lines->first()['tunnel_name'],
            'slot' => $lines->first()['slot'],
            'capacity' => (float) ($lines->first()['cart_capacity'] ?? 0),
            'lines' => $lines->values(),
            'total' => (float) $lines->sum('quantity_kg'),
        ])
            ->sortBy('code', SORT_NATURAL)
            ->values();
        $fishSummary = $rows->groupBy('fish')->map(fn ($lines, $fish) => [
            'fish' => $fish,
            'kg' => (float) $lines->sum('quantity_kg'),
            'carts' => $lines->pluck('cart_code')->unique()->implode(', '),
        ])->values();
        $distributionValidated = ! empty($processing->cart_distribution);

        $settings = [
            'company_name' => CompanySetting::get('company_name', 'FreezePro Maroc'),
            'company_address' => CompanySetting::get('company_address', ''),
            'company_phone' => CompanySetting::get('company_phone', ''),
            'company_email' => CompanySetting::get('company_email', ''),
            'company_nif' => CompanySetting::get('company_nif', ''),
            'company_ice' => CompanySetting::get('company_ice', ''),
            'company_logo' => CompanySetting::get('company_logo', '/images/company-default-logo.svg'),
        ];

        $logoPath = null;
        $logoValue = (string) ($settings['company_logo'] ?? '');
        if ($logoValue !== '') {
            $normalized = ltrim(str_replace('\\', '/', $logoValue), '/');
            if (!str_starts_with($logoValue, 'http')) {
                $candidate = str_starts_with($normalized, 'storage/') ? public_path($normalized) : public_path($normalized);
                if ($candidate && file_exists($candidate)) {
                    $logoPath = $candidate;
                }
            }
        }

        $pdf = Pdf::loadView('processing.worker_sheet_pdf', compact('processing', 'rows', 'totalWeight', 'settings', 'logoPath', 'carts', 'fishSummary', 'distributionValidated'))->setPaper('a4');

        return $pdf->stream('Fiche-Chargement-' . $processing->reference . '.pdf');
    }

    private function buildSuggestedDistribution(Collection $items, Collection $carts): array
    {
        if ($items->isEmpty() || $carts->isEmpty()) {
            return [];
        }

        $sortedItems = $items->sortByDesc(fn ($item) => (float) $item->output_weight)->values();
        $sortedCarts = $carts->sortByDesc(fn ($cart) => (float) $cart->capacity_kg)->values();

        $remainingByCart = [];
        foreach ($sortedCarts as $cart) {
            $remainingByCart[(int) $cart->id] = max(0, (float) $cart->capacity_kg);
        }

        $distribution = [];
        foreach ($sortedItems as $item) {
            $remainingItemWeight = max(0, (float) $item->output_weight);
            $selectedCartIds = [];

            foreach ($sortedCarts as $cart) {
                if ($remainingItemWeight <= 0) {
                    break;
                }

                $cartId = (int) $cart->id;
                $cartRemaining = $remainingByCart[$cartId] ?? 0;
                if ($cartRemaining <= 0) {
                    continue;
                }

                $selectedCartIds[] = $cartId;
                $allocated = min($remainingItemWeight, $cartRemaining);
                $remainingItemWeight -= $allocated;
                $remainingByCart[$cartId] = max(0, $cartRemaining - $allocated);
            }

            if (empty($selectedCartIds) && $sortedCarts->isNotEmpty()) {
                $selectedCartIds[] = (int) $sortedCarts->first()->id;
            }

            $distribution[(int) $item->id] = array_values(array_unique($selectedCartIds));
        }

        return $distribution;
    }

    private function syncProcessingInvoice(ProcessingOrder $processing): void
    {
        if ($processing->status === 'cancelled') {
            return;
        }

        $processing->loadMissing(['items.fishType', 'client', 'packagingOrders.items']);

        $lines = $processing->items
            ->map(function ($item) use ($processing) {
                $quantity = (float) $item->output_weight;
                $unitPrice = (float) ($item->price_per_kg ?: $processing->price ?: 0);

                return [
                    'service_type' => 'processing',
                    'reference_type' => 'processing_item',
                    'reference_id' => $item->id,
                    'description' => trim(($item->fishType->name ?? 'Poisson') . ' - ' . $processing->reference),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $quantity * $unitPrice,
                ];
            })
            ->filter(fn ($line) => $line['quantity'] > 0)
            ->values();

        if ($lines->isEmpty() && (float) $processing->output_weight > 0) {
            $unitPrice = (float) ($processing->price ?: 0);
            $quantity = (float) $processing->output_weight;
            $lines = collect([[
                'service_type' => 'processing',
                'reference_type' => 'processing_order',
                'reference_id' => $processing->id,
                'description' => 'Traitement - ' . $processing->reference,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => $quantity * $unitPrice,
            ]]);
        }

        if ($lines->isEmpty()) {
            $unitPrice = (float) ($processing->price ?: 0);

            if ($unitPrice > 0) {
                $quantity = 0.0;
                $description = 'Traitement - ' . $processing->reference;

                switch ($processing->billing_method) {
                    case 'kg':
                        $quantity = (float) ($processing->output_weight ?: $processing->input_weight ?: 0);
                        $description = 'Traitement au kg - ' . $processing->reference;
                        break;

                    case 'carton':
                        $quantity = (float) $processing->packagingOrders
                            ->flatMap(fn ($order) => $order->items)
                            ->sum('cartons_count');
                        $description = 'Traitement au carton - ' . $processing->reference;
                        break;

                    case 'day':
                        $start = $processing->start_date ? Carbon::parse($processing->start_date) : now();
                        $end = $processing->end_date ? Carbon::parse($processing->end_date) : now();
                        $quantity = (float) max(1, $start->startOfDay()->diffInDays($end->endOfDay()) + 1);
                        $description = 'Traitement journalier - ' . $processing->reference;
                        break;

                    case 'fixed':
                        $quantity = 1.0;
                        $description = 'Traitement forfaitaire - ' . $processing->reference;
                        break;
                }

                if ($quantity > 0) {
                    $lines = collect([[
                        'service_type' => 'processing',
                        'reference_type' => 'processing_order',
                        'reference_id' => $processing->id,
                        'description' => $description,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total' => $quantity * $unitPrice,
                    ]]);
                }
            }
        }

        if ($lines->isEmpty()) {
            return;
        }

        $taxRate = CompanySetting::taxRate();
        $subtotal = (float) $lines->sum('total');
        $tax = $subtotal * ($taxRate / 100);
        $total = $subtotal + $tax;

        $invoiceDate = $processing->end_date?->toDateString()
            ?? $processing->start_date?->toDateString()
            ?? now()->toDateString();

        $dueDate = Carbon::parse($invoiceDate)->addDays(7)->toDateString();

        $invoiceData = [
            'client_id' => $processing->client_id,
            'processing_order_id' => $processing->id,
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'tax_rate' => $taxRate,
            'notes' => 'Facture auto générée depuis le traitement ' . $processing->reference,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'status' => 'draft',
        ];

        $existingInvoice = Invoice::with('items')->where('processing_order_id', $processing->id)->first();

        if ($existingInvoice) {
            if (in_array($existingInvoice->status, ['paid', 'cancelled'], true)) {
                return;
            }

            $existingInvoice->update($invoiceData);
            $existingInvoice->items()->delete();
            $invoice = $existingInvoice;
        } else {
            $prefix = CompanySetting::get('invoice_prefix', 'FAC');
            $num = str_pad(Invoice::count() + 1, 5, '0', STR_PAD_LEFT);
            $invoice = Invoice::create(array_merge($invoiceData, [
                'invoice_number' => $prefix . '-' . now()->format('Y') . '-' . $num,
            ]));
        }

        foreach ($lines as $line) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'service_type' => $line['service_type'],
                'reference_type' => $line['reference_type'],
                'reference_id' => $line['reference_id'],
                'description' => $line['description'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'total' => $line['total'],
            ]);
        }

        // Garder le statut partielle/payee si des encaissements existent deja
        if ($invoice->payments()->exists()) {
            $invoice->refreshPaymentStatus();
        }
    }

    private function getActiveTunnelSlotMap(): array
    {
        $assignments = CartAssignment::with(['cart', 'tunnel'])
            ->where('status', 'active')
            ->whereNotNull('tunnel_id')
            ->get()
            ->groupBy('tunnel_id');

        $slotMap = [];
        foreach ($assignments as $tunnelId => $rows) {
            $sorted = $rows->sortBy([
                fn ($a) => $a->assigned_at ? $a->assigned_at->timestamp : PHP_INT_MAX,
                fn ($a) => $a->cart->code ?? '',
                fn ($a) => $a->id,
            ])->values();

            foreach ($sorted as $idx => $assignment) {
                $cartId = (int) $assignment->cart_id;
                $slotMap[$cartId] = [
                    'slot' => $idx + 1,
                    'tunnel_id' => (int) $assignment->tunnel_id,
                    'tunnel_name' => $assignment->tunnel->name ?? '-',
                ];
            }
        }

        return $slotMap;
    }

    private function buildSlotSuggestionByCart(Collection $availableCarts, array $activeSlotMap, array $activeCountByTunnel): array
    {
        $suggestions = [];
        $extraByTunnel = [];

        foreach ($availableCarts->groupBy('tunnel_id') as $tunnelId => $carts) {
            $base = (int) ($activeCountByTunnel[$tunnelId] ?? 0);
            $extraByTunnel[$tunnelId] = 0;

            foreach ($carts->sortBy('code') as $cart) {
                $cartId = (int) $cart->id;

                if (isset($activeSlotMap[$cartId])) {
                    $suggestions[$cartId] = (int) $activeSlotMap[$cartId]['slot'];
                    continue;
                }

                $extraByTunnel[$tunnelId]++;
                $suggestions[$cartId] = $base + $extraByTunnel[$tunnelId];
            }
        }

        return $suggestions;
    }
}

