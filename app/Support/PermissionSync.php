<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSync
{
    /**
     * Cree en base une permission pour chaque action du catalogue et donne tout au Super Admin.
     * Retourne [crees, obsoletes].
     */
    public static function run(bool $prune = false): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $names = PermissionCatalog::all()->pluck('name');
        $created = [];
        foreach ($names as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            if ($permission->wasRecentlyCreated) {
                $created[] = $name;
            }
        }

        $obsolete = Permission::whereNotIn('name', $names)->pluck('name')->all();
        if ($prune && $obsolete) {
            Permission::whereIn('name', $obsolete)->delete();
        }

        Role::where('name', 'Super Admin')->first()?->syncPermissions(Permission::whereIn('name', $names)->get());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return [$created, $obsolete];
    }
}
