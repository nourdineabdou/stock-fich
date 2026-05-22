<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delivery_items', function (Blueprint $table) {
             $table->id();

            $table->foreignId('delivery_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('client_stock_batch_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('fish_type_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('carton_type_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('cartons_count');

            $table->decimal('weight_kg', 15, 2);
        $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_items');
    }
};
