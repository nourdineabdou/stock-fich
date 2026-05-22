<?php

namespace App\Http\Controllers;

use App\Models\Tunnel;
use App\Models\ColdRoom;
use App\Models\CartAssignment;
use App\Http\Requests\TunnelRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TunnelController extends Controller
{
    public function index(Request $request)
    {
        $tunnels = Tunnel::with(['coldRoom'])
            ->withCount('carts')
            ->when($request->cold_room_id, fn($q) => $q->where('cold_room_id', $request->cold_room_id))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
        $coldRooms = ColdRoom::where('active', true)->orderBy('name')->get();
        return view('tunnels.index', compact('tunnels', 'coldRooms'));
    }

    public function create()
    {
        $coldRooms = ColdRoom::where('active', true)->orderBy('name')->get();
        return view('tunnels.create', compact('coldRooms'));
    }

    public function store(TunnelRequest $request)
    {
        Tunnel::create($request->validated());
        return redirect()->route('tunnels.index')->with('success', 'Tunnel créé.');
    }

    public function show(Tunnel $tunnel)
    {
        $tunnel->load([
            'coldRoom',
            'carts.levels',
            'freezingCycles' => fn($q) => $q->latest()->limit(10),
            'cartAssignments' => fn($q) => $q->latest()->limit(10),
        ]);

        $activeAssignmentsByCart = CartAssignment::with(['processingOrder.client'])
            ->where('tunnel_id', $tunnel->id)
            ->where('status', 'active')
            ->orderBy('assigned_at')
            ->get()
            ->keyBy('cart_id');

        return view('tunnels.show', compact('tunnel', 'activeAssignmentsByCart'));
    }

    public function releaseAssignment(Tunnel $tunnel, CartAssignment $assignment)
    {
        if ((int) $assignment->tunnel_id !== (int) $tunnel->id || $assignment->status !== 'active') {
            return back()->withErrors([
                'assignment' => 'Affectation introuvable ou deja dissociee.',
            ]);
        }

        DB::transaction(function () use ($assignment) {
            $assignment->update([
                'status' => 'completed',
                'released_at' => now(),
            ]);

            $assignment->cart()?->update(['status' => 'empty']);
        });

        $redirect = $assignment->processing_order_id
            ? route('packaging.create', ['processing_order_id' => $assignment->processing_order_id])
            : route('tunnels.show', $tunnel);

        return redirect($redirect)->with('success', 'Chariot dissocie du tunnel. Vous pouvez passer au cartonnage.');
    }

    public function releaseMultipleAssignments(Request $request, Tunnel $tunnel)
    {
        $assignmentIds = $request->validate([
            'assignment_ids' => 'required|array',
            'assignment_ids.*' => 'integer|exists:cart_assignments,id',
        ])['assignment_ids'];

        if (empty($assignmentIds)) {
            return back()->withErrors(['assignments' => 'Aucun chariot sélectionné.']);
        }

        $assignments = CartAssignment::where('tunnel_id', $tunnel->id)
            ->whereIn('id', $assignmentIds)
            ->where('status', 'active')
            ->get();

        if ($assignments->isEmpty()) {
            return back()->withErrors(['assignments' => 'Aucune affectation active trouvée pour la sélection.']);
        }

        // Récupérer les processing_order_ids uniques avant la dissociation
        $processingOrderIds = $assignments
            ->pluck('processing_order_id')
            ->unique()
            ->filter()
            ->values()
            ->toArray();

        DB::transaction(function () use ($assignments) {
            foreach ($assignments as $assignment) {
                $assignment->update([
                    'status' => 'completed',
                    'released_at' => now(),
                ]);

                $assignment->cart()?->update(['status' => 'empty']);
            }
        });

        // Rediriger vers le cartonnage avec les processing orders
        if (count($processingOrderIds) === 1) {
            // Si un seul processing order, rediriger directement
            return redirect(route('packaging.create', ['processing_order_id' => $processingOrderIds[0]]))
                ->with('success', count($assignments) . ' chariot(s) dissocie(s). Veuillez passer au cartonnage.');
        } else {
            // Si plusieurs processing orders, afficher la page de cartonnage avec banneau
            return redirect(route('packaging.create'))
                ->with('success', count($assignments) . ' chariot(s) dissocie(s). Veuillez continuer vers le cartonnage.')
                ->with('pending_processing_order_ids', $processingOrderIds);
        }
    }

    public function edit(Tunnel $tunnel)
    {
        $coldRooms = ColdRoom::where('active', true)->orderBy('name')->get();
        return view('tunnels.edit', compact('tunnel', 'coldRooms'));
    }

    public function update(TunnelRequest $request, Tunnel $tunnel)
    {
        $tunnel->update($request->validated());
        return redirect()->route('tunnels.show', $tunnel)->with('success', 'Tunnel mis à jour.');
    }

    public function destroy(Tunnel $tunnel)
    {
        $tunnel->delete();
        return redirect()->route('tunnels.index')->with('success', 'Tunnel supprimé.');
    }
}
