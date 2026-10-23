<?php

use App\Support\PermissionCatalog;
use App\Support\PermissionSync;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

/**
 * Passage des permissions par module (clients.view, invoices.edit...) aux permissions
 * par action (clients.index, processing.assign-carts...). Chaque role garde les memes droits.
 */
return new class extends Migration
{
    public function up(): void
    {
        PermissionSync::run();

        foreach (Role::with('permissions')->where('name', '!=', 'Super Admin')->get() as $role) {
            $role->syncPermissions(PermissionCatalog::fromLegacy($role->permissions->pluck('name')->all()));
        }

        PermissionSync::run(prune: true);
    }

    public function down(): void
    {
        // Conversion non reversible automatiquement : relancer le seeder des roles si besoin.
    }
};
