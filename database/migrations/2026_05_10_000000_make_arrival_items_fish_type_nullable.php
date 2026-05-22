<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE arrival_items MODIFY fish_type_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('UPDATE arrival_items SET fish_type_id = (SELECT id FROM fish_types LIMIT 1) WHERE fish_type_id IS NULL');
        DB::statement('ALTER TABLE arrival_items MODIFY fish_type_id BIGINT UNSIGNED NOT NULL');
    }
};