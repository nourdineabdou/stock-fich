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
        Schema::create('processing_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('processing_order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('fish_type_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('input_weight', 15, 2)->default(0);

            $table->decimal('output_weight', 15, 2)->default(0);

            $table->decimal('waste_weight', 15, 2)->default(0);

            $table->decimal('returned_weight', 15, 2)->default(0);

            $table->decimal('price_per_kg', 12, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processing_items');
    }
};
