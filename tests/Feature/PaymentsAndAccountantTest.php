<?php

namespace Tests\Feature;

use App\Models\CartonType;
use App\Models\Client;
use App\Models\ClientStockBatch;
use App\Models\ColdRoom;
use App\Models\FishType;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PricingRule;
use App\Models\StorageEntry;
use App\Models\User;
use App\Services\StorageBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentsAndAccountantTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-21 10:00:00');
        Role::firstOrCreate(['name' => 'Super Admin']);
        $this->client = Client::create(['name' => 'Client Froid', 'active' => true]);
    }

    private function user(?string $role): User
    {
        $user = User::factory()->create();
        if ($role) {
            $user->assignRole($role);
        }
        return $user;
    }

    private function invoice(float $total, string $date, string $type = 'storage'): Invoice
    {
        static $n = 0;
        return Invoice::create([
            'client_id' => $this->client->id,
            'invoice_type' => $type,
            'invoice_number' => 'FAC-T-' . (++$n),
            'invoice_date' => $date,
            'tax_rate' => 0,
            'subtotal' => $total,
            'tax' => 0,
            'total' => $total,
            'status' => 'draft',
        ]);
    }

    public function test_partial_then_full_payment_on_an_invoice(): void
    {
        $this->actingAs($this->user('Comptable'));
        $invoice = $this->invoice(1000, '2026-10-01', 'processing');

        $this->post(route('invoices.payments.store', $invoice), ['amount' => 400, 'method' => 'cash'])
            ->assertSessionHas('pdf_url');
        $invoice->refresh();
        $this->assertSame('partial', $invoice->status);
        $this->assertSame(600.0, $invoice->balance);

        // Trop payer est refuse
        $this->post(route('invoices.payments.store', $invoice), ['amount' => 700, 'method' => 'cash'])
            ->assertSessionHasErrors('amount');

        $this->post(route('invoices.payments.store', $invoice), ['amount' => 600, 'method' => 'transfer', 'reference' => 'VIR-1']);
        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertSame(1000.0, (float) $invoice->paid_amount);

        $receipt = InvoicePayment::latest('id')->value('receipt_number');
        $pdf = $this->get(route('payments.receipt', $receipt))->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        // Annuler un encaissement remet la facture en partielle
        $this->delete(route('payments.destroy', InvoicePayment::latest('id')->first()));
        $this->assertSame('partial', $invoice->fresh()->status);
    }

    public function test_client_cold_payment_is_spread_over_oldest_invoices(): void
    {
        $this->actingAs($this->user('Comptable'));
        $old = $this->invoice(1000, '2026-09-01');
        $new = $this->invoice(500, '2026-10-01');

        $this->post(route('storage-billing.collect', $this->client), ['amount' => 1200, 'method' => 'cash'])
            ->assertSessionHas('success');

        $this->assertSame('paid', $old->fresh()->status);
        $this->assertSame('partial', $new->fresh()->status);
        $this->assertSame(300.0, $new->fresh()->balance);
        $this->assertSame(1, InvoicePayment::distinct()->count('receipt_number'));

        $this->post(route('storage-billing.collect', $this->client), ['amount' => 301, 'method' => 'cash'])
            ->assertSessionHasErrors('amount');
    }

    public function test_accountant_access_is_limited_to_billing(): void
    {
        $this->actingAs($this->user('Comptable'));
        $invoice = $this->invoice(100, '2026-10-01');

        $this->get(route('invoices.index'))->assertOk();
        $this->get(route('invoices.show', $invoice))->assertOk()->assertSee('Encaisser');
        $this->get(route('invoices.pdf', $invoice))->assertOk();
        $this->get(route('storage-billing.index'))->assertOk();
        $this->get(route('clients.show', $this->client))->assertOk();

        $this->get(route('processing.create'))->assertForbidden();
        $this->get(route('arrivals.index'))->assertForbidden();
        $this->get(route('settings.index'))->assertForbidden();
        $this->delete(route('invoices.destroy', $invoice))->assertForbidden();
    }

    public function test_role_without_collect_permission_cannot_cash(): void
    {
        Role::firstOrCreate(['name' => 'Facturation'])->syncPermissions(['invoices.index', 'invoices.show', 'invoices.create', 'invoices.edit']);
        $this->actingAs($this->user('Facturation'));
        $invoice = $this->invoice(100, '2026-10-01');

        $this->get(route('invoices.show', $invoice))->assertOk()->assertDontSee('Enregistrer l\'encaissement');
        $this->post(route('invoices.payments.store', $invoice), ['amount' => 50, 'method' => 'cash'])->assertForbidden();
    }

    public function test_super_admin_and_legacy_users_keep_full_access(): void
    {
        $this->actingAs($this->user('Super Admin'));
        $this->get(route('settings.index'))->assertOk();

        $this->actingAs($this->user(null));
        $this->get(route('arrivals.index'))->assertOk();
    }

    public function test_variable_cold_invoice_has_one_line_per_week(): void
    {
        $this->actingAs($this->user('Comptable'));
        $fish = FishType::create(['name' => 'Poulpe', 'active' => true]);
        $carton = CartonType::create(['name' => 'Carton 16', 'weight_kg' => 16, 'active' => true]);
        $room = ColdRoom::create(['name' => 'CF1', 'type' => 'storage', 'active' => true]);
        $batch = ClientStockBatch::create([
            'client_id' => $this->client->id, 'fish_type_id' => $fish->id, 'carton_type_id' => $carton->id,
            'cold_room_id' => $room->id, 'lot_number' => 'L1', 'cartons_count' => 28, 'weight_per_carton' => 16,
            'total_weight' => 448, 'available_cartons' => 20, 'available_weight' => 320, 'entry_date' => '2026-10-06', 'status' => 'partial',
        ]);
        StorageEntry::create(['client_stock_batch_id' => $batch->id, 'type' => 'in', 'cartons_count' => 28, 'weight_kg' => 448, 'entry_date' => '2026-10-06 09:00:00']);
        StorageEntry::create(['client_stock_batch_id' => $batch->id, 'type' => 'out', 'cartons_count' => 8, 'weight_kg' => 128, 'entry_date' => '2026-10-14 15:00:00']);
        $rule = PricingRule::create([
            'client_id' => $this->client->id, 'service_type' => 'storage', 'billing_method' => 'carton',
            'charge_unit' => 'carton', 'billing_frequency' => 'week', 'week_start_day' => 1, 'price' => 40, 'active' => true,
        ]);

        $this->post(route('storage-billing.generate-unbilled', $this->client))->assertSessionHas('success');

        $invoice = Invoice::where('invoice_type', 'storage')->with('items')->firstOrFail();
        // Semaines 05/10 et 12/10 terminees (28 cartons chacune), semaine en cours exclue
        $this->assertSame([28.0, 28.0], $invoice->items->pluck('quantity')->map(fn ($q) => (float) $q)->all());
        $this->assertSame(2240.0, (float) $invoice->total);

        $fees = app(StorageBillingService::class)->weeklyBreakdown($this->client, $rule);
        $this->assertSame(0, $fees['billable_weeks']);
        $this->assertSame(2240.0, $fees['balance']);
    }
}
