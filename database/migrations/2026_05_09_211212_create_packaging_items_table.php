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
        Schema::create('packaging_items', function (Blueprint $table) {
          $table->id();

        $table->foreignId('packaging_order_id')
            ->constrained()
            ->cascadeOnDelete();

        $table->foreignId('fish_type_id')
            ->constrained()
            ->cascadeOnDelete();

        $table->foreignId('carton_type_id')
            ->constrained()
            ->cascadeOnDelete();

        $table->integer('cartons_count');

        $table->decimal('weight_per_carton', 10, 2);
        $table->string('lot_number');

        $table->foreignId('cold_room_id')
            ->nullable()
            ->constrained()
            ->nullOnDelete();

        $table->dateTime('produced_at')->nullable();

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packaging_items');
    }
};
