<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartLevel;
use App\Models\Tunnel;
use App\Http\Requests\CartRequest;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $carts = Cart::with(['tunnel.coldRoom'])
            ->when($request->tunnel_id, fn($q) => $q->where('tunnel_id', $request->tunnel_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('code', 'like', "%{$request->search}%"))
            ->orderBy('code')
            ->paginate(10)
            ->withQueryString();
        $tunnels = Tunnel::where('active', true)->with('coldRoom')->orderBy('name')->get();
        return view('carts.index', compact('carts', 'tunnels'));
    }

    public function create()
    {
        $tunnels = Tunnel::where('active', true)->with('coldRoom')->orderBy('name')->get();
        return view('carts.create', compact('tunnels'));
    }

    public function store(CartRequest $request)
    {
        $cart = Cart::create($request->validated());
        // Create default levels
        $levels = $request->levels ?? [];
        if (empty($levels)) {
            CartLevel::create(['cart_id' => $cart->id, 'level_number' => 1, 'capacity_kg' => $cart->capacity_kg]);
        } else {
            foreach ($levels as $i => $cap) {
                CartLevel::create(['cart_id' => $cart->id, 'level_number' => $i + 1, 'capacity_kg' => $cap ?: $cart->capacity_kg]);
            }
        }
        return redirect()->route('carts.index')->with('success', 'Chariot créé: ' . $cart->code);
    }

    public function show(Cart $cart)
    {
        $cart->load(['tunnel.coldRoom', 'levels', 'assignments.processingOrder.client']);
        return view('carts.show', compact('cart'));
    }

    public function edit(Cart $cart)
    {
        $cart->load('levels');
        $tunnels = Tunnel::where('active', true)->with('coldRoom')->orderBy('name')->get();
        return view('carts.edit', compact('cart', 'tunnels'));
    }

    public function update(CartRequest $request, Cart $cart)
    {
        $cart->update($request->validated());
        return redirect()->route('carts.show', $cart)->with('success', 'Chariot mis à jour.');
    }

    public function destroy(Cart $cart)
    {
        $cart->delete();
        return redirect()->route('carts.index')->with('success', 'Chariot supprimé.');
    }
}
