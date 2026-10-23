<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Le code utilise pending / delivered / cancelled, mais la colonne n'acceptait que
 * pending / completed : la validation d'une livraison echouait (« Data truncated »).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE deliveries MODIFY status ENUM('pending','completed','delivered','cancelled') NOT NULL DEFAULT 'pending'");
        DB::table('deliveries')->where('status', 'completed')->update(['status' => 'delivered']);
        DB::statement("ALTER TABLE deliveries MODIFY status ENUM('pending','delivered','cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE deliveries MODIFY status ENUM('pending','completed','delivered','cancelled') NOT NULL DEFAULT 'pending'");
        DB::table('deliveries')->where('status', 'delivered')->update(['status' => 'completed']);
        DB::table('deliveries')->where('status', 'cancelled')->update(['status' => 'pending']);
        DB::statement("ALTER TABLE deliveries MODIFY status ENUM('pending','completed') NOT NULL DEFAULT 'pending'");
    }
};
