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
        Schema::create('fish_type_carton_types', function (Blueprint $table) {
            $table->id();
             $table->foreignId('fish_type_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('carton_type_id')
                ->constrained()
                ->cascadeOnDelete();

            // Poids par défaut pour ce type de carton
            $table->decimal('default_weight', 10, 2)->nullable();

            // Nombre minimum de cartons
            $table->integer('min_cartons')->default(0);

            // Nombre maximum de cartons
            $table->integer('max_cartons')->nullable();
             // Prix spécifique si nécessaire
            $table->decimal('price', 12, 2)->nullable();

            // Carton principal par défaut
            $table->boolean('is_default')->default(false);

            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->unique([
                'fish_type_id',
                'carton_type_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fish_type_carton_types');
    }
};
