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
        Schema::create('packaging_orders', function (Blueprint $table) {
              $table->id();

            $table->foreignId('processing_order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('reference')->unique();

            $table->dateTime('date');

            $table->enum('status', [
                'pending',
                'completed'
            ])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packaging_orders');
    }
};
