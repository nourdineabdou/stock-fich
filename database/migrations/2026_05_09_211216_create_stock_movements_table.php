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
        Schema::create('stock_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->enum('movement_type', [
                'arrival',
                'transfer',
                'processing',
                'freezing',
                'packaging',
                'storage',
                'delivery'
            ]);

            $table->string('reference_type')->nullable();

            $table->unsignedBigInteger('reference_id')->nullable();
             $table->foreignId('fish_type_id')
        ->nullable()
        ->constrained()
        ->nullOnDelete();

            $table->decimal('quantity', 15, 2)->default(0);

            $table->decimal('weight_kg', 15, 2)->default(0);

            $table->string('from_location')->nullable();

            $table->string('to_location')->nullable();

            $table->dateTime('movement_date');

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
