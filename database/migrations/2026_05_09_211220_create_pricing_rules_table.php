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
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->enum('service_type', [
                'storage',
                'processing',
                'packaging',
                'freezing'
            ]);

            $table->enum('billing_method', [
                'kg',
                'carton',
                'day'
            ]);
            $table->decimal('price', 12, 2);

            $table->foreignId('fish_type_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('carton_type_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->boolean('active')->default(true);

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
