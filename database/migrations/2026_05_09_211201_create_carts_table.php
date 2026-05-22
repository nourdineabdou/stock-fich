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
        Schema::create('carts', function (Blueprint $table) {
             $table->id();

            $table->foreignId('tunnel_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('code')->unique();

            $table->decimal('capacity_kg', 12, 2);

            $table->enum('status', [
                'empty',
                'processing',
                'frozen',
                'completed'
            ])->default('empty');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
