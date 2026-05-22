<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE packaging_orders MODIFY COLUMN status ENUM('pending','completed','delivered') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE packaging_orders MODIFY COLUMN status ENUM('pending','completed') NOT NULL DEFAULT 'pending'");
    }
};
