<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('processing_orders', function (Blueprint $table) {
            $table->decimal('input_weight', 15, 2)->default(0)->after('notes');
            $table->decimal('output_weight', 15, 2)->default(0)->after('input_weight');
            $table->decimal('waste_weight', 15, 2)->default(0)->after('output_weight');
            $table->decimal('returned_weight', 15, 2)->default(0)->after('waste_weight');
        });
    }

    public function down(): void
    {
        Schema::table('processing_orders', function (Blueprint $table) {
            $table->dropColumn(['input_weight', 'output_weight', 'waste_weight', 'returned_weight']);
        });
    }
};