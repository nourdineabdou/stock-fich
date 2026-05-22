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
        Schema::create('client_stock_batches', function (Blueprint $table) {
             $table->id();

            $table->foreignId('client_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('fish_type_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('carton_type_id')
                ->constrained()
                ->cascadeOnDelete();
                $table->foreignId('cold_room_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('lot_number');

            $table->integer('cartons_count')->default(0);

            $table->decimal('weight_per_carton', 10, 2);

            $table->decimal('total_weight', 15, 2);

            $table->integer('available_cartons')->default(0);

            $table->decimal('available_weight', 15, 2)->default(0);

            $table->integer('reserved_cartons')->default(0);

            $table->decimal('reserved_weight', 15, 2)->default(0);
            $table->dateTime('frozen_at')->nullable();

            $table->dateTime('entry_date')->nullable();

            $table->enum('status', [
                'active',
                'partial',
                'empty',
                'available',
                'complete'
            ])->default('complete');

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_stock_batches');
    }
};
