<?php

namespace Database\Seeders;

use App\Models\CompanySetting;
use App\Models\User;
use App\Support\PermissionCatalog;
use App\Support\PermissionSync;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Une permission par action du systeme ; les listes ci-dessous (par module)
        // sont converties en permissions par action avec PermissionCatalog::fromLegacy().
        PermissionSync::run(prune: true);
        $expand = fn (array $legacy) => PermissionCatalog::fromLegacy($legacy);

        // Super Admin
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->syncPermissions(Permission::all());

        // Direction
        $direction = Role::firstOrCreate(['name' => 'Direction']);
        $direction->syncPermissions(Permission::whereNotIn('name', ['users.destroy', 'settings.edit', 'roles.create', 'roles.edit', 'roles.destroy'])->get());

        // Gestionnaire Stock
        $stockManager = Role::firstOrCreate(['name' => 'Gestionnaire Stock']);
        $stockManager->syncPermissions($expand([
            'clients.view', 'arrivals.view', 'arrivals.create', 'arrivals.edit',
            'stock.view', 'stock.create', 'stock.edit', 'deliveries.view',
            'deliveries.create', 'deliveries.edit', 'cold-rooms.view',
            'fish-types.view', 'carton-types.view', 'reports.view',
        ]));

        // Gestionnaire Traitement
        $processingManager = Role::firstOrCreate(['name' => 'Gestionnaire Traitement']);
        $processingManager->syncPermissions($expand([
            'clients.view', 'arrivals.view', 'processing.view', 'processing.create',
            'processing.edit', 'tunnels.view', 'carts.view', 'carts.edit',
            'packaging.view', 'packaging.create', 'packaging.edit',
            'fish-types.view', 'carton-types.view', 'reports.view',
        ]));

        // Facturation
        $billing = Role::firstOrCreate(['name' => 'Facturation']);
        $billing->syncPermissions($expand([
            'clients.view', 'invoices.view', 'invoices.create', 'invoices.edit',
            'stock.view', 'deliveries.view', 'reports.view',
        ]));

        // Comptable : voit les factures, les emet, les imprime et encaisse (paiement partiel ou total)
        $accountant = Role::firstOrCreate(['name' => 'Comptable']);
        $accountant->syncPermissions($expand([
            'clients.view', 'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.collect',
            'processing.view', 'stock.view', 'deliveries.view', 'reports.view',
        ]));

        // Opérateur Tunnel
        $tunnelOp = Role::firstOrCreate(['name' => 'Opérateur Tunnel']);
        $tunnelOp->syncPermissions($expand([
            'tunnels.view', 'tunnels.edit', 'carts.view', 'carts.edit',
            'processing.view',
        ]));

        // Magasinier
        $warehouse = Role::firstOrCreate(['name' => 'Magasinier']);
        $warehouse->syncPermissions($expand([
            'stock.view', 'stock.create', 'cold-rooms.view',
            'arrivals.view', 'deliveries.view',
        ]));

        // Client lecture seule
        $clientViewer = Role::firstOrCreate(['name' => 'Client']);
        $clientViewer->syncPermissions($expand([
            'stock.view', 'invoices.view', 'deliveries.view',
        ]));

        // Create super admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@freezepro.ma'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
            ]
        );
        $admin->syncRoles(['Super Admin']);

        // Seed default company settings
        $defaults = [
            'company_name'     => 'FreezePro ERP',
            'company_address'  => 'Zone Industrielle, Agadir, Maroc',
            'company_phone'    => '+212 5 28 00 00 00',
            'company_email'    => 'contact@freezepro.ma',
            'company_nif'      => '00000000',
            'company_ice'      => '000000000000000',
            'invoice_prefix'   => 'FAC',
            'delivery_prefix'  => 'BL',
            'arrival_prefix'   => 'ARR',
            'processing_prefix'=> 'TRT',
            'tax_rate'         => '20',
            'currency'         => 'MRU',
            'temp_min'         => '-25',
            'temp_max'         => '-18',
        ];

        foreach ($defaults as $key => $value) {
            CompanySetting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->command->info('Roles, permissions & settings seeded successfully!');
    }
}

