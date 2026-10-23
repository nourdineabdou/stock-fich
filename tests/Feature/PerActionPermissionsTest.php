<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ProcessingOrder;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PerActionPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function userWith(array $permissions): User
    {
        $role = Role::create(['name' => 'Test ' . uniqid()]);
        $role->syncPermissions($permissions);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_every_protected_action_has_its_own_permission(): void
    {
        $names = PermissionCatalog::all()->pluck('name');

        foreach (['processing.assign-carts', 'processing.place-tunnels', 'packaging.finalize', 'deliveries.validate',
                  'stock.transfer', 'invoices.payments.store', 'payments.destroy', 'roles.edit', 'clients.index', 'clients.show'] as $expected) {
            $this->assertContains($expected, $names);
        }
        // store/update fusionnes avec create/edit, routes ouvertes absentes
        $this->assertNotContains('clients.store', $names);
        $this->assertNotContains('dashboard', $names);
        // Toutes les permissions existent en base apres migration
        $this->assertSame($names->count(), Permission::whereIn('name', $names)->count());
        $this->assertSame(0, Permission::whereNotIn('name', $names)->count());
    }

    public function test_a_single_action_can_be_allowed_or_denied(): void
    {
        $client = Client::create(['name' => 'C1', 'active' => true]);
        $order = ProcessingOrder::create(['client_id' => $client->id, 'reference' => 'TRT-X', 'billing_method' => 'kg', 'status' => 'processing']);

        // Voit le traitement mais ne peut pas affecter les chariots
        $this->actingAs($this->userWith(['processing.index', 'processing.show']));
        $this->get(route('processing.show', $order))->assertOk();
        $this->get(route('processing.index'))->assertOk();
        $this->post(route('processing.assign-carts', $order), ['cart_ids' => []])->assertForbidden();
        $this->get(route('processing.client-pdf', $order))->assertForbidden();
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_roles_screen_creates_role_with_chosen_actions(): void
    {
        $this->actingAs($this->userWith(['roles.index', 'roles.create', 'roles.edit']));

        $this->get(route('roles.create'))->assertOk()->assertSee('Affecter les chariots');

        $this->post(route('roles.store'), [
            'name' => "Chef d'équipe",
            'permissions' => ['processing.index', 'processing.show', 'processing.assign-carts'],
        ])->assertRedirect(route('roles.index'));

        $role = Role::findByName("Chef d'équipe");
        $this->assertEqualsCanonicalizing(['processing.index', 'processing.show', 'processing.assign-carts'], $role->permissions->pluck('name')->all());

        $this->put(route('roles.update', $role), ['name' => "Chef d'équipe", 'permissions' => ['processing.index']])->assertRedirect();
        $this->assertSame(['processing.index'], $role->fresh()->permissions->pluck('name')->all());

        // Une permission inventee est refusee
        $this->post(route('roles.store'), ['name' => 'X', 'permissions' => ['n-importe-quoi']])->assertSessionHasErrors('permissions.0');
    }

    public function test_presentation_page_is_public_in_both_languages(): void
    {
        // Visiteur non connecte : pas de redirection vers la connexion
        $this->get(route('presentation'))->assertOk()
            ->assertSee('Sept étapes, de la balance au client')
            ->assertSee('سبع مراحل، من الميزان إلى العميل')
            ->assertSee('dir="rtl"', false)
            ->assertSee('Se connecter');

        // Connecte avec un role sans permission : toujours accessible
        $this->actingAs($this->userWith([]));
        $this->get(route('presentation'))->assertOk()->assertSee("Ouvrir l'application", false);
    }

    public function test_legacy_roles_were_converted(): void
    {
        $accountant = Role::findByName('Comptable')->permissions->pluck('name');

        $this->assertContains('invoices.payments.store', $accountant);
        $this->assertContains('storage-billing.collect', $accountant);
        $this->assertContains('processing.client-pdf', $accountant);
        $this->assertNotContains('invoices.destroy', $accountant);
        $this->assertNotContains('processing.assign-carts', $accountant);
    }
}
