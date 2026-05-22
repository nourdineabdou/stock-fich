<?php

namespace Database\Seeders;

use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Clients
            'clients.view', 'clients.create', 'clients.edit', 'clients.delete',
            // Fish types
            'fish-types.view', 'fish-types.create', 'fish-types.edit', 'fish-types.delete',
            // Carton types
            'carton-types.view', 'carton-types.create', 'carton-types.edit', 'carton-types.delete',
            // Arrivals
            'arrivals.view', 'arrivals.create', 'arrivals.edit', 'arrivals.delete',
            // Processing
            'processing.view', 'processing.create', 'processing.edit', 'processing.delete',
            // Tunnels
            'tunnels.view', 'tunnels.create', 'tunnels.edit', 'tunnels.delete',
            // Carts
            'carts.view', 'carts.create', 'carts.edit', 'carts.delete',
            // Cold rooms
            'cold-rooms.view', 'cold-rooms.create', 'cold-rooms.edit', 'cold-rooms.delete',
            // Packaging
            'packaging.view', 'packaging.create', 'packaging.edit', 'packaging.delete',
            // Stock
            'stock.view', 'stock.create', 'stock.edit', 'stock.delete',
            // Deliveries
            'deliveries.view', 'deliveries.create', 'deliveries.edit', 'deliveries.delete',
            // Invoices
            'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete',
            // Reports
            'reports.view',
            // Settings
            'settings.view', 'settings.edit',
            // Users
            'users.view', 'users.create', 'users.edit', 'users.delete',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Super Admin
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->syncPermissions(Permission::all());

        // Direction
        $direction = Role::firstOrCreate(['name' => 'Direction']);
        $direction->syncPermissions(Permission::whereNotIn('name', ['users.delete', 'settings.edit'])->get());

        // Gestionnaire Stock
        $stockManager = Role::firstOrCreate(['name' => 'Gestionnaire Stock']);
        $stockManager->syncPermissions([
            'clients.view', 'arrivals.view', 'arrivals.create', 'arrivals.edit',
            'stock.view', 'stock.create', 'stock.edit', 'deliveries.view',
            'deliveries.create', 'deliveries.edit', 'cold-rooms.view',
            'fish-types.view', 'carton-types.view', 'reports.view',
        ]);

        // Gestionnaire Traitement
        $processingManager = Role::firstOrCreate(['name' => 'Gestionnaire Traitement']);
        $processingManager->syncPermissions([
            'clients.view', 'arrivals.view', 'processing.view', 'processing.create',
            'processing.edit', 'tunnels.view', 'carts.view', 'carts.edit',
            'packaging.view', 'packaging.create', 'packaging.edit',
            'fish-types.view', 'carton-types.view', 'reports.view',
        ]);

        // Facturation
        $billing = Role::firstOrCreate(['name' => 'Facturation']);
        $billing->syncPermissions([
            'clients.view', 'invoices.view', 'invoices.create', 'invoices.edit',
            'stock.view', 'deliveries.view', 'reports.view',
        ]);

        // Opérateur Tunnel
        $tunnelOp = Role::firstOrCreate(['name' => 'Opérateur Tunnel']);
        $tunnelOp->syncPermissions([
            'tunnels.view', 'tunnels.edit', 'carts.view', 'carts.edit',
            'processing.view',
        ]);

        // Magasinier
        $warehouse = Role::firstOrCreate(['name' => 'Magasinier']);
        $warehouse->syncPermissions([
            'stock.view', 'stock.create', 'cold-rooms.view',
            'arrivals.view', 'deliveries.view',
        ]);

        // Client lecture seule
        $clientViewer = Role::firstOrCreate(['name' => 'Client']);
        $clientViewer->syncPermissions([
            'stock.view', 'invoices.view', 'deliveries.view',
        ]);

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

