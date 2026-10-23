<?php

use App\Support\PermissionSync;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

/** Nouvelle action « Sortir les chariots du tunnel » : donnee aux roles qui sortaient deja les chariots depuis le tunnel. */
return new class extends Migration
{
    public function up(): void
    {
        PermissionSync::run();

        Role::whereHas('permissions', fn ($q) => $q->whereIn('name', ['tunnels.release-assignment', 'processing.place-tunnels']))
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo('processing.release-tunnels'));
    }

    public function down(): void
    {
    }
};
