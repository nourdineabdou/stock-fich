<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartAssignment;
use App\Models\CartonType;
use App\Models\Client;
use App\Models\ClientStockBatch;
use App\Models\ColdRoom;
use App\Models\FishType;
use App\Models\PackagingOrder;
use App\Models\PricingRule;
use App\Models\ProcessingItem;
use App\Models\ProcessingOrder;
use App\Models\StorageEntry;
use App\Models\Tunnel;
use App\Models\User;
use App\Services\StorageBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AffectationAndColdFeesTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;
    private FishType $fish;
    private CartonType $carton;
    private ColdRoom $room;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-21 10:00:00');

        $this->actingAs(User::factory()->create());
        $this->client = Client::create(['name' => 'Client Test', 'active' => true]);
        $this->fish = FishType::create(['name' => 'Poulpe', 'active' => true]);
        $this->carton = CartonType::create(['name' => 'Carton 16', 'weight_kg' => 16, 'active' => true]);
        $this->room = ColdRoom::create(['name' => 'CF1', 'type' => 'storage', 'active' => true]);
    }

    private function seedStock(): void
    {
        $batch = ClientStockBatch::create([
            'client_id' => $this->client->id,
            'fish_type_id' => $this->fish->id,
            'carton_type_id' => $this->carton->id,
            'cold_room_id' => $this->room->id,
            'lot_number' => 'LOT-1',
            'cartons_count' => 28,
            'weight_per_carton' => 16,
            'total_weight' => 448,
            'available_cartons' => 20,
            'available_weight' => 320,
            'entry_date' => '2026-10-06 09:00:00',
            'status' => 'partial',
        ]);

        // Mardi semaine du 05/10 : +28 cartons ; mercredi semaine du 12/10 : -8 cartons
        StorageEntry::create(['client_stock_batch_id' => $batch->id, 'cold_room_id' => $this->room->id, 'type' => 'in', 'cartons_count' => 28, 'weight_kg' => 448, 'entry_date' => '2026-10-06 09:00:00']);
        StorageEntry::create(['client_stock_batch_id' => $batch->id, 'cold_room_id' => $this->room->id, 'type' => 'out', 'cartons_count' => 8, 'weight_kg' => 128, 'reference' => 'LIV-1', 'entry_date' => '2026-10-14 15:00:00']);
    }

    private function rule(): PricingRule
    {
        return PricingRule::create([
            'client_id' => $this->client->id,
            'service_type' => 'storage',
            'billing_method' => 'carton',
            'charge_unit' => 'carton',
            'billing_frequency' => 'week',
            'week_start_day' => 1,
            'price' => 50,
            'active' => true,
        ]);
    }

    public function test_validating_a_delivery_takes_cartons_out_of_stock(): void
    {
        $this->seedStock(); // 20 cartons disponibles
        $batch = ClientStockBatch::firstOrFail();
        $delivery = \App\Models\Delivery::create([
            'client_id' => $this->client->id, 'reference' => 'BL-TEST', 'delivery_date' => now(), 'status' => 'pending',
            'receiver_name' => 'Ahmed', 'receiver_id_number' => 'CIN123', 'receiver_phone' => '22000000',
        ]);
        $delivery->items()->create([
            'client_stock_batch_id' => $batch->id, 'fish_type_id' => $this->fish->id, 'carton_type_id' => $this->carton->id,
            'cartons_count' => 5, 'weight_kg' => 80,
        ]);

        $this->post(route('deliveries.validate', $delivery))->assertSessionHas('success');

        $this->assertSame('delivered', $delivery->fresh()->status);
        $this->assertSame(15, (int) $batch->fresh()->available_cartons);
        $this->assertSame(1, StorageEntry::where('reference', 'BL-TEST')->where('type', 'out')->count());
        $this->assertSame(1, \App\Models\StockMovement::where('movement_type', 'delivery')->where('reference_id', $delivery->id)->count());
    }

    public function test_weekly_cold_fees_accumulate_per_week(): void
    {
        $this->seedStock();
        $service = app(StorageBillingService::class);

        $fees = $service->weeklyBreakdown($this->client, $this->rule());

        // Plus recente en premier : 20 (sem. 19/10), 28 (sem. 12/10, sortie deduite la semaine suivante), 28 (sem. 05/10)
        $this->assertSame([20.0, 28.0, 28.0], array_map(fn ($r) => $r['quantity'], $fees['rows']));
        $this->assertSame(3800.0, $fees['total']);
        $this->assertSame(28.0, $service->quantityForPeriod($this->client, Carbon::parse('2026-10-12'), Carbon::parse('2026-10-18'), 'carton'));
        $this->assertSame(448.0, $service->quantityForPeriod($this->client, Carbon::parse('2026-10-12'), Carbon::parse('2026-10-18'), 'kg'));
    }

    public function test_billing_a_week_marks_it_invoiced(): void
    {
        $this->seedStock();
        $rule = $this->rule();

        $this->post(route('storage-billing.generate-week', $this->client), ['week_start' => '2026-10-12'])
            ->assertSessionHas('success');

        $fees = app(StorageBillingService::class)->weeklyBreakdown($this->client, $rule);
        $this->assertSame(1400.0, $fees['invoiced']);
        $this->assertSame(2400.0, $fees['unbilled']);
        $this->assertNotNull($fees['rows'][1]['invoice']);
    }

    public function test_client_page_shows_stock_history_and_filters(): void
    {
        $this->seedStock();
        $this->rule();

        $this->get(route('clients.show', $this->client))
            ->assertOk()
            ->assertSee('Stock actuel par poisson et carton')
            ->assertSee('Poulpe')
            ->assertSee('Frais de froid par semaine')
            ->assertSee('LIV-1');

        $this->get(route('clients.show', ['client' => $this->client, 'date_mode' => 'exact', 'date' => '2026-10-06']))
            ->assertOk()
            ->assertDontSee('LIV-1');

        $this->get(route('clients.show', ['client' => $this->client, 'date_from' => '2026-10-10', 'date_to' => '2026-10-20', 'fish_type_id' => $this->fish->id, 'carton_type_id' => $this->carton->id, 'movement' => 'out']))
            ->assertOk()
            ->assertSee('LIV-1');
    }

    private function orderWithCarts(): array
    {
        $t1 = Tunnel::create(['cold_room_id' => $this->room->id, 'name' => 'Tunnel A', 'capacity_chariots' => 1, 'active' => true]);
        $t2 = Tunnel::create(['cold_room_id' => $this->room->id, 'name' => 'Tunnel B', 'capacity_chariots' => 2, 'active' => true]);
        $order = ProcessingOrder::create(['client_id' => $this->client->id, 'reference' => 'TRT-1', 'billing_method' => 'kg', 'status' => 'processing', 'output_weight' => 500]);
        ProcessingItem::create(['processing_order_id' => $order->id, 'fish_type_id' => $this->fish->id, 'output_weight' => 500]);

        $carts = collect(['CH-1', 'CH-2'])->map(function ($code) use ($t1, $order) {
            $cart = Cart::create(['tunnel_id' => $t1->id, 'code' => $code, 'capacity_kg' => 300, 'status' => 'processing']);
            CartAssignment::create(['cart_id' => $cart->id, 'processing_order_id' => $order->id, 'tunnel_id' => $t1->id, 'assigned_at' => now(), 'status' => 'active']);
            return $cart;
        });

        return [$order, $carts, $t1, $t2];
    }

    public function test_processing_page_renders_three_steps(): void
    {
        [$order] = $this->orderWithCarts();

        $this->get(route('processing.show', $order))
            ->assertOk()
            ->assertSee('Choix des chariots')
            ->assertSee('Poissons dans les chariots')
            ->assertSee('Chariots dans les tunnels');
    }

    public function test_place_carts_in_tunnels_respects_capacity(): void
    {
        [$order, $carts, $t1, $t2] = $this->orderWithCarts();

        $this->post(route('processing.place-tunnels', $order), ['placements' => [$carts[0]->id => $t1->id, $carts[1]->id => $t1->id]])
            ->assertSessionHasErrors('placements');

        $this->post(route('processing.place-tunnels', $order), ['placements' => [$carts[0]->id => $t1->id, $carts[1]->id => $t2->id]])
            ->assertSessionHas('success');

        $this->assertSame($t2->id, (int) CartAssignment::where('cart_id', $carts[1]->id)->value('tunnel_id'));
        $this->assertSame($t2->id, (int) $carts[1]->fresh()->tunnel_id);
    }

    public function test_processing_sheet_form_and_a4_pdf(): void
    {
        $this->get(route('processing.create'))->assertOk()->assertSee('Réception et filtrage');

        $this->post(route('processing.store'), [
            'client_id' => $this->client->id,
            'start_date' => '2026-10-21',
            'billing_method' => 'kg',
            'status' => 'processing',
            'price' => 5,
            'input_weight' => 4000,
            'waste_weight' => 10,
            'output_weight' => 3990,
            'items' => [
                ['fish_type_id' => $this->fish->id, 'output_weight' => 3000, 'price_per_kg' => 5],
                ['fish_type_id' => FishType::create(['name' => 'Seiche', 'active' => true])->id, 'output_weight' => 990, 'price_per_kg' => 4],
            ],
        ])->assertRedirect()->assertSessionHas('pdf_url');

        $order = ProcessingOrder::firstOrFail();
        $this->assertSame(2, $order->items()->count());
        $this->assertSame(19950.0, (float) $order->invoice->subtotal); // 3990 kg x 5 (un seul prix pour tous les types)
        $this->assertSame(19950.0, (float) $order->invoice->total);    // TVA desactivee par defaut
        $this->assertSame(0.0, \App\Models\CompanySetting::taxRate());

        \App\Models\CompanySetting::set('tax_enabled', '1');
        $this->assertSame(20.0, \App\Models\CompanySetting::taxRate());
        \App\Models\CompanySetting::set('tax_enabled', '0');

        $response = $this->get(route('processing.client-pdf', $order))->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_worker_loading_sheet_lists_fish_per_cart(): void
    {
        [$order, $carts] = $this->orderWithCarts();
        $item = $order->items()->first();

        $this->post(route('processing.distribution', $order), ['distribution' => [$item->id => [$carts[0]->id, $carts[1]->id]]])
            ->assertSessionHas('pdf_url', route('processing.worker-sheet-pdf', $order));

        $pdf = $this->get(route('processing.worker-sheet-pdf', $order))->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_chosen_carts_are_not_in_a_tunnel_until_placed(): void
    {
        $tunnel = Tunnel::create(['cold_room_id' => $this->room->id, 'name' => 'Tunnel Z', 'capacity_chariots' => 5, 'active' => true]);
        $cart = Cart::create(['tunnel_id' => $tunnel->id, 'code' => 'CHZ-1', 'capacity_kg' => 600, 'status' => 'empty']);
        $order = ProcessingOrder::create(['client_id' => $this->client->id, 'reference' => 'TRT-Z', 'billing_method' => 'kg', 'status' => 'processing', 'output_weight' => 500]);
        ProcessingItem::create(['processing_order_id' => $order->id, 'fish_type_id' => $this->fish->id, 'output_weight' => 500]);

        $this->post(route('processing.assign-carts', $order), ['cart_ids' => [$cart->id]]);

        $assignment = CartAssignment::where('cart_id', $cart->id)->firstOrFail();
        $this->assertNull($assignment->tunnel_id);
        $this->assertStringContainsString('pas encore mis dans un tunnel', $order->fresh()->packagingBlockReason());
        $this->get(route('tunnels.show', $tunnel))->assertSee('pas encore dans un tunnel');

        $this->post(route('processing.place-tunnels', $order), ['placements' => [$cart->id => $tunnel->id]]);
        $this->assertSame($tunnel->id, (int) $assignment->fresh()->tunnel_id);
        $this->assertStringContainsString('encore dans le tunnel', $order->fresh()->packagingBlockReason());
    }

    public function test_tunnel_shows_time_of_carts_against_one_to_three_days(): void
    {
        [$order, $carts, $t1, $t2] = $this->orderWithCarts();
        CartAssignment::where('cart_id', $carts[0]->id)->update(['assigned_at' => now()->subDays(2)]); // pret
        CartAssignment::where('cart_id', $carts[1]->id)->update(['assigned_at' => now()->subDays(4)]); // depasse

        $this->get(route('tunnels.show', $t1))->assertOk()
            ->assertSee('1 prêt(s) à sortir')
            ->assertSee('1 dépassé(s) — à sortir')
            ->assertSee('tunnelTimer(', false);
        $this->get(route('tunnels.index'))->assertOk()->assertSee('1 prêt(s)')->assertSee('1 dépassé(s)');
        $this->get(route('processing.show', $order))->assertOk()->assertSee('Temps dans le tunnel');

        // Fenetre configurable : maximum porte a 5 jours => plus aucun depasse
        \App\Models\CompanySetting::set('tunnel_max_hours', '120');
        $this->get(route('tunnels.show', $t1))->assertSee('2 prêt(s) à sortir')->assertDontSee('dépassé(s) — à sortir');
    }

    public function test_release_button_takes_carts_out_and_opens_packaging(): void
    {
        [$order, $carts] = $this->orderWithCarts();
        CartAssignment::query()->update(['assigned_at' => now()->subDays(2)]);

        $this->get(route('processing.show', $order))->assertOk()
            ->assertSee('Sortir les 2 chariot(s) du tunnel et passer au cartonnage');

        $this->post(route('processing.release-tunnels', $order))
            ->assertRedirect(route('packaging.create', ['processing_order_id' => $order->id]));

        $this->assertSame(0, CartAssignment::where('status', 'active')->count());
        $this->assertSame('empty', $carts[0]->fresh()->status);
        $this->assertNull($order->fresh()->packagingBlockReason());
        $this->get(route('packaging.create', ['processing_order_id' => $order->id]))->assertOk();
    }

    public function test_worker_sheet_handles_weight_above_cart_capacity(): void
    {
        [$order, $carts] = $this->orderWithCarts();
        $item = $order->items()->first();

        // 500 kg dans un seul chariot de 300 kg : 200 kg en depassement
        $order->update(['cart_distribution' => [$item->id => [$carts[0]->id]]]);

        $pdf = $this->get(route('processing.worker-sheet-pdf', $order))->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_processing_prices_are_editable_from_the_sheet(): void
    {
        [$order] = $this->orderWithCarts();
        $item = $order->items()->first();

        $this->get(route('processing.show', $order))->assertOk()->assertSee('Frais de traitement par kg');
        $this->post(route('processing.prices', $order), [])->assertSessionHasErrors('price');

        $this->post(route('processing.prices', $order), ['price' => 6])
            ->assertSessionHas('success');

        $this->assertSame(6.0, (float) $item->fresh()->price_per_kg);
        $this->assertSame(3000.0, (float) $order->fresh()->invoice->subtotal); // 500 kg x 6

        $order->invoice->update(['status' => 'paid']);
        $this->post(route('processing.prices', $order), ['price' => 9])->assertSessionHasErrors('price');
    }

    public function test_packaging_create_and_edit_render(): void
    {
        [$order, $carts, $t1] = $this->orderWithCarts();
        $items = [['fish_type_id' => $this->fish->id, 'carton_type_id' => $this->carton->id, 'cartons_count' => 32, 'weight_per_carton' => 16]];

        // Chariots encore dans le tunnel : cartonnage verrouille (bouton, page et enregistrement)
        $this->get(route('processing.show', $order))->assertSee('Cartonnage verrouillé');
        $this->get(route('packaging.create', ['processing_order_id' => $order->id]))->assertRedirect(route('processing.show', $order));
        $this->post(route('packaging.store'), ['processing_order_id' => $order->id, 'date' => '2026-10-21', 'items' => $items])
            ->assertSessionHasErrors('processing_order_id');

        // Sortie d'un seul chariot : toujours verrouille, on reste sur le tunnel
        $first = CartAssignment::where('cart_id', $carts[0]->id)->first();
        $this->post(route('tunnels.release-assignment', [$t1, $first]))->assertRedirect(route('tunnels.show', $t1));

        // Sortie du dernier chariot : direction le cartonnage
        $last = CartAssignment::where('cart_id', $carts[1]->id)->first();
        $this->post(route('tunnels.release-assignment', [$t1, $last]))
            ->assertRedirect(route('packaging.create', ['processing_order_id' => $order->id]));

        $this->get(route('processing.show', $order))->assertDontSee('Cartonnage verrouillé')->assertSee('sorti(s) du tunnel');
        $this->get(route('packaging.create', ['processing_order_id' => $order->id]))
            ->assertOk()
            ->assertSee('packagingForm', false);

        $this->post(route('packaging.store'), [
            'processing_order_id' => $order->id,
            'date' => '2026-10-21',
            'items' => [['fish_type_id' => $this->fish->id, 'carton_type_id' => $this->carton->id, 'cartons_count' => 32, 'weight_per_carton' => 16]],
        ])->assertRedirect();

        $packaging = PackagingOrder::firstOrFail();
        $this->assertSame(32, (int) $packaging->items()->value('cartons_count'));

        $this->get(route('packaging.edit', $packaging))->assertOk()->assertSee('Modifier: ' . $packaging->reference);

        // Finalisation : stockage en chambre froide => lots de stock + entree de stock
        $this->post(route('packaging.finalize', $packaging), ['mode' => 'store', 'cold_room_id' => $this->room->id])
            ->assertRedirect(route('packaging.show', $packaging))
            ->assertSessionHas('success');

        $this->assertSame('completed', $packaging->fresh()->status);
        $batch = ClientStockBatch::where('packaging_order_id', $packaging->id)->firstOrFail();
        $this->assertSame(32, (int) $batch->available_cartons);
        $this->assertSame(512.0, (float) $batch->available_weight);
        $this->assertSame(1, StorageEntry::where('client_stock_batch_id', $batch->id)->where('type', 'in')->count());
    }
}
