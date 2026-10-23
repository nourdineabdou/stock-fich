<?php

use App\Support\PermissionSync;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

/** Nouvelle action « Saisir les frais de traitement par kg » : donnee aux roles qui modifient deja les traitements. */
return new class extends Migration
{
    public function up(): void
    {
        PermissionSync::run();

        Role::whereHas('permissions', fn ($q) => $q->where('name', 'processing.edit'))
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo('processing.prices'));
    }

    public function down(): void
    {
    }
};
