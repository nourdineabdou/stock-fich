<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('processing_orders', function (Blueprint $table) {
            $table->json('cart_distribution')->nullable()->after('returned_weight');
        });
    }

    public function down(): void
    {
        Schema::table('processing_orders', function (Blueprint $table) {
            $table->dropColumn('cart_distribution');
        });
    }
};
